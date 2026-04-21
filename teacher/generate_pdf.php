<?php
// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных и библиотек
require_once 'C:\xampp\htdocs\переделанная\15\vendor\autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once '../includes/db_connect.php';

if ($link === false) {
    die("Ошибка подключения: " . print_r(sqlsrv_errors(), true));
}

// Получение данных из POST
$course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
$sort_by = isset($_POST['sort_by']) ? $_POST['sort_by'] : 'фио';
$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 10;

// Получение названия курса
$sql_course = "SELECT название FROM Курсы WHERE id_курса = ?";
$stmt_course = sqlsrv_query($link, $sql_course, [$course_id]);
if ($stmt_course === false) {
    die("Ошибка выполнения запроса курса: " . print_r(sqlsrv_errors(), true));
}
$row_course = sqlsrv_fetch_array($stmt_course, SQLSRV_FETCH_ASSOC);
$course_name = $row_course['название'] ?? 'Неизвестный курс';

// --- ИСПРАВЛЕНИЕ 1: Безопасная сортировка (Защита от SQL-инъекции) ---
$allowedSorts = [
    'фио' => 'o.фио ASC',
    'прогресс_desc' => 'progress_percent DESC',
    'прогресс_asc' => 'progress_percent ASC',
];
$order_by = $allowedSorts[$sort_by] ?? 'o.фио ASC';

// --- ИСПРАВЛЕНИЕ 2: Корректный SQL-запрос для SQL Server ---
$sql = "
    SELECT
        o.id_студента,
        o.фио,
        CASE 
            WHEN total.total_lessons = 0 THEN 0
            ELSE (COUNT(DISTINCT pc.id_урока) * 100.0 / total.total_lessons)
        END AS progress_percent
    FROM Обучающиеся o
    INNER JOIN proUR pu ON o.id_студента = pu.id_студента AND pu.id_курса = ?
    LEFT JOIN Прогресс_Курса pc ON o.id_студента = pc.id_студента
    CROSS APPLY (
        SELECT 
            (SELECT COUNT(*) FROM Уроки WHERE id_курса = pu.id_курса) +
        (SELECT COUNT(*) FROM Видео_Уроки WHERE id_курса = pu.id_курса)
            AS total_lessons
    ) total
    GROUP BY o.id_студента, o.фио, total.total_lessons
    ORDER BY $order_by
";

// --- ИСПРАВЛЕНИЕ 3: Корректные параметры для LIMIT (OFFSET-FETCH) ---
$params = [$course_id];
if ($limit > 0) {
    $sql .= " OFFSET 0 ROWS FETCH NEXT ? ROWS ONLY";
    $params[] = $limit;
}

$stmt = sqlsrv_query($link, $sql, $params);
if ($stmt === false) {
    die("Ошибка выполнения запроса студентов: " . print_r(sqlsrv_errors(), true));
}

$students = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $students[] = $row;
}

// --- ИСПРАВЛЕНИЕ 4: Генерация HTML (Исправлено имя поля в массиве) ---
$html = '
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align: center; color: #333; }
        .info { margin: 15px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .progress-bar {
            background: #e9ecef;
            border-radius: 4px;
            height: 20px;
            overflow: hidden;
        }
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 0%; /* Значение подставляется из PHP */
        }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($course_name) . '</h1>
    <div class="info">
        <p><strong>Дата формирования:</strong> ' . date('d.m.Y H:i') . '</p>
        <p><strong>Количество учеников в отчёте:</strong> ' . count($students) . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Ученик</th>
                <th>Прогресс в курсе</th>
                <th>Процент выполнения</th>
            </tr>
        </thead>
        <tbody>';

foreach ($students as $student) {
    $full_name = htmlspecialchars($student['фио']);
    // ИСПРАВЛЕНО: 'прогресс' -> 'progress_percent'
    $progress_percent_value = round($student['progress_percent'], 1);

    // Исправлена ошибка с незакрытым тегом </div> в предыдущей версии
    $html .= "<tr>
        <td>{$full_name}</td>
        <td>
            <div class='progress-bar'>
                <div class='progress' style='width:{$progress_percent_value}%'></div>
            </div>
        </td>
        <td>{$progress_percent_value}%</td>
    </tr>";
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Генерация PDF
$options = new Options();
$options->setIsRemoteEnabled(true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Отправка PDF в браузер
$filename = 'progress_report_' . $course_id . '_' . date('Y-m-d') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
echo $dompdf->output();

// Освобождение ресурсов
sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($stmt_course);
sqlsrv_close($link);
?>