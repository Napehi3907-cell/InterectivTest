<!DOCTYPE html>
<html lang="ru">

<head>

    <meta charset="UTF-8">
    <title>Редактирование ФИО пользователя</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="container">

</body><header>
    <div class="nav-bar">
        <span>Добро пожаловать, Ученик!</span>
         <button class="openbtn" id="openBtn">☰ Меню</button>
        <a href="http://localhost/15/your_project_folder/teacher/reposts_Html.php">Выход</a>

    </div>
</header>
<div id="mySidebar" class="sidebar closed">
    <!-- Кнопка закрытия (крестик) -->
    <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>
    
    <!-- Пункты меню -->
   <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.html">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
    
    <hr style="border-color: #4a637a; margin: 10px 20px;">
    
    <!-- Кнопка выхода -->
    <button class="Regis-btn">
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/login.php" class="no-underline">Выход</a>
    </button>
</div>
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
    </style>
</head>


    <div>
        <main>


            <?php
            require_once '../includes/db_connect.php';

            // Обработка изменений
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $newFIO = trim(filter_input(INPUT_POST, 'new_fio', FILTER_SANITIZE_STRING));

                if ($id && $newFIO) {
                    try {
                        $sql = "UPDATE PL SET ФИО=? WHERE id_поль=?";
                        $params = [$newFIO, $id];
                        $result = sqlsrv_query($link, $sql, $params);

                        if ($result === false) {
                            throw new Exception(print_r(sqlsrv_errors(), true));
                        }

                        echo '<div class="success-message">ФИО успешно обновлено.</div>';
                    } catch (Exception $e) {
                        echo '<div class="error-message">Ошибка при изменении ФИО: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    echo '<div class="error-message">Проверьте введённые данные.</div>';
                }
            }
            ?>


            <h1>Редактирование ФИО пользователя</h1>
            <form method="post" action="">
                <div class="form-group">
                    <label for="id">ID пользователя:</label>
                    <input type="text" id="id" name="id" placeholder="Введите ID пользователя" required>
                </div>
                <div class="form-group">
                    <label for="new_fio">Новое ФИО:</label>
                    <input type="text" id="new_fio" name="new_fio" placeholder="Введите новое ФИО" required>
                </div>
                <button type="submit" class="Regis-btn">Изменить ФИО</button>
            </form>
        </main>
    </div>
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

</html>