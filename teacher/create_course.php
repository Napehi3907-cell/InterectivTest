<?php



session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../includes/db_connect.php';


define('ROOT_PATH', __DIR__ . '/');


$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, какая кнопка была нажата
    if (isset($_POST['create_bt'])) {

        $course_name = trim($_POST['course_name']);
        $course_description = trim($_POST['course_description']);

        if (empty($course_name) || empty($course_description)) {
            $error_message = "Пожалуйста, заполните все поля.";
        } else {

            $sql_create_course = "INSERT INTO Курсы (название, описание) VALUES (?, ?)";
            $params_create_course = [$course_name, $course_description];

            $stmt_create_course = sqlsrv_prepare($link, $sql_create_course, $params_create_course);
            if ($stmt_create_course === false) {
                log_sqlsrv_errors("Подготовка запроса создания курса");
                $error_message = "Ошибка сервера при создании курса.";
            } else {
                if (sqlsrv_execute($stmt_create_course)) {
                    $success_message = "Курс создан успешно!";
                } else {
                    log_sqlsrv_errors("Выполнение запроса создания курса");
                    $error_message = "Ошибка сервера при создании курса.";
                }
            }
        }
    } elseif (isset($_POST['up_bt'])) {
        // Обработка редактирования курса
        $course_id = trim($_POST['course_id']);
        $course_name = trim($_POST['course_name']);
        $course_description = trim($_POST['course_description']);

        if (empty($course_id) || empty($course_name) || empty($course_description)) {
            $error_message = "Пожалуйста, заполните все поля.";
        } else {
            // SQL-запрос для редактирования курса
            $sql_update_course = "UPDATE Курсы SET название = ?, описание = ? WHERE id_курс = ?";
            $params_update_course = [$course_name, $course_description, $course_id];

            $stmt_update_course = sqlsrv_prepare($link, $sql_update_course, $params_update_course);
            if ($stmt_update_course === false) {
                log_sqlsrv_errors("Подготовка запроса редактирования курса");
                $error_message = "Ошибка сервера при редактировании курса.";
            } else {
                if (sqlsrv_execute($stmt_update_course)) {
                    $success_message = "Курс отредактирован успешно!";
                } else {
                    log_sqlsrv_errors("Выполнение запроса редактирования курса");
                    $error_message = "Ошибка сервера при редактировании курса.";
                }
            }
        }

    } else {
        $error_message = "Неизвестное действие.";
    }
    header("Location: http://localhost/15/your_project_folder/teacher/reposts_Html.php");
}
?>