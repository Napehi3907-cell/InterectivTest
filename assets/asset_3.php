<?php
// Подключение к БД
require_once '../includes/db_connect.php';

// Получаем список всех курсов
$sql_courses = "SELECT id_курса, название FROM Курсы ORDER BY название";
$stmt_courses = sqlsrv_query($link, $sql_courses);
$courses = [];
while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
    $courses[] = $row;
}

// ID студента (в реальном коде берётся из сессии)
$student_id = $_SESSION['id_студента'] ?? 1; // Замените на реальный ID студента

// ID выбранного курса (из GET‑параметра или по умолчанию первый)
$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : ($courses[0]['id_курса'] ?? 0);

// Запрос для получения прогресса по выбранному курсу
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

$params = [$student_id, $selected_course_id];
$stmt_progress = sqlsrv_prepare($link, $sql_progress, $params);
$progress_data = null;

if (sqlsrv_execute($stmt_progress)) {
    $progress_data = sqlsrv_fetch_array($stmt_progress, SQLSRV_FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Прогресс обучения</title>
    <style>
        .progress-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .course-selector {
            margin-bottom: 20px;
        }
        .progress-bar-container {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            border-radius: 15px;
            transition: width 0.5s ease-in-out;
        }
        .progress-text {
            text-align: center;
            margin-top: 5px;
            font-weight: bold;
            color: #333;
        }
        select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Прогресс обучения</h1>

        <!-- Выпадающий список курсов -->
        <div class="course-selector">
            <label for="courseSelect">Выберите курс: </label>
            <select id="courseSelect" onchange="changeCourse(this.value)">
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id_курса']; ?>"
                <?php if ($course['id_курса'] == $selected_course_id): ?> selected<?php endif; ?>>
                <?php echo htmlspecialchars($course['название']); ?>
            </option>
        <?php endforeach; ?>
            </select>
        </div>

        <!-- Контейнер прогресса -->
        <div class="progress-container">
            <?php if ($progress_data): ?>
                <h3><?php echo htmlspecialchars($progress_data['название']); ?></h3>
                <p>Прогресс: <?php echo $progress_data['процент_выполнения']; ?>%
                (<?php echo $progress_data['пройденных_уроков']; ?> из
                <?php echo $progress_data['общее_количество_уроков']; ?> уроков)</p>

                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $progress_data['процент_выполнения']; ?>%"></div>
                </div>
                <div class="progress-text">
                    <?php echo $progress_data['процент_выполнения']; ?>% выполнено
                </div>
            <?php else: ?>
                <p>Данные о прогрессе не найдены.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function changeCourse(courseId) {
            // Перезагружаем страницу с новым параметром курса
            window.location.href = '?course_id=' + courseId;
        }
    </script>
</body>
</html>