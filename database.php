<?php

class Database
{
    public static function getConnection(): mysqli
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '12345678';
        $dbname = getenv('DB_DATABASE') ?: 'absensi_rfid';
        
        return new mysqli($host, $username, $password, $dbname);
    }
}