<?php

declare(strict_types=1);

use App\vehiculos\Controllers\VehiculoController;
use Slim\App;

return function (App $app): void {
    $app->get('/vehiculos',                [VehiculoController::class, 'index']);
    $app->get('/vehiculos/{id}',           [VehiculoController::class, 'show']);
    $app->post('/vehiculos',               [VehiculoController::class, 'store']);
    $app->put('/vehiculos/{id}',           [VehiculoController::class, 'update']);
    $app->patch('/vehiculos/{id}/estado',  [VehiculoController::class, 'cambiarEstado']);
};
