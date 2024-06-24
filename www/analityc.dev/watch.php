<?php
require_once __DIR__ . '/events.php';

$events = new Events();
$events->getAllEvents(true);