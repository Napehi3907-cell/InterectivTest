
<?php
// test_vidioUrok.php

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

// Получаем ID курса из GET‑параметра (исправлено: course_id вместо id_курса)
$course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($course_id <= 0) {
    $error_message = "Не указан ID курса. Вернитесь к списку курсов.";
}

// Обработка формы добавления видеоурока
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video_lesson'])) {
    // Проверяем соединение с БД
    if ($link === false) {
        $error_message = "Ошибка подключения к базе данных.";
    } else {
        $lesson_name = trim($_POST['lesson_name']);
        $lesson_content = trim($_POST['lesson_content']);
        $lesson_description = trim($_POST['lesson_description']);
        $video_link = trim($_POST['video_link']);

        if (empty($lesson_name) || empty($video_link)) {
            $error_message = "Пожалуйста, заполните все обязательные поля.";
        } else {
            // SQL‑запрос для добавления видеоурока
            $sql_add_lesson = "INSERT INTO Видео_Уроки (название, контент, описание, Ссылка, id_курса) VALUES (?, ?, ?, ?, ?)";
            $params_add_lesson = [$lesson_name, $lesson_content, $lesson_description, $video_link, $course_id];

            $stmt_add_lesson = sqlsrv_prepare($link, $sql_add_lesson, $params_add_lesson);
            if ($stmt_add_lesson === false) {
                $error_message = "Ошибка сервера при подготовке запроса: " . print_r(sqlsrv_errors(), true);
            } else {
                if (sqlsrv_execute($stmt_add_lesson)) {
                    $success_message = "Видеоурок добавлен успешно!";
                    // Очищаем поля формы после успешного добавления
                    $lesson_name = $lesson_content = $lesson_description = $video_link = '';
                } else {
                    $error_message = "Ошибка сервера при добавлении видеоурока: " . print_r(sqlsrv_errors(), true);
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
    <title>Добавление видеоурока</title>

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
    </style>
</head>

<body class="container">
    <!-- Sidebar (боковая панель навигации) -->
     <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

        <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.html">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
        <hr style="border-color: #4a637a; margin: 10px 20px;">

        <!-- Кнопка выхода -->
        <button name="login_as_regist" class="Regis-btn">
            <a href="http://localhost/15/your_project_folder/login.php" class="no-underline">
                Выход
            </a>
        </button>
    </div>

    <!-- 2. Шапка (Header) -->
    <header>
        <div class="nav-bar">
            <!-- Кнопка для открытия Sidebar -->
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Добавление видеоурока</span>
        </div>
    </header>


    <!-- Основной контент -->
    <main class="ma">
        <?php
        // Вывод сообщений об ошибках или успехе
        if (!empty($error_message)) {
            echo '<div class="error">' . htmlspecialchars($error_message) . '</div>';
        }
        if (!empty($success_message)) {
            echo '<div class="success">' . htmlspecialchars($success_message) . '</div>';
        }
        ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="lesson_name">Название урока *</label>
                <input type="text" id="lesson_name" name="lesson_name"
                    value="<?php echo htmlspecialchars($lesson_name ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="lesson_description">Описание урока</label>
                <textarea id="lesson_description" name="lesson_description"
                    rows="4"><?php echo htmlspecialchars($lesson_description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="lesson_content">Содержание урока</label>
                <textarea id="lesson_content" name="lesson_content"
                    rows="6"><?php echo htmlspecialchars($lesson_content ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="video_link">Ссылка на видео *</label>
                <input type="text" id="video_link" name="video_link"
                    value="<?php echo htmlspecialchars($video_link ?? ''); ?>" required
                    placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <input type="hidden" name="add_video_lesson" value="1">
            <button type="submit" class="Regis-btn">Добавить видеоурок</button>
        </form>

        
    </main>

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