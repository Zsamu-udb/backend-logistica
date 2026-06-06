<?php
declare(strict_types=1);

namespace App\vehiculos\Presentation\Repositories;

use App\vehiculos\Models\Vehiculo;
use Illuminate\Database\Eloquent\Collection;

class VehiculoRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new Vehiculo();
    }

    public function getAll(): Collection
    {
        return Vehiculo::orderBy('created_at', 'desc')->get();
    }

    public function filtrar(array $filtros): Collection
    {
        $query = Vehiculo::query();

        if (!empty($filtros['placa'])) {
            $query->where('placa', 'like', '%' . $filtros['placa'] . '%');
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo_vehiculo', 'like', '%' . $filtros['tipo'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function existePlaca(string $placa, ?int $excludeId = null): bool
    {
        $query = Vehiculo::where('placa', strtoupper($placa));
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function cambiarEstado(int $id, string $estado): ?Vehiculo
    {
        $vehiculo = $this->getById($id);
        if ($vehiculo) {
            $vehiculo->estado = $estado;
            $vehiculo->save();
        }
        return $vehiculo;
    }
}