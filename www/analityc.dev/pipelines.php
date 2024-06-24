<?php

ini_set('display_errors', 1);

require_once __DIR__ . '/script/src/functions.php';
require_once __DIR__ . '/script/bd/connect.php';
include_once __DIR__ . '/bootstrap.php';

class Pipelines
{
    private int $id;
    private string $name;
    private int $sort;
    private int $is_main;
    private int $is_unsorted_on;
    private int $is_archive;
    private int $account_id;
    private ?mysqli $mysqli;

    public function __construct()
    {
        $this->mysqli = new_connect();
    }

    private function setAttrubute($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->sort = $data['sort'];
        $this->is_main = $data['is_main'];
        $this->is_unsorted_on = $data['is_unsorted_on'];
        $this->is_archive = $data['is_archive'];
        $this->account_id = $data['account_id'];
    }

    private function save($type = 'ins')
    {
        if ($type == 'ins') {
            $this->insert();
        } else {
            $this->update();
        }
    }

    private function insert()
    {
        try {
            $stmt = $this->mysqli->prepare("INSERT INTO `pipelines` (`id`,`name`, `sort`, `is_main`, `is_unsorted_on`, `is_archive`, `account_id`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isiiiii", $this->id,$this->name,$this->sort,$this->is_main,$this->is_unsorted_on, $this->is_archive, $this->account_id);
            $sql = $stmt->execute();

            // Если запись не создалась логируем
            if (!$sql)
                Write("db_requests", [
                    "ConnectError" => 'Ошибка : (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error
                ]);
        } catch (Exception $ex) {
            // Логирование
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка: ' . $ex->getMessage() . '; Код ошибки:' . $ex->getCode(),
                'data' => ''
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            Write('contact_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
        }
    }

    private function update()
    {
        try {
            $sql = $this->mysqli->query("UPDATE 'contacts' SET
                id = '$this->id', 
                name = '$this->name', 
                sort = '$this->sort', 
                is_main = $this->is_main, 
                is_unsorted_on = $this->is_unsorted_on, 
                is_archive = $this->is_archive, 
                account_id = $this->account_id, 
                WHERE crm_id = $this->crm_id");

            // Если запись не создалась логируем
            if (!$sql)
                Write("db_requests", [
                    "ConnectError" => 'Ошибка : (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error
                ]);
        } catch (Exception $ex) {
            // Логирование
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка: ' . $ex->getMessage() . '; Код ошибки:' . $ex->getCode(),
                'data' => ''
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            Write('contact_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
        }
    }

    public function get_all()
    {
        global $apiClient;
        try {
            $pipelinesService = $apiClient->pipelines();
            $pipelines = $pipelinesService->get();
            $pipelinesArray = $pipelines->toArray();
            if (count($pipelinesArray) == 0) {
                echo 'Что то пошло не так! Нет контактов';
                Write('main_errors', 'Ошибка: Нет воронок');
                die();
            }
            foreach ($pipelinesArray as $pipeline) {
                $this->setAttrubute($pipeline);
                $this->save();
                if (isset($pipeline['statuses'])) {
                    $ct = new Status($this->mysqli);
                    $ct->prepareRow($pipeline['statuses']);
                }
            }
        } catch (Exception $ex) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка: ' . $ex->getMessage() . '; Код ошибки:' . $ex->getCode(),
                'data' => ''
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            Write('contact_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
        } finally {
            $this->mysqli->close();
        }
    }

    public function get_one($id)
    {
        global $apiClient;
        try {
            $pipelinesService = $apiClient->pipelines();
            $pipeline = $pipelinesService->getOne($id);
            debug_print($pipeline);
            if (!isset($pipeline)) {
                echo 'Что то пошло не так! Нет воронки';
                Write('main_errors', 'Ошибка: Нет воронки');
                die();
            }

            $this->setAttrubute($pipeline->toArray());
            $this->save();
        } catch (Exception $ex) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка: ' . $ex->getMessage() . '; Код ошибки:' . $ex->getCode(),
                'data' => ''
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            Write('contact_errors', 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
        } finally {
            $this->mysqli->close();
        }
    }
}

class Status
{
    private int $id;
    private string $name;
    private int $sort;
    private int $is_editable;
    private int $type;
    private int $account_id;
    private ?mysqli $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function prepareRow(array $fields): void
    {
        foreach ($fields as $field)
        {
            $this->id = $field['id'];
            $this->name = $field['name'];
            $this->sort = $field['sort'];
            $this->is_editable = $field['is_editable'];
            $this->type = $field['type'];
            $this->account_id = $field['account_id'];
            $this->saveRow();
        }
    }

    private function saveRow(): void
    {
        try {
            $stmt = $this->mysqli->prepare("INSERT INTO `statuses` (`id`, `name`, `sort`, `is_editable`, `type`, `account_id`)
            VALUES (?, ?, ?, ?, ?, ?)");

            /* bind params */
            $stmt->bind_param("isiiii", $this->id,$this->name, $this->sort, $this->is_editable, $this->type, $this->account_id);
            $stmt->execute();
        }
        catch (Exception $e) {
        }
    }
}