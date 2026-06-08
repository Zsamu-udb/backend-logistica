<?php
declare(strict_types=1);

namespace App\rutas\Presentation\Repositories;

use App\rutas\Models\ProgramacionViaje;
use Illuminate\Database\Eloquent\Collection;

class ProgramacionRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new ProgramacionViaje();
    }

    public function getAll(): Collection
    {
        return ProgramacionViaje::orderBy('created_at', 'desc')->get();
    }

    public function filtrar(array $filtros): Collection
    {
        $query = ProgramacionViaje::query();

        if (!empty($filtros['conductor_id'])) {
            $query->where('conductor_id', $filtros['conductor_id']);
        }

        if (!empty($filtros['vehiculo_id'])) {
            $query->where('vehiculo_id', $filtros['vehiculo_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha'])) {
            $query->where('fecha_salida', $filtros['fecha']);
        }

        return $query->orderBy('fecha_salida', 'desc')->get();
    }

    public function conductorDisponible(int $conductorId): bool
    {
        return !ProgramacionViaje::where('conductor_id', $conductorId)
            ->whereIn('estado', ['programado', 'en_transito', 'retrasado'])
            ->exists();
    }

    public function vehiculoDisponible(int $vehiculoId): bool
    {
        return !ProgramacionViaje::where('vehiculo_id', $vehiculoId)
            ->whereIn('estado', ['programado', 'en_transito', 'retrasado'])
            ->exists();
    }

    public function cambiarEstado(int $id, string $estado): ?ProgramacionViaje
    {
        $programacion = $this->getById($id);
        if ($programacion) {
            $programacion->estado = $estado;
            $programacion->save();
        }
        return $programacion;
    }
}