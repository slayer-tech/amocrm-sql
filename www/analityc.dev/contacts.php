<?php

ini_set('display_errors', 1);

require_once __DIR__ . '/script/src/functions.php';
require_once __DIR__ . '/script/bd/connect.php';
include_once __DIR__ . '/bootstrap.php';

class Contacts
{
    private int $crm_id;
    private string|null $name;
    private string|null $first_name;
    private string|null $last_name;
    private int $responsible_user_id;
    private int $group_id;
    private int $created_by;
    private int $updated_by;
    private int $created_at;
    private int $updated_at;
    private ?mysqli $mysqli;

    public function __construct()
    {
        $this->mysqli = new_connect();
    }

    private function setAttrubute($data)
    {
        debug_print($data);
        $this->crm_id = $data['id'];
        $this->name = $data['name'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
        $this->responsible_user_id = $data['responsible_user_id'];
        $this->group_id = $data['group_id'];
        $this->created_by = $data['created_by'];
        $this->updated_by = $data['updated_by'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
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
            $stmt = $this->mysqli->prepare("INSERT INTO `contacts` (`id`,`crm_id`, `name`, `first_name`, `last_name`, `responsible_user_id`, `group_id`, `created_by`, `updated_by`, `created_at`, `updated_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))");

            $stmt->bind_param("iisssiiiiii", $this->crm_id,$this->crm_id,$this->name,$this->first_name,$this->last_name, $this->responsible_user_id, $this->group_id, $this->created_by, $this->updated_by, $this->created_at, $this->updated_at);
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
            $sql = $this->mysqli->query('UPDATE `contacts` SET
                name = "'.$this->name.'", 
                first_name = "'.$this->first_name.'", 
                last_name = "'.$this->last_name.'", 
                responsible_user_id = "'.$this->responsible_user_id.'", 
                group_id = "'.$this->group_id.'", 
                created_by = "'.$this->created_by.'", 
                updated_by = "'.$this->updated_by.'", 
                created_at = "'.$this->created_at.'", 
                updated_at = "'.$this->updated_at.'"
                WHERE crm_id = "'.$this->crm_id.'"');

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
            $contactsService = $apiClient->contacts();
            $contacts = $contactsService->get();
            $contactsArray = $contacts->toArray();
            debug_print($contactsArray);
            if (count($contactsArray) == 0) {
                echo 'Что то пошло не так! Нет контактов';
                Write('main_errors', 'Ошибка: Нет контактов');
                die();
            }
            foreach ($contactsArray as $contact) {
                $this->setAttrubute($contact);
                $this->save();
                $cf = new ContactFields($this->mysqli);
                $cf->prepareRow($contact['custom_fields_values'], $this->crm_id);
                if (isset($contact['tags'])) {
                    $ct = new ContactTags($this->mysqli);
                    $ct->prepareRow($contact['tags'], $this->crm_id);
                }
            }

            while (true) {
                $contacts = $contactsService->nextPage($contacts);
                $contactsArray = $contacts->toArray();
                if ($contactsArray == NULL or count($contactsArray) == 0) break;
                foreach ($contactsArray as $contact) {
                    $this->setAttrubute($contact);
                    $this->save();
                    $cf = new ContactFields($this->mysqli);
                    if (isset($contact['custom_fields_values'])) {
                        $cf->prepareRow($contact['custom_fields_values'], $this->crm_id);
                    }
                    if (isset($contact['tags'])) {
                        $ct = new ContactTags($this->mysqli);
                        $ct->prepareRow($contact['tags'], $this->crm_id);
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
            $contactsService = $apiClient->contacts();
            $contact = $contactsService->getOne($id);
            debug_print($contact);
            if (!isset($contact)) {
                echo 'Что то пошло не так! Нет контакта';
                Write('main_errors', 'Ошибка: Нет контакта');
                die();
            }

            $this->setAttrubute($contact->toArray());
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

class ContactFields
{
    private int $field_id;
    private string $name;
    private string $value;
    private int $contact_id;
    private ?mysqli $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function prepareRow(array $fields, int $contract_id): void
    {
        foreach ($fields as $field)
        {
            foreach ($field['values'] as $value)
            {
                $this->field_id = $field['field_id'];
                $this->name = $field['field_name'];
                $this->contact_id = $contract_id;
                $this->value = $value['value'];
                $this->saveRow();
            }
        }
    }

    private function saveRow(): void
    {
        $stmt = $this->mysqli->prepare("INSERT INTO `contacts_fields` (`field_id`, `name`, `id_contact`, `value`)
            VALUES (?, ?, ?, ?)");

        /* bind params */
        $stmt->bind_param("isis", $this->field_id,$this->name, $this->contact_id, $this->value);
        $stmt->execute();
    }

}

class ContactTags
{
    private int $id;
    private string $name;
    private int $id_contact;
    private ?mysqli $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function prepareRow(array $fields, int $id_contact): void
    {
        foreach ($fields as $field)
        {
            $this->name = $field['name'];
            $this->id_contact = $id_contact;
            $this->id = $field['id'];
            $this->saveRow();
        }
    }

    private function saveRow(): void
    {
        $stmt = $this->mysqli->prepare("INSERT INTO `contacts_tags` (`id`, `name`, `id_contact`)
            VALUES (?, ?, ?)");

        /* bind params */
        $stmt->bind_param("isi", $this->id,$this->name, $this->id_contact);
        $stmt->execute();
    }

}
