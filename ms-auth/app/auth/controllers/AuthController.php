<?php

declare(strict_types=1);

namespace App\auth\Controllers;

use App\auth\Presentation\Repositories\UsuarioRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends AbstractController
{
    private UsuarioRepository $usuarioRepository;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $identificador = $data['usuario'] ?? $data['correo'] ?? null;
        $contrasena    = $data['contrasena'] ?? null;

        if (!$identificador || !$contrasena) {
            return $this->error($response, 'Usuario/correo y contraseña son obligatorios.', 400);
        }

        $usuario = $this->usuarioRepository->findByCredencial($identificador);

        if (!$usuario || $usuario->contrasena !== $contrasena) {
            return $this->error($response, 'Credenciales incorrectas.', 401);
        }

        $token = bin2hex(random_bytes(32));
        $this->usuarioRepository->activarSesion($usuario, $token);

        return $this->success($response, [
            'token'   => $token,
            'usuario' => [
                'id'      => $usuario->id,
                'nombre'  => $usuario->nombre,
                'correo'  => $usuario->correo,
                'usuario' => $usuario->usuario,
                'rol'     => $usuario->rol,
            ],
        ], 'Sesión iniciada correctamente.');
    }

    public function logout(Request $request, Response $response): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return $this->error($response, 'Token no proporcionado.', 401);
        }

        $usuario = $this->usuarioRepository->findByToken($token);

        if (!$usuario) {
            return $this->error($response, 'Token inválido.', 401);
        }

        $this->usuarioRepository->cerrarSesion($usuario);

        return $this->success($response, null, 'Sesión cerrada correctamente.');
    }

    public function validate(Request $request, Response $response): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return $this->error($response, 'Token no proporcionado.', 401);
        }

        $usuario = $this->usuarioRepository->findByToken($token);

        if (!$usuario) {
            return $this->error($response, 'Sesión inválida o expirada.', 401);
        }

        return $this->success($response, [
            'id'      => $usuario->id,
            'nombre'  => $usuario->nombre,
            'correo'  => $usuario->correo,
            'usuario' => $usuario->usuario,
            'rol'     => $usuario->rol,
        ], 'Token válido.');
    }
}
