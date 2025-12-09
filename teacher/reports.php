<?php
// reports.php

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../includes/db_connect.php';


define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');


$error_message = '';
$success_message = '';
$courses = [];

// Получаем данные из базы данных
$sql_courses = "SELECT id_курса, название FROM Курсы";
$stmt_courses = sqlsrv_query($link, $sql_courses);

if ($stmt_courses === false) {
    log_sqlsrv_errors("Подготовка запроса списка курсов");
    $error_message = "Ошибка сервера при получении списка курсов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_report'])) {
    $course_id = trim($_POST['course_id']);
    $report_date = trim($_POST['report_date']);

    $success_message = "Отчет для курса с ID: $course_id и даты: $report_date успешно сформирован.";
}
?>