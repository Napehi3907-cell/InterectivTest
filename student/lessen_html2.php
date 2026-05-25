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
$student_id = $user_id;

// Получаем список курсов для ученика через его класс и прикреплённого преподавателя
$sql_courses = "
    SELECT DISTINCT c.id_курса, c.название,
           CAST(c.описание AS VARCHAR(8000)) AS описание,
           p.id_преподавателя, p.фио AS фио_преподавателя
    FROM Обучающиеся s
    JOIN Класс cl ON s.id_класса = cl.id_класса
    JOIN Преподаватели p ON cl.id_преподавателя = p.id_преподавателя
    JOIN Курсы c ON p.id_преподавателя = c.id_преподавателя
    WHERE s.id_студента = ?
    ORDER BY c.название
";

$params_courses = [$student_id];
$stmt_courses = sqlsrv_prepare($link, $sql_courses, $params_courses);

if ($stmt_courses === false) {
    $errors = sqlsrv_errors();
    error_log("Подготовка запроса списка курсов для ученика: " . print_r($errors, true));
    $error_message = "Ошибка сервера при получении списка модулей. Подробности: " . print_r($errors, true);
} else {
    if (sqlsrv_execute($stmt_courses)) {
        while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
            $courses[] = $row;
        }
        if (empty($courses)) {
            // Дополнительная диагностика
            $check_class = "SELECT id_класса FROM Обучающиеся WHERE id_студента = ?";
            $stmt_check = sqlsrv_prepare($link, $check_class, [$student_id]);
            if (sqlsrv_execute($stmt_check)) {
                $class_row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                if (!$class_row || empty($class_row['id_класса'])) {
                    $error_message = "Ученик не прикреплён к классу. Обратитесь к администратору.";
                } else {
                    $error_message = "Для вашего класса не создано ни одного курса.";
                }
            }
        }
    } else {
        $errors = sqlsrv_errors();
        error_log("Выполнение запроса списка модулей для ученика: " . print_r($errors, true));
        $error_message = "Ошибка при выполнении запроса модуля. Подробности: " . print_r($errors, true);
    }
}
// Функция для получения прогресса по курсу
function getCourseProgress($link, $student_id, $course_id)
{
    // Сначала получаем общее количество уроков в курсе
    $sql_total_lessons = "SELECT COUNT(*) AS total FROM Уроки WHERE id_курса = ?";
    $stmt_total = sqlsrv_prepare($link, $sql_total_lessons, [$course_id]);

    if ($stmt_total === false || !sqlsrv_execute($stmt_total)) {
        log_sqlsrv_errors("Ошибка подсчёта общего количества уроков");
        return [
            'общее_количество_уроков' => 0,
            'пройденных_уроков' => 0,
            'процент_выполнения' => 0
        ];
    }

    $total_row = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
    $total_lessons = (int) ($total_row['total'] ?? 0);

    // Получаем количество пройденных уроков
    $sql_completed = "
        SELECT COUNT(DISTINCT p.id_урока) AS completed
        FROM Прогресс_Курса p
        WHERE p.id_студента = ? AND p.id_курса = ?
    ";
    $stmt_completed = sqlsrv_prepare($link, $sql_completed, [$student_id, $course_id]);

    if ($stmt_completed === false || !sqlsrv_execute($stmt_completed)) {
        log_sqlsrv_errors("Ошибка подсчёта пройденных уроков");
        return [
            'общее_количество_уроков' => $total_lessons,
            'пройденных_уроков' => 0,
            'процент_выполнения' => 0
        ];
    }

    $completed_row = sqlsrv_fetch_array($stmt_completed, SQLSRV_FETCH_ASSOC);
    $completed_lessons = (int) ($completed_row['completed'] ?? 0);

    // Рассчитываем процент выполнения
    $percentage = $total_lessons > 0
        ? round(($completed_lessons * 100) / $total_lessons, 2)
        : 0;

    return [
        'общее_количество_уроков' => $total_lessons,
        'пройденных_уроков' => $completed_lessons,
        'процент_выполнения' => $percentage
    ];
}
$temp_courses = [];
$sql_temp_courses = "
    SELECT tc.id_вр, tc.название, tc.описание, tc.id_преподавателя,
           c.id_курса AS original_course_id, c.название AS original_course_name
    FROM Временный_курс tc
    JOIN Курсы c ON tc.id_курса = c.id_курса
    JOIN Класс cl ON tc.id_преподавателя = cl.id_преподавателя
    JOIN Обучающиеся s ON cl.id_класса = s.id_класса
    WHERE s.id_студента = ?
    ORDER BY tc.название
";
$params_temp_courses = [$student_id];
$stmt_temp_courses = sqlsrv_prepare($link, $sql_temp_courses, $params_temp_courses);

if ($stmt_temp_courses === false) {
    error_log("Ошибка подготовки запроса временных курсов для ученика: " . print_r(sqlsrv_errors(), true));
} else {
    if (sqlsrv_execute($stmt_temp_courses)) {
        while ($row = sqlsrv_fetch_array($stmt_temp_courses, SQLSRV_FETCH_ASSOC)) {
            $temp_courses[] = $row;
        }
    } else {
        error_log("Ошибка выполнения запроса временных курсов для ученика: " . print_r(sqlsrv_errors(), true));
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Уроки - Ученик</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .course-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
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

        .teacher-info {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            font-size: 0.9em;
        }

        .teacher-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .teacher-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .no-teacher {
            color: #6c757d;
            font-style: italic;
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
    </style>
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать <?php
            if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
                echo htmlspecialchars($_SESSION['full_name']);
            } elseif (isset($_SESSION['login']) && !empty($_SESSION['login'])) {
                echo htmlspecialchars($_SESSION['login']);
            } else {
                echo 'Пользователь';
            }
            ?></span>
            <a href="../student/Name.php">Изменить профиль</a>
            <a href="../Login.php">Выход</a>
        </div>
    </header>

    <main>
        <h2>Интерактивный курс по информатике</h2>
        <div class="search-container">
            <input type="text" id="courseSearch" placeholder="Введите название модуля для поиска..."
                aria-label="Поиск модулей">
            <button type="button" id="searchBtn">Найти</button>
        </div>

        <div id="searchResults" class="search-results" style="display: none;">
            <h3>Результаты поиска:</h3>
            <ul id="searchResultsList"></ul>
        </div>

        <p>Здесь ученики могут выбирать и проходить интерактивные уроки.</p>

        <!-- Список курсов и уроков -->
        <div class="card">
            <section>
                <h1>Раздел модулей</h1>

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
                                $progress = getCourseProgress($link, $student_id, $course['id_курса']);
                                ?>
                                <div class="progress-bar-container">
                                    <div class="progress-bar"
                                        style="width: <?php echo $progress['процент_выполнения']; ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    Прогресс: <?php echo $progress['процент_выполнения']; ?>%
                                    (<?php echo $progress['пройденных_уроков']; ?> из
                                    <?php echo $progress['общее_количество_уроков']; ?> уроков)
                                </div>
                            </div>

                            <div class="teacher-info">
                                <?php if (!empty($course['фио_преподавателя'])): ?>
                                    <a href="../student/Profil_teacher.php?id_преподавателя=<?php echo (int) $course['id_преподавателя']; ?>"
                                        class="teacher-link">
                                        <?php echo htmlspecialchars($course['фио_преподавателя']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="no-teacher">Преподаватель не указан</span>
                                <?php endif; ?>
                            </div>


                            <!-- Список уроков внутри курса -->
                            <ul class="lessons-list" id="lessons-<?php echo $course['id_курса']; ?>" style="display: none;">
                                <?php
                                // Получаем обычные уроки
                                $sql_regular = "SELECT id_урока, название,описание,  контент1, контент2, контент3, контент4 FROM Уроки WHERE id_курса = ?";
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
                                usort($allLessons, function ($a, $b) {
                                    return $a['id_урока'] - $b['id_урока'];
                                });

                                if (!empty($allLessons)) {
                                    foreach ($allLessons as $lesson) {
                                        echo '<li class="lesson-item ' . ($lesson['is_video'] ? 'video-lesson' : 'regular-lesson') . '">';
                                        echo '<h3>' . htmlspecialchars($lesson['название']) . '</h3>';
                                        echo '<p>' . htmlspecialchars($lesson['описание']) . '</p>';

                                        ;

                                        // Определяем URL и текст кнопки в зависимости от типа урока
                                        if ($lesson['is_video']) {
                                            $url = "../student/VideoUrok.php?id_урока=" . $lesson['id_урока'];
                                            $buttonText = "🎞 Смотреть видеоурок";
                                        } else {
                                            $url = "../student/Uroki.php?id_урока=" . $lesson['id_урока'];
                                            $buttonText = "📖 Начать урок";
                                        }

                                        echo '<a href="' . $url . '" class="btn">' . $buttonText . '</a>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li class="no-lessons">В этом модуле пока нет уроков</li>';
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
                                    $progress = getCourseProgress($link, $student_id, $temp_course['original_course_id']);
                                    ?>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar"
                                            style="width: <?php echo $progress['процент_выполнения']; ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        Прогресс: <?php echo $progress['процент_выполнения']; ?>%
                                        (<?php echo $progress['пройденных_уроков']; ?> из
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
                    searchResultsList.innerHTML = '<li>Модули курса не найдены</li>';
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