<?php

declare(strict_types=1);

namespace App\conductores\Controllers;

use App\conductores\Presentation\Repositories\ConductorRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConductorController extends AbstractController
{
    private ConductorRepository $conductorRepository;

    public function __construct()
    {
        $this->conductorRepository = new ConductorRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $filtros = [
            'documento' => $params['documento'] ?? null,
            'licencia'  => $params['licencia']  ?? null,
            'estado'    => $params['estado']    ?? null,
        ];

        $conductores = $this->conductorRepository->filtrar($filtros);

        return $this->success($response, $conductores, 'Conductores obtenidos correctamente.');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $conductor = $this->conductorRepository->getById((int) $args['id']);

        if (!$conductor) {
            return $this->error($response, 'Conductor no encontrado.', 404);
        }

        return $this->success($response, $conductor, 'Conductor obtenido correctamente.');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validar campos obligatorios
        $required = [
            'nombres',
            'apellidos',
            'documento',
            'telefono',
            'correo',
            'numero_licencia',
            'categoria_licencia',
            'fecha_vencimiento_licencia'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        // Validar duplicados
        if ($this->conductorRepository->existeDocumento($data['documento'])) {
            return $this->error($response, 'Ya existe un conductor con ese documento.', 409);
        }

        if ($this->conductorRepository->existeLicencia($data['numero_licencia'])) {
            return $this->error($response, 'Ya existe un conductor con ese número de licencia.', 409);
        }

        if ($this->conductorRepository->existeCorreo($data['correo'])) {
            return $this->error($response, 'Ya existe un conductor con ese correo.', 409);
        }

        // Validar fecha de vencimiento
        if (strtotime($data['fecha_vencimiento_licencia']) < time()) {
            return $this->error($response, 'La licencia está vencida.', 400);
        }

        $conductor = $this->conductorRepository->create([
            'nombres'                    => $data['nombres'],
            'apellidos'                  => $data['apellidos'],
            'documento'                  => $data['documento'],
            'telefono'                   => $data['telefono'],
            'correo'                     => $data['correo'],
            'numero_licencia'            => $data['numero_licencia'],
            'categoria_licencia'         => $data['categoria_licencia'],
            'fecha_vencimiento_licencia' => $data['fecha_vencimiento_licencia'],
            'estado'                     => $data['estado'] ?? 'disponible',
        ]);

        return $this->success($response, $conductor, 'Conductor creado correctamente.', 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $conductor = $this->conductorRepository->getById((int) $args['id']);

        if (!$conductor) {
            return $this->error($response, 'Conductor no encontrado.', 404);
        }

        $data = $request->getParsedBody();

        // Validar duplicados excluyendo el actual
        if (!empty($data['documento'])) {
            if ($this->conductorRepository->existeDocumento($data['documento'], $conductor->id)) {
                return $this->error($response, 'Ya existe un conductor con ese documento.', 409);
            }
        }

        if (!empty($data['numero_licencia'])) {
            if ($this->conductorRepository->existeLicencia($data['numero_licencia'], $conductor->id)) {
                return $this->error($response, 'Ya existe un conductor con ese número de licencia.', 409);
            }
        }

        if (!empty($data['correo'])) {
            if ($this->conductorRepository->existeCorreo($data['correo'], $conductor->id)) {
                return $this->error($response, 'Ya existe un conductor con ese correo.', 409);
            }
        }

        $conductor = $this->conductorRepository->update($conductor->id, $data);

        return $this->success($response, $conductor, 'Conductor actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $conductor = $this->conductorRepository->getById((int) $args['id']);

        if (!$conductor) {
            return $this->error($response, 'Conductor no encontrado.', 404);
        }

        $data   = $request->getParsedBody();
        $estado = $data['estado'] ?? null;

        $estadosValidos = ['disponible', 'en_ruta', 'inactivo'];

        if (!in_array($estado, $estadosValidos)) {
            return $this->error($response, 'Estado no válido. Use: disponible, en_ruta, inactivo.', 400);
        }

        $conductor = $this->conductorRepository->cambiarEstado($conductor->id, $estado);

        return $this->success($response, $conductor, 'Estado del conductor actualizado correctamente.');
    }
}
