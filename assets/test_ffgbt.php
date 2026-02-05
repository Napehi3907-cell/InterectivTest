<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Редактирование ФИО пользователя</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Бургер меню */
        body {
            font-family: Arial, sans-serif;
        }
        
        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .burger-menu {
            display: none;
            list-style-type: none;
            padding-left: 0;
            background-color: #333;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .burger-menu li a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
        }
        
        .burger-icon {
            cursor: pointer;
            font-size: 24px;
        }
        
        @media screen and (max-width: 768px) {
            .burger-menu {
                display: none;
            }
            
            .show-burger-menu {
                display: block !important;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <span>Добро пожаловать, Ученик!</span>
        <div class="burger-icon" onclick="toggleBurgerMenu()">☰</div>
        <ul class="burger-menu">
            <li><a href="#">Главная</a></li>
            <li><a href="#">Курсы</a></li>
            <li><a href="#">Профиль</a></li>
            <li><a href="----------------">Выход</a></li>
        </ul>
    </header>
    
    <!-- Main content remains the same -->
    <main class="ma">
        <div class="container1">
            <h1>Добавление урока</h1>
            <div class="form-group">
                <label for="course_id">ID курса:</label>
                <input type="text" id="course_id" name="course_id" required>
            </div>
            <div class="form-group">
                <label for="lesson_name">Название урока:</label>
                <input type="text" id="lesson_name" name="lesson_name" required>
            </div>
            <div class="form-group">
                <label for="lesson_content">Содержание урока:</label>
                <textarea id="lesson_content" name="lesson_content" required></textarea>
            </div>
            <button type="submit" name="add_bt" class="btn">Добавление урока</button>
            <button name="login_as_regist" class="Regis-btn">
                <a Href="http://localhost/15/your_project_folder/teacher/reposts_Html.php" class="no-underline">
                    Выход
                </a>
            </button>
        </div>
    </main>

    <script>
        function toggleBurgerMenu() {
            document.querySelector('.burger-menu').classList.toggle('show-burger-menu');
        }
    </script>
</body>

</html>