<?php
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

$lesson_title = "Выбор урока";
if(isset($_GET['id_урока'])) {
    $lesson_id = (int)$_GET['id_урока'];
    $sql = "SELECT название, описание FROM Уроки WHERE id_урока = ?";
    $stmt = sqlsrv_prepare($link, $sql, array($lesson_id));

    if(sqlsrv_execute($stmt)) {
        $lesson = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if($lesson) {
            $lesson_title = htmlspecialchars($lesson['название']);
        }
    }
}
$lesson_id = 1; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE Уроки SET статус = 1 WHERE id_урока = ?";
    $stmt = sqlsrv_prepare($link, $sql, array($lesson_id));
    
    if (sqlsrv_execute($stmt)) {
        $result_message = "Статус урока успешно изменён!";
    } else {
        $result_message = "Ошибка: " . print_r(sqlsrv_errors(), true);
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
        if(isset($_GET['id_урока'])) {
            $lesson_id = (int)$_GET['id_урока'];
            
            $sql = "SELECT название, контент FROM Уроки WHERE id_урока = ?";
            $stmt = sqlsrv_prepare($link, $sql, array($lesson_id));
            
            if(sqlsrv_execute($stmt)) {
                $lesson = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                
                if($lesson) {
                    echo '<h1>' . htmlspecialchars($lesson['название']) . '</h1>';
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
            <button type="submit" class="status-btn">
                завершить
            </button>
        </form>
        
        <?php if(isset($result_message)): ?>
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