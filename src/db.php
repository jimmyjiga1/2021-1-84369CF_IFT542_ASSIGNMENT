<?php
/**
 * Database connection.
 *
 * SECURITY NOTE (Task 2): this file only ever hands back a PDO handle configured
 * with ATTR_EMULATE_PREPARES = false, so every query executed through it uses
 * real server-side prepared statements. Application code must NEVER concatenate
 * user input into SQL text — see src/repository.php for the parameterized pattern.
 */

require_once __DIR__ . '/env.php';

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'student_reg';
    $user = getenv('DB_USER') ?: 'student_reg_app';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements, not client-side string building
    ]);

    return $pdo;
}
