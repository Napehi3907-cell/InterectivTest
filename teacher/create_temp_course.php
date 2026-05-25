<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = trim($_POST['course_id'] ?? '');
    $temp_name = trim($_POST['temp_name'] ?? '');
    $temp_desc = trim($_POST['temp_desc'] ?? '');

    if (!$course_id || !$temp_name) {
        echo json_encode([
            'success' => false,
            'message' => 'Заполните все обязательные поля'
        ]);
        exit;
    }

    // Получаем данные основного курса
    $sql_get_course = "
        SELECT название, описание
        FROM Курсы
        WHERE id_курса = ?
    ";
    $stmt_get = sqlsrv_prepare($link, $sql_get_course, [$course_id]);

    if ($stmt_get === false || !sqlsrv_execute($stmt_get)) {
        error_log("Ошибка получения данных основного курса: " . print_r(sqlsrv_errors(), true));
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка получения данных основного курса'
        ]);
        exit;
    }

    $course_data = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);
    if (!$course_data) {
        echo json_encode([
            'success' => false,
            'message' => 'Основной курс не найден'
        ]);
        exit;
    }

    // Создаём временный курс
    $sql_insert = "
        INSERT INTO Временный_курс (название, описание, id_преподавателя, id_курса)
        VALUES (?, ?, ?, ?)
    ";

    $params_insert = [
        $temp_name,
        $temp_desc ?: $course_data['описание'],
        $user_id,
        $course_id
    ];

    $stmt_insert = sqlsrv_prepare($link, $sql_insert, $params_insert);

    if ($stmt_insert === false) {
        $errors = sqlsrv_errors();
        error_log("Ошибка подготовки запроса создания временного курса: " . print_r($errors, true));
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка подготовки запроса к базе данных'
        ]);
        exit;
    }

    if (!sqlsrv_execute($stmt_insert)) {
        $errors = sqlsrv_errors();
        error_log("Ошибка выполнения запроса создания временного курса: " . print_r($errors, true));
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при создании временного курса в базе данных'
        ]);
        exit;
    }

    // Получаем ID созданного временного курса
    $sql_get_id = "SELECT SCOPE_IDENTITY() AS last_id";
    $stmt_get_id = sqlsrv_query($link, $sql_get_id);

    if ($stmt_get_id === false) {
        error_log("Ошибка получения ID временного курса");
        echo json_encode([
            'success' => true,
            'message' => 'Временный модуль создан, но не удалось получить его ID',
            'temp_course_id' => null,
            'course_name' => $temp_name
        ]);
    } else {
        $row = sqlsrv_fetch_array($stmt_get_id, SQLSRV_FETCH_ASSOC);
        $temp_course_id = (int) $row['last_id'];

        echo json_encode([
            'success' => true,
            'message' => 'Временный модуль создан успешно!',
            'temp_course_id' => $temp_course_id,
            'course_name' => $temp_name
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>