<?php

require_once __DIR__ . '/script/src/functions.php';
require_once __DIR__ . '/script/bd/connect.php';
require_once __DIR__ . '/bootstrap.php';

$mysqli =  new_connect();

function generate_contacts_view($mysqli)
{
    $stmt = $mysqli->prepare("SELECT DISTINCT name FROM `contacts_fields`");
    $stmt->execute();

    $delivered_table_sql_tags = "SELECT id_contact as `contact_tags_id_contact`, JSON_ARRAYAGG(JSON_OBJECT('name', name)) as `tags` from `contacts_tags` GROUP BY `contact_tags_id_contact`";
    $delivered_table_sql_fields = "SELECT DISTINCT `contacts_fields`.id_contact as `contact_fields_id_contact`";
    $fields_column_names = "";
    $column_names = "";
    $fields = [];

    foreach ($stmt->get_result()->fetch_all() as $field_name) {
        $fields[] = $field_name[0];
        $column_names .= ", `" . $field_name[0] . '`';
        $fields_column_names .= ", contact_fields.`" . $field_name[0] . '`';
    }

    $delivered_table_sql_fields .= $column_names . " from contacts_fields";

    foreach ($fields as $field_name) {
        if ($field_name != '') {
            $delivered_table_sql_fields .= " left join (select id_contact, name as 'contact_fields_name', value as '" . $field_name . "' from contacts_fields where name='$field_name') `" . $field_name . "` using (id_contact)";
        }
    }

    $sql = "CREATE OR REPLACE VIEW v_contacts AS SELECT contacts.id, contacts.name, first_name, last_name, created_at, updated_at, contact_tags.tags$fields_column_names FROM `contacts` 
    LEFT JOIN ($delivered_table_sql_tags) as `contact_tags` ON `contact_tags`.`contact_tags_id_contact` = `contacts`.id 
    LEFT JOIN ($delivered_table_sql_fields) as `contact_fields` ON `contact_fields`.`contact_fields_id_contact` = `contacts`.id";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
}

function generate_leads_view($mysqli)
{
    $stmt = $mysqli->prepare("SELECT DISTINCT name FROM `leads_fields`");
    $stmt->execute();

    $delivered_table_sql_tags = "SELECT id_lead as `lead_tags_id_lead`, JSON_ARRAYAGG(JSON_OBJECT('name', name)) as `tags` from `leads_tags` GROUP BY `lead_tags_id_lead`";
    $delivered_table_sql_fields = "SELECT DISTINCT `leads_fields`.id_lead as `lead_fields_id_lead`";
    $column_names = "";
    $fields_column_names = "";
    $fields = [];

    foreach ($stmt->get_result()->fetch_all() as $field_name) {
        $fields[] = $field_name[0];
        $column_names .= ", `" . $field_name[0] . '`';
        $fields_column_names .= ", lead_fields.`" . $field_name[0] . '`';
    }

    $delivered_table_sql_fields .= $column_names . " from leads_fields";

    foreach ($fields as $field_name) {
        if ($field_name != '') {
            $delivered_table_sql_fields .= " left join (select id_lead, name as 'lead_fields_name', value as '" . $field_name . "' from leads_fields where name='$field_name') `" . $field_name . "` using (id_lead)";
        }
    }

    $sql = "CREATE OR REPLACE VIEW v_leads AS SELECT leads.id, leads.name, price, closed_at, created_at, updated_at, closest_task_at, is_deleted, lead_tags.tags$fields_column_names, pipelines.name as `pipeline_name`, statuses.name as `status_name` FROM `leads` 
    LEFT JOIN ($delivered_table_sql_tags) as `lead_tags` ON `lead_tags`.`lead_tags_id_lead` = `leads`.id
    LEFT JOIN ($delivered_table_sql_fields) as `lead_fields` ON `lead_fields`.`lead_fields_id_lead` = `leads`.id
    LEFT JOIN pipelines ON pipelines.id = leads.id
    LEFT JOIN statuses ON statuses.id = leads.id";

    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
}

function generate_events_view($mysqli)
{
    $sql = "CREATE OR REPLACE VIEW v_events AS SELECT events_data.id, date, type, entity, timestamp, value_before, value_after, contacts.name as 'contact_name', leads.name as 'lead_name' FROM events_data LEFT JOIN leads ON id_entity = leads.id LEFT JOIN contacts ON id_entity = contacts.id";
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
}

generate_contacts_view($mysqli);
generate_leads_view($mysqli);
generate_events_view($mysqli);