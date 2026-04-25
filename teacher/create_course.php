<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';

define('ROOT_PATH', __DIR__ . '/');

$error_message = '';
$success_message = '';

// Получаем сообщения из сессии
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bt'])) {
    $course_name = trim($_POST['course_name']);
    $course_description = trim($_POST['course_description']);

    if (empty($course_name) || empty($course_description)) {
        $_SESSION['error_message'] = "Пожалуйста, заполните все поля.";
    } else {
        $sql_create_course = "INSERT INTO Курсы (название, описание) VALUES (?, ?)";
        $params_create_course = [$course_name, $course_description];

        $stmt_create_course = sqlsrv_prepare($link, $sql_create_course, $params_create_course);
        if ($stmt_create_course === false) {
            log_sqlsrv_errors("Подготовка запроса создания курса");
            $_SESSION['error_message'] = "Ошибка сервера при создании курса.";
        } else {
            if (sqlsrv_execute($stmt_create_course)) {
                $_SESSION['success_message'] = "Курс создан успешно!";
            } else {
                log_sqlsrv_errors("Выполнение запроса создания курса");
                $_SESSION['error_message'] = "Ошибка сервера при создании курса.";
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание курса</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Создание курса</span>
        </div>
    </header>

    <div id="mySidebar" class="sidebar closed">
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>
        <a href="../teacher/asset_srt.html">Главная</a>
        <a href="../teacher/UrokiPlus.php">Уроки</a>
        <a href="../teacher/ProgressSt.php">Прогресс</a>
        <a href="../teacher/report_settings.php">Отчёты</a>
        <hr style="border-color: #4a637a; margin: 10px 20px;">
        <button class="Regis-btn">
            <a href="../Login.php" class="no-underline">Выход</a>
        </button>
    </div>

    <main>
        <section class="form-section">
            <div class="card">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <h1>Создание курса</h1>
                <form method="post" action="create_course.php">
                    <div class="form-group">
                        <label for="course_name">Название курса:</label>
                        <input type="text" id="course_name" name="course_name" required>
                    </div>
            <div class="form-group">
                <label for="course_description">Описание курса:</label>
                <textarea id="course_description" name="course_description" required></textarea>
            </div>
            <button type="submit" name="create_bt" class="btn save-btn">Создать курс</button>
        </form>
    </div>
</section>
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

    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
            closeNav();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeNav();
        }
    });
</script>
</body>
</html>
