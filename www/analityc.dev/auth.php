<?php

//include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/token_actions.php';


$subdomain = 'makhota'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
    'client_id' => '27118436-e36e-42d5-ac9a-35ddebb3b57d',
    'client_secret' => '9e4ub8AytgrTgbfz4FLsR0lvNeCB1TSiYF6TjwgTrVUMLSnEHh7wQfMVY7vPt3IL',
    'grant_type' => 'authorization_code',
    'code' => 'def502003064e1f0f3a9b602bb64df8845cf1f8e328f933a2c3f57a9d0349e8acd9036d6f7eda1dfa3aa249738734acc09bdc44bb3632ccf33ce8cedce40d5664cf4a522cce22e870d45cd61427271b765c069878d83cf016a23c2fa0197b56240b374e4fc4efa1613d1d41e7d498d7b2c2b9d84f3442ee39b98264f61e6822a6fc819a5ae49f3e199312fc3c921713a282c718afeee8c2b4772b14dc86c1f67e65e7898ea41ca08a15889e311f473e08bf7c728b2325f80c8fdda59b94615e72e80e2fbf5ad9a979bce0e414261532a7e8e934341448d547d8297b6c2f29f7bd269084d70c103db7d2f607fd218389d182f16af637b8752362f548693fe712a17f17ef56085e308f6d58e0eee50ea67db1a721cce33ffdb41a3acc956e3fad9b4a0554eb8af89edd375d624b696534792b1f899e34acd8af1c504dfd650aedcff5de2504112ab7d630bdd5016b7d25a5422c57f4ae7fb837c3c19f44879b8391451e1397185d50b911fb9fc06b346f91baa42a9bf6ae72f23fffa34dfe4ff938905540fee5980e3a61c7af582ec6f36ee1045b16cde7299e7fa8a9a56726cae4448d72bf1e14f877f10151bbf57d6f3256e308b13fb264eb182e1fe1f6c5c2d9ba28d91a244b034ab817795c5aec1268565af97',
    'redirect_uri' => 'https://apps.telecombg.ru/',
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{
    /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
    if ($code < 200 || $code > 204) {
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(\Exception $e)
{
    echo '<pre>';
    print_r($e);
    echo '</pre>';
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

saveToken([
    'accessToken' => $access_token,
    'refreshToken' => $refresh_token,
    'expires' => $expires_in,
    'baseDomain' => 'makhota.amocrm.ru'
]);