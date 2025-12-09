<?php
$serverName = "DESKTOP-KU7TVN0\\SQLEXPRESS"; // Имя вашего сервера (с именем экземпляра)
$databaseName = "prod";                     // Имя вашей базы данных

// Параметры аутентификации
$connectionOptions = array(
    "UID" => "",            // Ваше имя пользователя SQL Server
    "PWD" => "",                // Ваш пароль SQL Server
    "Database" => $databaseName,
    "CharacterSet" => "UTF-8"
);

// Попытка подключения
$link = sqlsrv_connect($serverName, $connectionOptions);
session_start();

// --- Проверка авторизации и роли ---
// Если пользователь не авторизован или не является учителем, перенаправляем на страницу входа
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'учитель') {
    header("Location: ../Login.php");
    exit;
}

// Получаем имя пользователя из сессии
$user_login = $_SESSION['login'] ?? 'Учитель';
?>
