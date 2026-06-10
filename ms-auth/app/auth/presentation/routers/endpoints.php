<?php

declare(strict_types=1);

use App\auth\Controllers\AuthController;
use Slim\App;

return function (App $app): void {
    $app->post('/login',   [AuthController::class, 'login']);
    $app->post('/logout',  [AuthController::class, 'logout']);
    $app->get('/validate', [AuthController::class, 'validate']);
};
