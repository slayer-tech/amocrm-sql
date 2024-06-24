<?php

require_once __DIR__ . '/src/constants.php';
require_once __DIR__ . '/src/AmoCrmV4Client.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/bd/connect.php';
$mysqli = new_connect();
try {

    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);

    // Получаем события для контактов и сделок
    $events = $amoV4Client->GETAll('events', [
        'filter[created_at]' => strtotime("-30 min"),
    ]);

    // Исключение если нет событий
    if (empty($events))
        throw new Exception('Нет новых событий', 404);

    // Перебираем полученные данные
    foreach ($events as $event) {
        $dateText = date('Y-m-d H:i', $event['created_at']); // Текстовое представление данных
        $time = $event['created_at']; // timestamp
        $type = $event['type']; // Тип события
        $entityId = $event['entity_id']; // id сущности
        $entity = $event['entity_type']; // Сущность (lead, contact)
        $json = json_encode($event, JSON_UNESCAPED_UNICODE); // Все данные событие в формате json
        // Запрос к базе данных
        $table = db_table;
        $sql = $mysqli->query("INSERT INTO $table (date, timestamp, type, entity, id_entity, json_data) 
            VALUES ('$dateText', '$time', '$type', '$entity', '$entityId', '$json')");

        // Если запись не создалась логируем
        if (!$sql)
            Write("db_requests", [
                "ConnectError" => 'Ошибка : (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error
            ]);
    }
} catch (Exception $ex) {
    // Логирование
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка: ' . $ex->getMessage() . '; Код ошибки:' . $ex->getCode(),
        'data' => ''
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    Write('main_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
} finally {
    // Разрываем соединение с Базой данных
    $mysqli->close();
}
