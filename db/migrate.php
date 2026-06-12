<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \"'");
    }
}

function getConnection(): PDO
{
    $host = $_ENV['PGHOST']     ?? $_ENV['DB_HOST']     ?? 'localhost';
    $port = $_ENV['PGPORT']     ?? $_ENV['DB_PORT']     ?? '5432';
    $name = $_ENV['PGDATABASE'] ?? $_ENV['DB_NAME']     ?? 'sor';
    $user = $_ENV['PGUSER']     ?? $_ENV['DB_USER']     ?? 'sor_user';
    $pass = $_ENV['PGPASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$name";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}


function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id         SERIAL       PRIMARY KEY,
            version    VARCHAR(50)  UNIQUE NOT NULL,
            filename   VARCHAR(255) NOT NULL,
            checksum   VARCHAR(64)  NOT NULL,
            applied_at TIMESTAMP    NOT NULL DEFAULT NOW()
        )
    ");
}

function getAppliedMigrations(PDO $pdo): array
{
    $rows    = $pdo->query("SELECT version, checksum FROM schema_migrations ORDER BY id")->fetchAll();
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['version']] = $row['checksum'];
    }
    return $applied;
}

function getMigrationFiles(): array
{
    $files = glob(__DIR__ . '/migrations/V*.sql');
    if (empty($files)) {
        return [];
    }
    usort($files, fn(string $a, string $b) =>
        extractVersionNumber(basename($a)) <=> extractVersionNumber(basename($b))
    );
    return $files;
}

function extractVersionNumber(string $filename): int
{
    preg_match('/^V(\d+)__/', $filename, $matches);
    return isset($matches[1]) ? (int) $matches[1] : 0;
}

function extractVersionLabel(string $filename): string
{
    preg_match('/^(V\d+)__/', $filename, $matches);
    return $matches[1] ?? 'V?';
}

function output(string $message, string $color = 'default', bool $newline = true): void
{
    $colors = [
        'green'   => "\033[0;32m",
        'red'     => "\033[0;31m",
        'yellow'  => "\033[0;33m",
        'cyan'    => "\033[0;36m",
        'default' => "\033[0m",
    ];
    echo ($colors[$color] ?? $colors['default']) . $message . "\033[0m" . ($newline ? PHP_EOL : '');
}


function runMigrate(PDO $pdo): void
{
    ensureMigrationsTable($pdo);
    $applied = getAppliedMigrations($pdo);
    $files   = getMigrationFiles();

    if (empty($files)) {
        output('No migration files found in db/migrations/', 'yellow');
        return;
    }

    $count = 0;
    foreach ($files as $file) {
        $filename = basename($file);
        $version  = extractVersionLabel($filename);
        $sql      = file_get_contents($file);
        $checksum = hash('sha256', $sql);

        if (isset($applied[$version])) {
            if ($applied[$version] !== $checksum) {
                output("WARN  $filename — modified after apply. Create a new migration instead.", 'yellow');
            }
            continue;
        }

        try {
            $pdo->beginTransaction();
            $pdo->exec($sql);
            $pdo->prepare("INSERT INTO schema_migrations (version, filename, checksum) VALUES (?, ?, ?)")
                ->execute([$version, $filename, $checksum]);
            $pdo->commit();
            output("OK    $filename", 'green');
            $count++;
        } catch (PDOException $e) {
            $pdo->rollBack();
            output("FAIL  $filename — " . $e->getMessage(), 'red');
            exit(1);
        }
    }

    $count === 0
        ? output('All migrations already applied.', 'cyan')
        : output("$count migration(s) applied.", 'green');
}

function runSeed(PDO $pdo): void
{
    $seedFile = __DIR__ . '/seeds/seed.sql';
    if (!file_exists($seedFile)) {
        output('Seed file not found: db/seeds/seed.sql', 'red');
        exit(1);
    }

    try {
        $pdo->exec(file_get_contents($seedFile));
        output('Seed applied successfully.', 'green');
    } catch (PDOException $e) {
        output('Seed FAILED — ' . $e->getMessage(), 'red');
        exit(1);
    }
}

function runStatus(PDO $pdo): void
{
    ensureMigrationsTable($pdo);
    $applied = getAppliedMigrations($pdo);
    $files   = getMigrationFiles();

    output(str_pad('Version', 10) . str_pad('Status', 12) . 'File');
    output(str_repeat('-', 55));

    foreach ($files as $file) {
        $filename = basename($file);
        $version  = extractVersionLabel($filename);
        $checksum = hash('sha256', file_get_contents($file));

        if (!isset($applied[$version])) {
            [$status, $color] = ['pending',  'yellow'];
        } elseif ($applied[$version] !== $checksum) {
            [$status, $color] = ['modified', 'red'];
        } else {
            [$status, $color] = ['applied',  'green'];
        }

        output(str_pad($version, 10) . str_pad($status, 12) . $filename, $color);
    }
}

function runFresh(PDO $pdo): void
{
    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env !== 'development') {
        output('fresh is only allowed in development environment.', 'red');
        exit(1);
    }

    output('This will delete ALL data. Continue? (yes/no): ', 'yellow', false);
    if (trim(fgets(STDIN)) !== 'yes') {
        output('Cancelled.', 'cyan');
        return;
    }

    $tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")
        ->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($tables)) {
        $list = implode(', ', array_map(fn($t) => '"' . $t . '"', $tables));
        $pdo->exec("DROP TABLE IF EXISTS $list CASCADE");
        output('All tables dropped.', 'yellow');
    }

    runMigrate($pdo);
}

$command = $argv[1] ?? 'migrate';
output('SOr Migration Runner', 'cyan');
output(str_repeat('-', 30), 'cyan');

try {
    $pdo = getConnection();
    match ($command) {
        'migrate' => runMigrate($pdo),
        'seed'    => runSeed($pdo),
        'status'  => runStatus($pdo),
        'fresh'   => runFresh($pdo),
        default   => output('Available commands: migrate | seed | status | fresh', 'yellow'),
    };
} catch (PDOException $e) {
    output('Cannot connect to PostgreSQL: ' . $e->getMessage(), 'red');
    exit(1);
}