<?php

require 'vendor/autoload.php';

$factory = new LittleToDo\Factory();

/** @var LittleToDo\App $app */
$littleToDo = $factory->getApp();

$config = [
	'settings' => [
		'displayErrorDetails' => true,
	],
];
/** @var Slim\App $app */
$app = new Slim\App($config);

$app->get('/', function ($request, $response, $args) use($littleToDo) {
	return $littleToDo->render();
});

$app->get('/hello/{name}', function ($request, $response, $args) {
	return $response->getBody()->write("Hello, " . $args['name']);
});

$app->run();