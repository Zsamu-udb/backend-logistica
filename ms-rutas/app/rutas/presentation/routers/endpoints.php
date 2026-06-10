<?php
declare(strict_types=1);

use App\rutas\Controllers\RutaController;
use App\rutas\Controllers\ProgramacionController;
use Slim\App;

return function (App $app): void {
    // Rutas
    $app->get('/rutas',       [RutaController::class, 'index']);
    $app->get('/rutas/{id}',  [RutaController::class, 'show']);
    $app->post('/rutas',      [RutaController::class, 'store']);
    $app->put('/rutas/{id}',  [RutaController::class, 'update']);

    // Programaciones
    $app->get('/programaciones',              [ProgramacionController::class, 'index']);
    $app->get('/programaciones/{id}',         [ProgramacionController::class, 'show']);
    $app->post('/programaciones',             [ProgramacionController::class, 'store']);
    $app->put('/programaciones/{id}',         [ProgramacionController::class, 'update']);
    $app->patch('/programaciones/{id}/estado',[ProgramacionController::class, 'cambiarEstado']);
};