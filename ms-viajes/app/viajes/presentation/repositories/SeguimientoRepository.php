<?php
declare(strict_types=1);

namespace App\viajes\Presentation\Repositories;

use App\viajes\Models\SeguimientoViaje;
use Illuminate\Database\Eloquent\Collection;

class SeguimientoRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new SeguimientoViaje();
    }

    public function getAll(): Collection
    {
        return SeguimientoViaje::orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();
    }

    public function filtrar(array $filtros): Collection
    {
        $query = SeguimientoViaje::query();

        if (!empty($filtros['programacion_viaje_id'])) {
            $query->where('programacion_viaje_id', $filtros['programacion_viaje_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();
    }

    public function getHistorial(int $programacionId): Collection
    {
        return SeguimientoViaje::where('programacion_viaje_id', $programacionId)
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();
    }

    public function getUltimoEstado(int $programacionId): ?SeguimientoViaje
    {
        return SeguimientoViaje::where('programacion_viaje_id', $programacionId)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->first();
    }

    public function registrar(int $programacionId, string $estado, string $novedad): SeguimientoViaje
    {
        return $this->create([
            'programacion_viaje_id' => $programacionId,
            'fecha'                 => date('Y-m-d'),
            'hora'                  => date('H:i:s'),
            'estado'                => $estado,
            'novedad'               => $novedad,
        ]);
    }
}