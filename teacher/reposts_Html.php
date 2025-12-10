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

    // Получаем название курса
    $sql_course_name = "SELECT название FROM Курсы WHERE id_курса = ?";
    $stmt_course_name = sqlsrv_prepare($link, $sql_course_name, array($course_id));
    $course_name = '';
    if ($stmt_course_name && sqlsrv_execute($stmt_course_name)) {
        $row_course_name = sqlsrv_fetch_array($stmt_course_name, SQLSRV_FETCH_ASSOC);
        if ($row_course_name) {
            $course_name = htmlspecialchars($row_course_name['название']);
        }
    }

    // Получаем студентов
    $sql_students = "SELECT s.id_студента, s.имя, s.фамилия
                     FROM Студенты s
                     JOIN Записи_на_курс z ON s.id_студента = z.id_студента
                     WHERE z.id_курса = ?";
    $stmt_students = sqlsrv_prepare($link, $sql_students, array($course_id));
    $students = [];
    if ($stmt_students && sqlsrv_execute($stmt_students)) {
        while ($row_student = sqlsrv_fetch_array($stmt_students, SQLSRV_FETCH_ASSOC)) {
            $students[] = $row_student;
        }
    }

    // Получаем посещаемость
    $sql_attendance = "SELECT id_студента, COUNT(*) AS посещаемость
                       FROM proUR
                       WHERE id_курса = ? AND CONVERT(DATE, data) = ?
                       GROUP BY id_студента";
    $stmt_attendance = sqlsrv_prepare($link, $sql_attendance, array($course_id, $report_date));
    $attendance_data = [];
    if ($stmt_attendance && sqlsrv_execute($stmt_attendance)) {
        while ($row_attendance = sqlsrv_fetch_array($stmt_attendance, SQLSRV_FETCH_ASSOC)) {
            $attendance_data[$row_attendance['id_студента']] = $row_attendance['посещаемость'];
        }
    }

    $show_report_form = true; // показываем таблицу
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
        <span>Добро пожаловать, Преподаватель!</span>
        <a href="http://localhost/15/your_project_folder/Login.php">Выход</a>
    </div>
</header>
<main>
    <h2>Управление Отчётами</h2>
    <p>Здесь учителя могут просматривать и сохранять отчёты по успеваемости учеников.</p>

    <div class="card form-section">
        <h3>Выберите курс и дату для отчёта о посещаемости</h3>

        <!-- Вывод ошибок и сообщений -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Форма выбора курса и даты -->
        <form method="POST" action="reposts_Html.php">
            <label for="course_select">Курс:</label>
            <select id="course_select" name="course_id" required>
                <option value="">-- Выберите курс --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id_курса']; ?>"><?php echo htmlspecialchars($course['название']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="report_date">Дата:</label>
            <input type="date" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="view_report" class="btn">Показать отчет</button>
        </form>

        <!-- Блок с таблицей и кнопкой скачивания, отображается только при условии -->
        <?php if (isset($_POST['view_report']) && $show_report_form): ?>
            <div class="card">
                <h3>Отчет о посещаемости за <span id="report_date_display"><?php echo htmlspecialchars($_POST['report_date']); ?></span> по курсу "<span id="course_name_display"><?php echo $course_name; ?></span>"</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Посещаемость</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['фамилия'] . ' ' . $student['имя']); ?></td>
                                    <td>
                                        <?php
                                        $attendance_count = isset($attendance_data[$student['id_студента']]) ? $attendance_data[$student['id_студента']] : 0;
                                        echo $attendance_count;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Студенты не найдены для этого курса.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <form method="POST" action="generate_pdf.php" target="_blank">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>" />
                    <input type="hidden" name="report_date" value="<?php echo htmlspecialchars($_POST['report_date']); ?>" />
                    <input type="hidden" name="course_name" value="<?php echo htmlspecialchars($course_name); ?>" />
                    <button type="submit" name="generate_pdf" class="download-btn">Скачать PDF</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
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