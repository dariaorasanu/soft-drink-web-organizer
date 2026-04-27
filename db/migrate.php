<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Bootstrap.php';

function getConnection(): PDO
{
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    );
    return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id         SERIAL      PRIMARY KEY,
            version    VARCHAR(50) UNIQUE NOT NULL,  -- ex: V1
            filename   VARCHAR(255) NOT NULL,         -- ex: V1__create_users.sql
            checksum   VARCHAR(64) NOT NULL,          -- SHA256 al fisierului
            applied_at TIMESTAMP   NOT NULL DEFAULT NOW()
        )
    ");
}

function getAppliedMigrations(PDO $pdo): array
{
    $rows = $pdo->query("SELECT version, checksum FROM schema_migrations ORDER BY id")
        ->fetchAll();

    // { 'V1' => 'abc123...', 'V2' => 'def456...' }
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['version']] = $row['checksum'];
    }
    return $applied;
}

function getMigrationFiles(): array
{
    $dir   = __DIR__ . '/migrations';
    $files = glob($dir . '/V*.sql');

    if ($files === false || empty($files)) {
        return [];
    }
    usort($files, function (string $a, string $b): int {
        $versionA = extractVersion(basename($a));
        $versionB = extractVersion(basename($b));
        return $versionA <=> $versionB;
    });

    return $files;
}

function extractVersion(string $filename): int
{
    preg_match('/^V(\d+)__/', $filename, $matches);
    return isset($matches[1]) ? (int)$matches[1] : 0;
}

function extractVersionLabel(string $filename): string
{
    preg_match('/^(V\d+)__/', $filename, $matches);
    return $matches[1] ?? 'V?';
}


function runMigrate(PDO $pdo): void
{
    ensureMigrationsTable($pdo);

    $applied = getAppliedMigrations($pdo);
    $files   = getMigrationFiles();
    if (empty($files)) {
        output('⚠️  Nu există fișiere de migrații în db/migrations/', 'yellow');
        return;
    }
    $pending = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        $version  = extractVersionLabel($filename);
        $sql      = file_get_contents($file);
        $checksum = hash('sha256', $sql);

        if (isset($applied[$version])) {
            if ($applied[$version] !== $checksum) {
                output("$filename — MODIFICAT după aplicare! Nu se re-rulează.", 'red');
                output("Creează o migrație nouă în loc să modifici una existentă.", 'red');
            }
            continue;
        }

        // Aplică migrația într-o tranzacție
        try {
            $pdo->beginTransaction();
            $pdo->exec($sql);
            $pdo->prepare("
                INSERT INTO schema_migrations (version, filename, checksum)
                VALUES (?, ?, ?)
            ")->execute([$version, $filename, $checksum]);
            $pdo->commit();

            output("✅  $filename — aplicată cu succes", 'green');
            $pending++;

        } catch (PDOException $e) {
            $pdo->rollBack();
            output("$filename — EROARE: " . $e->getMessage(), 'red');
            exit(1); // Oprește la prima eroare
        }
    }

    if ($pending === 0) {
        output('✨  Toate migrațiile sunt deja aplicate.', 'cyan');
    } else {
        output("\n🎉  $pending migrație(i) aplicată(e) cu succes.", 'green');
    }
}

function runStatus(PDO $pdo): void
{
    ensureMigrationsTable($pdo);

    $applied = getAppliedMigrations($pdo);
    $files   = getMigrationFiles();

    output("\n📋  Status migrații:\n", 'cyan');
    output(str_pad('Versiune', 8) . str_pad('Status', 12) . 'Fișier');
    output(str_repeat('─', 60));

    foreach ($files as $file) {
        $filename = basename($file);
        $version  = extractVersionLabel($filename);
        $sql      = file_get_contents($file);
        $checksum = hash('sha256', $sql);

        if (!isset($applied[$version])) {
            $status = '⏳ pending';
            $color  = 'yellow';
        } elseif ($applied[$version] !== $checksum) {
            $status = '⚠️  modified';
            $color  = 'red';
        } else {
            $status = '✅ applied';
            $color  = 'green';
        }

        output(str_pad($version, 8) . str_pad($status, 14) . $filename, $color);
    }

    output('');
}

function runFresh(PDO $pdo): void
{
    if ($_ENV['APP_ENV'] !== 'development') {
        output('❌  fresh este permis DOAR în APP_ENV=development!', 'red');
        exit(1);
    }

    output('⚠️  Aceasta va șterge TOATĂ baza de date. Continui? (yes/no): ', 'yellow', false);
    $confirm = trim(fgets(STDIN));

    if ($confirm !== 'yes') {
        output('Anulat.', 'cyan');
        return;
    }

    //stergem in cascada
    $tables = $pdo->query("
        SELECT tablename FROM pg_tables
        WHERE schemaname = 'public'
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($tables)) {
        $tableList = implode(', ', array_map(fn($t) => '"' . $t . '"', $tables));
        $pdo->exec("DROP TABLE IF EXISTS $tableList CASCADE");
        output('🗑️  Toate tabelele șterse.', 'yellow');
    }

    output('🔄  Rulează migrațiile...' . "\n", 'cyan');
    runMigrate($pdo);
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

    $reset = "\033[0m";
    $code  = $colors[$color] ?? $colors['default'];

    echo $code . $message . $reset . ($newline ? PHP_EOL : '');
}


$command = $argv[1] ?? 'migrate';
output("\n🥤  SOr Migration Runner\n", 'cyan');

try {
    $pdo = getConnection();
    match ($command) {
        'migrate' => runMigrate($pdo),
        'status'  => runStatus($pdo),
        'fresh'   => runFresh($pdo),
        default   => output("Comenzi disponibile: migrate | status | fresh", 'yellow'),
    };
} catch (PDOException $e) {
    output('Nu mă pot conecta la PostgreSQL: ' . $e->getMessage(), 'red');
    output('Verifică .env și asigură-te că PostgreSQL rulează.', 'yellow');
    exit(1);
}

output('');