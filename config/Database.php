<?php

class Database
{
    public static function connect(): PDO
    {
        $host = $_ENV['PGHOST']     ?? $_ENV['DB_HOST']     ?? 'localhost';
        $port = $_ENV['PGPORT']     ?? $_ENV['DB_PORT']     ?? '5432';
        $name = $_ENV['PGDATABASE'] ?? $_ENV['DB_NAME']     ?? 'sor';
        $user = $_ENV['PGUSER']     ?? $_ENV['DB_USER']     ?? 'sor_user';
        $pass = $_ENV['PGPASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? 'parola123';

        $dsn = "pgsql:host=$host;port=$port;dbname=$name";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
        }
    }
}