<?php
define('DB_HOST', 'mysql');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASSWORD', '1234567890');
define('DB_NAME', 'events');
define('DB_TABLE_VERSIONS', 'versions');

// Подключаемся к базе данных
function connectDB()
{
    $errorMessage = 'Невозможно подключиться к серверу базы данных';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if (!$conn)
        throw new Exception($errorMessage);
    else {
        $query = $conn->query('set names utf8');
        if (!$query)
            throw new Exception($errorMessage);
        else
            return $conn;
    }
}

// Получаем список файлов для миграций
function getMigrationFiles($conn)
{
    // Находим папку с миграциями
    $sqlFolder = str_replace('\\', '/', realpath(dirname(__FILE__)) . '/');
    // Получаем список всех sql-файлов
    $allFiles = glob($sqlFolder . '*.sql');
//    $allFiles = [];
//    $allInDir = scandir(__DIR__);
//    foreach ($allInDir as $file) {
//        if ($file == '.' || $file == '..') continue;
//        if ($file == 'migration.php') continue;
//        $allFiles[] = $file;
//    }
//    echo '<pre>';
//    print_r($allFiles);
//    echo '</pre>';
//    die();

    // Проверяем, есть ли таблица versions 
    // Так как versions создается первой, то это равносильно тому, что база не пустая
    $query = sprintf('show tables from `%s` like "%s"', DB_NAME, DB_TABLE_VERSIONS);
    $data = $conn->query($query);
    $firstMigration = !$data->num_rows;

    // Первая миграция, возвращаем все файлы из папки sql
    if ($firstMigration) {
        return $allFiles;
    }

    // Ищем уже существующие миграции
    $versionsFiles = array();
    // Выбираем из таблицы versions все названия файлов
    $query = sprintf('select `name` from `%s`', DB_TABLE_VERSIONS);
    $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    // Загоняем названия в массив $versionsFiles
    // Не забываем добавлять полный путь к файлу
    foreach ($data as $row) {
        array_push($versionsFiles, $sqlFolder . $row['name']);
    }

    // Возвращаем файлы, которых еще нет в таблице versions
    return array_diff($allFiles, $versionsFiles);
}


// Накатываем миграцию файла
function migrate($conn, $file)
{
    // Формируем команду выполнения mysql-запроса из внешнего файла
//    $command = sprintf('mysql -u%s -p%s -h %s -D %s < %s', DB_USER, DB_PASSWORD, DB_HOST, DB_NAME, $file);
    // Выполняем shell-скрипт
//    shell_exec($command);
    try {
        $sql = file_get_contents($file);
        $conn->query($sql);
    } catch (Exception $ex) {
        print_r($ex->getMessage());
    }

    // Вытаскиваем имя файла, отбросив путь
    $baseName = basename($file);
    // Формируем запрос для добавления миграции в таблицу versions
    $query = sprintf('insert into `%s` (`name`) values("%s")', DB_TABLE_VERSIONS, $baseName);
    // Выполняем запрос
    $conn->query($query);
}

// Стартуем

// Подключаемся к базе
$conn = connectDB();

// Получаем список файлов для миграций за исключением тех, которые уже есть в таблице versions
$files = getMigrationFiles($conn);

// Проверяем, есть ли новые миграции
if (empty($files)) {
    echo 'База данных в актуальном состоянии.';
} else {
    echo 'Начинаем миграцию...<br><br>';
    // Накатываем миграцию для каждого файла
    foreach ($files as $file) {
        migrate($conn, $file);
        // Выводим название выполненного файла
        echo basename($file) . '<br>';
    }
    echo '<br>Миграция завершена.';
}