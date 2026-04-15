<?php
// login.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once 'includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

// Определяем корень приложения
define('ROOT_PATH', __DIR__ . '/');

// Инициализация переменных
$error_message = '';
$login = '';
$password = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая кнопка была нажата
    if (isset($_POST['login_as_student'])) {
        $role = 'ученик';
        $table = 'Обучающиеся';
        $id_field = 'id_студента';
    } elseif (isset($_POST['login_as_teacher'])) {
        $role = 'препод';
        $table = 'Преподаватели';
        $id_field = 'id_преподавателя';
    } else {
        $error_message = "Неизвестная роль пользователя.";
    }

    // Получаем данные из формы
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    // Валидация данных
    if (empty($login) || empty($password)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // Запрос для проверки пользователя в соответствующей таблице
        $sql_user = "SELECT $id_field, фио, логин, пароль FROM $table WHERE логин = ? AND пароль = ?";
        $params_user = [$login, $password];

        // Использование prepare/execute для безопасности
        $stmt_user = sqlsrv_prepare($link, $sql_user, $params_user);
        if ($stmt_user === false) {
            log_sqlsrv_errors("Подготовка запроса пользователя");
            $error_message = "Ошибка сервера при проверке пользователя.";
        } else {
            if (sqlsrv_execute($stmt_user)) {
                // Получаем результат
                $user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
                if (!$user) {
                    $error_message = "Неверный логин или пароль.";
                }
            } else {
                log_sqlsrv_errors("Выполнение запроса пользователя");
                $error_message = "Ошибка сервера при проверке пользователя.";
            }
        }

        // Если пользователь найден
        if ($user) {
            // Сохраняем данные в сессии
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['login'] = $login;
            $_SESSION['full_name'] = $user['фио'];

            // Перенаправляем в зависимости от роли
            if ($role === 'препод') {
                header("Location: http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.html");
                exit;
            } elseif ($role === 'ученик') {
                header("Location: http://localhost/переделанная/15/your_project_folder/student/lesson_Html2.php");
                exit;
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
    <title>Авторизация</title>
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

        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
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
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .student-btn {
            background-color: #4CAF50;
            color: white;
        }

        .teacher-btn {
            background-color: #2196F3;
            color: white;
        }

        .Registr-btn {
            background-color: #e4f830ff;
            text-align: center;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-top: 15px;
        }

        .no-underline {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 10px 0;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Авторизация</h2>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="button-group">
                <button type="submit" name="login_as_student" class="student-btn">
                    Войти как ученик
                </button>
                <button type="submit" name="login_as_teacher" class="teacher-btn">Войти как учитель</button>
            </div>
            <button class="Registr-btn">
                <a href="http://localhost/15/your_project_folder/Registr.html" class="no-underline">
                    Регистрация
                </a>
            </button>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
