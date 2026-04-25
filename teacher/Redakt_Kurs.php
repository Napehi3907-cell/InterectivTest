<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';

define('ROOT_PATH', __DIR__ . '/');

$error_message = '';
$success_message = '';
$courses = [];

// Получаем список курсов для выпадающего списка
$sql_courses = "SELECT id_курса, название FROM Курсы ORDER BY название";
$stmt_courses = sqlsrv_query($link, $sql_courses);
while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
    $courses[] = $row;
}

// Получаем сообщения из сессии
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['up_bt'])) {
    $course_id = (int)$_POST['course_id'];
    $new_course_name = trim($_POST['new_course_name']);
    $new_course_description = trim($_POST['new_course_description']);

    if (empty($course_id) || empty($new_course_name) || empty($new_course_description)) {
        $_SESSION['error_message'] = "Пожалуйста, заполните все поля.";
    } else {
        $sql_update_course = "UPDATE Курсы SET название = ?, описание = ? WHERE id_курса = ?";
        $params_update_course = [$new_course_name, $new_course_description, $course_id];

        $stmt_update_course = sqlsrv_prepare($link, $sql_update_course, $params_update_course);
        if ($stmt_update_course === false) {
            log_sqlsrv_errors("Подготовка запроса редактирования курса");
            $_SESSION['error_message'] = "Ошибка сервера при редактировании курса.";
        } else {
            if (sqlsrv_execute($stmt_update_course)) {
                $_SESSION['success_message'] = "Курс отредактирован успешно!";
            } else {
                log_sqlsrv_errors("Выполнение запроса редактирования курса");
                $_SESSION['error_message'] = "Ошибка сервера при редактировании курса.";
            }
        }
    }
     if (isset($stmt_update_course)) {
        sqlsrv_free_stmt($stmt_update_course);
    }

    // Перенаправляем на ту же страницу, чтобы избежать повторной отправки формы
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Функция для логирования ошибок SQL Server


// Закрываем соединение с базой данных в конце скрипта
register_shutdown_function(function() use ($link) {
    if ($link) {
        sqlsrv_close($link);
    }
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование курсов</title>
     <link rel="stylesheet" href="../css/style.css">
    <style>
    
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* === Основные настройки шрифтов и фона === */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
  width: 100%;
  height: 100%;
  background: linear-gradient(#3f87a6 10%, #ebf8e1a2 10%),
    linear-gradient(to right, #ebf8e100 10%, #c73030 10% 10.2%, #ebf8e100 10.5%);
  background-size: 100% 25px, 100% 100%;
  background-repeat: repeat;
  /* Add your background pattern here */
}


        /* === Sidebar (левая панель) === */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            background-color: #34495e;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 15px;
        }

        /* Закрытость Sidebar на небольших экранах */
        .sidebar.closed {
            transform: translateX(-250px);
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #ecf0f1;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #4a637a;
        }

        /* Крестик закрытия Sidebar */
        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 25px;
            font-size: 36px;
            cursor: pointer;
            color: #ecf0f1;
            line-height: 30px;
        }

        /* === Шапка (Header) === */
        header {
            background-color: #2c3e50;
            padding: 10px 20px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Кнопка-гамбургер */
        .openbtn {
            font-size: 22px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            padding: 5px 10px;
            transition: 0.3s;
            width: 35%;
        }

        .openbtn:hover {
            background-color: #34495e;
        }

        /* === Основной контент === */
        .ma {
            transition: margin-left 0.5s;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(116, 86, 86, 0.1);
            max-width: 477px;
            min-width: 300px;
            height: 400px auto;
            margin: 30px auto;
        }

        /* === Форма добавления урока === */
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
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Дополнительные стили для кнопок и сообщений */
        .Regis-btn {
            font-size: 16px;
            font-weight: bold;
            background-color: #35416d;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 12px 24px;
            position: relative;
            line-height: 24px;
            border-radius: 9px;
            box-shadow: 0px 1px 2px #333b58, 0px 4px 16px #363c55;
            transform-style: preserve-3d;
            transform: scale(var(--s, 1)) perspective(600px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
            perspective: 600px;
            transition: transform 0.1s;
        }

        .Regis-btn:hover {
            --s: 1.05;
        }

        .Regis-btn:active {
            transform: translateY(2px);
        }

        .no-underline {
            text-decoration: none;
            background: linear-gradient(90deg, #866ee7, #ea60da, #ed8f57, #fbd41d, #2cca91);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }

        .rainbow-hover:active {
            transition: 0.3s;
            transform: scale(0.93);
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
    <div class = "ma">

    
    <h1>Редактирование учебных курсов</h1>

    <!-- Вывод сообщений об ошибках или успехе -->
    <?php if ($error_message): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="form-group">
            <label for="course_id">Выберите курс для редактирования:</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Выберите курс --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo (int)$course['id_курса']; ?>">
                <?php echo htmlspecialchars($course['название']); ?>
            </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="new_course_name">Новое название курса:</label>
            <input type="text" id="new_course_name" name="new_course_name" required>
        </div>

        <div class="form-group">
            <label for="new_course_description">Новое описание курса:</label>
            <textarea id="new_course_description" name="new_course_description" required></textarea>
        </div>

        <button type="submit" name="up_bt">Обновить курс</button>
    </form>
</div>
    
</body>
</html>