<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'];


// Подключение к базе данных
require_once '../includes/db_connect.php'; // убедитесь, что путь к файлу подключения корректен


// ID преподавателя (в реальном приложении может передаваться через GET/POST или определяться по сессии)
$teacher_id = $user_id; // замените на актуальный способ получения ID


// Запрос для получения данных преподавателя
$sql = "SELECT фио, почта, номер_телефона FROM Преподаватели WHERE id_преподавателя = ?";
$params = [$teacher_id];


$stmt = sqlsrv_query($link, $sql, $params);
if ($stmt === false) {
    die("Ошибка выполнения запроса: " . print_r(sqlsrv_errors(), true));
}

$teacher = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Освобождаем ресурсы
sqlsrv_free_stmt($stmt);
sqlsrv_close($link);

// Если преподаватель не найден, задаём значения по умолчанию
if (!$teacher) {
    $teacher = [
        'фио' => 'Не указано',
        'почта' => 'Не указан',
        'номер_телефона' => 'Не указан'
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="profile-card.css">
</head>

<body>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            text-align: center;
        }

        .profile-image {
            padding: 30px;
            background: #f8f9fa;
        }

        .profile-info {
            padding: 25px;
        }

        .profile-name {
            color: #333;
            margin-bottom: 5px;
            font-size: 24px;
        }

        .profile-position {
            color: #6c757d;
            margin-bottom: 20px;
            font-style: italic;
        }

        .profile-details {
            text-align: left;
            margin-top: 20px;
        }

        .profile-details p {
            margin-bottom: 10px;
            color: #555;
        }

        .profile-actions {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Адаптивность */
        @media (max-width: 480px) {
            .profile-card {
                max-width: 95%;
            }

            .profile-actions {
                flex-direction: row;
                justify-content: space-between;
            }
        }
    </style>
    <div class="profile-card">
        <div class="profile-image">
            <svg fill="#000000" xml:space="preserve" viewBox="0 0 64 64" height="70px" width="70px"
                xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" id="Layer_1"
                version="1.0">
                <g stroke-width="0" id="SVGRepo_bgCarrier"></g>
                <g stroke-linejoin="round" stroke-linecap="round" id="SVGRepo_tracerCarrier"></g>
                <g id="SVGRepo_iconCarrier">
                    <g>
                        <path
                            d="M18,12c0-5.522,4.478-10,10-10h8c5.522,0,10,4.478,10,10v7c0-3.313-2.687-6-6-6h-6c-2.209,0-4-1.791-4-4 c0-0.553-0.447-1-1-1s-1,0.447-1,1c0,2.209-1.791,4-4,4c-3.313,0-6,2.687-6,6V12z"
                            fill="#506C7F"></path>
                        <path
                            d="M62,60c0,1.104-0.896,2-2,2H4c-1.104,0-2-0.896-2-2v-8c0-1.104,0.447-2.104,1.172-2.828l-0.004-0.004 c4.148-3.343,8.896-5.964,14.046-7.714C20.869,45.467,26.117,48,31.973,48c5.862,0,11.115-2.538,14.771-6.56 c5.167,1.75,9.929,4.376,14.089,7.728l-0.004,0.004C61.553,49.896,62,50.896,62,52V60z"
                            fill="#7d988a"></path>
                        <g>
                            <path
                                d="M32,42c-2.853,0-5.502-0.857-7.715-2.322c-1.675,0.283-3.325,0.638-4.934,1.097 C22.602,43.989,27.041,46,31.973,46c4.938,0,9.383-2.017,12.634-5.238c-1.595-0.454-3.231-0.803-4.892-1.084 C37.502,41.143,34.853,42,32,42z"
                                fill="#F9EBB2"></path>
                            <path
                                d="M46,22h-1c-0.553,0-1-0.447-1-1v-1v-1c0-2.209-1.791-4-4-4h-6c-2.088,0-3.926-1.068-5-2.687 C27.926,13.932,26.088,15,24,15c-2.209,0-4,1.791-4,4v1v1c0,0.553-0.447,1-1,1h-1c-0.553,0-1,0.447-1,1v2c0,0.553,0.447,1,1,1h1 c0.553,0,1,0.447,1,1v1c0,6.627,5.373,12,12,12s12-5.373,12-12v-1c0-0.553,0.447-1,1-1h1c0.553,0,1-0.447,1-1v-2 C47,22.447,46.553,22,46,22z"
                                fill="#F9EBB2"></path>
                        </g>
                        <path
                            d="M62.242,47.758l0.014-0.014c-5.847-4.753-12.84-8.137-20.491-9.722C44.374,35.479,46,31.932,46,28 c1.657,0,3-1.343,3-3v-2c0-0.886-0.391-1.673-1-2.222V12c0-6.627-5.373-12-12-12h-8c-6.627,0-12,5.373-12,12v8.778 c-0.609,0.549-1,1.336-1,2.222v2c0,1.657,1.343,3,3,3c0,3.932,1.626,7.479,4.236,10.022c-7.652,1.586-14.646,4.969-20.492,9.722 l0.014,0.014C0.672,48.844,0,50.344,0,52v8c0,2.211,1.789,4,4,4h56c2.211,0,4-1.789,4-4v-8C64,50.344,63.328,48.844,62.242,47.758z M18,12c0-5.522,4.478-10,10-10h8c5.522,0,10,4.478,10,10v7c0-3.313-2.687-6-6-6h-6c-2.209,0-4-1.791-4-4c0-0.553-0.447-1-1-1 s-1,0.447-1,1c0,2.209-1.791,4-4,4c-3.313-6-6h-6c-2.209,0-4-1.791-4-4c0-0.553-0.447-1-1-1s-1,0.447-1,1c0,2.209-1.791,4-4,4c-3.313,0-6,2.687-6,6V12z"
                            fill="#506C7F">
                        </path>
                    </g>
            </svg>
        </div>

        <div class="profile-container">
            <div class="profile-info">
                <h2 class="profile-name"><?php echo htmlspecialchars($teacher['фио']); ?></h2>
                <p class="profile-position">Преподователь</p>
                <div class="profile-details">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['почта']); ?></p>
                    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($teacher['номер_телефона']); ?></p>
                </div>
            </div>

            <div class="profile-actions">

                <a href="http://localhost/переделанная/15/your_project_folder/teacher/RedecktProf.php">


                    <button class="btn btn-primary">Редактировать профиль</button>
                </a>


                <a href="http://localhost/переделанная/15/your_project_folder/teacher/asset_srt.php">
                    <button class="btn btn-secondary">Главное меню</button>
                </a>
            </div>
        </div>
    </div>
</body>

</html>