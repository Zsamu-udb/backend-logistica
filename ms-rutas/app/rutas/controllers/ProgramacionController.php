<?php
declare(strict_types=1);

namespace App\rutas\Controllers;

use App\rutas\Presentation\Repositories\ProgramacionRepository;
use App\rutas\Presentation\Repositories\RutaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProgramacionController extends AbstractController
{
    private ProgramacionRepository $programacionRepository;
    private RutaRepository $rutaRepository;

    public function __construct()
    {
        $this->programacionRepository = new ProgramacionRepository();
        $this->rutaRepository         = new RutaRepository();
    }

    // ── GET /programaciones ───────────────────────────────────
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $filtros = [
            'conductor_id' => $params['conductor_id'] ?? null,
            'vehiculo_id'  => $params['vehiculo_id']  ?? null,
            'estado'       => $params['estado']       ?? null,
            'fecha'        => $params['fecha']        ?? null,
        ];

        $programaciones = $this->programacionRepository->filtrar($filtros);

        return $this->success($response, $programaciones, 'Programaciones obtenidas correctamente.');
    }

    // ── GET /programaciones/{id} ──────────────────────────────
    public function show(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        return $this->success($response, $programacion, 'Programación obtenida correctamente.');
    }

    // ── POST /programaciones ──────────────────────────────────
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $required = [
            'conductor_id', 'vehiculo_id', 'ruta_id',
            'fecha_salida', 'hora_salida', 'fecha_estimada_llegada'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        // Validar que la ruta exista
        $ruta = $this->rutaRepository->getById((int) $data['ruta_id']);
        if (!$ruta) {
            return $this->error($response, 'La ruta especificada no existe.', 404);
        }

        // Validar disponibilidad conductor
        if (!$this->programacionRepository->conductorDisponible((int) $data['conductor_id'])) {
            return $this->error($response, 'El conductor no está disponible, tiene un viaje activo.', 409);
        }

        // Validar disponibilidad vehículo
        if (!$this->programacionRepository->vehiculoDisponible((int) $data['vehiculo_id'])) {
            return $this->error($response, 'El vehículo no está disponible, tiene un viaje activo.', 409);
        }

        $programacion = $this->programacionRepository->create([
            'conductor_id'           => (int) $data['conductor_id'],
            'vehiculo_id'            => (int) $data['vehiculo_id'],
            'ruta_id'                => (int) $data['ruta_id'],
            'fecha_salida'           => $data['fecha_salida'],
            'hora_salida'            => $data['hora_salida'],
            'fecha_estimada_llegada' => $data['fecha_estimada_llegada'],
            'observaciones'          => $data['observaciones'] ?? null,
            'estado'                 => 'programado',
        ]);

        return $this->success($response, $programacion, 'Viaje programado correctamente.', 201);
    }

    // ── PUT /programaciones/{id} ──────────────────────────────
    // Solo para reprogramar — fechas, conductor, vehículo, observaciones
    // ms-viajes NO usa este método, usa PATCH /programaciones/{id}/estado
    public function update(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        // No se puede reprogramar si ya terminó o se canceló
        if (in_array($programacion->estado, ['finalizado', 'cancelado'])) {
            return $this->error($response, 'No se puede modificar un viaje finalizado o cancelado.', 400);
        }

        $data = $request->getParsedBody();

        // Validar disponibilidad conductor si cambia
        if (!empty($data['conductor_id']) && (int) $data['conductor_id'] !== $programacion->conductor_id) {
            if (!$this->programacionRepository->conductorDisponible((int) $data['conductor_id'])) {
                return $this->error($response, 'El conductor no está disponible.', 409);
            }
        }

        // Validar disponibilidad vehículo si cambia
        if (!empty($data['vehiculo_id']) && (int) $data['vehiculo_id'] !== $programacion->vehiculo_id) {
            if (!$this->programacionRepository->vehiculoDisponible((int) $data['vehiculo_id'])) {
                return $this->error($response, 'El vehículo no está disponible.', 409);
            }
        }

        // Ignorar el campo estado en este método — no se cambia aquí
        unset($data['estado']);

        $programacion = $this->programacionRepository->update($programacion->id, $data);

        return $this->success($response, $programacion, 'Programación actualizada correctamente.');
    }

    // ── PATCH /programaciones/{id}/estado ─────────────────────
    // EXCLUSIVO para ms-viajes — sincroniza el estado sin validaciones de negocio
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        $data   = $request->getParsedBody();
        $estado = $data['estado'] ?? null;

        $estadosValidos = ['programado', 'en_transito', 'retrasado', 'finalizado', 'cancelado'];

        if (!in_array($estado, $estadosValidos)) {
            return $this->error($response, 'Estado no válido.', 400);
        }

        $programacion = $this->programacionRepository->cambiarEstado($programacion->id, $estado);

        return $this->success($response, $programacion, 'Estado de programación actualizado correctamente.');
    }
}