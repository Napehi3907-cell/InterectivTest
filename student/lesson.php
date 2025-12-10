<?php
session_start();
require_once 'includes/db_connect.php';
// --- Проверка авторизации и роли ---
// Если пользователь не авторизован или не является учеником, перенаправляем на страницу входа
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'ученик') {
    header("Location: ../Login.php");
    exit;
}
$sql_courses = "SELECT id_курса, название, описание FROM Курсы";
$stmt_courses = sqlsrv_query($link, $sql_courses);

if ($stmt_courses === false) {
    log_sqlsrv_errors("Подготовка запроса списка курсов");
    $error_message = "Ошибка сервера при получении списка курсов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}
// Получаем имя пользователя из сессии для отображения
$user_login = $_SESSION['login'] ?? 'Ученик';
?>