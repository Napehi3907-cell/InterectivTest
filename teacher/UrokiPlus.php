<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];


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
$teacher_id = $user_id;

// Получаем список всех курсов для текущего преподавателя
if ($teacher_id !== null) {
    $sql_courses = "
        SELECT c.id_курса, c.название, c.описание
        FROM Курсы c
        WHERE c.id_преподавателя = ?
        ORDER BY c.название
    ";
    $params_courses = [$teacher_id];
    $stmt_courses = sqlsrv_prepare($link, $sql_courses, $params_courses);

    if ($stmt_courses === false) {
        log_sqlsrv_errors("Подготовка запроса списка курсов преподавателя");
        $error_message = "Ошибка сервера при получении списка модулей.";
    } else {
        if (sqlsrv_execute($stmt_courses)) {
            while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
                $courses[] = $row;
            }
        } else {
            log_sqlsrv_errors("Выполнение запроса списка модулей преподавателя");
            $error_message = "Ошибка при выполнении запроса модуля.";
        }
    }
}


// Функция для получения прогресса по курсу
function getCourseProgress($link, $course_id)
{
    // Сначала получаем общее количество уроков в курсе
    $sql_total_lessons = "SELECT COUNT(*) AS total FROM Уроки WHERE id_курса = ?";
    $stmt_total = sqlsrv_prepare($link, $sql_total_lessons, [$course_id]);

    if ($stmt_total === false) {
        $errors = sqlsrv_errors();
        error_log("Ошибка подготовки запроса общего количества уроков: " . print_r($errors, true));
        return [
            'общее_количество_уроков' => 0,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    if (!sqlsrv_execute($stmt_total)) {
        $errors = sqlsrv_errors();
        error_log("Ошибка выполнения запроса общего количества уроков: " . print_r($errors, true));
        return [
            'общее_количество_уроков' => 0,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    $total_row = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
    $total_lessons = (int) ($total_row['total'] ?? 0);

    // Если в курсе нет уроков, возвращаем нулевые значения
    if ($total_lessons === 0) {
        return [
            'общее_количество_уроков' => 0,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    // Получаем прогресс по всем ученикам курса
    $sql_progress_all = "
        SELECT
            s.id_студента,
            COUNT(DISTINCT p.id_урока) AS completed_lessons
        FROM Обучающиеся s
        JOIN Класс cl ON s.id_класса = cl.id_класса
        JOIN Курсы c ON cl.id_преподавателя = c.id_преподавателя
        LEFT JOIN Прогресс_Курса p ON s.id_студента = p.id_студента AND p.id_курса = ?
        WHERE c.id_курса = ?
        GROUP BY s.id_студента
    ";

    $params = [$course_id, $course_id];
    $stmt_progress = sqlsrv_prepare($link, $sql_progress_all, $params);

    if ($stmt_progress === false) {
        $errors = sqlsrv_errors();
        error_log("Ошибка подготовки запроса прогресса всех учеников: " . print_r($errors, true));
        return [
            'общее_количество_уроков' => $total_lessons,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    if (!sqlsrv_execute($stmt_progress)) {
        $errors = sqlsrv_errors();
        error_log("Ошибка выполнения запроса прогресса всех учеников: " . print_r($errors, true));
        return [
            'общее_количество_уроков' => $total_lessons,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    // Собираем данные по всем ученикам
    $students_progress = [];
    while ($row = sqlsrv_fetch_array($stmt_progress, SQLSRV_FETCH_ASSOC)) {
        $completed_lessons = (int) ($row['completed_lessons'] ?? 0);
        $percentage = round(($completed_lessons * 100) / $total_lessons, 2);

        $students_progress[] = [
            'id_студента' => $row['id_студента'],
            'пройденных_уроков' => $completed_lessons,
            'процент_выполнения' => $percentage
        ];
    }

    $student_count = count($students_progress);

    // Если учеников нет, возвращаем нулевые значения
    if ($student_count === 0) {
        return [
            'общее_количество_уроков' => $total_lessons,
            'среднее_количество_пройденных_уроков' => 0,
            'средний_процент_выполнения' => 0,
            'количество_учеников' => 0
        ];
    }

    // Рассчитываем средние значения
    $sum_completed = array_sum(array_column($students_progress, 'пройденных_уроков'));
    $average_completed = round($sum_completed / $student_count, 2);

    $sum_percentage = array_sum(array_column($students_progress, 'процент_выполнения'));
    $average_percentage = round($sum_percentage / $student_count, 2);

    return [
        'общее_количество_уроков' => $total_lessons,
        'среднее_количество_пройденных_уроков' => $average_completed,
        'средний_процент_выполнения' => $average_percentage,
        'количество_учеников' => $student_count
    ];
}

// Обработка удаления урока
if (isset($_POST['delete_lesson']) && isset($_POST['lesson_id'])) {
    $lesson_id = trim($_POST['lesson_id']);

    // SQL-запрос для удаления урока
    $sql_delete_lesson = "DELETE FROM Уроки WHERE id_урока = ?";
    $params_delete_lesson = [$lesson_id];

    $stmt_delete_lesson = sqlsrv_prepare($link, $sql_delete_lesson, $params_delete_lesson);
    if ($stmt_delete_lesson === false) {
        log_sqlsrv_errors("Подготовка запроса удаления урока");
        $error_message = "Ошибка сервера при удалении урока.";
    } else {
        if (sqlsrv_execute($stmt_delete_lesson)) {
            $success_message = "Урок удалён успешно!";
        } else {
            log_sqlsrv_errors("Выполнение запроса удаления урока");
            $error_message = "Ошибка сервера при удалении урока.";
        }
    }
}
if (isset($_POST['delete_test']) && isset($_POST['test_id'])) {
    $test_id = trim($_POST['test_id']);

    // SQL‑запрос для удаления теста
    $sql_delete_test = "DELETE FROM TestUr WHERE id_test = ?";
    $params_delete_test = [$test_id];

    $stmt_delete_test = sqlsrv_prepare($link, $sql_delete_test, $params_delete_test);
    if ($stmt_delete_test === false) {
        log_sqlsrv_errors("Подготовка запроса удаления теста");
        $error_message = "Ошибка сервера при удалении теста.";
    } else {
        if (sqlsrv_execute($stmt_delete_test)) {
            $success_message = "Тест удалён успешно!";
        } else {
            log_sqlsrv_errors("Выполнение запроса удаления теста");
            $error_message = "Ошибка сервера при удалении теста.";
        }
    }
}
$all_courses = [];
$sql_all_courses = "
    SELECT id_курса, название, описание
    FROM Курсы
    ORDER BY название
";
$stmt_all_courses = sqlsrv_prepare($link, $sql_all_courses, []);

if ($stmt_all_courses === false) {
    error_log("Ошибка подготовки запроса всех курсов: " . print_r(sqlsrv_errors(), true));
} else {
    if (sqlsrv_execute($stmt_all_courses)) {
        while ($row = sqlsrv_fetch_array($stmt_all_courses, SQLSRV_FETCH_ASSOC)) {
            $all_courses[] = $row;
        }
    } else {
        error_log("Ошибка выполнения запроса всех курсов: " . print_r(sqlsrv_errors(), true));
    }
}
$temp_courses = [];
$sql_temp_courses = "
    SELECT tc.id_вр, tc.название, tc.описание, tc.id_преподавателя, tc.id_курса AS original_course_id,
           c.название AS original_course_name
    FROM Временный_курс tc
    JOIN Курсы c ON tc.id_курса = c.id_курса
    WHERE tc.id_преподавателя = ?
    ORDER BY tc.название
";
$params_temp_courses = [$teacher_id];
$stmt_temp_courses = sqlsrv_prepare($link, $sql_temp_courses, $params_temp_courses);

if ($stmt_temp_courses === false) {
    error_log("Ошибка подготовки запроса временных курсов: " . print_r(sqlsrv_errors(), true));
} else {
    if (sqlsrv_execute($stmt_temp_courses)) {
        while ($row = sqlsrv_fetch_array($stmt_temp_courses, SQLSRV_FETCH_ASSOC)) {
            $temp_courses[] = $row;
        }
    } else {
        error_log("Ошибка выполнения запроса временных курсов: " . print_r(sqlsrv_errors(), true));
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>

    <meta charset="UTF-8">
    <title>Модули и Уроки</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .course-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;


        }

        .course-list::before {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            background-image: url('https://i.pinimg.com/736x/43/ed/a2/43eda2144796d0514817178b8496c0fc.jpg');
            background-size: contain;
            margin-right: 10px;
            vertical-align: middle;
        }

        .progress-container {
            margin-top: 10px;
        }

        .progress-bar-container {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            border-radius: 10px;
            transition: width 0.5s ease-in-out;
        }

        .progress-text {
            text-align: center;
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .search-container {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        #courseSearch {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        #searchBtn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        #searchBtn:hover {
            background: #0056b3;
        }

        .search-results {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .search-result-item {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .temp-course-btn {
            background: #ff9800;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .temp-course-btn:hover {
            background: #f57c00;
        }

        /* Стили для модального окна */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
        }

        .close-modal {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .temp-courses-section {
            margin-top: 40px;
            padding: 20px;
            background: #fff9e6;
            /* Светлый оранжевый фон */
            border-radius: 8px;
            border: 1px solid #ffd99d;
        }

        .temp-courses-section h2 {
            color: #d97706;
            /* Оранжевый цвет заголовка */
            border-bottom: 2px solid #ffd99d;
            padding-bottom: 10px;
        }

        /* Стили для временных курсов */
        .course-item.temp-course {
            border-color: #ffd99d;
            background: #fffdf5;
            /* Более светлый фон для временных курсов */
        }

        /* Оранжевая кнопка просмотра урока */
        .btn-view-lesson {
            background: #ff9800;
            /* Оранжевый цвет */
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-view-lesson:hover {
            background: #e68900;
            /* Темнее при наведении */
        }

        /* Сообщение об отсутствии временных курсов */
        .no-temp-courses {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        .lesson-item.video-lesson {
            border-left: 4px solid #ff9800;
            /* Оранжевая полоса слева */
            background: #fff8e1;
            /* Светлый оранжевый фон */
            padding: 12px 15px;
        }

        .lesson-item.regular-lesson {
            border-left: 4px solid #4CAF50;
            /* Зелёная полоса для обычных уроков */
            background: #f1f8e9;
            /* Светлый зелёный фон */
        }

        /* Иконка перед названием видеоурока */
        .video-lesson .lesson-title::before {
            content: "🎞 ";
            color: #ff9800;
        }

        .lesson-item.test-lesson {
            border-left: 4px solid #2196F3;
            /* Синяя полоса слева для тестов */
            background: #E3F2FD;
            /* Светлый синий фон */
            padding: 12px 15px;
        }

        /* Иконка перед названием теста */
        .test-lesson .lesson-title::before {
            content: "📝 ";
            color: #2196F3;
        }
    </style>
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Модули и Уроки</span>

            <button class="temp-course-btn" id="tempCourseBtn">⏰ Временные модули</button>
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
    <main>
        <h2>Интерактивный курс по информатике</h2>
        <p>Здесь ученики могут выбирать и проходить интерактивные уроки.</p>

        <!-- Список курсов и уроков -->
        <div class="card">
            <section>
                <h1>Раздел Модулей</h1>

                <div class="search-container">
                    <input type="text" id="courseSearch" placeholder="Введите название модуля для поиска..."
                        aria-label="Поиск модулей">
                    <button type="button" id="searchBtn">Найти</button>
                </div>

                <div id="searchResults" class="search-results" style="display: none;">
                    <h3>Результаты поиска:</h3>
                    <ul id="searchResultsList"></ul>
                </div>






                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <ol class="courses-list">
                    <?php foreach ($courses as $course): ?>
                        <li class="course-item">

                            <h2><?php echo htmlspecialchars($course['название']); ?></h2>
                            <p><?php echo htmlspecialchars($course['описание']); ?></p>

                            <!-- Прогресс‑бар для текущего курса -->
                            <div class="progress-container">
                                <?php
                                $progress = getCourseProgress($link, $course['id_курса']);
                                ?>
                                <div class="progress-bar-container">
                                    <div class="progress-bar"
                                        style="width: <?php echo $progress['средний_процент_выполнения']; ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    Средний прогресс по курсу: <?php echo $progress['средний_процент_выполнения']; ?>%
                                    (в среднем <?php echo $progress['среднее_количество_пройденных_уроков']; ?> из
                                    <?php echo $progress['общее_количество_уроков']; ?> уроков на ученика)
                                    <br>
                                    Участвует учеников: <?php echo $progress['количество_учеников']; ?>
                                </div>
                            </div>

                            <!-- Кнопка добавления урока для этого курса -->
                            <div style="margin: 15px 0;">
                                <a href="../teacher/test_menuUrok.php?course_id=<?php echo $course['id_курса']; ?>"
                                    class="btn add-lesson-btn">
                                    📚 Добавить урок
                                </a>
                                <a href="../teacher/test_vidioUrok.php?course_id=<?php echo $course['id_курса']; ?>"
                                    class="btn add-lesson-btn">
                                    🎥 Добавить видеоурок
                                </a>
                            </div>


                            <!-- Список уроков внутри курса -->
                            <ul class="lessons-list" id="lessons-<?php echo $course['id_курса']; ?>" style="display: none;">
                                <?php
                                // Получаем обычные уроки
                                $sql_regular = "SELECT id_урока, id_курса, название, описание, контент1, контент2, контент3, контент4, картинка FROM Уроки WHERE id_урока = ?";
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

                                // ПОЛУЧАЕМ ТЕСТЫ ДЛЯ ЭТОГО КУРСА (внутри цикла, где $course определена)
                                $tests = [];
                                $sql_tests = "
                    SELECT t.id_test, t.название, t.описание, t.ссылка, t.id_урока, l.название AS lesson_name
            FROM TestUr t
            JOIN Уроки l ON t.id_урока = l.id_урока
            WHERE l.id_курса = ?
        ";
                                $params_tests = [$course['id_курса']];
                                $stmt_tests = sqlsrv_prepare($link, $sql_tests, $params_tests);
                                if ($stmt_tests !== false && sqlsrv_execute($stmt_tests)) {
                                    while ($test = sqlsrv_fetch_array($stmt_tests, SQLSRV_FETCH_ASSOC)) {
                                        $tests[] = $test;
                                    }
                                }

                                // Объединяем уроки в один массив
                                $allLessons = array_merge($regularLessons, $videoLessons);

                                // Сортируем по ID урока
                                usort($allLessons, function ($a, $b) {
                                    return $a['id_урока'] - $b['id_урока'];
                                });

                                // Выводим уроки
                                if (!empty($allLessons)) {
                                    foreach ($allLessons as $lesson) {
                                        echo '<li class="lesson-item ' . ($lesson['is_video'] ? 'video-lesson' : 'regular-lesson') . '">';
                                        echo '<h3 class="lesson-title lesson-title-' . htmlspecialchars($lesson['id_урока']) . '">' . htmlspecialchars($lesson['название']) . '</h3>';
                                        echo '<p>' . htmlspecialchars($lesson['описание']) . '</p>';

                                        // Определяем URL и текст кнопки в зависимости от типа урока
                                        // Определяем URL и текст кнопок в зависимости от типа урока
                                        $url = '';
                                        $buttonText = '';
                                        $editUrl = '';
                                        $deleteParamName = 'lesson_id';
                                        $deleteValue = $lesson['id_урока'] ?? '';
                                        $deleteActionName = 'delete_lesson';
                                        $contentType = 'урок';

                                        if (isset($lesson['is_video']) && $lesson['is_video'] == 1) {
                                            // Для видеоуроков
                                            $lessonId = $lesson['id_урока'] ?? '';
                                            $url = "../student/VideoUrok.php?id_урока=" . htmlspecialchars((string) $lessonId);
                                            $buttonText = "🎞 Смотреть видеоурок";
                                            $editUrl = "Redakt_VideoUr.php?id_урока=" . htmlspecialchars((string) $lessonId);
                                            $contentType = 'видеоурок';
                                        } elseif (isset($lesson['test_id']) && $lesson['test_id'] !== null) {
                                            // Для тестов
                                            $testId = $lesson['test_id'] ?? '';
                                            $url = "../student/TestUrok.php?id_теста=" . htmlspecialchars((string) $testId);
                                            $buttonText = "📝 Пройти тест";
                                            $editUrl = "Redakt_test.php?id_теста=" . htmlspecialchars((string) $testId);
                                            $deleteParamName = 'test_id';
                                            $deleteValue = $testId;
                                            $deleteActionName = 'delete_test';
                                            $contentType = 'тест';
                                        } else {
                                            // Для обычных уроков
                                            $lessonId = $lesson['id_урока'] ?? '';
                                            $url = "../student/Uroki.php?id_урока=" . htmlspecialchars((string) $lessonId);
                                            $buttonText = "📖 Предпросмотр урока";
                                            $editUrl = "Redakt_urok.php?id_урока=" . htmlspecialchars((string) $lessonId);
                                        }

                                        // Получаем название урока с безопасной обработкой null
                                        $lessonName = $lesson['lesson_name'] ?? $lesson['название'] ?? 'Без названия';
                                        $safeLessonName = htmlspecialchars(addslashes((string) $lessonName));

                                        // Блок с кнопками
                                        echo '<div class="lesson-actions">';

                                        // Кнопка для студентов — доступна для всех типов
                                        if (!empty($url) && !empty($buttonText)) {
                                            echo '<a href="' . $url . '" class="btn btn-primary">' . $buttonText . '</a>';
                                        }

                                        // Кнопка добавления теста — только для обычных уроков и видеоуроков (если нужно)
                                        if (!isset($lesson['test_id']) || $lesson['test_id'] === null) {
                                            $lessonIdForTest = $lesson['id_урока'] ?? '';
                                            echo '<a href="test_TestUrok.php?id_урока=' . htmlspecialchars((string) $lessonIdForTest) . '"
           class="btn btn-add-test">📝 Добавить тест к уроку</a>';
                                        }

                                        // Кнопка редактирования — разная для разных типов
                                        echo '<a href="' . htmlspecialchars($editUrl) . '" class="btn btn-edit">🖊 Редактировать</a>';


                                        // Кнопка удаления — с разной логикой для разных типов
                                        $deleteValueSafe = $deleteValue ?? '';
                                        echo '<form method="post" action=""
       onsubmit="return confirm(\'Вы уверены, что хотите удалить ' . $contentType .
                                            ' «' . $safeLessonName . '»? Это действие нельзя отменить!\');"
       style="display: inline;">';
                                        echo '<input type="hidden" name="' . htmlspecialchars($deleteParamName) .
                                            '" value="' . htmlspecialchars((string) $deleteValueSafe) . '">';
                                        echo '<button type="submit" name="' . htmlspecialchars($deleteActionName) .
                                            '" class="btn btn-delete">🗑 Удалить</button>';
                                        echo '</form>';

                                        echo '</div>'; // Закрытие .lesson-actions
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li class="no-lessons">В этом курсе пока нет уроков</li>';
                                }

                                // Выводим тесты, привязанные к урокам этого курса
                                if (!empty($tests)) {
                                    echo '<li class="tests-section"><h3>Тесты к модулю:</h3><ul class="tests-list">';
                                    foreach ($tests as $test) {

                                        echo '<ul class="lesson-item test-lesson">';
                                        echo '<h4 class="test-title">' . htmlspecialchars($test['название']) . '</h4>';
                                        if (!empty($test['описание'])) {
                                            echo '<p class="test-description">' . htmlspecialchars($test['описание']) . '</p>';
                                        }

                                        echo '<div class="test-actions">';

                                        // Кнопка «Пройти тест»
                                        echo '<a href="' . htmlspecialchars($test['ссылка']) . '" target="_blank"
       class="btn btn-test">📖 Пройти тест</a>';

                                        // Кнопка редактирования теста
                                        $testId = $test['id_test'] ?? '';
                                        echo '<a href="Redakt_test.php?id_test=' . htmlspecialchars((string) $testId) . '"
       class="btn btn-edit">🖊 Редактировать</a>';

                                        // Форма с кнопкой удаления теста
                                        echo '<form method="post" action=""
       onsubmit="return confirm(\'Вы уверены, что хотите удалить тест «' .
                                            htmlspecialchars(addslashes($test['название'] ?? 'Без названия')) . '»? Это действие нельзя отменить!\');"
       style="display: inline; margin-left: 10px;">';
                                        echo '<input type="hidden" name="test_id" value="' . htmlspecialchars((string) $testId) . '">';
                                        echo '<button type="submit" name="delete_test" class="btn btn-delete">🗑 Удалить</button>';
                                        echo '</form>';

                                        echo '</div>';
                                        echo '</ul>';
                                    }
                                    echo '</ul></li>';
                                } else {
                                    echo '<li class="no-tests">К этому модулю пока не прикреплены тесты</li>';
                                }
                                ?>
                            </ul>

                            <button type="button" class="btn" onclick="toggleLessons(<?php echo $course['id_курса']; ?>)">
                                Показать/скрыть уроки
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
            <section class="temp-courses-section">
                <h2>Временные модули</h2>

                <?php if (empty($temp_courses)): ?>
                    <div class="no-temp-courses">
                        <p>У вас пока нет временных модулей</p>
                    </div>
                <?php else: ?>
                    <ol class="courses-list">
                        <?php foreach ($temp_courses as $temp_course): ?>
                            <li class="course-item temp-course">
                                <h2><?php echo htmlspecialchars($temp_course['название']); ?></h2>
                                <p><?php echo htmlspecialchars($temp_course['описание']); ?></p>

                                <!-- Прогресс‑бар для временного курса -->
                                <div class="progress-container">
                                    <?php
                                    $progress = getCourseProgress($link, $temp_course['original_course_id']);
                                    ?>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar"
                                            style="width: <?php echo $progress['средний_процент_выполнения']; ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        Средний прогресс: <?php echo $progress['средний_процент_выполнения']; ?>%
                                        (<?php echo $progress['среднее_количество_пройденных_уроков']; ?> из
                                        <?php echo $progress['общее_количество_уроков']; ?> уроков)
                                    </div>
                                </div>

                                <!-- Список уроков временного курса -->
                                <ul class="lessons-list" id="temp-lessons-<?php echo $temp_course['id_вр']; ?>"
                                    style="display: none;">
                                    <?php
                                    // Получаем уроки из основного курса (временный курс — копия)
                                    $sql_lessons = "
    SELECT
        l.id_урока,
        l.название,
        l.описание,
        0 AS is_video,
        NULL AS ссылка
    FROM Уроки l
    WHERE l.id_курса = ?

    UNION ALL
    SELECT
        v.id_урока,
        v.название,
        v.описание,
        1 AS is_video,
        v.ссылка
    FROM Видео_Уроки v
    WHERE v.id_курса = ?
    ORDER BY название
";
                                    $params_lessons = [$temp_course['original_course_id'], $temp_course['original_course_id']];
                                    $stmt_lessons = sqlsrv_prepare($link, $sql_lessons, $params_lessons);

                                    if ($stmt_lessons !== false && sqlsrv_execute($stmt_lessons)) {
                                        $has_lessons = false;
                                        while ($lesson = sqlsrv_fetch_array($stmt_lessons, SQLSRV_FETCH_ASSOC)) {
                                            $has_lessons = true;
                                            echo '<li class="lesson-item ' . ($lesson['is_video'] ? 'video-lesson' : 'regular-lesson') . '">';
                                            echo '<h3 class="lesson-title">' . htmlspecialchars($lesson['название']) . '</h3>';
                                            echo '<p>' . htmlspecialchars($lesson['описание']) . '</p>';

                                            // Определяем URL и текст кнопки в зависимости от типа урока
                                            if ($lesson['is_video']) {
                                                $url = "../student/VideoUrok.php?id_урока=" . htmlspecialchars((string) $lesson['id_урока']);
                                                $buttonText = "🎞 Смотреть видеоурок";
                                            } else {
                                                $url = "../student/Uroki.php?id_урока=" . htmlspecialchars((string) $lesson['id_урока']);
                                                $buttonText = "📖 Предпросмотр урока";
                                            }

                                            echo '<div class="lesson-actions">';
                                            echo '<a href="' . $url . '" class="btn btn-view-lesson">' . $buttonText . '</a>';
                                            echo '</div>';
                                            echo '</li>';
                                        }
                                        if (!$has_lessons) {
                                            echo '<li class="no-lessons">В этом временном модуле пока нет уроков</li>';
                                        }
                                    } else {
                                        echo '<li class="error-lessons">Ошибка загрузки уроков</li>';
                                    }
                                    ?>
                                </ul>

                                <button type="button" class="btn"
                                    onclick="toggleLessons1('temp-lessons-<?php echo $temp_course['id_вр']; ?>')">
                                    Показать/скрыть уроки
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>
        </div>

    </main>

    <script>
        // Функция для переключения отображения списка уроков
        function toggleLessons(courseId) {
            const lessonsList = document.getElementById('lessons-' + courseId);
            const button = document.querySelector(`button[onclick="toggleLessons(${courseId})"]`);

            if (lessonsList.style.display === 'none' || lessonsList.style.display === '') {
                lessonsList.style.display = 'block';
                button.textContent = 'Скрыть уроки';
            } else {
                lessonsList.style.display = 'none';
                button.textContent = 'Показать уроки';
            }
        }
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const courseSearch = document.getElementById('courseSearch');
            const searchBtn = document.getElementById('searchBtn');
            const searchResults = document.getElementById('searchResults');
            const searchResultsList = document.getElementById('searchResultsList');

            // Получаем все курсы из DOM
            const courseItems = document.querySelectorAll('.course-item');
            const courseTitles = [];

            courseItems.forEach(item => {
                const titleElement = item.querySelector('h2');
                if (titleElement) {
                    courseTitles.push({
                        title: titleElement.textContent,
                        element: item
                    });
                }
            });

            // Функция поиска курсов
            function searchCourses(query) {
                searchResultsList.innerHTML = '';

                if (!query.trim()) {
                    searchResults.style.display = 'none';
                    return;
                }

                const results = courseTitles.filter(course =>
                    course.title.toLowerCase().includes(query.toLowerCase())
                );

                if (results.length > 0) {
                    results.forEach(result => {
                        const li = document.createElement('li');
                        li.className = 'search-result-item';
                        li.textContent = result.title;
                        li.addEventListener('click', () => {
                            scrollToCourse(result.element);
                            searchResults.style.display = 'none';
                            courseSearch.value = '';
                        });
                        searchResultsList.appendChild(li);
                    });
                    searchResults.style.display = 'block';
                } else {
                    searchResultsList.innerHTML = '<li>Курсы не найдены</li>';
                    searchResults.style.display = 'block';
                }
            }

            // Функция прокрутки к курсу
            function scrollToCourse(courseElement) {
                courseElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Визуальный эффект выделения
                courseElement.style.background = '#e8f4fd';
                setTimeout(() => {
                    courseElement.style.transition = 'background 0.5s';
                    courseElement.style.background = '#f9f9f9';
                }, 1500);
            }

            // Обработчики событий
            searchBtn.addEventListener('click', () => {
                searchCourses(courseSearch.value);
            });

            courseSearch.addEventListener('input', () => {
                searchCourses(courseSearch.value);
            });

            // Закрытие результатов при клике вне области
            document.addEventListener('click', (e) => {
                if (!searchResults.contains(e.target) && e.target !== courseSearch && e.target !== searchBtn) {
                    searchResults.style.display = 'none';
                }
            });

            // Поиск по нажатию Enter
            courseSearch.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchCourses(courseSearch.value);
                }
            });
        });
    </script>
    <div id="tempCourseModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h3>Создать временный модуль</h3>
            <form id="tempCourseForm">
                <div class="form-group">
                    <label for="courseSelect">Выберите основной модуль:</label>
                    <select id="courseSelect" class="form-control" required>
                        <option value="">-- Выберите модуль --</option>
                        <?php foreach ($all_courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['id_курса']); ?>">
                                <?php echo htmlspecialchars($course['название']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tempCourseName">Название временного модуля:</label>
                    <input type="text" id="tempCourseName" class="form-control"
                        placeholder="Введите название временного модуля" required>
                </div>

                <div class="form-group">
                    <label for="tempCourseDesc">Описание:</label>
                    <textarea id="tempCourseDesc" class="form-control" rows="3"
                        placeholder="Описание временного модуля"></textarea>
                </div>

                <button type="submit" class="btn-primary">Создать временный модуль</button>
            </form>
        </div>
    </div>
    <script>
        // Элементы модального окна
        const tempCourseModal = document.getElementById('tempCourseModal');
        const tempCourseBtn = document.getElementById('tempCourseBtn');
        const closeModal = document.getElementById('closeModal');

        // Открытие модального окна
        tempCourseBtn.addEventListener('click', function () {
            tempCourseModal.style.display = 'block';
        });

        // Закрытие модального окна
        closeModal.addEventListener('click', function () {
            tempCourseModal.style.display = 'none';
        });

        // Закрытие при клике вне окна
        window.addEventListener('click', function (event) {
            if (event.target === tempCourseModal) {
                tempCourseModal.style.display = 'none';
            }
        });

        // Обработка формы создания временного курса
        document.getElementById('tempCourseForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const courseId = document.getElementById('courseSelect').value;
            const tempName = document.getElementById('tempCourseName').value;
            const tempDesc = document.getElementById('tempCourseDesc').value;

            if (!courseId || !tempName) {
                alert('Пожалуйста, выберите основной модуль и введите название временного модуля');
                return;
            }

            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('temp_name', tempName);
            formData.append('temp_desc', tempDesc);

            console.log('Отправляем данные:', {
                course_id: courseId,
                temp_name: tempName,
                temp_desc: tempDesc
            });

            fetch('create_temp_course.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('HTTP статус:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text(); // сначала получаем сырой ответ
                })
                .then(text => {
                    console.log('Сырой ответ сервера:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Распарсенный JSON:', data);

                        if (data.success) {
                            alert(data.message);
                            tempCourseModal.style.display = 'none';
                            document.getElementById('tempCourseForm').reset();
                        } else {
                            alert('Ошибка сервера: ' + (data.message || 'Неизвестная ошибка'));
                        }
                    } catch (parseError) {
                        console.error('Ошибка парсинга JSON:', parseError);
                        console.error('Сырой текст ответа:', text);
                        alert('Ошибка формата ответа сервера. Проверьте консоль для деталей.');
                    }
                })
                .catch(error => {
                    console.error('Критическая ошибка:', error);
                    alert('Произошла критическая ошибка: ' + error.message);
                });
        });
    </script>
    <script>
        function toggleLessons1(containerId) {
            const lessonsList = document.getElementById(containerId);
            const button = lessonsList.nextElementSibling; // Кнопка рядом с списком уроков

            if (lessonsList.style.display === 'none' || lessonsList.style.display === '') {
                lessonsList.style.display = 'block';
                button.textContent = 'Скрыть уроки';
            } else {
                lessonsList.style.display = 'none';
                button.textContent = 'Показать уроки';
            }
        }
    </script>

</body>

</html>