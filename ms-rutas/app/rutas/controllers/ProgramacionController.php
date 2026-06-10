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

    public function index(Request $request, Response $response): Response
    {
        $params  = $request->getQueryParams();
        $filtros = [
            'conductor_id' => $params['conductor_id'] ?? null,
            'vehiculo_id'  => $params['vehiculo_id']  ?? null,
            'estado'       => $params['estado']       ?? null,
            'fecha'        => $params['fecha']        ?? null,
        ];
        $programaciones = $this->programacionRepository->filtrar($filtros);
        return $this->success($response, $programaciones, 'Programaciones obtenidas correctamente.');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);
        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }
        return $this->success($response, $programacion, 'Programación obtenida correctamente.');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $required = ['conductor_id', 'vehiculo_id', 'ruta_id', 'fecha_salida', 'hora_salida', 'fecha_estimada_llegada'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        // Validar que fecha_estimada_llegada >= fecha_salida
        if (strtotime($data['fecha_estimada_llegada']) < strtotime($data['fecha_salida'])) {
            return $this->error($response, 'La fecha de llegada no puede ser anterior a la fecha de salida.', 400);
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

        // Cambiar estado del conductor y vehículo a en_ruta
        $this->programacionRepository->actualizarEstadoConductor((int) $data['conductor_id'], 'en_ruta');
        $this->programacionRepository->actualizarEstadoVehiculo((int) $data['vehiculo_id'], 'en_ruta');

        return $this->success($response, $programacion, 'Viaje programado correctamente.', 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);
        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        if (in_array($programacion->estado, ['finalizado', 'cancelado'])) {
            return $this->error($response, 'No se puede modificar un viaje finalizado o cancelado.', 400);
        }

        $data = $request->getParsedBody();

        // Validar fechas si vienen
        if (!empty($data['fecha_salida']) && !empty($data['fecha_estimada_llegada'])) {
            if (strtotime($data['fecha_estimada_llegada']) < strtotime($data['fecha_salida'])) {
                return $this->error($response, 'La fecha de llegada no puede ser anterior a la fecha de salida.', 400);
            }
        }

        if (!empty($data['conductor_id']) && (int) $data['conductor_id'] !== $programacion->conductor_id) {
            if (!$this->programacionRepository->conductorDisponible((int) $data['conductor_id'])) {
                return $this->error($response, 'El conductor no está disponible.', 409);
            }
        }

        if (!empty($data['vehiculo_id']) && (int) $data['vehiculo_id'] !== $programacion->vehiculo_id) {
            if (!$this->programacionRepository->vehiculoDisponible((int) $data['vehiculo_id'])) {
                return $this->error($response, 'El vehículo no está disponible.', 409);
            }
        }

        unset($data['estado']);
        $programacion = $this->programacionRepository->update($programacion->id, $data);
        return $this->success($response, $programacion, 'Programación actualizada correctamente.');
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $programacion = $this->programacionRepository->getById((int) $args['id']);
        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        $data   = $request->getParsedBody();
        $estado = $data['estado'] ?? null;
        $valid  = ['programado', 'en_transito', 'retrasado', 'finalizado', 'cancelado'];

        if (!in_array($estado, $valid)) {
            return $this->error($response, 'Estado no válido.', 400);
        }

        $programacion = $this->programacionRepository->cambiarEstado($programacion->id, $estado);
        return $this->success($response, $programacion, 'Estado actualizado correctamente.');
    }
}
