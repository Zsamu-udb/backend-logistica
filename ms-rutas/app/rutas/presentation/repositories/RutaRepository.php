<?php

declare(strict_types=1);

namespace App\rutas\Presentation\Repositories;

use App\rutas\Models\Ruta;
use Illuminate\Database\Eloquent\Collection;

class RutaRepository extends AbstractRepository
{
    public function __construct()
    {
        $this->model = new Ruta();
    }

    public function getAll(): Collection
    {
        return Ruta::orderBy('created_at', 'desc')->get();
    }

    public function filtrar(array $filtros): Collection
    {
        $query = Ruta::query();

        if (!empty($filtros['ciudad'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('ciudad_origen',  'like', '%' . $filtros['ciudad'] . '%')
                    ->orWhere('ciudad_destino', 'like', '%' . $filtros['ciudad'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function existeRuta(string $origen, string $destino, ?int $excludeId = null): bool
    {
        $query = Ruta::where('ciudad_origen',  $origen)
            ->where('ciudad_destino', $destino);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}
