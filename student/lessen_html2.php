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
$student_id = $_SESSION['id_студента'] ?? 1; // ID студента из сессии

// Получаем список всех курсов
$sql_courses = "SELECT id_курса, название, описание FROM Курсы ORDER BY название";
$stmt_courses = sqlsrv_query($link, $sql_courses);

if ($stmt_courses === false) {
    log_sqlsrv_errors("Подготовка запроса списка курсов");
    $error_message = "Ошибка сервера при получении списка курсов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}

// Функция для получения прогресса по курсу
function getCourseProgress($link, $student_id, $course_id) {
    $sql_progress = "
        SELECT
            c.id_курса,
            c.название,
            COUNT(DISTINCT l.id_урока) AS общее_количество_уроков,
            COUNT(DISTINCT p.id_урока) AS пройденных_уроков,
            CASE
                WHEN COUNT(DISTINCT l.id_урока) = 0 THEN 0
                ELSE ROUND(COUNT(DISTINCT p.id_урока) * 100 / COUNT(DISTINCT l.id_урока), 2)
            END AS процент_выполнения
        FROM Курсы c
        LEFT JOIN Уроки l ON c.id_курса = l.id_курса
        LEFT JOIN Прогресс_Курса p ON l.id_урока = p.id_урока AND p.id_студента = ?
        WHERE c.id_курса = ?
        GROUP BY c.id_курса, c.название
    ";

    $params = [$student_id, $course_id];
    $stmt_progress = sqlsrv_prepare($link, $sql_progress, $params);
    $progress_data = null;

    if (sqlsrv_execute($stmt_progress)) {
        $progress_data = sqlsrv_fetch_array($stmt_progress, SQLSRV_FETCH_ASSOC);
    }

    return $progress_data ?: [
        'общее_количество_уроков' => 0,
        'пройденных_уроков' => 0,
        'процент_выполнения' => 0
    ];
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

    </style>
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
        <div class="search-container">
    <input type="text" id="courseSearch" placeholder="Введите название курса для поиска..." aria-label="Поиск курсов">
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
                <h1>Список курсов и уроков</h1>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <ul class="courses-list">
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
                    <div class="progress-bar" style="width: <?php echo $progress['процент_выполнения']; ?>%"></div>
        </div>
        <div class="progress-text">
            Прогресс: <?php echo $progress['процент_выполнения']; ?>%
            (<?php echo $progress['пройденных_уроков']; ?> из
            <?php echo $progress['общее_количество_уроков']; ?> уроков)
        </div>
    </div>

    <!-- Список уроков внутри курса -->
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
            <button type="button" class="toggle-lessons-btn"
                    onclick="toggleLessons(<?php echo $course['id_курса']; ?>)">
                Показать/скрыть уроки
            </button>
        </li>
    <?php endforeach; ?>
</ul>
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
document.addEventListener('DOMContentLoaded', function() {
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

</body>
</html>

