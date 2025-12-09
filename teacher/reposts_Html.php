<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчеты - Учитель</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="nav-bar">
            <span>Добро пожаловать, Преподователь!</span>
            <a href="http://localhost/15/your_project_folder/Login.php">Выход</a>
        </div>
    </header>
    <main>
        <h2>Управление Отчетами</h2>
        <p>Здесь учителя могут просматривать и сохранять отчеты по успеваемости учеников.</p>

        <!-- Форма выбора курса и даты для отчета -->
        <div class="card form-section">
            <h3>Выберите данные для отчета</h3>
            <form method="POST" action="reports.php"> <!-- Форма отправляет данные на эту же страницу -->
                <label for="course_select">Курс:</label>
                <select id="course_select" name="course_id" required>
                    <option value="">-- Выберите курс --</option>
                    <!-- Здесь должны быть реальные курсы из БД -->
                    <option value="1">Основы Веб-разработки</option>
                    <option value="2">Продвинутый PHP</option>
                </select>
                
                <label for="report_date">Дата:</label>
                <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                
                <button type="submit" name="view_report" class="btn">Показать отчет</button>
            </form>
        </div>

        <!-- Секция для отображения отчета -->
        <?php if (isset($_POST['view_report'])): // Этот блок выполнится, если форма была отправлена ?>
            <div class="card">
                <h3>Отчет за <span id="report_date_display"><?php echo htmlspecialchars($_POST['report_date']); ?></span> по курсу "<span id="course_name_display">Название Курса</span>"</h3>
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Оценка (0-100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Пример данных: в реальном приложении данные будут загружаться из БД -->
                        <tr>
                            <td>Петров А.С.</td>
                            <td>
                                <input type="number" name="grades[1][course_average]" min="0" max="100" value="85" required>
                            </td>
                        </tr>
                        <tr>
                            <td>Сидорова М.К.</td>
                            <td>
                                <input type="number" name="grades[2][course_average]" min="0" max="100" value="92" required>
                            </td>
                        </tr>
                        <tr>
                            <td>Иванов И.И.</td>
                            <td>
                                <input type="number" name="grades[3][course_average]" min="0" max="100" value="78" required>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn save-btn">Сохранить отчет</button>
            </div>
        <?php endif; ?>

    </main>
        <main>
            <main>
                <form method="get" action="progress_indicator.php">
            <h1>Форма для ввода номера курса и студента</h1>
            <div class="form-group">
                <label for="student_id">ID студента:</label>
                <input type="text" id="student_id" name="student_id" required>
            </div>
            <div class="form-group">
                <label for="course_id">ID курса:</label>
                <input type="text" id="course_id" name="course_id" required>
            </div>
            <button type="submit" class="rainbow-hover" ><a class = "sp" href="http://localhost/15/your_project_folder/teacher/progress_indicator.php">
                
            </a></button>
        </form>
        
        

        <form method="post" action="create_course.php">
            <h1>Создание курса</h1>
            <div class="form-group">
                <label for="course_name">Название курса:</label>
                <input type="text" id="course_name" name="course_name" required>
            </div>
            <div class="form-group">
                <label for="course_description">Описание курса:</label>
                <textarea id="course_description" name="course_description" required></textarea>
            </div>
            <button type="submit" name="create_bt" class="btn">Создать курс</button>

            <div class="form-group">
                <label for="course_id">ID курса:</label>
                <input type="text" id="course_id" name="course_id" required>
            </div>
            <button type="submit" name="up_bt" class="btn">Редактировать курс</button>
 </main>
 <main>
            <button type="submit" name="add_bt" class="rainbow-hover">
                <a href = " http://localhost/15/your_project_folder/teacher/Urok.php" class = "sp">
                      Добавление урока
            </a>
            </button>
    
 </main>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </form>
    </main>

</body>
</html>