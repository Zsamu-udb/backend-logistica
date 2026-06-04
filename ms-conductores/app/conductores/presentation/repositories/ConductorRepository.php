<?php
declare(strict_types=1);

namespace App\conductores\Presentation\Repositories;

use App\conductores\models\Conductor;
use Illuminate\Database\Eloquent\Collection;

class ConductorRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new Conductor();
    }

    public function getAll(): Collection
    {
        return Conductor::orderBy('created_at', 'desc')->get();
    }

    public function filtrar(array $filtros): Collection
    {
        $query = Conductor::query();

        if (!empty($filtros['documento'])) {
            $query->where('documento', 'like', '%' . $filtros['documento'] . '%');
        }

        if (!empty($filtros['licencia'])) {
            $query->where('numero_licencia', 'like', '%' . $filtros['licencia'] . '%');
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function existeDocumento(string $documento, ?int $excludeId = null): bool
    {
        $query = Conductor::where('documento', $documento);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function existeLicencia(string $licencia, ?int $excludeId = null): bool
    {
        $query = Conductor::where('numero_licencia', $licencia);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function existeCorreo(string $correo, ?int $excludeId = null): bool
    {
        $query = Conductor::where('correo', $correo);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function cambiarEstado(int $id, string $estado): ?Conductor
    {
        $conductor = $this->getById($id);
        if ($conductor) {
            $conductor->estado = $estado;
            $conductor->save();
        }
        return $conductor;
    }
}