<?php

class Conexion
{
    private static $host = 'localhost';
    private static $bd = 'new_ua';
    private static $usuario = 'root';
    private static $password = '';
    private static $pdo = null;

    public static function obtener(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$bd . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, self::$usuario, self::$password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }
}
