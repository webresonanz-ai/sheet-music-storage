<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Singleton PDO connection to MySQL.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Config::get('DB_HOST', '127.0.0.1');
            $port = Config::get('DB_PORT', '3306');
            $name = Config::get('DB_DATABASE', 'sheet_music_db');
            $user = Config::get('DB_USERNAME', 'root');
            $pass = Config::get('DB_PASSWORD');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }
}