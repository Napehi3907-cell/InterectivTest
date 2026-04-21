<?php
// Redakt_urok.php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

$error_message = '';
$success_message = '';
$lesson_id = $_GET['id_урока'] ?? null;
$lesson = null;

// Получаем данные урока для редактирования
if ($lesson_id) {
    $sql_get_lesson = "SELECT id_урока, id_курса, название, контент FROM Уроки WHERE id_урока = ?";
    $params_get_lesson = [$lesson_id];
    
    $stmt_get_lesson = sqlsrv_prepare($link, $sql_get_lesson, $params_get_lesson);
    if ($stmt_get_lesson !== false && sqlsrv_execute($stmt_get_lesson)) {
        $lesson = sqlsrv_fetch_array($stmt_get_lesson, SQLSRV_FETCH_ASSOC);
    } else {
        log_sqlsrv_errors("Получение данных урока для редактирования");
        $error_message = "Ошибка при получении данных урока.";
    }
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $lesson_id = trim($_POST['lesson_id']);
    $course_id = trim($_POST['course_id']);
    $lesson_name = trim($_POST['lesson_name']);
    $lesson_content = trim($_POST['lesson_content']);

    if (empty($course_id) || empty($lesson_name) || empty($lesson_content)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // SQL-запрос для обновления урока
        $sql_update_lesson = "UPDATE Уроки SET id_курса = ?, название = ?, контент = ? WHERE id_урока = ?";
        $params_update_lesson = [$course_id, $lesson_name, $lesson_content, $lesson_id];

        $stmt_update_lesson = sqlsrv_prepare($link, $sql_update_lesson, $params_update_lesson);
        if ($stmt_update_lesson === false) {
            log_sqlsrv_errors("Подготовка запроса обновления урока");
            $error_message = "Ошибка сервера при обновлении урока.";
        } else {
            if (sqlsrv_execute($stmt_update_lesson)) {
                $success_message = "Урок успешно обновлён!";
                // Обновляем данные в переменной
                $lesson = [
                    'id_урока' => $lesson_id,
            'id_курса' => $course_id,
            'название' => $lesson_name,
            'контент' => $lesson_content
        ];
            } else {
                log_sqlsrv_errors("Выполнение запроса обновления урока");
                $error_message = "Ошибка сервера при обновлении урока.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    
    <meta charset="UTF-8">
    <title>Редактирование урока</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Стили аналогичны add_lesson.php */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
        .success-message {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <header>
        <div class="nav-bar">
            <span>Просмотр прогресса учеников</span>
              <button class="openbtn" id="openBtn">☰ Меню</button>
            <a href="../Login.php">Выход</a>
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
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/login.php" class="no-underline">Выход</a>
    </button>
</div>
    <div class="container">
        <h1>Редактирование урока</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($lesson): ?>
            <form method="post" action="Redakt_urok.php?id_урока=<?php echo $lesson['id_урока']; ?>">
                <input type="hidden" name="lesson_id" value="<?php echo $lesson['id_урока']; ?>">

                <div class="form-group">
                    <label for="course_id">ID курса:</label>
                    <input type="text" id="course_id" name="course_id"
                   value="<?php echo htmlspecialchars($lesson['id_курса']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="lesson_name">Название урока:</label>
            <input type="text" id="lesson_name" name="lesson_name"
                   value="<?php echo htmlspecialchars($lesson['название']); ?>" required>
                </div>

                <div class="form-group">
            <label for="lesson_content">Содержание урока:</label>
            <textarea id="lesson_content" name="lesson_content" rows="6" required><?php echo htmlspecialchars($lesson['контент']); ?></textarea>
                </div>

                <button type="submit" name="update_lesson">Обновить урок</button>
            </form>
        <?php else: ?>
            <p>Урок не найден.</p>
        <?php endif; ?>
    </div>
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
