<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

$lesson_title = "Выбор урока";
$test_link = null;
$interactive_link = null;
$result_message = '';

if (isset($_GET['id_урока'])) {
    $lesson_id = (int)$_GET['id_урока'];

    // Получаем данные урока
    $sql_lesson = "SELECT название, контент1, контент2, контент3, контент4, картинка FROM Уроки WHERE id_урока = ?";
    $stmt_lesson = sqlsrv_prepare($link, $sql_lesson, array($lesson_id));

    if (sqlsrv_execute($stmt_lesson)) {
        $lesson = sqlsrv_fetch_array($stmt_lesson, SQLSRV_FETCH_ASSOC);
        if ($lesson) {
            $lesson_title = htmlspecialchars($lesson['название']);
        }
    }

    // Получаем ссылку на тест для этого урока
    $sql_test = "SELECT ссылка, ссылка_интерактив FROM TestUr WHERE id_урока = ?";
$stmt_test = sqlsrv_prepare($link, $sql_test, array($lesson_id));

if (sqlsrv_execute($stmt_test)) {
    $test = sqlsrv_fetch_array($stmt_test, SQLSRV_FETCH_ASSOC);
    if ($test) {
        $test_link = $test['ссылка'] ?? null;
        $interactive_link = $test['ссылка_интерактив'] ?? null;
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
            <span>Добро пожаловать, <?= htmlspecialchars($user_name) ?>!</span>
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
            .lesson-section {
                margin-bottom: 25px;
            }
            .lesson-section h3 {
                color: #2c3e50;
                border-bottom: 2px solid #3498db;
                padding-bottom: 5px;
            }
            .lesson-image {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
                margin: 15px 0;
                display: block;
            }
              /* Стили для контента урока */
            .lesson-content {
                margin: 20px 0;
                line-height: 1.6;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
            }
            .lesson-section {
                margin-bottom: 25px;
            }
            .lesson-section h3 {
                color: #2c3e50;
                border-bottom: 2px solid #3498db;
                padding-bottom: 5px;
                margin-bottom: 15px;
            }
            .lesson-image {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
                margin: 15px auto;
                display: block;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            /* Размещение картинки в середине текста */
            .content-with-image {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                align-items: start;
            }
            .text-content {
                flex: 1;
                min-width: 300px;
            }
            .image-container {
                flex: 1;
                min-width: 100%;
         
                display: flex;
                justify-content: center;
                align-items: center;
            }
            /* Стили для текста */
            .lesson-text {
                font-size: 16px;
                text-align: justify;
                hyphens: auto;
            }
            .lesson-text p {
                margin-bottom: 1em;
            }
            .lesson-text ul, .lesson-text ol {
                margin-left: 20px;
                margin-bottom: 1em;
            }
            .lesson-text li {
                margin-bottom: 0.5em;
            }
            /* Адаптивность */
            @media (max-width: 768px) {
                .content-with-image {
                    flex-direction: column;
                }
                .image-container {
                    order: 1;
                }
                .text-content {
                    order: 2;
                }
            }
            .interactive-content {
    margin: 30px 0;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.interactive-content h3 {
    color: #007bff;
    margin-top: 0;
}

.iframe-container {
    width: 100%;
    overflow: hidden;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.iframe-container iframe {
    width: 100%;
    height: 400px;
    border: none;
}

@media (max-width: 768px) {
    .iframe-container iframe {
        height: 300px;
    }
}
        </style>

         <?php
        if (isset($_GET['id_урока'])) {
            if ($lesson) {
                echo '<h1>' . htmlspecialchars($lesson['название'] ?? '') . '</h1>';

                echo '<div class="lesson-content">';

                // Разделяем контент на части для размещения картинки посередине
                $sections = [
                    'контент1' => 'Введение',
            'контент2' => 'Основная часть',
            'контент3' => 'Примеры и задачи',
            'контент4' => 'Домашнее задание'
                ];

                $content_parts = [];
                foreach ($sections as $field => $title) {
                    if (!empty($lesson[$field])) {
                        $content_parts[] = [
                            'title' => $title,
                    'content' => $lesson[$field]
                ];
            }
        }

        $total_parts = count($content_parts);
        $image_position = floor($total_parts / 2); // Позиция для картинки — середина

        foreach ($content_parts as $index => $part) {
            // Показываем картинку перед средней частью контента
            if ($index == $image_position && !empty($lesson['картинка'])) {
                echo '<div class="content-with-image">';
                echo '<div class="text-content">';
            }

            if ($index != $image_position || empty($lesson['картинка'])) {
                  echo '<div class="lesson-section">';
                echo '<h3>' . htmlspecialchars($part['title']) . '</h3>';
                echo '<p class="lesson-text">' . nl2br(htmlspecialchars($part['content'])) . '</p>';
                echo '</div>';
            }

            // Закрываем контейнер с текстом, если это место для картинки
            if ($index == $image_position && !empty($lesson['картинка'])) {
                echo '</div>'; // закрываем .text-content

                // Добавляем контейнер с картинкой
                echo '<div class="image-container">';
                echo '<img src="' . htmlspecialchars($lesson['картинка']) . '" alt="Иллюстрация к уроку" class="lesson-image">';
                echo '</div>';
                echo '</div>'; // закрываем .content-with-image
            }
        }

        echo '</div>'; // закрываем .lesson-content

       if ($interactive_link) {
            echo '<div class="interactive-content">';
            echo '<h3>Интерактивное задание</h3>';
            echo '<p>Выполните интерактивное упражнение перед завершением урока:</p>';
            echo '<div class="iframe-container">';
            echo '<iframe style="max-width:100%" src="' . htmlspecialchars($interactive_link) . '" width="500" height="380" frameborder="0" allowfullscreen></iframe>';
            echo '</div>';
            echo '</div>';
        }

        // Кнопка завершения урока (всегда отображается)
        echo '<div class="btn-container">';
        echo '<form method="post">';
        echo '<input type="hidden" name="id_урока" value="' . $lesson_id . '">';

        if ($test_link) {
            echo '<button type="submit" class="status-btn">Завершить урок и перейти к тесту</button>';
        } else {
            echo '<button type="submit" class="status-btn">Завершить урок</button>';
        }

        echo '</form>';
        echo '</div>';

        // Информационное сообщение о тесте
        if ($test_link) {
            echo '<div class="alert alert-info">После завершения урока вы будете перенаправлены к тесту.</div>';
        } else {
            echo '<div class="alert alert-warning">Для этого урока тест не предусмотрен.</div>';
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
