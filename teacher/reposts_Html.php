<?php
// Подключение к базе данных
require_once '../includes/db_connect.php'; // Убедитесь, что путь к файлу правильный
$courses = [];
$sql_courses = "SELECT id_курса, название FROM Курсы";
$stmt_courses = sqlsrv_query($link, $sql_courses);
if ($stmt_courses) {
    while ($row = sqlsrv_fetch_array($stmt_courses, SQLSRV_FETCH_ASSOC)) {
        $courses[] = $row;
    }
}
// Определяем корень приложения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_report'])) {
    $course_id = trim($_POST['course_id']);
    $report_date = trim($_POST['report_date']);

    // Получение названия курса
    $sql_course_name = "SELECT название FROM Курсы WHERE id_курса = ?";
    $params_course_name = array($course_id);
    $stmt_course_name = sqlsrv_prepare($link, $sql_course_name, $params_course_name);
    if ($stmt_course_name && sqlsrv_execute($stmt_course_name)) {
        $row_course_name = sqlsrv_fetch_array($stmt_course_name, SQLSRV_FETCH_ASSOC);
        $course_name = $row_course_name ? $row_course_name['название'] : '';
    }

    // Получение студентов, записанных на курс

    // Получение посещений по урокам курса за выбранную датуФ
    $attendance_data = [];
    if (!empty($students)) {
        // Получение уроков курса
        $lessons = [];
        $sql_lessons = "SELECT id_урока, название FROM Уроки WHERE id_курса = ?";
        $stmt_lessons = sqlsrv_prepare($link, $sql_lessons, array($course_id));
        if ($stmt_lessons && sqlsrv_execute($stmt_lessons)) {
            while ($row_lesson = sqlsrv_fetch_array($stmt_lessons, SQLSRV_FETCH_ASSOC)) {
                $lessons[] = $row_lesson;
            }
        }

        // Получение посещений для каждого урока
        foreach ($lessons as $lesson) {
            $lesson_id = $lesson['id_урока'];
            $sql_attendance = "SELECT id_студента, COUNT(*) AS count FROM proUR WHERE id_урока = ? AND CONVERT(DATE, дата) = ? GROUP BY id_студента";
            $params_attendance = array($lesson_id, $report_date);
            $stmt_attendance = sqlsrv_prepare($link, $sql_attendance, $params_attendance);
            if ($stmt_attendance && sqlsrv_execute($stmt_attendance)) {
                while ($row_att = sqlsrv_fetch_array($stmt_attendance, SQLSRV_FETCH_ASSOC)) {
                    $attendance_data[$row_att['id_студента']] = isset($attendance_data[$row_att['id_студента']]) ? $attendance_data[$row_att['id_студента']] + (int) $row_att['count'] : (int) $row_att['count'];
                }
            }
        }
    }

    $show_report_form = true;
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
            <form method="POST" action="">
                <label for="course_select">Курс:</label>
                <select id="course_select" name="course_id" required>
                    <option value="">-- Выберите курс --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id_курса']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id_курса']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['название']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="report_date">Дата:</label>
                <input type="date" id="report_date" name="report_date"
                    value="<?php echo isset($_POST['report_date']) ? htmlspecialchars($_POST['report_date']) : date('Y-m-d'); ?>"
                    required>

                <button type="submit" name="view_report" class="btn">Показать отчет</button>
            </form>

            <!-- Вывод таблицы, если отчет получен -->
            <?php if ($show_report_form): ?>
                <h2>Отчет по курсу: <?php echo htmlspecialchars($course_name); ?> за
                    <?php echo htmlspecialchars($report_date); ?>
                </h2>
                <table cellpadding="5" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ФИО студента</th>
                            <th>Прохождения уроков</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($students)) {
                            echo "<tr><td colspan='2'>Нет студентов для выбранного курса и даты.</td></tr>";
                        } else {
                            foreach ($students as $student):
                                $student_id = $student['id_студента'];
                                $full_name = htmlspecialchars($student['фио']);
                                $attendance_count = isset($attendance_data[$student_id]) ? (int) $attendance_data[$student_id] : 0;
                                ?>
                                <tr>
                                    <td><?php echo $full_name; ?></td>
                                    <td><?php echo $attendance_count; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php } ?>
                    </tbody>
                </table>

                <form method="POST" action="generate_pdf.php" target="_blank">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>" />
                    <input type="hidden" name="report_date"
                        value="<?php echo htmlspecialchars($_POST['report_date']); ?>" />
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
                    <button type="submit" class="rainbow-hover"><a class="sp"
                            href="http://localhost/15/your_project_folder/teacher/progress_indicator.php">

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
                    <a href=" http://localhost/15/your_project_folder/teacher/Urok.php" class="sp">
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