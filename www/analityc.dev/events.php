;<?php
use MCurl\Client;
ini_set('max_execution_time', '10000');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/script/src/constants.php';
require_once __DIR__ . '/script/src/functions.php';
require_once __DIR__ . '/script/bd/connect.php';
require_once __DIR__ . '/contacts.php';
require_once __DIR__ . '/leads.php';
include_once __DIR__ . '/bootstrap.php';

class Events {
    private $mysqli;

    public function __construct()
    {
        $this->mysqli = new_connect();
    }

    public function getAllEvents(bool $is_watching = false): void
    {
        global $apiClient;
        try {
            $client = new Client();
            $eventsService = $apiClient->events();
            $filter = new AmoCRM\Filters\EventsFilter();
            $filter->setEntity(['contact', 'lead']);
            $filter->setLimit(250);

            if ($is_watching) {
                $filter->setCreatedAt([time() - 35*60, time()]);
            }

            $events = $eventsService->get($filter);
            $eventsArray = $events->toArray();
            if (count($eventsArray) == 0) {
                echo 'Что то пошло не так! Нет Событий';
                Write('main_errors', 'Ошибка: Нет событий');
                return;
            }

            $this->prepareEvents($eventsArray);

            $i = 1;

            // Максимум запросов в секунду - 5, чтобы идти без потери времени, засечем время выполнения скрипта и будем отнимать од еденицы
            $sum = 0;
            while (true) {
                $time_start = microtime(true);
                $events = $eventsService->nextPage($events);
                $eventsArray = $events->toArray();
                if (count($eventsArray) == 0) break;
                $time_end = microtime(true);
                $sum += $time_end - $time_start;
                $this->prepareEvents($eventsArray);
                if ($i == 5) {
                    $i = 0;
                    print("Времени занято: " . $sum);
                    print("Памяти выделено: " . memory_get_usage());
                    if ($sum < 1.1) {
                        sleep(1.1-$sum);
                    }
                    $sum = 0;
                }
                $i++;
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
            $this->mysqli->close();
        }
    }

    private function prepareEvents(array $events): void
    {
        $eventsArray = [];
        foreach ($events as $event) {
            $ev['dateText'] = date('Y-m-d H:i', $event['created_at']); // Текстовое представление данных
            $ev['time'] = $event['created_at']; // timestamp
            $ev['type'] = $event['type']; // Тип события
            $ev['entityId'] = $event['entity_id']; // id сущности
            $ev['entity'] = $event['entity_type']; // Сущность (lead, contact)
            $ev['id'] = $event['id'];

            foreach ($event['value_after'] as $valueAfterArray)
            {
                foreach($valueAfterArray as $valueAfter) {
                    $ev['value_after'] = (($valueAfter['name'] ?? $valueAfter['text']) ?? $valueAfter['id']) ?? $valueAfter['sale'] ;
                    if ($ev === NULL) {
                        $ev['value_after'] = $valueAfter['entity']['id'];
                    }
                }
            }

            if ($event['value_before'] != []) {
                foreach ($event['value_before'] as $valueBeforeArray) {
                    foreach ($valueBeforeArray as $valueBefore) {
                        $ev['value_before'] = (($valueBefore['name'] ?? $valueBefore['text']) ?? $valueBefore['id']) ?? $valueBefore['sale'];
                        if ($ev === NULL) {
                            $ev['value_before'] = $valueBefore['entity']['id'];
                        }
                    }
                }
            }

            $eventsArray[] = $ev;
        }
        $this->saveEvent($eventsArray);
    }

    private function saveEvent(array $events, bool $watching = false): void
    {
        foreach ($events as $event) {
            /* create a prepared statement */
            $stmt = $this->mysqli->prepare("INSERT INTO `events_data` (id, date, timestamp, type, id_entity, entity, event_id, value_after, value_before)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            /* bind parameters */
            $stmt->bind_param("ssisissss", $event['id'], $event['dateText'],$event['time'],$event['type'],$event['entityId'], $event['entity'], $event['id'], $event['value_after'], $event['value_before']);

            if ($watching) {
                $type = 'upd';
                if (str_contains($event['type'], 'added')) {
                    $type = 'ins';
                }

                if ($event['entity'] == 'contact') {
                    (new Contacts())->get_one($event['entityId'], $type);
                } elseif ($event['entity'] == 'lead') {
                    (new Leads())->get_one($event['entityId'], $type);
                }
            }

            $sql = "";

            try {
                /* execute query */
                $sql = $stmt->execute();
            } catch (Exception $ex) {
                debug_print($ex);
                Write("db_requests", [
                    "Error" => 'Ошибка : (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error
                ]);
                echo $event['id'] . ' не был записан';
            }

            // Если запись не создалась логируем
            if (!$sql) {
                Write("db_requests", [
                    "ConnectError" => 'Ошибка : (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error
                ]);
                echo $event['id'] . ' не был записан';
            }
            echo $event['id'] . ' записан';
        }
    }
}