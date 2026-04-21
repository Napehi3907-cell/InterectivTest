<?php
// Подключение базы данных
require_once '../includes/db_connect.php';

// Получение списка курсов
$sql_courses = "SELECT id_курса, название FROM Курсы ORDER BY название";
$stmt_courses = sqlsrv_query($link, $sql_courses);
$courses = [];
while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки отчёта по прогрессу курса</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
        }

        /* --- Sidebar --- */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            background-color: #34495e;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 15px;
        }
        .sidebar.closed {
            transform: translateX(-250px);
        }
        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #ecf0f1;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #4a637a;
        }
        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 25px;
            font-size: 36px;
            cursor: pointer;
            color: #ecf0f1;
        }

        /* --- Кнопка открытия меню (для моб. устройств) --- */
        .openbtn {
            font-size: 22px;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            padding: 5px 10px;
            transition: 0.3s;
        }

        /* --- Основное содержимое (main) --- */
        main.ma {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 477px;
            min-width: 300px;

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
         }

         select,
         input[type="text"],
         textarea {
             width: 100%;
             padding: 8px;
             box-sizing: border-box;
             border: 1px solid #ccc;
             border-radius: 4px;
         }

         button {
             display: block; 
             width: 100%;
             padding: 10px; 
             margin-top: 10px; 
             border: none; 
             border-radius: 4px; 
             background-color: #4CAF50; 
             color: white; 
             cursor: pointer; 
             font-size: 16px; 
         }

         button:hover {
             background-color: #45a049; 
         }
    </style>
</head>
<body class="container">
    <!-- Кнопка для открытия Sidebar (на мобильных) -->
     <header>
        <div class="nav-bar">
            <!-- Кнопка для открытия Sidebar -->
            <button class="openbtn" id="openBtn">☰ Меню</button>
            <span>Создание отчета</span>
        </div>
    </header>

    <!-- Sidebar -->
     
    <div id="mySidebar" class="sidebar closed">
        <a href="javascript:void(0)" class="closebtn" id="closeBtn">×</a>
        
 <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.html">Главная</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/UrokiPlus.php">Уроки</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/ProgressSt.php">прогресс</a>
        <a href="http://localhost/переделанная/15/your_project_folder/teacher/report_settings.php">Отчеты</a>
        <hr style="border-color:#4a637a; margin:10px 20px;">
        
        <button name="login_as_regist" class="Regis-btn">
          <a href="http://localhost/переделанная/15/your_project_folder/teacher/login.php" class="no-underline">Выход</a>
      </button>
    </div>

    <!-- Основное содержимое -->
    <main class="ma">
        <h1>Настройки отчёта по прогрессу курса</h1>
    
        <form method="POST" action="generate_pdf.php">
           <div class="form-group">
                <label for="course_id">Выберите курс:</label>
                <select id="course_id" name="course_id" required>
                    <option value="">-- Выберите курс --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id_курса']; ?>">
                            <?php echo htmlspecialchars($course['название']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
           </div>
        
           <div class="form-group">
                <label for="sort_by">Сортировка:</label>
                <select id="sort_by" name="sort_by">
                    <option value="фио">По фамилии ученика</option>
                    <option value="прогресс_desc">По прогрессу (убывание)</option>
                    <option value="прогресс_asc">По прогрессу (возрастание)</option>
                </select>
           </div>
        
           <div class="form-group">
                <label for="limit">Количество учеников:</label>
                <select id="limit" name="limit">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="0">Все</option>
                </select>
           </div>
        
           <button type="submit">Сгенерировать отчёт</button>
       </form>
    </main>

    <!-- Скрипт для управления Sidebar -->
    <script>
        const sidebar = document.getElementById("mySidebar");
        const openBtn = document.getElementById("openBtn");
        const closeBtn = document.getElementById("closeBtn");

        function openNav() {
          sidebar.classList.remove("closed");
        }
    
        function closeNav() {
          sidebar.classList.add("closed");
        }
    
        openBtn.addEventListener('click', openNav);
        closeBtn.addEventListener('click', closeNav);
    </script>
</body>
</html>