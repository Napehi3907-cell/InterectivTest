<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php';

// Инициализация переменных
$error_message = '';
$selected_class_id = $_GET['class_id'] ?? null;
$students_data = [];
$classes = [];

// Получаем список всех классов для выпадающего списка
$sql_classes = "SELECT id_класса, название FROM Класс ORDER BY название";
$stmt_classes = sqlsrv_query($link, $sql_classes);

if ($stmt_classes === false) {
    $error_message = "Ошибка при получении списка классов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_classes, SQLSRV_FETCH_ASSOC)) {
        $classes[] = $row;
    }
}

// Если выбран класс, получаем список учеников этого класса
if ($selected_class_id) {
    $sql_students = "
SELECT
    s.id_студента,
    COALESCE(s.фио, s.логин) AS имя_для_отображения,
    s.логин,
    s.фио
FROM Обучающиеся s
WHERE s.id_класса = ?
ORDER BY s.фио
";
    
    $params = [$selected_class_id];
    $stmt_students = sqlsrv_prepare($link, $sql_students, $params);
    
    if (sqlsrv_execute($stmt_students)) {
        while ($row = sqlsrv_fetch_array($stmt_students, SQLSRV_FETCH_ASSOC)) {
            $students_data[] = $row;
        }
    } else {
        $error_message = "Ошибка при получении списка учеников класса.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ученики класса</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .class-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .select-class {
            margin: 20px 0;
            padding: 10px;
            font-size: 16px;
            width: 300px;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .students-table th, .students-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .students-table th {
            background-color: #4a637a;
            color: white;
            font-weight: bold;
        }
        
        .students-table tr:hover {
            background-color: #f5f9fc;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .student-count {
            font-size: 0.9em;
            color: #666;
            margin-left: 10px;
        }
    </style>
</head>

<body class="container">

<header>
    <div class="nav-bar">
        <button class="openbtn" id="openBtn">☰ Меню</button>
        <span>Просмотр учеников класса</span>
    </div>
</header>

<div id="mySidebar" class="sidebar closed">
    <!-- Кнопка закрытия (крестик) -->
    <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

    <!-- Пункты меню -->
    <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.php">Главная</a>
    <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
    <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">Прогресс</a>
    <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчёты</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/Klass_teacher.php" >Класс</a>

    <hr style="border-color: #4a637a; margin: 10px 20px;">


    <!-- Кнопка выхода -->
    <button class="Regis-btn">
        <a href="http://localhost/переделанная/15/your_project_folder/login.php" class="no-underline">Выход</a>
    </button>
</div>

<main>
    <h2>Ученики класса</h2>
    <p>Выберите класс для просмотра списка учеников.</p>

    <!-- Выпадающий список для выбора класса -->
    <form method="GET" action="">
        <label for="class_select">Выберите класс:</label>
        <select id="class_select" name="class_id" class="select-class" onchange="this.form.submit()">
            <option value="">-- Выберите класс --</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?php echo htmlspecialchars($class['id_класса']); ?>"
                    <?php if ($selected_class_id == $class['id_класса']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($class['название']); ?>
        </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Отображение таблицы учеников, если выбран класс -->
   <?php if ($selected_class_id): ?>
    <div class="header-section">
        <h3>Ученики класса:</h3>
        <span class="student-count">
            <?php echo count($students_data); ?> учеников
        </span>
    </div>
    <?php if (!empty($students_data)): ?>
        <table class="students-table">
            <thead>
                <tr>
                    <th>ФИО/Логин</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students_data as $student): ?>
                    <tr>
                <td>
                    <?php
            $display_name = !empty($student['фио'])
                ? $student['фио']
                : $student['логин'];
            echo htmlspecialchars($display_name);
            ?>
        </td>
            </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">В выбранном классе пока нет учеников.</div>
    <?php endif; ?>
<?php endif; ?>
</main>

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
