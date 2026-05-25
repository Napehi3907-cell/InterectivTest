<?php
// Registr.php

// Начало сессии
session_start();

// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';

define('ROOT_PATH', __DIR__ . '/');

$error_message = '';
$success_message = '';
$login = '';
$password = '';

// Загрузка списка классов из базы данных
$classes = [];
$sql_get_classes = "SELECT id_класса, название FROM Класс ORDER BY id_класса";
$stmt_get_classes = sqlsrv_prepare($link, $sql_get_classes);
if ($stmt_get_classes === false) {
    log_sqlsrv_errors("Подготовка запроса получения классов");
} else {
    if (sqlsrv_execute($stmt_get_classes)) {
        while ($row = sqlsrv_fetch_array($stmt_get_classes, SQLSRV_FETCH_ASSOC)) {
            $classes[] = $row;
        }
    } else {
        log_sqlsrv_errors("Выполнение запроса получения классов");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая кнопка была нажата
    if (isset($_POST['login_Reubctr_student'])) {
        $role = 'ученик';
        $table = 'Обучающиеся';
        $id_field = 'id_студента';

        // Получаем ID класса для ученика
        $class_id = $_POST['class_id'] ?? null;

        // Проверяем выбор класса
        if (empty($class_id)) {
            $error_message = "Для регистрации как ученик необходимо выбрать класс.";
        }
    } elseif (isset($_POST['login_Reubctr_teacher'])) {
        $role = 'препод';
        $table = 'Преподаватели';
        $id_field = 'id_преподавателя';
        $class_id = null; // Для учителей класс не нужен
    } else {
        $error_message = "Неизвестная роль пользователя.";
    }

    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    if (empty($login) || empty($password)) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        // Проверяем, существует ли пользователь с таким логином в соответствующей таблице
        $sql_check_user = "SELECT $id_field FROM $table WHERE логин = ?";
        $params_check_user = [$login];

        $stmt_check_user = sqlsrv_prepare($link, $sql_check_user, $params_check_user);
        if ($stmt_check_user === false) {
            log_sqlsrv_errors("Подготовка запроса проверки пользователя");
            $error_message = "Ошибка сервера при проверке пользователя.";
        } else {
            if (sqlsrv_execute($stmt_check_user)) {
                $existing_user = sqlsrv_fetch_array($stmt_check_user, SQLSRV_FETCH_ASSOC);
                if ($existing_user) {
                    $error_message = "Пользователь с таким логином уже существует.";
                } else {
                    // Регистрируем нового пользователя в соответствующей таблице
                    if ($role === 'ученик') {
                        // Для обучающихся — добавляем поле id_класса
                        $sql_register_user = "INSERT INTO $table (логин, пароль, id_класса) VALUES (?, ?, ?)";
                        $params_register_user = [$login, $password, $class_id];
                    } else {
                        // Для преподавателей — добавляем фио (можно расширить при необходимости)
                        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : 'Не указано';
                        $sql_register_user = "INSERT INTO $table (логин, пароль, фио) VALUES (?, ?, ?)";
                        $params_register_user = [$login, $password, $full_name];
                    }

                    $stmt_register_user = sqlsrv_prepare($link, $sql_register_user, $params_register_user);
                    if ($stmt_register_user === false) {
                        log_sqlsrv_errors("Подготовка запроса регистрации пользователя");
                        $error_message = "Ошибка сервера при регистрации пользователя.";
                    } else {
                        if (sqlsrv_execute($stmt_register_user)) {
                            $success_message = "Регистрация прошла успешно! Теперь вы можете войти.";
                            header("Location: http://localhost/передаланная/15/your_project_folder/login.php");
                            exit;
                        } else {
                            log_sqlsrv_errors("Выполнение запроса регистрации пользователя");
                            $error_message = "Ошибка сервера при регистрации пользователя.";
                        }
                    }
                }
            } else {
                log_sqlsrv_errors("Выполнение запроса проверки пользователя");
                $error_message = "Ошибка сервера при проверке пользователя.";
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

        .Fond {
            color: rgb(21, 76, 95)
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
        input[type="password"],
        select {
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
            padding: 7px 7px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        .student-btn {
            background-color: #4CAF50;
            color: white;
        }

        .teacher-btn {
            background-color: #2196F3;
            color: white;
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
            transform: scale(var(--s, 1)) perspective(600px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
            perspective: 600px;
            transition: transform 0.1s;
        }

        .rainbow-hover:active {
            transition: 0.3s;
            transform: scale(0.93);
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }

        .no-underline {
            text-decoration: none;
            background: linear-gradient(90deg,
                    #866ee7,
                    #ea60da,
                    #ed8f57,
                    #fbd41d,
                    #2cca91);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }
    </style>
</head>

<body class="Fond">
    <div class="login-container">
        <h2>Регистрация</h2>
        <form method="post" action="Registr.php">
            <!-- ... поля логина и пароля без изменений ... -->

            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" value="" required>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <!-- Выпадающий список выбора класса (только для ученика) -->
            <div class="form-group" id="class-selection" style="display: none;">
                <label for="class_id">Класс:</label>
                <select id="class_id" name="class_id">
                    <option value="">-- Выберите класс --</option>
                    <?php
                    if (!empty($classes)) {
                        foreach ($classes as $class) {
                            echo '<option value="' . htmlspecialchars($class['id_класса']) . '">' .
                                htmlspecialchars($class['название']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="button-group">
                <!-- Кнопка только для показа списка -->
                <button type="button" class="student-btn" onclick="showClassSelection()">
                    Показать выбор класса
                </button>
                <!-- Отдельная кнопка для отправки (видна после показа списка) -->
                <div id="submit-student" style="display: none;">
                    <button type="submit" name="login_Reubctr_student" class="student-btn">
                        Зарегистрироваться как ученик
                    </button>
                </div>
                <button type="submit" name="login_Reubctr_teacher" class="teacher-btn">
                    Зарегистрироваться как учитель
                </button>
            </div>

            <div class="nav-bar">
                <button name="login_as_regist" class="Regis-btn">
                    <a href="http://localhost/переделанная/15/your_project_folder/login.php" class="no-underline">
                        Выход
                    </a>
                </button>
            </div>
        </form>
    </div>

    <script>
        function showClassSelection() {
            document.getElementById('class-selection').style.display = 'block';
            document.getElementById('submit-student').style.display = 'block';
        }
    </script>
</body>

</html>