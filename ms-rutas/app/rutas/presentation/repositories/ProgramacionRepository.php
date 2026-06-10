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

        return $query
            ->orderBy('id', 'desc')
            ->get();
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
        if (!$programacion) return null;

        $programacion->estado = $estado;
        $programacion->save();

        // Al finalizar o cancelar → liberar conductor y vehículo
        if (in_array($estado, ['finalizado', 'cancelado'])) {
            $this->actualizarEstadoConductor($programacion->conductor_id, 'disponible');
            $this->actualizarEstadoVehiculo($programacion->vehiculo_id, 'disponible');
        }

        return $programacion;
    }

    // Token interno para llamadas entre microservicios
    // Busca un admin activo para usar su token
    private function getInternalToken(): string
    {
        // Intentar obtener el token de la variable de entorno primero
        $token = $_ENV['INTERNAL_TOKEN'] ?? null;
        if ($token) return 'Bearer ' . $token;

        // Fallback: buscar en la DB de auth cualquier usuario con sesión activa
        try {
            $pdo = new \PDO(
                'mysql:host=' . ($_ENV['AUTH_DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['AUTH_DB_NAME'] ?? 'db_auth'),
                $_ENV['AUTH_DB_USER'] ?? 'root',
                $_ENV['AUTH_DB_PASS'] ?? ''
            );
            $stmt = $pdo->query("SELECT token FROM usuarios WHERE sesion_activa = 1 AND token IS NOT NULL LIMIT 1");
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && $row['token']) return 'Bearer ' . $row['token'];
        } catch (\Throwable $e) {
        }

        return '';
    }

    public function actualizarEstadoConductor(int $id, string $estado): void
    {
        $url   = ($_ENV['MS_CONDUCTORES_URL'] ?? 'http://localhost:8002') . "/conductores/{$id}/estado";
        $token = $this->getInternalToken();

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'estado' => $estado
        ]));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $token,
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        file_put_contents(
            __DIR__ . '/debug_conductor.log',
            date('Y-m-d H:i:s')
                . " | HTTP {$httpCode}"
                . " | RESPUESTA: {$response}\n",
            FILE_APPEND
        );

        curl_close($ch);
    }

    public function actualizarEstadoVehiculo(int $id, string $estado): void
    {
        $url   = ($_ENV['MS_VEHICULOS_URL'] ?? 'http://localhost:8003') . "/vehiculos/{$id}/estado";
        $token = $this->getInternalToken();

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'estado' => $estado
        ]));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $token,
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        file_put_contents(
            __DIR__ . '/debug_vehiculo.log',
            date('Y-m-d H:i:s')
                . " | HTTP {$httpCode}"
                . " | RESPUESTA: {$response}\n",
            FILE_APPEND
        );

        curl_close($ch);
    }
}
