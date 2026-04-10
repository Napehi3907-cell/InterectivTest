<?php
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный


$lesson_title = "Выбор урока";
$video_url = null;
$result_message = "";

if (isset($_GET['id_урока'])) {
    $lesson_id = (int)$_GET['id_урока'];
    
    // Проверка корректности ID
    if ($lesson_id <= 0) {
        $result_message = "Некорректный ID урока.";
    } else {
        // Запрос для получения данных урока
        $sql = "SELECT название, описание FROM Видео_Уроки WHERE id_урока = ?";
        $stmt = sqlsrv_prepare($link, $sql, array($lesson_id));
        
        if ($stmt === false) {
            die("Ошибка подготовки запроса урока: " . print_r(sqlsrv_errors(), true));
        }
        if (sqlsrv_execute($stmt)) {
            $lesson = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($lesson) {
                $lesson_title = htmlspecialchars($lesson['название']);
            } else {
                $result_message = "Урок не найден.";
            }
        } else {
            $result_message = "Ошибка выполнения запроса урока: " . print_r(sqlsrv_errors(), true);
        }

        // Запрос для получения ссылки на видео
        $sql_video = "SELECT Ссылка FROM Видео_Уроки WHERE id_урока = ?";
        $stmt_video = sqlsrv_prepare($link, $sql_video, array($lesson_id));
        if ($stmt_video === false) {
            error_log("Ошибка подготовки запроса видео: " . print_r(sqlsrv_errors(), true));
        } else {
            if (sqlsrv_execute($stmt_video)) {
                $video = sqlsrv_fetch_array($stmt_video, SQLSRV_FETCH_ASSOC);
                if ($video && !empty($video['Ссылка'])) {
                    $video_url = htmlspecialchars($video['Ссылка']);
                }
            } else {
                error_log("Ошибка выполнения запроса видео: " . print_r(sqlsrv_errors(), true));
            }
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать, Ученик!</span>
            <a href="http://localhost/15/your_project_folder/student/lesson_Html.php">Выход</a>
        
        </div>
    </header>
    <main><style>
       
        .btn-container {
            text-align: center;
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
        }
        .status-btn:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: <?= isset($result_message) && strpos($result_message, 'Ошибка') === false ? '#d4edda' : '#f8d7da' ?>;
            color: <?= isset($result_message) && strpos($result_message, 'Ошибка') === false ? '#155724' : '#721c24' ?>;
            border-radius: 4px;
        }
    </style>

         <?php
         
    if (isset($_GET['id_урока'])) {
        $lesson_id = (int)$_GET['id_урока'];
        
        $sql = "SELECT название, контент FROM  Видео_Уроки WHERE id_урока = ?";
        $stmt = sqlsrv_prepare($link, $sql, array($lesson_id));
        
        if (sqlsrv_execute($stmt)) {
            $lesson = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($lesson) {
                echo '<h1>' . htmlspecialchars($lesson['название']) . '</h1>';
                
                // Блок видео
                echo '<div class="video-container">';
                if (!empty($video_url)) {
    // Экранируем URL для безопасности
                $safe_video_url = htmlspecialchars($video_url, ENT_QUOTES, 'UTF-8');
                echo '<iframe width="1200" height="720"  src="' . $safe_video_url . '" ';
                echo 'frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>';
                } else {
                echo '<div class="no-video">Видео для этого урока отсутствует</div>';
                }
                echo '</div>';
                // Контент урока
                echo '<div class="lesson-content">';
                echo '<p>' . nl2br(htmlspecialchars($lesson['контент'])) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-info">Урок не найден</div>';
            }
        } else {
            echo '<div class="alert alert-error">Ошибка при получении данных урока</div>';
        }
    } else {
        echo '<div class="alert alert-warning">Выберите урок из списка</div>';
    }
    ?>
       <div class="btn-container">
        <form method="post">
            <button type="submit" class="status-btn">Завершить</button>
        </form>
    </div>
    
    <?php if (isset($result_message)): ?>
        <div class="message">
            <?= htmlspecialchars($result_message) ?>
        </div>
    <?php endif; ?>
</main>
    <script>
    function completeLesson(lessonId) {
        if(confirm('Вы уверены, что хотите завершить этот урок?')) {
            fetch('complete_lesson.php?id_урока=' + lessonId)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Урок успешно завершен!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Ошибка сети: ' + error);
                });
        }
    }
    </script>
      
</body>

</html>