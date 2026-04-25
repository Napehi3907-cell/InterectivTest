<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php';

// Инициализация переменных
$error_message = '';
$selected_course_id = $_GET['course_id'] ?? null;
$students_progress = [];

// Получаем список всех курсов для выпадающего списка
$sql_courses = "SELECT id_курса, название FROM Курсы ORDER BY название";
$stmt_courses = sqlsrv_query($link, $sql_courses);
$courses = [];

if ($stmt_courses === false) {
    log_sqlsrv_errors("Получение списка курсов");
    $error_message = "Ошибка при получении списка курсов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}

// Если выбран курс, получаем прогресс всех студентов по этому курсу
if ($selected_course_id) {
    $sql_progress = "
        SELECT
            s.id_студента,
            s.фио AS имя,
            COUNT(DISTINCT l.id_урока) AS общее_количество_уроков,
            COUNT(DISTINCT p.id_урока) AS пройденных_уроков,
            CASE
                WHEN COUNT(DISTINCT l.id_урока) = 0 THEN 0
                ELSE ROUND(COUNT(DISTINCT p.id_урока) * 100 / COUNT(DISTINCT l.id_урока), 2)
            END AS процент_выполнения
        FROM Обучающиеся s
        CROSS JOIN Курсы c
        LEFT JOIN Уроки l ON c.id_курса = l.id_курса
        LEFT JOIN Прогресс_Курса p ON l.id_урока = p.id_урока AND s.id_студента = p.id_студента
        WHERE c.id_курса = ?
        GROUP BY s.id_студента, s.фио
        ORDER BY процент_выполнения DESC
    ";

    $params = [$selected_course_id];
    $stmt_progress = sqlsrv_prepare($link, $sql_progress, $params);

    if (sqlsrv_execute($stmt_progress)) {
        while ($row = sqlsrv_fetch_array($stmt_progress, SQLSRV_FETCH_ASSOC)) {
            $students_progress[] = $row;
        }
    } else {
        log_sqlsrv_errors("Получение прогресса студентов");
        $error_message = "Ошибка при получении прогресса студентов.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    
    <meta charset="UTF-8">
    <title>Прогресс учеников по курсам</title>
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
        .select-course {
            margin: 20px 0;
            padding: 10px;
            font-size: 16px;
            width: 300px;
        }
        .students-progress {
            margin-top: 20px;
        }
        .student-progress-item {
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .student-name {
            font-weight: bold;
            flex: 1;
        }
        .progress-info {
            text-align: right;
            white-space: nowrap;
            margin-left: 15px;
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
   <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.html">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
    
    <hr style="border-color: #4a637a; margin: 10px 20px;">
    
    <!-- Кнопка выхода -->
    <button class="Regis-btn">
        <a href="http://localhost/переделанная/15/your_project_folder/login.php" class="no-underline">Выход</a>
    </button>
</div>
    <main>
        <h2>Прогресс учеников по курсам</h2>
        <p>Выберите курс для просмотра прогресса всех учеников.</p>

        <!-- Выпадающий список для выбора курса -->
        <form method="GET" action="">
            <label for="course_select">Выберите курс:</label>
            <select id="course_select" name="course_id" class="select-course" onchange="this.form.submit()">
                <option value="">-- Выберите курс --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['id_курса']); ?>"
                <?php if ($selected_course_id == $course['id_курса']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($course['название']); ?>
            </option>
        <?php endforeach; ?>
            </select>
        </form>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Отображение прогресса учеников, если выбран курс -->
        <?php if ($selected_course_id && !empty($students_progress)): ?>
            <div class="students-progress">
                <h3>Прогресс учеников по курсу:</h3>
                
                <?php foreach ($students_progress as $student): ?>
                    <div class="student-progress-item">
                    <span class="student-name"><?php echo htmlspecialchars($student['имя']); ?></span>
                    
            <!-- Прогресс‑бар для студента -->
            <div class="progress-container" style="flex: 2; margin: 0 15px;">
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $student['процент_выполнения']; ?>%"></div>
                </div>
            </div>
            
            <!-- Информация о прогрессе -->
            <div class="progress-info">
                <?php echo $student['процент_выполнения']; ?>%
                (<?php echo $student['пройденных_уроков']; ?> из
                <?php echo $student['общее_количество_уроков']; ?> уроков)
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($selected_course_id): ?>
    <div class="no-data">По выбранному курсу пока нет данных о прогрессе учеников.</div>
<?php endif; ?>
</main>

<script>
// Добавляем обработчик для формы, чтобы предотвратить множественные отправки
document.querySelector('form').addEventListener('submit', function(e) {
    // Предотвращаем повторную отправку формы при обновлении страницы
    // Это нужно только для корректной работы выпадающего списка с onchange
});
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
    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
            closeNav();
        }
    });

    // Закрытие Sidebar при нажатии Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeNav();
        }
    });
</script>
</body>
</html>
