<?php
// add_lesson.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

// Определяем корень приложения
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

// Инициализация переменных
$error_message = '';
$success_message = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка добавления урока
    $course_id = trim($_POST['course_id']);
    $lesson_name = trim($_POST['lesson_name']);
    $lesson_content = trim($_POST['lesson_content']);

    if (empty($course_id) || empty($lesson_name) || empty($lesson_content)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // SQL-запрос для добавления урока
        $sql_add_lesson = "INSERT INTO Уроки (id_курса, название, контент) VALUES (?, ?, ?)";
        $params_add_lesson = [$course_id, $lesson_name, $lesson_content];

        $stmt_add_lesson = sqlsrv_prepare($link, $sql_add_lesson, $params_add_lesson);
        if ($stmt_add_lesson === false) {
            log_sqlsrv_errors("Подготовка запроса добавления урока");
            $error_message = "Ошибка сервера при добавлении урока.";
        } else {
            if (sqlsrv_execute($stmt_add_lesson)) {
                $success_message = "Урок добавлен успешно!";
            } else {
                log_sqlsrv_errors("Выполнение запроса добавления урока");
                $error_message = "Ошибка сервера при добавлении урока.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление урока</title>
  
    
    <style>
        /* === Общий сброс стилей === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* === Основные настройки шрифтов и фона === */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
  width: 100%;
  height: 100%;
  background: linear-gradient(#3f87a6 10%, #ebf8e1a2 10%),
    linear-gradient(to right, #ebf8e100 10%, #c73030 10% 10.2%, #ebf8e100 10.5%);
  background-size: 100% 25px, 100% 100%;
  background-repeat: repeat;
  /* Add your background pattern here */
}


        /* === Sidebar (левая панель) === */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            background-color: #34495e;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 15px;
        }

        /* Закрытость Sidebar на небольших экранах */
        .sidebar.closed {
            transform: translateX(-250px);
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #ecf0f1;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #4a637a;
        }

        /* Крестик закрытия Sidebar */
        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 25px;
            font-size: 36px;
            cursor: pointer;
            color: #ecf0f1;
            line-height: 30px;
        }

        /* === Шапка (Header) === */
        header {
            background-color: #2c3e50;
            padding: 10px 20px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Кнопка-гамбургер */
        .openbtn {
            font-size: 22px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            padding: 5px 10px;
            transition: 0.3s;
            width: 35%;
        }

        .openbtn:hover {
            background-color: #34495e;
        }

        /* === Основной контент === */
        .ma {
            transition: margin-left 0.5s;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(116, 86, 86, 0.1);
            max-width: 477px;
            min-width: 300px;
            height: 400px auto;
            margin: 30px auto;
        }

        /* === Форма добавления урока === */
        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Дополнительные стили для кнопок и сообщений */
        .Regis-btn {
            font-size: 16px;
            font-weight: bold;
            background-color: #35416d;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 12px 24px;
            position: relative;
            line-height: 24px;
            border-radius: 9px;
            box-shadow: 0px 1px 2px #333b58, 0px 4px 16px #363c55;
            transform-style: preserve-3d;
            transform: scale(var(--s, 1)) perspective(600px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
            perspective: 600px;
            transition: transform 0.1s;
        }

        .Regis-btn:hover {
            --s: 1.05;
        }

        .Regis-btn:active {
            transform: translateY(2px);
        }

        .no-underline {
            text-decoration: none;
            background: linear-gradient(90deg, #866ee7, #ea60da, #ed8f57, #fbd41d, #2cca91);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }

        .rainbow-hover:active {
            transition: 0.3s;
            transform: scale(0.93);
        }
    </style>
</head>
<body class="container">

    <!-- 1. Боковая панель (SideBar) -->
    <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>
        
        <a href="#">Главная</a>
        <a href="#">Справочники</a>
        <a href="#">Учет операций</a>
        <a href="#">Отчеты</a>
        <hr style="border-color: #4a637a; margin: 10px 20px;">
        
        <!-- Кнопка выхода -->
        <button name="login_as_regist" class="Regis-btn">
            <a href="http://localhost/15/your_project_folder/teacher/reposts_Html.php" class="no-underline">
                Выход
            </a>
        </button>
    </div>
<script>
    const sidebar = document.getElementById("mySidebar");
    const openBtn = document.getElementById("openBtn");
    const closeBtn = document.getElementById("closeBtn");

    function openNav() {
        sidebar.classList.remove("closed");
    }

    function closeNav() {
        sidebar.classList.add("closed");
    }

    openBtn.addEventListener('click', openNav);
    closeBtn.addEventListener('click', closeNav);

    // Закрытие Sidebar при клике вне его области
    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
            closeNav();
        }
    });
</script>
    <!-- 2. Шапка (Header) -->
    <header>
        <div class="nav-bar">
            <!-- Кнопка для открытия Sidebar -->
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Добро пожаловать, Ученик!</span>
        </div>
    </header>

    <!-- 3. Основной контент -->
    <div class="container">
        <main class="ma">
            <div class="container1">
                 <h1>Добавление урока</h1>
                 
                <div class="form-group">
                    <label for="course_id">Намер курса:</label>
                    <input type="text" id="course_id" name="course_id" required>
                </div>
                <div class="form-group">
                    <label for="lesson_name">Название урока:</label>
                    <input type="text" id="lesson_name" name="lesson_name" required>
                </div>
                <div class="form-group">
                    <label for="lesson_content">Содержание урока:</label>
                    <textarea id="lesson_content" name="lesson_content" required></textarea>
                </div>
                <button type="submit" name="add_bt" class="btn">Добавление урока</button>
            </div>
        </main>
    </div>

    <!-- 4. Логика открытия Sidebar -->
    <script>
        const sidebar = document.getElementById("mySidebar");
        const openBtn = document.getElementById("openBtn");
        const closeBtn = document.getElementById("closeBtn");

        function openNav() {
            sidebar.classList.remove("closed");
        }

        function closeNav() {
            sidebar.classList.add("closed");
        }

        openBtn.addEventListener('click', openNav);
        closeBtn.addEventListener('click', closeNav);
    </script>

</body>
</html>