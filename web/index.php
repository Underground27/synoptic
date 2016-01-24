<?php

require_once __DIR__.'/../vendor/autoload.php';

define('APP_DEBUG', true);

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

ErrorHandler::register();
ExceptionHandler::register(APP_DEBUG);

$app = new Silex\Application();

//Настройки фреймворка
$app['debug'] = APP_DEBUG;
$app['locale'] = 'ua';
$app['locale.supported'] = Array('ua', 'ru', 'en');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

//Подключение пространства имен контроллеров и моделей
use Synoptic\Controllers as Controllers;
use Synoptic\Models as Models;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'	=> 'pdo_mysql',
		'dbname'	=> 'synoptic',
		'host'		=> 'localhost',
		'user'		=> 'root',
		'password'	=> '',
		'charset'   => 'utf8'
    ),
));

//Инициализация моделей
$app['models.locations'] = $app->share(function() use ($app) {
    return new Models\LocationsModel($app['db'], $app['locale']);
});
$app['models.sources'] = $app->share(function() use ($app) {
    return new Models\SourcesModel($app['db'], $app['locale']);
});

//Инициализация контроллеров
$app->mount('/api/locations', new Controllers\LocationsController());
$app->mount('/api/sources', new Controllers\SourcesController());

$app->get('/', function () {
    return 'Hello!';
});

//Обработчик ошибок
$app->error(function (\Exception $e, $code) use ($app) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
			
        default:
			//В продакшене не выводить детальное описание ошибок
			if (!$app['debug']) {
				$message = 'Internal server error.';
				break;
			}
			
			$message = $e->getMessage();
    }
	
	return new JsonResponse( array(
		'status'=>'error',
		'message' => $message,
		'code' => $code
	));
});

$app->run();
