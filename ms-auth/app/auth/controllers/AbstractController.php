<?php

declare(strict_types=1);

namespace App\auth\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

    protected function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (!$header) return null;
        // Acepta "Bearer token" o solo "token"
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }
        return $header;
    }
}
