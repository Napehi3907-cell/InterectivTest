<?php
// Redakt_urok.php — для редактирования видеоуроков

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

$error_message = '';
$success_message = '';
$lesson_id = $_GET['id_урока'] ?? null;
$lesson = null;

// Получаем данные видеоурока для редактирования
if ($lesson_id) {
    $sql_get_lesson = "SELECT id_урока, id_курса, название, описание, контент, Ссылка FROM Видео_Уроки WHERE id_урока = ?";
    $params_get_lesson = [$lesson_id];

    $stmt_get_lesson = sqlsrv_prepare($link, $sql_get_lesson, $params_get_lesson);
    if ($stmt_get_lesson !== false && sqlsrv_execute($stmt_get_lesson)) {
        $lesson = sqlsrv_fetch_array($stmt_get_lesson, SQLSRV_FETCH_ASSOC);
    } else {
        log_sqlsrv_errors("Получение данных видеоурока для редактирования");
        $error_message = "Ошибка при получении данных видеоурока.";
    }
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $lesson_id = trim($_POST['lesson_id']);
    $course_id = trim($_POST['course_id']);
    $lesson_name = trim($_POST['lesson_name']);
    $description = trim($_POST['description']);
    $content = trim($_POST['content']);
    $video_link = trim($_POST['video_link']);

    if (empty($course_id) || empty($lesson_name)) {
        $error_message = "Пожалуйста, заполните обязательные поля: ID курса и название урока.";
    } else {
        // SQL‑запрос для обновления видеоурока
        $sql_update_lesson = "UPDATE Видео_Уроки SET id_курса = ?, название = ?, описание = ?, контент = ?, Ссылка = ? WHERE id_урока = ?";
        $params_update_lesson = [
            $course_id,
            $lesson_name,
            $description,
            $content,
            $video_link,
            $lesson_id
        ];

        $stmt_update_lesson = sqlsrv_prepare($link, $sql_update_lesson, $params_update_lesson);
        if ($stmt_update_lesson === false) {
            log_sqlsrv_errors("Подготовка запроса обновления видеоурока");
            $error_message = "Ошибка сервера при обновлении видеоурока.";
        } else {
            if (sqlsrv_execute($stmt_update_lesson)) {
                $success_message = "Видеоурок успешно обновлён!";
                // Обновляем данные в переменной
                $lesson = [
                    'id_урока' => $lesson_id,
                    'id_курса' => $course_id,
                    'название' => $lesson_name,
                    'описание' => $description,
                    'контент' => $content,
                    'Ссылка' => $video_link
                ];
            } else {
                log_sqlsrv_errors("Выполнение запроса обновления видеоурока");
                $error_message = "Ошибка сервера при обновлении видеоурока.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>

    <meta charset="UTF-8">
    <title>Редактирование урока</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
    <header>
        <div class="nav-bar">

            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Просмотр прогресса учеников</span>
        </div>
    </header>

    <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

        <!-- Пункты меню -->
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.php">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">Прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/Klass_teacher.php">Класс</a>

        <hr style="border-color: #4a637a; margin: 10px 20px;">

        <!-- Кнопка выхода -->
        <button class="Regis-btn">
            <a href="http://localhost/переделанная/15/your_project_folder/login.php" class="no-underline">Выход</a>
        </button>
    </div>
    <mail>
        <div class="ma">

            <div>
                <h1>Редактирование урока</h1>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if ($lesson): ?>
                    <form method="post" action="Redakt_urok.php?id_урока=<?php echo $lesson['id_урока']; ?>">
                        <input type="hidden" name="lesson_id" value="<?php echo $lesson['id_урока']; ?>">


                        <div class="form-group">
                            <label for="course_id">ID курса:</label>
                            <input type="text" id="course_id" name="course_id"
                                value="<?php echo htmlspecialchars($lesson['id_курса'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="lesson_name">Название видеоурока:</label>
                            <input type="text" id="lesson_name" name="lesson_name"
                                value="<?php echo htmlspecialchars($lesson['название'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Описание видеоурока:</label>
                            <textarea id="description" name="description"
                                rows="3"><?php echo htmlspecialchars($lesson['описание'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="content">Контент видеоурока (текст перед видео):</label>
                            <textarea id="content" name="content"
                                rows="6"><?php echo htmlspecialchars($lesson['контент'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="video_link">Ссылка на видео:</label>
                            <input type="text" id="video_link" name="video_link"
                                value="<?php echo htmlspecialchars($lesson['Ссылка'] ?? ''); ?>">
                            <small>Введите полную ссылку на видео (YouTube, Vimeo и т. д.)</small>
                        </div>

                        <button type="submit" name="update_lesson">Обновить видеоурок</button>

                    </form>
                <?php else: ?>
                    <p>Урок не найден.</p>
                <?php endif; ?>
            </div>

        </div>
    </mail>
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