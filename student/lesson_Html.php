<?php
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

<body class="container">
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать, Ученик!</span>
            <a href="../student/Name.php">Изменить имя</a>
            <a href="../Login.php">Выход</a>
        </div>
    </header>

    <main>
        <h2>Раздел Уроков</h2>
        <p>Здесь ученики могут выбирать и проходить интерактивные уроки.</p>

        <!-- Список курсов и уроков -->
        <div class="card">
            
            <section>
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
                            <ul class="lessons-list" id="lessons-<?php echo $course['id_курса']; ?>" style="display: none;">
                <?php
                // Получаем обычные уроки
                $sql_regular = "SELECT id_урока, название, контент FROM Уроки WHERE id_курса = ?";
                $params_regular = [$course['id_курса']];
                $stmt_regular = sqlsrv_prepare($link, $sql_regular, $params_regular);
                $regularLessons = [];
                if ($stmt_regular !== false && sqlsrv_execute($stmt_regular)) {
                    while ($lesson = sqlsrv_fetch_array($stmt_regular, SQLSRV_FETCH_ASSOC)) {
                $lesson['is_video'] = false;
                $regularLessons[] = $lesson;
            }
        }

        // Получаем видеоуроки
        $sql_video = "SELECT id_урока, название, контент, описание, Ссылка FROM Видео_Уроки WHERE id_курса = ?";
        $params_video = [$course['id_курса']];
        $stmt_video = sqlsrv_prepare($link, $sql_video, $params_video);
        $videoLessons = [];
        if ($stmt_video !== false && sqlsrv_execute($stmt_video)) {
            while ($lesson = sqlsrv_fetch_array($stmt_video, SQLSRV_FETCH_ASSOC)) {
                $lesson['is_video'] = true;
                $videoLessons[] = $lesson;
            }
        }

        // Объединяем уроки в один массив
        $allLessons = array_merge($regularLessons, $videoLessons);

        // Сортируем по ID урока
        usort($allLessons, function($a, $b) {
            return $a['id_урока'] - $b['id_урока'];
        });

        if (!empty($allLessons)) {
            foreach ($allLessons as $lesson) {
                echo '<li class="lesson-item ' . ($lesson['is_video'] ? 'video-lesson' : 'regular-lesson') . '">';
                echo '<h3>' . htmlspecialchars($lesson['название']) . '</h3>';
                echo '<p>' . htmlspecialchars($lesson['контент']) . '</p>';

                // Определяем URL и текст кнопки в зависимости от типа урока
                if ($lesson['is_video']) {
                    $url = "../student/VideoUrok.php?id_урока=" . $lesson['id_урока'];
            $buttonText = "Смотреть видеоурок";
        } else {
            $url = "../student/Uroki.php?id_урока=" . $lesson['id_урока'];
            $buttonText = "Начать урок";
        }

        echo '<a href="' . $url . '" class="btn">' . $buttonText . '</a>';
        echo '</li>';
            }
        } else {
            echo '<li class="no-lessons">В этом курсе пока нет уроков</li>';
        }
        ?>
            </ul>
        </li>
    <?php endforeach; ?>
</ul>
</section>

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
</script>
</div>
</main>
</body>
</html>
