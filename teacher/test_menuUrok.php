<?php
// add_lesson.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php';

// Определяем корень приложения
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

// Инициализация переменных
$error_message = '';
$success_message = '';
$lesson_name = '';
$content1 = '';
$content2 = '';
$content3 = '';
$content4 = '';
$image_url = '';

// Получаем course_id из URL (GET-параметр)
$course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($course_id <= 0) {
    $error_message = "Не указан ID курса. Вернитесь к списку курсов.";
}

// Обработка формы добавления урока
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    // Проверяем соединение с БД
    if ($link === false) {
        $error_message = "Ошибка подключения к базе данных.";
    } else {
        $lesson_name = trim($_POST['lesson_name']);
        $content1 = trim($_POST['content1']);
        $content2 = trim($_POST['content2']);
        $content3 = trim($_POST['content3']);
        $content4 = trim($_POST['content4']);
        $image_url = trim($_POST['image_url']);
        $course_id = (int) $_POST['course_id']; // Получаем ID из формы

        if (empty($lesson_name)) {
            $error_message = "Пожалуйста, заполните название урока.";
        } else {
            // SQL-запрос для добавления урока
            $sql_add_lesson = "INSERT INTO Уроки (id_курса, название, контент1, контент2, контент3, контент4, картинка, статус)
VALUES (?, ?, ?, ?, ?, ?, ?, 0)"; // статус по умолчанию 0 (черновик)
            $params_add_lesson = [$course_id, $lesson_name, $content1, $content2, $content3, $content4, $image_url];

            $stmt_add_lesson = sqlsrv_prepare($link, $sql_add_lesson, $params_add_lesson);
            if ($stmt_add_lesson === false) {
                $error_message = "Ошибка сервера при подготовке запроса: " . print_r(sqlsrv_errors(), true);
            } else {
                if (sqlsrv_execute($stmt_add_lesson)) {
                    $success_message = "Урок добавлен успешно!";
                    // Очищаем поля формы после успешного добавления
                    $lesson_name = '';
                    $content1 = '';
                    $content2 = '';
                    $content3 = '';
                    $content4 = '';
                    $image_url = '';
                } else {
                    $error_message = "Ошибка сервера при добавлении урока: " . print_r(sqlsrv_errors(), true);
                }
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        input[type="text"],
        textarea {
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

        .page-title {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Адаптивность: уменьшаем размер шрифта на мобильных устройствах */
        @media (max-width: 768px) {
            .page-title {
                font-size: 24px;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }
        }
    </style>
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Создание урока</span>
        </div>
    </header>
    <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

        <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.php">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">Прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/Klass_teacher.php">Класс</a>
        <hr style="border-color: #4a637a; margin: 10px 20px;">

        <!-- Кнопка выхода -->
        <button name="login_as_regist" class="Regis-btn">
            <a href="http://localhost/15/your_project_folder/login.php" class="no-underline">
                Выход
            </a>
        </button>
    </div>

    <!-- Основной контент -->
    <div class="container1">
        <main class="ma">
            <h1 class="page-title">Добавление нового урока</h1>
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Форма для добавления урока -->
            <form method="POST" action="">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars((string) $course_id); ?>">

                <div class="form-group">
                    <label for="lesson_name">Название урока *</label>
                    <input type="text" id="lesson_name" name="lesson_name"
                        value="<?php echo htmlspecialchars($lesson_name); ?>" placeholder="Введите название урока"
                        required>
                </div>

                <!-- Секция 1: Введение -->
                <div class="content-section">
                    <div class="section-title">Введение</div>
                    <div class="form-group">
                        <label for="content1">Содержание (часть 1)</label>
                        <textarea id="content1" name="content1"
                            placeholder="Введите вводную часть урока"><?php echo htmlspecialchars($content1); ?></textarea>
                    </div>
                </div>

                <!-- Секция 2: Основная часть -->
                <div class="content-section">
                    <div class="section-title">Основная часть</div>
                    <div class="form-group">
                        <label for="content2">Содержание (часть 2)</label>
                        <textarea id="content2" name="content2"
                            placeholder="Введите основную часть урока"><?php echo htmlspecialchars($content2); ?></textarea>
                    </div>
                </div>

                <!-- Секция 3: Примеры и задачи -->
                <div class="content-section">
                    <div class="section-title">Примеры и задачи</div>
                    <div class="form-group">
                        <label for="content3">Содержание (часть 3)</label>
                        <textarea id="content3" name="content3"
                            placeholder="Введите примеры и задачи для урока"><?php echo htmlspecialchars($content3); ?></textarea>
                    </div>
                </div>

                <!-- Секция 4: Домашнее задание -->
                <div class="content-section">
                    <div class="section-title">Домашнее задание</div>
                    <div class="form-group">
                        <label for="content4">Содержание (часть 4)</label>
                        <textarea id="content4" name="content4"
                            placeholder="Введите домашнее задание"><?php echo htmlspecialchars($content4); ?></textarea>
                    </div>
                </div>

                <!-- Поле для URL картинки -->
                <div class="form-group">
                    <label for="image_url">URL изображения (опционально)</label>
                    <input type="text" id="image_url" name="image_url"
                        value="<?php echo htmlspecialchars($image_url); ?>"
                        placeholder="Введите URL изображения для урока">
                </div>

                <div class="form-group">
                    <button type="submit" name="add_lesson">Добавить урок</button>
                </div>
            </form>
        </main>
    </div>

    <!-- Скрипт для управления Sidebar -->
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
    <script>
        const sidebar = document.getElementById("mySidebar");
        const openBtn = document.getElementById("openBtn");
        const closeBtn = document.getElementById("closeBtn");
        const body = document.body;

        function openNav() {
            sidebar.classList.remove("closed");
            body.classList.add("sidebar-open");
        }

        function closeNav() {
            sidebar.classList.add("closed");
            body.classList.remove("sidebar-open");
        }

        openBtn.addEventListener('click', openNav);
        closeBtn.addEventListener('click', closeNav);

        // Закрытие Sidebar при клике вне его области
        document.addEventListener('click', function (event) {
            if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
                closeNav();
            }
        });

        // Закрытие Sidebar при нажатии Escape
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeNav();
            }
        });
    </script>
</body>

</html>