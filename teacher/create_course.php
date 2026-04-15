<?php



session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../includes/db_connect.php';


define('ROOT_PATH', __DIR__ . '/');


$error_message = '';
$success_message = '';

// Получаем сообщения из сессии (для отображения после редиректа)
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая кнопка была нажата
    if (isset($_POST['create_bt'])) {
        $course_name = trim($_POST['course_name']);
        $course_description = trim($_POST['course_description']);

        if (empty($course_name) || empty($course_description)) {
            $_SESSION['error_message'] = "Пожалуйста, заполните все поля.";
        } else {
            $sql_create_course = "INSERT INTO Курсы (название, описание) VALUES (?, ?)";
            $params_create_course = [$course_name, $course_description];

            $stmt_create_course = sqlsrv_prepare($link, $sql_create_course, $params_create_course);
            if ($stmt_create_course === false) {
                log_sqlsrv_errors("Подготовка запроса создания курса");
                $_SESSION['error_message'] = "Ошибка сервера при создании курса.";
            } else {
                if (sqlsrv_execute($stmt_create_course)) {
                    $_SESSION['success_message'] = "Курс создан успешно!";
                } else {
                    log_sqlsrv_errors("Выполнение запроса создания курса");
                    $_SESSION['error_message'] = "Ошибка сервера при создании курса.";
                }
            }
        }
    } elseif (isset($_POST['up_bt'])) {
        // Обработка редактирования курса
        $course_id = trim($_POST['course_id']);
        $new_course_name = trim($_POST['new_course_name']);
        $new_course_description = trim($_POST['new_course_description']);

        if (empty($course_id) || empty($new_course_name) || empty($new_course_description)) {
            $_SESSION['error_message'] = "Пожалуйста, заполните все поля.";
        } else {
            // SQL-запрос для редактирования курса
            $sql_update_course = "UPDATE Курсы SET название = ?, описание = ? WHERE id_курса = ?";
            $params_update_course = [$new_course_name, $new_course_description, $course_id];

            $stmt_update_course = sqlsrv_prepare($link, $sql_update_course, $params_update_course);
            if ($stmt_update_course === false) {
                log_sqlsrv_errors("Подготовка запроса редактирования курса");
                $_SESSION['error_message'] = "Ошибка сервера при редактировании курса.";
            } else {
                if (sqlsrv_execute($stmt_update_course)) {
                    $_SESSION['success_message'] = "Курс отредактирован успешно!";
                } else {
                    log_sqlsrv_errors("Выполнение запроса редактирования курса");
                    $_SESSION['error_message'] = "Ошибка сервера при редактировании курса.";
                }
            }
        }
    } else {
        $_SESSION['error_message'] = "Неизвестное действие.";
    }

    // Редирект для предотвращения повторной отправки формы
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание и редактирование курса</title>
    <link rel="stylesheet" href="../css/style.css">
    
</head>

<body class="container">
    <header>
        <div class="nav-bar">
            <span>Система управления курсами</span>
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <a href="../Login.php">Выход</a>
        </div>
    </header>
    <div id="mySidebar" class="sidebar closed">
        <!-- Кнопка закрытия (крестик) -->
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>

        <!-- Пункты меню -->
        <a href="#">Главная</a>
        <a href="#">Уроки</a>
        <a href="#">Прогресс</a>
        <a href="#">Отчёты</a>

        <hr style="border-color: #4a637a; margin: 10px 20px;">

        <!-- Кнопка выхода -->
        <button class="Regis-btn">
            <a href="../Login.php" class="no-underline">Выход</a>
        </button>
    </div>

    <main>
        <section class="form-section">
            <div class="card">
                <!-- Вывод ошибок и сообщений -->
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <h1>Создание курса</h1>
                <form method="post" action="create_course.php">
                    <div class="form-group">
                        <label for="course_name">Название курса:</label>
                        <input type="text" id="course_name" name="course_name" required>
            </div>
            <div class="form-group">
                <label for="course_description">Описание курса:</label>
                <textarea id="course_description" name="course_description" required></textarea>
            </div>
            <button type="submit" name="create_bt" class="btn save-btn">Создать курс</button>
        </form>

        <hr style="margin: 40px 0; border: 1px solid #e9ecef;">

        <h2>Редактирование курса</h2>
        <form method="post" action="edit_course.php">
            <div class="form-group">
                <label for="course_id">ID курса:</label>
                <input type="number" id="course_id" name="course_id" required>
            </div>
            <div class="form-group">
                <label for="new_course_name">Новое название курса:</label>
                <input type="text" id="new_course_name" name="new_course_name">
            </div>
            <div class="form-group">
                <label for="new_course_description">Новое описание курса:</label>
                <textarea id="new_course_description" name="new_course_description"></textarea>
            </div>
            <button type="submit" name="up_bt" class="btn">Редактировать курс</button>
        </form>
    </div>
</section>
</main>
<script>
    const sidebar = document.getElementById("mySidebar");
    const openBtn = document.getElementById("openBtn");
    const closeBtn = document.getElementById("closeBtn");
    const body = document.body;

    function openNav() {
        sidebar.classList.remove("closed");
        body.classList.add("sidebar-open");
    }

    function closeNav() {
        sidebar.classList.add("closed");
        body.classList.remove("sidebar-open");
    }

    openBtn.addEventListener('click', openNav);
    closeBtn.addEventListener('click', closeNav);

    // Закрытие Sidebar при клике вне его области
    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
            closeNav();
        }
    });

    // Закрытие Sidebar при нажатии Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeNav();
        }
    });
</script>
</body>
</html>
