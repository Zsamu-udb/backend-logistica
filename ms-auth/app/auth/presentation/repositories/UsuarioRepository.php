<?php

declare(strict_types=1);

namespace App\auth\Presentation\Repositories;

use App\auth\Models\Usuario;

class UsuarioRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new Usuario();
    }

    public function findByCredencial(string $identificador): ?Usuario
    {
        return Usuario::where(function ($q) use ($identificador) {
            $q->where('usuario', $identificador)
                ->orWhere('correo', $identificador);
        })
            ->where('estado', 'activo')
            ->first();
    }

    public function findByToken(string $token): ?Usuario
    {
        return Usuario::where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();
    }

    public function activarSesion(Usuario $usuario, string $token): void
    {
        $usuario->token         = $token;
        $usuario->sesion_activa = true;
        $usuario->save();
    }

    public function cerrarSesion(Usuario $usuario): void
    {
        $usuario->token         = null;
        $usuario->sesion_activa = false;
        $usuario->save();
    }
}
