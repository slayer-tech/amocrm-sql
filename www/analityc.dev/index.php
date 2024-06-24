<?php

require_once __DIR__ . '/contacts.php';
require_once __DIR__ . '/leads.php';
require_once __DIR__ . '/pipelines.php';
require_once __DIR__ . '/events.php';

$contacts = new Contacts();
$contacts->get_all();

$leads = new Leads();
$leads->get_all();

$pipelines = new Pipelines();
$pipelines->get_all();

$events = new Events();
$events->getAllEvents();