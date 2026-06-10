<?php

declare(strict_types=1);

namespace App\vehiculos\Controllers;

use App\vehiculos\Presentation\Repositories\VehiculoRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VehiculoController extends AbstractController
{
    private VehiculoRepository $vehiculoRepository;

    public function __construct()
    {
        $this->vehiculoRepository = new VehiculoRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $filtros = [
            'placa'  => $params['placa']  ?? null,
            'estado' => $params['estado'] ?? null,
            'tipo'   => $params['tipo']   ?? null,
        ];

        $vehiculos = $this->vehiculoRepository->filtrar($filtros);

        return $this->success($response, $vehiculos, 'Vehículos obtenidos correctamente.');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $vehiculo = $this->vehiculoRepository->getById((int) $args['id']);

        if (!$vehiculo) {
            return $this->error($response, 'Vehículo no encontrado.', 404);
        }

        return $this->success($response, $vehiculo, 'Vehículo obtenido correctamente.');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validar campos obligatorios
        $required = ['placa', 'tipo_vehiculo', 'capacidad_carga', 'modelo', 'marca'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        // Validar placa duplicada
        if ($this->vehiculoRepository->existePlaca($data['placa'])) {
            return $this->error($response, 'Ya existe un vehículo con esa placa.', 409);
        }

        // Validar capacidad mayor a cero
        if ((float) $data['capacidad_carga'] <= 0) {
            return $this->error($response, 'La capacidad de carga debe ser mayor a cero.', 400);
        }

        $vehiculo = $this->vehiculoRepository->create([
            'placa'           => strtoupper($data['placa']),
            'tipo_vehiculo'   => $data['tipo_vehiculo'],
            'capacidad_carga' => (float) $data['capacidad_carga'],
            'modelo'          => $data['modelo'],
            'marca'           => $data['marca'],
            'estado'          => $data['estado'] ?? 'disponible',
        ]);

        return $this->success($response, $vehiculo, 'Vehículo creado correctamente.', 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $vehiculo = $this->vehiculoRepository->getById((int) $args['id']);

        if (!$vehiculo) {
            return $this->error($response, 'Vehículo no encontrado.', 404);
        }

        $data = $request->getParsedBody();

        // Validar placa duplicada excluyendo el actual
        if (!empty($data['placa'])) {
            if ($this->vehiculoRepository->existePlaca($data['placa'], $vehiculo->id)) {
                return $this->error($response, 'Ya existe un vehículo con esa placa.', 409);
            }
            $data['placa'] = strtoupper($data['placa']);
        }

        // Validar capacidad si viene en el body
        if (!empty($data['capacidad_carga']) && (float) $data['capacidad_carga'] <= 0) {
            return $this->error($response, 'La capacidad de carga debe ser mayor a cero.', 400);
        }

        $vehiculo = $this->vehiculoRepository->update($vehiculo->id, $data);

        return $this->success($response, $vehiculo, 'Vehículo actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $vehiculo = $this->vehiculoRepository->getById((int) $args['id']);

        if (!$vehiculo) {
            return $this->error($response, 'Vehículo no encontrado.', 404);
        }

        $data   = $request->getParsedBody();
        $estado = $data['estado'] ?? null;

        $estadosValidos = ['disponible', 'en_ruta', 'mantenimiento', 'inactivo'];

        if (!in_array($estado, $estadosValidos)) {
            return $this->error($response, 'Estado no válido. Use: disponible, en_ruta, mantenimiento, inactivo.', 400);
        }

        $vehiculo = $this->vehiculoRepository->cambiarEstado($vehiculo->id, $estado);

        return $this->success($response, $vehiculo, 'Estado del vehículo actualizado correctamente.');
    }
}
