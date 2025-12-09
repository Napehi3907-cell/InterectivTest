

<?php
// add_lesson.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

// Определяем корень приложения
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

// Инициализация переменных
$error_message = '';
$success_message = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка добавления урока
    $course_id = trim($_POST['course_id']);
    $lesson_name = trim($_POST['lesson_name']);
    $lesson_content = trim($_POST['lesson_content']);

    if (empty($course_id) || empty($lesson_name) || empty($lesson_content)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // SQL-запрос для добавления урока
        $sql_add_lesson = "INSERT INTO Уроки (id_курса, название, контент) VALUES (?, ?, ?)";
        $params_add_lesson = [$course_id, $lesson_name, $lesson_content];

        $stmt_add_lesson = sqlsrv_prepare($link, $sql_add_lesson, $params_add_lesson);
        if ($stmt_add_lesson === false) {
            log_sqlsrv_errors("Подготовка запроса добавления урока");
            $error_message = "Ошибка сервера при добавлении урока.";
        } else {
            if (sqlsrv_execute($stmt_add_lesson)) {
                $success_message = "Урок добавлен успешно!";
            } else {
                log_sqlsrv_errors("Выполнение запроса добавления урока");
                $error_message = "Ошибка сервера при добавлении урока.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление урока</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        main {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
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
        .btn:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-top: 10px;
        }
         .Regis-btn {
        font-size: 16px;
  font-weight: 700;
  color: #ff7576;
  background-color: #35416d;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 12px 24px;
  position: relative;
  line-height: 24px;
  border-radius: 9px;
  box-shadow: 0px 1px 2px #333b58,
    0px 4px 16px #363c55;
  transform-style: preserve-3d;
  transform: scale(var(--s, 1)) perspective(600px)
    rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
  perspective: 600px;
  transition: transform 0.1s;
        }
          .no-underline { text-decoration: none;
            background: linear-gradient(
      90deg,
      #866ee7,
      #ea60da,
      #ed8f57,
      #fbd41d,
      #2cca91
    );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: block;
            

}
.rainbow-hover:active {
  transition: 0.3s;
  transform: scale(0.93);
}
    </style>
</head>
<body>
    <main>
        <form method="post" action="Urok.php">
            <h1>Добавление урока</h1>
            <div class="form-group">
                <label for="course_id">ID курса:</label>
                <input type="text" id="course_id" name="course_id" required>
            </div>
            <div class="form-group">
                <label for="lesson_name">Название урока:</label>
                <input type="text" id="lesson_name" name="lesson_name" required>
            </div>
            <div class="form-group">
                <label for="lesson_content">Содержание урока:</label>
                <textarea id="lesson_content" name="lesson_content" required></textarea>
            </div>
            <button type="submit" name="add_bt" class="btn">Добавление урока</button>
             <button  name="login_as_regist" class="Regis-btn">
                  <a Href="http://localhost/15/your_project_folder/teacher/reposts_Html.php" class="no-underline">
                     Выход
                  </a> 
                </button>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>