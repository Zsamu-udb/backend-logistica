<?php
declare(strict_types=1);

use App\conductores\Controllers\ConductorController;
use Slim\App;

return function (App $app): void {
    $app->get('/conductores',             [ConductorController::class, 'index']);
    $app->get('/conductores/{id}',        [ConductorController::class, 'show']);
    $app->post('/conductores',            [ConductorController::class, 'store']);
    $app->put('/conductores/{id}',        [ConductorController::class, 'update']);
    $app->patch('/conductores/{id}/estado', [ConductorController::class, 'cambiarEstado']);
};