<?php
// includes/db_connect.php

// Параметры подключения к SQL Server
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

if (!$link) {
    // Если подключение не удалось, получаем подробную ошибку
    $errorMessage = "Ошибка подключения к MSSQL: ";
    $errors = sqlsrv_errors(); // Убираем аргумент $link
    if ($errors) {
        foreach ($errors as $error) {
            $errorMessage .= "[" . $error['SQLSTATE'] . "] " . $error['message'] . "\n";
        }
    } else {
        $errorMessage .= "Неизвестная ошибка.";
    }
    error_log($errorMessage);
    die('Не удалось подключиться к базе данных. Пожалуйста, обратитесь к администратору.');

}

// --- Функция для логирования ошибок SQLSRV ---
function log_sqlsrv_errors($context = "")
{
    $errors = sqlsrv_errors(); // Убираем аргумент $link
    $errorMessage = "SQLSRV Error" . ($context ? " in {$context}" : "") . ": ";
    if ($errors) {
        foreach ($errors as $error) {
            $errorMessage .= "[" . $error['SQLSTATE'] . "] " . $error['message'] . "\n";
        }
    } else {
        $errorMessage .= "Unknown SQLSRV error.";
    }
    error_log($errorMessage);
}

?>