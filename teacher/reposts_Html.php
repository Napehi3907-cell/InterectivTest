<?php
// reports_Html.php


// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный

// Определяем корень приложения
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

// Инициализация переменных
$error_message = '';
$success_message = '';
$courses = [];
$report_data = [];
$show_report_form = false; // Флаг для отображения формы отчета

// Получаем данные из базы данных для списка курсов
$sql_courses = "SELECT id_курса, название FROM Курсы";
$stmt_courses = sqlsrv_query($link, $sql_courses);

if ($stmt_courses === false) {
    log_sqlsrv_errors("Подготовка запроса списка курсов");
    $error_message = "Ошибка сервера при получении списка курсов.";
} else {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_report'])) {
    $course_id = trim($_POST['course_id']);
    $report_date = trim($_POST['report_date']);

    // Получаем данные отчета из базы данных
    $sql_report = "SELECT * FROM Отчеты WHERE id_курса = ? AND дата = ?";
    $params_report = [$course_id, $report_date];
    $stmt_report = sqlsrv_prepare($link, $sql_report, $params_report);

    if ($stmt_report === false) {
        log_sqlsrv_errors("Подготовка запроса данных отчета");
        $error_message = "Ошибка сервера при получении данных отчета.";
    } else {
        if (sqlsrv_execute($stmt_report)) {
            while ($row = sqlsrv_fetch_array($stmt_report, SQLSRV_FETCH_ASSOC)) {
                $report_data[] = $row;
            }
            if(empty($report_data)){
                $error_message = "Нет данных для выбранных параметров.";
            } else {
                 $success_message = "Отчет для курса с ID: $course_id и даты: $report_date успешно сформирован.";
                $show_report_form = true;
            }


        } else {
            log_sqlsrv_errors("Выполнение запроса данных отчета");
            $error_message = "Ошибка сервера при получении данных отчета.";
        }
    }
}
?>

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

       <div class="card form-section">
        <h3>Выберите данные для отчета</h3>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="reposts_Html.php">
            <label for="course_select">Курс:</label>
            <select id="course_select" name="course_id" required onchange="updateDates()">
                <option value="">-- Выберите курс --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id_курса']; ?>"><?php echo htmlspecialchars($course['название']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="report_date">Дата:</label>
            <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="view_report" class="btn">Показать отчет</button>
        </form>

        <div class="report-form-container <?php if ($show_report_form) echo 'show-report'; ?>">
            <?php if (!empty($report_data)): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Дата</th>
                            <th>Значение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['название']); ?></td>
                                <td><?php echo htmlspecialchars(date_format($row['дата'], 'Y-m-d')); ?></td>
                                <td><?php echo htmlspecialchars($row['значение']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="download-btn" onclick="downloadReport()">Скачать отчет</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Функция для динамического обновления списка дат
        function updateDates() {
            const courseSelect = document.getElementById('course_select');
            const reportDate = document.getElementById('report_date');

            reportDate.value = new Date().toISOString().split('T')[0];
        }

        // Функция для скачивания отчета
        function downloadReport() {
            const table = document.querySelector('.report-table');
            if (!table) {
                alert('Нет данных для скачивания.');
                return;
            }

            let csv = [];

            const headers = table.querySelectorAll('th');
            let headerRow = [];
            headers.forEach(header => {
                headerRow.push(`"${header.innerText}"`);
            });
            csv.push(headerRow.join(','));

            // Получаем данные таблицы
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowData = [];
                cells.forEach(cell => {
                    rowData.push(`"${cell.innerText}"`);
                });
                csv.push(rowData.join(','));
            });

            // Создаем CSV файл и скачиваем его
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'report.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>


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