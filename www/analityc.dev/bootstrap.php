<?php

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessTokenInterface;

include_once __DIR__ . '/vendor/autoload.php';

$clientId = '27118436-e36e-42d5-ac9a-35ddebb3b57d';
$clientSecret = '9e4ub8AytgrTgbfz4FLsR0lvNeCB1TSiYF6TjwgTrVUMLSnEHh7wQfMVY7vPt3IL';
$redirectUri = 'https://apps.telecombg.ru/';
$subdomain = 'makhota'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/';
$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

include_once __DIR__ . '/token_actions.php';
include_once __DIR__ . '/error_printer.php';

$accessToken = getToken();

$apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
        function (AccessTokenInterface $accessToken, string $baseDomain) {
            saveToken(
                [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $baseDomain,
                ]
            );
        }
    );


function debug_print($data): void
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

function debug_var($data): void
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}