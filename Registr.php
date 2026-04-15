<?php
// register.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';

define('ROOT_PATH', __DIR__ . '/');

$error_message = '';
$success_message = '';
$login = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая кнопка была нажата
    if (isset($_POST['login_Reubctr_student'])) {
        $role = 'ученик';
        $table = 'Обучающиеся';
        $id_field = 'id_студента';
    } elseif (isset($_POST['login_Reubctr_teacher'])) {
        $role = 'препод';
        $table = 'Преподаватели';
        $id_field = 'id_преподавателя';
    } else {
        $error_message = "Неизвестная роль пользователя.";
    }

    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    if (empty($login) || empty($password)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // Проверяем, существует ли пользователь с таким логином в соответствующей таблице
        $sql_check_user = "SELECT $id_field FROM $table WHERE логин = ?";
        $params_check_user = [$login];

        $stmt_check_user = sqlsrv_prepare($link, $sql_check_user, $params_check_user);
        if ($stmt_check_user === false) {
            log_sqlsrv_errors("Подготовка запроса проверки пользователя");
            $error_message = "Ошибка сервера при проверке пользователя.";
        } else {
            if (sqlsrv_execute($stmt_check_user)) {
                $existing_user = sqlsrv_fetch_array($stmt_check_user, SQLSRV_FETCH_ASSOC);
                if ($existing_user) {
                    $error_message = "Пользователь с таким логином уже существует.";
                } else {
                    // Регистрируем нового пользователя в соответствующей таблице
                    if ($role === 'ученик') {
                        // Для обучающихся — только обязательные поля
                        $sql_register_user = "INSERT INTO $table (логин, пароль) VALUES (?, ?)";
                        $params_register_user = [$login, $password];
                    } else {
                        // Для преподавателей — добавляем фио (можно расширить при необходимости)
                        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : 'Не указано';
                        $sql_register_user = "INSERT INTO $table (логин, пароль, фио) VALUES (?, ?, ?)";
                        $params_register_user = [$login, $password, $full_name];
                    }

                    $stmt_register_user = sqlsrv_prepare($link, $sql_register_user, $params_register_user);
            if ($stmt_register_user === false) {
                log_sqlsrv_errors("Подготовка запроса регистрации пользователя");
                $error_message = "Ошибка сервера при регистрации пользователя.";
            } else {
                if (sqlsrv_execute($stmt_register_user)) {
                    $success_message = "Регистрация прошла успешно! Теперь вы можете войти.";
                    header("Location: http://localhost/15/your_project_folder/Login.php");
            exit;
                } else {
                    log_sqlsrv_errors("Выполнение запроса регистрации пользователя");
                    $error_message = "Ошибка сервера при регистрации пользователя.";
                }
            }
        }
    } else {
        log_sqlsrv_errors("Выполнение запроса проверки пользователя");
        $error_message = "Ошибка сервера при проверке пользователя.";
    }
}
}
}

?>