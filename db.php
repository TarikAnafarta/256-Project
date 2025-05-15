<?php
// project/db.php

/**
 * Returns a singleton PDO for your MySQL database,
 * and also populates $pdo for backward compatibility.
 */
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host     = 'localhost';
        $dbname   = 'test';
        $username = 'root';
        $password = '';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// for any existing scripts that still do:
//    require 'db.php';
//    global $pdo;
//    $pdo->query(...);
// we’ll also populate the global:
$pdo = getPDO();
