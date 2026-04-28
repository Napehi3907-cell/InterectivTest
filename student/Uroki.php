<?php
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

$lesson_title = "Выбор урока";
$test_link = null;
$result_message = '';

if (isset($_GET['id_урока'])) {
    $lesson_id = (int)$_GET['id_урока'];

    // Получаем данные урока
    $sql_lesson = "SELECT название, контент FROM Уроки WHERE id_урока = ?";
    $stmt_lesson = sqlsrv_prepare($link, $sql_lesson, array($lesson_id));

    if (sqlsrv_execute($stmt_lesson)) {
        $lesson = sqlsrv_fetch_array($stmt_lesson, SQLSRV_FETCH_ASSOC);
        if ($lesson) {
            $lesson_title = htmlspecialchars($lesson['название']);
        }
    }

    // Получаем ссылку на тест для этого урока
    $sql_test = "SELECT ссылка FROM TestUr WHERE id_урока = ?";
    $stmt_test = sqlsrv_prepare($link, $sql_test, array($lesson_id));

    if (sqlsrv_execute($stmt_test)) {
        $test = sqlsrv_fetch_array($stmt_test, SQLSRV_FETCH_ASSOC);
        if ($test) {
            $test_link = $test['ссылка'];
        }
    }
}

// Обработка POST‑запроса для обновления статуса урока
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id_урока'])) {
        $lesson_id = (int)$_POST['id_урока'];

        // Обновляем статус урока
        $sql_update = "UPDATE Уроки SET статус = 1 WHERE id_урока = ?";
        $stmt_update = sqlsrv_prepare($link, $sql_update, array($lesson_id));

        if (sqlsrv_execute($stmt_update)) {
            $result_message = "Статус урока успешно изменён!";

            // Если есть тест, перенаправляем на него
            if ($test_link) {
                header("Location: " . htmlspecialchars($test_link));
                exit;
            }
        } else {
            $result_message = "Ошибка: " . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lesson_title) ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="container">
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать, Ученик!</span>
            <a href="logout.php">Выход</a>
        </div>
    </header>

    <main>
        <style>
            .btn-container {
                text-align: center;
                margin: 20px 0;
            }
            .status-btn {
                padding: 20px 40px;
                font-size: 18px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            .status-btn:hover {
                background-color: #45a049;
            }
            .message {
                margin-top: 20px;
                padding: 10px;
                border-radius: 4px;
                text-align: center;
            }
            .alert-success {
                background-color: #d4edda;
                color: #155724;
            }
            .alert-error {
                background-color: #f8d7da;
                color: #721c24;
            }
            .lesson-content {
                margin: 20px 0;
                line-height: 1.6;
            }
        </style>

        <?php
        if (isset($_GET['id_урока'])) {
            if ($lesson) {
                echo '<h1>' . htmlspecialchars($lesson['название']) . '</h1>';
                echo '<div class="lesson-content">';
                echo '<p>' . nl2br(htmlspecialchars($lesson['контент'])) . '</p>';
                echo '</div>';

                // Показываем кнопку завершения урока и перехода к тесту
                if ($test_link) {
                    echo '<div class="btn-container">';
            echo '<form method="post">';
            echo '<input type="hidden" name="id_урока" value="' . $lesson_id . '">';
            echo '<button type="submit" class="status-btn">Завершить урок и перейти к тесту</button>';
            echo '</form>';
            echo '</div>';
                } else {
                    echo '<div class="alert alert-warning">Для этого урока тест не предусмотрен</div>';
                }
            } else {
                echo '<div class="alert alert-info">Урок не найден</div>';
            }
        } else {
            echo '<div class="alert alert-warning">Выберите урок из списка</div>';
        }

        // Показываем сообщение о результате
        if (!empty($result_message)) {
            $alert_class = strpos($result_message, 'Ошибка') !== false ? 'alert-error' : 'alert-success';
            echo '<div class="message ' . $alert_class . '">' . htmlspecialchars($result_message) . '</div>';
        }
        ?>
    </main>
</body>
</html>
