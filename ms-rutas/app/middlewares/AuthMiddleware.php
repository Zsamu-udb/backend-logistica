<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

return function ($app) {
    $app->add(function (Request $request, Handler $handler) {
        $token = $request->getHeaderLine('Authorization');

        if (!$token) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token no proporcionado.'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $ch = curl_init('http://localhost:8001/validate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No autorizado. Sesión inválida.'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    });
};
