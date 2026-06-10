<?php

declare(strict_types=1);

namespace App\rutas\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractController
{
    protected function success(Response $response, mixed $data, string $message = 'OK', int $status = 200): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function error(Response $response, string $message, int $status = 400): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
