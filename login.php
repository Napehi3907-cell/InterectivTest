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
    } elseif (isset($_POST['login_as_teacher'])) {
        $role = 'препод';
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
        // Запрос для проверки пользователя по таблице ПОЛЬЗОВАТЕЛЬ
        $sql_user = "SELECT id_поль, login, Password, Rol FROM PL WHERE login = ? AND Password = ? AND Rol = ?";
        $params_user = [$login, $password, $role];

        // Отладочные сообщения
        echo "SQL Query: " . htmlspecialchars($sql_user) . "<br>";
        echo "Parameters: " . print_r($params_user, true) . "<br>";

        // Использование prepare/execute для безопасности
        $stmt_user = sqlsrv_prepare($link, $sql_user, $params_user);
        if ($stmt_user === false) {
            echo "Ошибка подготовки запроса: " . print_r(sqlsrv_errors(), true) . "<br>";
            log_sqlsrv_errors("Подготовка запроса пользователя");
            $error_message = "Ошибка сервера при проверке пользователя.";
            $user = null;
        } else {
            if (sqlsrv_execute($stmt_user)) {
                // Получаем результат
                $user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
                if (!$user) {
                    echo "Пользователь не найден.<br>";
                    $error_message = "Неверный логин или пароль.";
                } else {
                    echo "Пользователь найден: " . print_r($user, true) . "<br>";
                }
            } else {
                echo "Ошибка выполнения запроса: " . print_r(sqlsrv_errors(), true) . "<br>";
                log_sqlsrv_errors("Выполнение запроса пользователя");
                $error_message = "Ошибка сервера при проверке пользователя.";
                $user = null;
            }
        }

        // Если пользователь найден
        if ($user) {
            // Проверяем, что ключ "роль" существует в массиве $user
            if (isset($user['Rol'])) {
                // Сохраняем данные в сессии
                $_SESSION['role'] = $user['Rol'];
                $_SESSION['user_id'] = $user['id_поль'];
                $_SESSION['login'] = $login;

                // Перенаправляем в зависимости от роли

                if ($user['Rol'] === 'препод') {
                    header("Location: http://localhost/15/your_project_folder/teacher/reposts_Html.php");
                    exit;
                } elseif ($user['Rol'] === 'ученик') {
                    header("Location: http://localhost/15/your_project_folder/student/lesson_Html.php");
                    exit;
                }
            } else {
                echo "Ключ 'Rol' не найден в массиве \$user.<br>";
                $error_message = "Ошибка сервера: недопустимая роль пользователя.";
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
        }

        .no-underline {
            text-decoration: none;
            text-color: #ffffffff;
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
                <?php
                //require "dashboard.php";
                ?>
                <button type="submit" name="login_as_student" class="student-btn">Войти как ученик</button>
                <button type="submit" name="login_as_teacher" class="teacher-btn">Войти как учитель</button>
            </div>
            <button onclick="" class="Registr-btn"><a href="http://localhost/15/your_project_folder/Registr.html"
                    class="no-underline">
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