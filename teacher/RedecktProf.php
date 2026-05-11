<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];

$teacher_id = $user_id;
require_once '../includes/db_connect.php';

// Получаем текущие данные преподавателя
$sql_get_data = "SELECT фио, почта, номер_телефона, логин FROM Преподаватели WHERE id_преподавателя = ?";
$params_get = [$teacher_id];
$stmt_get = sqlsrv_query($link, $sql_get_data, $params_get);

$teacher_data = [];
if ($stmt_get !== false) {
    $row = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $teacher_data = $row;
    }
}

// Переменные для хранения сообщений
$message = '';
$message_type = ''; // 'success' или 'error'

// Обработка редактирования данных преподавателя
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получаем и санируем данные
    $fio = trim(filter_input(INPUT_POST, 'fio', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
    $login = trim(filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING));

    // Проверяем, что все поля заполнены
    if ($fio && $email && $phone && $login) {
        try {
            // Если пароль введён, хешируем его для безопасности
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // SQL‑запрос с обновлением пароля
                $sql = "UPDATE Преподаватели SET фио = ?, почта = ?, номер_телефона = ?, логин = ?, пароль = ? WHERE id_преподавателя = ?";
                $params = [$fio, $email, $phone, $login, $hashed_password, $teacher_id];
            } else {
                // Если пароль не введён, обновляем только остальные поля
                $sql = "UPDATE Преподаватели SET фио = ?, почта = ?, номер_телефона = ?, логин = ? WHERE id_преподавателя = ?";
                $params = [$fio, $email, $phone, $login, $teacher_id];
            }

            $result = sqlsrv_query($link, $sql, $params);

            if ($result === false) {
                throw new Exception('Ошибка выполнения запроса: ' . print_r(sqlsrv_errors(), true));
            }

            $message = 'Данные преподавателя успешно обновлены.';
            $message_type = 'success';
            // Обновляем данные в массиве после успешного сохранения
            $teacher_data['фио'] = $fio;
            $teacher_data['почта'] = $email;
            $teacher_data['номер_телефона'] = $phone;
            $teacher_data['логин'] = $login;
        } catch (Exception $e) {
            $message = 'Ошибка при обновлении данных: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Заполните все обязательные поля корректно.';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование данных преподавателя</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        main {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 20px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        input:focus {
            outline: 2px solid #4CAF50;
            border-color: #4CAF50;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body class="container">
    <header>
        <div class="nav-bar">
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Редактирование данных преподавателя</span>
        </div>
    </header>

    <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

        <!-- Пункты меню -->
        <a href="../teacher/asset_srt.php">Главная</a>
        <a href="../teacher/UrokiPlus.php">Уроки</a>
        <a href="../teacher/ProgressSt.php">Прогресс</a>
        <a href="../teacher/report_settings.php">Отчёты</a>

        <hr style="border-color: #4a637a; margin: 10px 20px;">

        <!-- Кнопка выхода -->
        <button class="Regis-btn">
            <a href="../login.php" class="no-underline">Выход</a>
        </button>
    </div>

    <div>
        <main>
            <h1>Редактирование данных преподавателя</h1>

            <!-- Вывод сообщений об успехе или ошибке -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>            <?php endif; ?>


            <form method="POST" action="">
                <div class="form-group">
                    <label for="fio">ФИО:</label>
                    <input type="text"
                   id="fio"
                   name="fio"
                   value="<?php echo htmlspecialchars($teacher_data['фио'] ?? ''); ?>"
                   required>
                </div>

                <div class="form-group">
                    <label for="email">Электронная почта:</label>
                    <input type="email"
                   id="email"
                   name="email"
                   value="<?php echo htmlspecialchars($teacher_data['почта'] ?? ''); ?>"
                   required>
                </div>

                <div class="form-group">
                    <label for="phone">Номер телефона:</label>
                    <input type="tel"
                   id="phone"
                   name="phone"
                   value="<?php echo htmlspecialchars($teacher_data['номер_телефона'] ?? ''); ?>"
                   required>
                </div>

                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text"
                   id="login"
                   name="login"
                   value="<?php echo htmlspecialchars($teacher_data['логин'] ?? ''); ?>"
                   required>
                </div>

                <div class="form-group">
                    <label for="password">Новый пароль (оставьте пустым, если не меняете):</label>
                    <div class="password-input-wrapper">
                        <input type="password"
                       id="password"
                       name="password"
                       autocomplete="new-password">
                        <button type="button"
                        class="password-toggle-btn"
                        onclick="togglePasswordVisibility()">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Сохранить изменения</button>
            </form>
        </main>
    </div>

    <script>
        // Функция для переключения видимости пароля
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }

        // Обработка открытия/закрытия сайдбара
        document.getElementById('openBtn').addEventListener('click', function() {
            document.getElementById('mySidebar').classList.remove('closed');
        });

        document.getElementById('closeBtn').addEventListener('click', function() {
            document.getElementById('mySidebar').classList.add('closed');
        });
    </script>
</body>
</html>
