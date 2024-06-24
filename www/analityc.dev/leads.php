<?php

ini_set('display_errors', 1);

require_once __DIR__ . '/script/src/functions.php';
require_once __DIR__ . '/script/bd/connect.php';
include_once __DIR__ . '/bootstrap.php';

class Leads
{
    private int $id;
    private string $name;
    private int|null $price;
    private string $last_name;
    private int $responsible_user_id;
    private int $group_id;
    private int $status_id;
    private int $pipeline_id;
    private int|null $loss_reason_id;
    private int $created_by;
    private int $updated_by;
    private int $created_at;
    private int|null $updated_at;
    private int|null $closed_at;
    private int|null $closest_task_at;
    private int $is_deleted;
    private ?mysqli $mysqli;

    public function __construct()
    {
        $this->mysqli = new_connect();
    }

    private function setAttrubute($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->price = $data['price'];
        $this->responsible_user_id = $data['responsible_user_id'];
        $this->group_id = $data['group_id'];
        $this->status_id = $data['status_id'];
        $this->pipeline_id = $data['pipeline_id'];
        $this->loss_reason_id = $data['loss_reason_id'];
        $this->created_by = $data['created_by'];
        $this->updated_by = $data['updated_by'];
        $this->closed_at = $data['closed_at'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
        $this->closest_task_at = $data['closest_task_at'];
        $this->is_deleted = $data['is_deleted'];
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
            $stmt = $this->mysqli->prepare("INSERT INTO `leads` (`id`,`name`, `price`, `responsible_user_id`, `group_id`, `status_id`, `pipeline_id`, `loss_reason_id`, `created_by`, `updated_by`, `closed_at`, `created_at`, `updated_at`, `closest_task_at`, `is_deleted`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?)");

            $stmt->bind_param("isiiiiiiiiiiiii", $this->id,$this->name,$this->price,$this->responsible_user_id,$this->group_id, $this->status_id, $this->pipeline_id, $this->loss_reason_id, $this->created_by, $this->updated_by, $this->closed_at, $this->created_at, $this->updated_at, $this->closest_task_at, $this->is_deleted);
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
            $sql = $this->mysqli->query("UPDATE 'leads' SET
                id = '$this->id', 
                name = '$this->name', 
                price = '$this->price', 
                responsible_user_id = $this->responsible_user_id, 
                group_id = $this->group_id, 
                status_id = $this->status_id, 
                pipeline_id = $this->pipeline_id, 
                loss_reason_id = $this->loss_reason_id, 
                created_by = $this->created_by,
                updated_by = $this->updated_by,
                closed_at = $this->closed_at,
                created_at = $this->created_at,
                updated_at = $this->updated_at,
                closest_task_at = $this->closest_task_at,
                is_deleted = $this->is_deleted
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
            $leadsService = $apiClient->leads();
            $leads = $leadsService->get();
            $leadsArray = $leads->toArray();
            debug_print($leadsArray);
            if (count($leadsArray) == 0) {
                echo 'Что то пошло не так! Нет контактов';
                Write('main_errors', 'Ошибка: Нет контактов');
                die();
            }
            foreach ($leadsArray as $lead) {
                $this->setAttrubute($lead);
                $this->save();
                $lf = new LeadFields($this->mysqli);
                $lf->prepareRow($lead['custom_fields_values'], $this->id);
                if (isset($lead['tags'])) {
                    $lt = new LeadTags($this->mysqli);
                    $lt->prepareRow($lead['tags'], $this->id);
                }
            }

            while (true) {
                $leads = $leadsService->nextPage($leads);
                $leadsArray = $leads->toArray();
                if (count($leadsArray) == 0) break;

                foreach ($leadsArray as $lead) {
                    $this->setAttrubute($lead);
                    $this->save();
                    $lf = new LeadFields($this->mysqli);
                    $lf->prepareRow($lead['custom_fields_values'], $this->id);
                    if (isset($lead['tags'])) {
                        $lt = new LeadTags($this->mysqli);
                        $lt->prepareRow($lead['tags'], $this->id);
                    }
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

    public function get_one($id, $type = 'ins')
    {
        global $apiClient;
        try {
            $leadsService = $apiClient->leads();
            $lead = $leadsService->getOne($id);
            debug_print($lead);
            if (!isset($lead)) {
                echo 'Что то пошло не так! Нет сделки';
                Write('main_errors', 'Ошибка: Нет сделки');
                die();
            }

            $this->setAttrubute($lead->toArray());
            $this->save($type);
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

class LeadFields
{
    private int $field_id;
    private string $name;
    private string $value;
    private int $id_lead;
    private ?mysqli $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function prepareRow(array $fields, int $id_lead): void
    {
        foreach ($fields as $field)
        {
            foreach ($field['values'] as $value)
            {
                $this->field_id = $field['field_id'];
                $this->name = $field['field_name'];
                $this->id_lead = $id_lead;
                $this->value = $value['value'];
                $this->saveRow();
            }
        }
    }

    private function saveRow(): void
    {
        $stmt = $this->mysqli->prepare("INSERT INTO `leads_fields` (`field_id`, `name`, `id_lead`, `value`)
            VALUES (?, ?, ?, ?)");

        /* bind params */
        $stmt->bind_param("isis", $this->field_id,$this->name, $this->id_lead, $this->value);
        $stmt->execute();
    }

}

class LeadTags
{
    private int $id;
    private string $name;
    private int $id_lead;
    private ?mysqli $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function prepareRow(array $fields, int $id_lead): void
    {
        foreach ($fields as $field)
        {
                $this->name = $field['name'];
                $this->id_lead = $id_lead;
                $this->id = $field['id'];
                $this->saveRow();
        }
    }

    private function saveRow(): void
    {
        $stmt = $this->mysqli->prepare("INSERT INTO `leads_tags` (`id`, `name`, `id_lead`)
            VALUES (?, ?, ?)");

        /* bind params */
        $stmt->bind_param("isi", $this->id,$this->name, $this->id_lead);
        $stmt->execute();
    }

}
