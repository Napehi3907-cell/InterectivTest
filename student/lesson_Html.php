<?php

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
$courses = [];

// Получаем данные из базы данных
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Уроки - Ученик</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать, Ученик!</span>
            <a href="http://localhost/15/your_project_folder/Login.php">Выход</a>
        </div>
    </header>
    <main>
        <h2>Раздел Уроков</h2>
        <p>Здесь ученики могут выбирать и проходить интерактивные уроки.</p>

        <!-- Пример: Список курсов и уроков -->
        <div class="card">
            <h3>Курс: Основы Веб-разработки</h3>
            <p>Прогресс: <strong>50%</strong></p>
            <div class="progress-bar-container large">
                <div class="progress-bar" style="width: 75%;"></div>
            </div>

           <main>
        <h1>Список курсов и уроков</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <ul class="courses-list">
            <?php foreach ($courses as $course): ?>
                <li class="course-item" onclick="toggleLessons(<?php echo $course['id_курса']; ?>)">
                    <h2><?php echo htmlspecialchars($course['название']); ?></h2>
                    <p><?php echo htmlspecialchars($course['описание']); ?></p>
                    <ul class="lessons-list" id="lessons-<?php echo $course['id_курса']; ?>">
                        <?php
                        $sql_lessons = "SELECT id_урока, название, контент FROM Уроки WHERE id_курса = ?";
                        $params_lessons = [$course['id_курса']];
                        $stmt_lessons = sqlsrv_prepare($link, $sql_lessons, $params_lessons);

                        if ($stmt_lessons === false) {
                            log_sqlsrv_errors("Подготовка запроса списка уроков");
                            $error_message = "Ошибка сервера при получении списка уроков.";
                        } else {
                            if (sqlsrv_execute($stmt_lessons)) {
                                while ($lesson = sqlsrv_fetch_array($stmt_lessons, SQLSRV_FETCH_ASSOC)) {
                                    echo '<li class="lesson-item">';
                                    echo '<h3>' . htmlspecialchars($lesson['название']) . '</h3>';
                                    echo '<p>' . htmlspecialchars($lesson['контент']) . '</p>';
                                    echo '<button class="btn" onclick="startLesson(' . $lesson['id_урока'] . ')">Начать урок</button>';
                                    echo '<button class="btn" onclick="completeLesson(' . $lesson['id_урока'] . ')">Завершить урок</button>';
                                    echo '</li>';
                                }
                            } else {
                                log_sqlsrv_errors("Выполнение запроса списка уроков");
                                $error_message = "Ошибка сервера при получении списка уроков.";
                            }
                        }
                        ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>

    <script>
        // Функция для переключения отображения списка уроков
        function toggleLessons(courseId) {
            const lessonsList = document.getElementById('lessons-' + courseId);
            if (lessonsList.style.display === 'none' || lessonsList.style.display === '') {
                lessonsList.style.display = 'block';
            } else {
                lessonsList.style.display = 'none';
            }
        }

        // Функция для начала урока
        function startLesson(lessonId) {
            alert('Начало урока с ID: ' + lessonId);
            // Здесь можно добавить код для начала урока
        }

        // Функция для завершения урока
        function completeLesson(lessonId) {
            alert('Завершение урока с ID: ' + lessonId);
            // Здесь можно добавить код для завершения урока
        }
    </script>

            <h4>Уроки:</h4>
            <ul class="lesson-list">
                <li>
                    <span class="lesson-title">Урок 1: Введение в HTML</span>
                    <a href="#" class="btn view-btn">Смотреть</a>
                    <span class="status completed">[ПРОЙДЕНО]</span>
                </li>
                <li>
                    <span class="lesson-title">Урок 2: Основы CSS</span>
                    <a href="#" class="btn view-btn">Смотреть</a>
                    <span class="status completed">[ПРОЙДЕНО]</span>
                </li>
                <li>
                    <span class="lesson-title">Урок 3: Введение в PHP</span>
                    <a href="#" class="btn view-btn">Смотреть</a>
                    <span class="status upcoming">[НЕ ПРОЙДЕНО]</span>
                </li>
                 <li>
                    <span class="lesson-title">Урок 4: Интерактивность</span>
                    <a href="#" class="btn view-btn">Смотреть</a>
                    <span class="status upcoming">[НЕ ПРОЙДЕНО]</span>
                </li>
            </ul>
        </div>

    </main>

</body>
</html>