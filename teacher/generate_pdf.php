<?php
// generate_pdf.php
require_once '../includes/db_connect.php'; //  Убедитесь, что путь правильный

// generate_pdf.php

// Подключение автозагрузчика Composer
require_once 'C:\xampp\htdocs\15\vendor\autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Получение данных из POST
$course_id = isset($_POST['course_id']) ? $_POST['course_id'] : '';
$report_date = isset($_POST['report_date']) ? $_POST['report_date'] : '';
$course_name = isset($_POST['course_name']) ? $_POST['course_name'] : '';

// Тут можно добавить дополнительные проверки на безопасность данных

// В этом примере предполагается, что вы уже получили список студентов и их посещаемость
// Передайте их через POST или получите заново из базы данных, если нужно

// Для примера создадим простую таблицу
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
    <h1>Отчёт о посещаемости</h1>
    <p><strong>Курс:</strong> ' . htmlspecialchars($course_name) . '</p>
    <p><strong>Дата:</strong> ' . htmlspecialchars($report_date) . '</p>
    <table>
        <thead>
            <tr>
                <th>Студент</th>
                <th>Посещаемость</th>
            </tr>
        </thead>
        <tbody>';

if (isset($_POST['students']) && isset($_POST['attendance'])) {
    $students = $_POST['students']; // массив студентов
    $attendance = $_POST['attendance']; // массив посещаемости

    foreach ($students as $index => $student_name) {
        $attend = htmlspecialchars($attendance[$index]);
        $student_display = htmlspecialchars($student_name);
        $html .= "<tr><td>{$student_display}</td><td>{$attend}</td></tr>";
    }
} else {
    // Временно добавим фиктивные данные
    $html .= "<tr><td>Иванов Иван Иванович</td><td>5</td></tr>";
    $html .= "<tr><td>Петров Пётр Петрович</td><td>3</td></tr>";
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Создаем экземпляр Dompdf
$options = new Options();
$options->setIsRemoteEnabled(true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Выводим PDF
$filename = 'report_' . date('Y-m-d') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');

echo $dompdf->output();
?>