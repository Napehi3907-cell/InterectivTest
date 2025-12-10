<?php
// progress_indicator.php

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
$progress = 0;

// Получаем данные из базы данных
if (isset($_GET['student_id']) && isset($_GET['course_id'])) {
    $student_id = trim($_GET['student_id']);
    $course_id = trim($_GET['course_id']);

    // SQL-запрос для получения общего количества уроков в курсе
    $sql_total_lessons = "SELECT COUNT(*) AS total_lessons FROM Уроки WHERE id_курса = ?";
    $params_total_lessons = [$course_id];

    $stmt_total_lessons = sqlsrv_prepare($link, $sql_total_lessons, $params_total_lessons);
    if ($stmt_total_lessons === false) {
        log_sqlsrv_errors("Подготовка запроса общего количества уроков");
        $error_message = "Ошибка сервера при получении общего количества уроков.";
    } else {
        if (sqlsrv_execute($stmt_total_lessons)) {
            $total_lessons = sqlsrv_fetch_array($stmt_total_lessons, SQLSRV_FETCH_ASSOC);
            $total_lessons = $total_lessons['total_lessons'];

            // SQL-запрос для получения количества завершенных уроков
            $sql_completed_lessons = "
                SELECT COUNT(*) AS completed_lessons
                FROM Прогресс_Курса pk
                JOIN Уроки u ON pk.id_урока = u.id_урока
                WHERE pk.id_студента = ? AND u.id_курса = ?
            ";
            $params_completed_lessons = [$student_id, $course_id];

            $stmt_completed_lessons = sqlsrv_prepare($link, $sql_completed_lessons, $params_completed_lessons);
            if ($stmt_completed_lessons === false) {
                log_sqlsrv_errors("Подготовка запроса количества завершенных уроков");
                $error_message = "Ошибка сервера при получении количества завершенных уроков.";
            } else {
                if (sqlsrv_execute($stmt_completed_lessons)) {
                    $completed_lessons = sqlsrv_fetch_array($stmt_completed_lessons, SQLSRV_FETCH_ASSOC);
                    $completed_lessons = $completed_lessons['completed_lessons'];

                    // Рассчитываем прогресс
                    if ($total_lessons > 0) {
                        $progress = ($completed_lessons / $total_lessons) * 100;
                    }
                } else {
                    log_sqlsrv_errors("Выполнение запроса количества завершенных уроков");
                    $error_message = "Ошибка сервера при получении количества завершенных уроков.";
                }
            }
        } else {
            log_sqlsrv_errors("Выполнение запроса общего количества уроков");
            $error_message = "Ошибка сервера при получении общего количества уроков.";
        }
    }
} else {
    $error_message = "Не указан ID студента или ID курса.";
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Индикатор выполнения курса</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        main {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .progress-container {
            width: 100%;
            background-color: #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .progress-bar {
            height: 20px;
            background-color: #4CAF50;
            border-radius: 4px;
            width:
                <?php echo $progress; ?>
                %;
            transition: width 0.3s;
        }

        .progress-text {
            text-align: center;
            margin-top: 10px;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <main>
        <h1>Индикатор выполнения курса</h1>
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <div class="progress-text" id="progress-text"><?php echo round($progress); ?>%</div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
    </main>

    <script>
        // Функция для обновления индикатора выполнения
        function updateProgressBar(progress) {
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');

            progressBar.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
        }

        // Обновляем индикатор выполнения при загрузке страницы
        window.onload = function () {
            updateProgressBar(<?php echo $progress; ?>);
        };
    </script>
</body>

</html>