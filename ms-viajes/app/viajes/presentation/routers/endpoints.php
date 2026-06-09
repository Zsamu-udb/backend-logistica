<?php
declare(strict_types=1);

use App\viajes\Controllers\ViajeController;
use Slim\App;

return function (App $app): void {
    // Seguimientos
    $app->get('/seguimientos',                         [ViajeController::class, 'index']);
    $app->get('/seguimientos/{id}',                    [ViajeController::class, 'show']);
    $app->get('/seguimientos/historial/{programacionId}', [ViajeController::class, 'historial']);
    $app->post('/seguimientos',                        [ViajeController::class, 'store']);

    // Acciones sobre viajes
    $app->post('/viajes/{programacionId}/iniciar',     [ViajeController::class, 'iniciar']);
    $app->post('/viajes/{programacionId}/finalizar',   [ViajeController::class, 'finalizar']);
    $app->post('/viajes/{programacionId}/cancelar',    [ViajeController::class, 'cancelar']);
    $app->post('/viajes/{programacionId}/retrasar',    [ViajeController::class, 'retrasar']);
};