<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connect.php';

// Получение ID обучающегося из GET-параметра или POST
$student_id = $user_id;

// Функция для безопасной обработки входных данных
function sanitizeInput($input)
{
    if ($input === null) {
        return '';
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Обработка изменений
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $student_id > 0) {
    // Безопасное получение и обработка данных
    $new_fio = sanitizeInput(filter_input(INPUT_POST, 'фио'));
    $new_login = sanitizeInput(filter_input(INPUT_POST, 'логин'));
    $new_password = sanitizeInput(filter_input(INPUT_POST, 'пароль'));

    if (!empty($new_fio) && !empty($new_login)) {
        try {
            // Если пароль не пустой, обновляем его (иначе оставляем старый)
            if (!empty($new_password)) {
                $sql = "UPDATE Обучающиеся SET фио=?, логин=?, пароль=? WHERE id_студента=?";
                $params = [$new_fio, $new_login, $new_password, $student_id];
            } else {
                $sql = "UPDATE Обучающиеся SET фио=?, логин=? WHERE id_студента=?";
                $params = [$new_fio, $new_login, $student_id];
            }

            $result = sqlsrv_query($link, $sql, $params);

            if ($result === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $success_message = 'Данные обучающегося успешно обновлены.';
        } catch (Exception $e) {
            $error_message = 'Ошибка при изменении данных: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Заполните обязательные поля (ФИО и логин).';
    }
}

// Получаем текущие данные обучающегося
$current_data = null;
if ($student_id > 0) {
    $sql = "SELECT * FROM Обучающиеся WHERE id_студента = ?";
    $params = [$student_id];
    $stmt = sqlsrv_query($link, $sql, $params);

    if ($stmt !== false) {
        $current_data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>

    <meta charset="UTF-8">
    <title>Редактирование ФИО пользователя</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<header>
    <div class="nav-bar">
        <span>Добро пожаловать, Ученик!</span>
        <a href="http://localhost/переделанная/15/your_project_folder/student/lesson_Html.php">Выход</a>

    </div>
</header>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Редактирование ФИО пользователя</title>
    <style>
        main {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 300px;
            min-width: 300px;
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
            font-weight: bold;
            color: #ff7576;
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

        .no-underline {
            text-decoration: none;
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
    </style>
</head>

<body class="container">
    <div>
        <main>

            <h1>Редактирование данных обучающегося</h1>
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>




            <?php if ($student_id <= 0): ?>
                <div class="error-message">Не указан ID обучающегося.</div>
            <?php elseif ($current_data === null): ?>
                <div class="error-message">Обучающийся с ID <?php echo $student_id; ?> не найден.</div>
            <?php else: ?>
                <form method="post" action="">
                    <input type="hidden" name="id_студента" value="<?php echo $current_data['id_студента']; ?>">

                    <div class="form-group">
                        <label for="fio">ФИО:</label>
                        <input type="text" id="fio" name="фио" value="<?php echo htmlspecialchars($current_data['фио']); ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="login">Логин:</label>
                        <input type="text" id="login" name="логин"
                            value="<?php echo htmlspecialchars($current_data['логин']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Новый пароль (оставьте пустым, если не меняете):</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" autocomplete="new-password">
                            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()">
                                👁️
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn">Сохранить изменения</button>
                </form>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="lessen_html2.php">
                        <button type="submit" class="btn"> Отмена</button>
                    </a>
                </div>

            <?php endif; ?>



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
        document.getElementById('openBtn').addEventListener('click', function () {
            document.getElementById('mySidebar').classList.remove('closed');
        });

        document.getElementById('closeBtn').addEventListener('click', function () {
            document.getElementById('mySidebar').classList.add('closed');
        });
    </script>
</body>

</html>

</html>