<?php
// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
require_once 'C:\xampp\htdocs\15\vendor\autoload.php'; // путь к автозагрузчику Composer

use Dompdf\Dompdf;
use Dompdf\Options;

require_once '../includes/db_connect.php';


if ($link === false) {
    die("Ошибка подключения: " . print_r(sqlsrv_errors(), true));
}

// Получение данных из POST
$course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
$report_date = isset($_POST['report_date']) ? $_POST['report_date'] : '';

// Получение названия курса
$sql_course = "SELECT название FROM Курсы WHERE id_курса = ?";
$params_course = [$course_id];

$stmt_course = sqlsrv_query($link, $sql_course, $params_course);
if ($stmt_course === false) {
    die("Ошибка выполнения запроса курса: " . print_r(sqlsrv_errors(), true));
}
$row_course = sqlsrv_fetch_array($stmt_course, SQLSRV_FETCH_ASSOC);
$course_name = $row_course['название'] ?? '';

// Запрос для получения студентов и их посещений
$sql = "
   SELECT o.id_студента, o.фио, 
    COUNT(p.id_pr) AS прохождение
FROM Обучающиеся o
LEFT JOIN proUR p 
    ON o.id_студента = p.id_студента 
    AND CAST(p.data AS DATE) = ?
    AND p.id_курса = ?
GROUP BY o.id_студента, o.фио
ORDER BY o.фио
";

$params = [$report_date, $course_id];

$stmt = sqlsrv_query($link, $sql, $params);
if ($stmt === false) {
    die("Ошибка выполнения запроса студентов: " . print_r(sqlsrv_errors(), true));
}

$students = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $students[] = $row;
}

// Создаем HTML для генерации PDF
$html = '
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Отчёт о прохождение курса</h1>
    <p><strong>Курс:</strong> ' . htmlspecialchars($course_name) . '</p>
    <p><strong>Дата:</strong> ' . htmlspecialchars($report_date) . '</p>
    <table>
        <thead>
            <tr>
                <th>Студент</th>
                <th>Кол-во прохождений</th>
            </tr>
        </thead>
        <tbody>';

foreach ($students as $student) {
    $full_name = htmlspecialchars($student['фио']);
    $attendances = (int) $student['посещений'];
    $html .= "<tr><td>{$full_name}</td><td>{$attendances}</td></tr>";
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
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Отправляем PDF в браузер
$filename = 'attendence_report_' . date('Y-m-d') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');

echo $dompdf->output();

// Освобождение ресурсов
sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($stmt_course);
sqlsrv_close($link);
?>