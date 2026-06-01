<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/database.php';

$cors      = require __DIR__ . '/../app/middlewares/CorsMiddleware.php';
$auth      = require __DIR__ . '/../app/middlewares/AuthMiddleware.php';
$endpoints = require __DIR__ . '/../app/viajes/presentation/pouters/endpoints.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$cors($app);
$auth($app);
$endpoints($app);

$app->run();