<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$studentId = $_SESSION['user_id'];

// Получаем данные из POST‑запроса
$input = json_decode(file_get_contents('php://input'), true);
$courseId = $input['courseId'] ?? null;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID курса']);
    exit;
}

// Подключение к базе данных
require_once '../includes/db_connect.php';

// Проверка наличия курса в временных
$sqlCheckTemp = "
    SELECT 1 FROM Временный_курс tc
    JOIN Обучающиеся s ON tc.id_преподавателя = (SELECT id_преподавателя FROM Класс WHERE id_класса = s.id_класса)
    WHERE tc.id_курса = ? AND s.id_студента = ?
";
$paramsCheckTemp = [$courseId, $studentId];
$stmtCheckTemp = sqlsrv_prepare($link, $sqlCheckTemp, $paramsCheckTemp);

if ($stmtCheckTemp === false) {
    error_log("Ошибка подготовки запроса проверки временных курсов: " . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    exit;
}

if (sqlsrv_execute($stmtCheckTemp)) {
    if (sqlsrv_has_rows($stmtCheckTemp)) {
        echo json_encode(['success' => false, 'message' => 'Курс уже добавлен']);
        exit;
    }
} else {
    error_log("Ошибка выполнения запроса проверки временных курсов: " . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    exit;
}

// Вставка в Временный_курс
$sqlInsert = "
    INSERT INTO Временный_курс (название, описание, id_преподавателя, id_курса)
    SELECT название, описание, id_преподавателя, id_курса FROM Курсы WHERE id_курса = ?
";
$stmtInsert = sqlsrv_prepare($link, $sqlInsert, [$courseId]);

if ($stmtInsert === false) {
    error_log("Ошибка подготовки запроса вставки временного курса: " . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    exit;
}

if (sqlsrv_execute($stmtInsert)) {
    echo json_encode(['success' => true]);
} else {
    error_log("Ошибка выполнения запроса вставки временного курса: " . print_r(sqlsrv_errors(), true));
    echo json_encode(['success' => false, 'message' => 'Ошибка добавления']);
}
?>
