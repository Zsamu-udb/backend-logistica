<?php

declare(strict_types=1);

namespace App\rutas\Controllers;

use App\rutas\Presentation\Repositories\RutaRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RutaController extends AbstractController
{
    private RutaRepository $rutaRepository;

    public function __construct()
    {
        $this->rutaRepository = new RutaRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $filtros = [
            'ciudad' => $params['ciudad'] ?? null,
        ];

        $rutas = $this->rutaRepository->filtrar($filtros);

        return $this->success($response, $rutas, 'Rutas obtenidas correctamente.');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $ruta = $this->rutaRepository->getById((int) $args['id']);

        if (!$ruta) {
            return $this->error($response, 'Ruta no encontrada.', 404);
        }

        return $this->success($response, $ruta, 'Ruta obtenida correctamente.');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $required = ['ciudad_origen', 'ciudad_destino', 'distancia', 'tiempo_estimado'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        // Validar distancia mayor a cero
        if ((float) $data['distancia'] <= 0) {
            return $this->error($response, 'La distancia debe ser mayor a cero.', 400);
        }

        // Validar ruta duplicada
        if ($this->rutaRepository->existeRuta($data['ciudad_origen'], $data['ciudad_destino'])) {
            return $this->error($response, 'Ya existe una ruta con ese origen y destino.', 409);
        }

        $ruta = $this->rutaRepository->create([
            'ciudad_origen'   => $data['ciudad_origen'],
            'ciudad_destino'  => $data['ciudad_destino'],
            'distancia'       => (float) $data['distancia'],
            'tiempo_estimado' => $data['tiempo_estimado'],
            'observaciones'   => $data['observaciones'] ?? null,
        ]);

        return $this->success($response, $ruta, 'Ruta creada correctamente.', 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $ruta = $this->rutaRepository->getById((int) $args['id']);

        if (!$ruta) {
            return $this->error($response, 'Ruta no encontrada.', 404);
        }

        $data = $request->getParsedBody();

        // Validar distancia si viene
        if (!empty($data['distancia']) && (float) $data['distancia'] <= 0) {
            return $this->error($response, 'La distancia debe ser mayor a cero.', 400);
        }

        // Validar duplicado excluyendo la actual
        if (!empty($data['ciudad_origen']) && !empty($data['ciudad_destino'])) {
            if ($this->rutaRepository->existeRuta($data['ciudad_origen'], $data['ciudad_destino'], $ruta->id)) {
                return $this->error($response, 'Ya existe una ruta con ese origen y destino.', 409);
            }
        }

        $ruta = $this->rutaRepository->update($ruta->id, $data);

        return $this->success($response, $ruta, 'Ruta actualizada correctamente.');
    }
}
