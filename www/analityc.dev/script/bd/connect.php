<?php
// Подключение к базе данных
require_once dirname(__DIR__) . '/src/constants.php';
function new_connect()
{
    $mysqli = new mysqli(db_host, db_user, db_password, db_base);

    // Если есть ошибка соединения, выводим её и убиваем подключение
    if ($mysqli->connect_error) {
        var_dump($mysqli->connect_errno);
        Write("db_connect", [
            "ConnectError" => 'Ошибка : (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error
        ]);
        $error = 'Ошибка : (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error;
        $mysqli->close();
        die($error);
    }
    return $mysqli;
}

