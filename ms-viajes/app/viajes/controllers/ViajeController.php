<?php

declare(strict_types=1);

namespace App\viajes\Controllers;

use App\viajes\Presentation\Repositories\SeguimientoRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViajeController extends AbstractController
{
    private SeguimientoRepository $seguimientoRepository;
    private string $msRutasUrl = 'http://localhost:8004';

    public function __construct()
    {
        $this->seguimientoRepository = new SeguimientoRepository();
    }

    // ── GET /seguimientos ─────────────────────────────────────
    public function index(Request $request, Response $response): Response
    {
        $params  = $request->getQueryParams();

        $filtros = [
            'programacion_viaje_id' => $params['programacion_viaje_id'] ?? null,
            'estado'                => $params['estado'] ?? null,
        ];

        $seguimientos = $this->seguimientoRepository->filtrar($filtros);

        return $this->success($response, $seguimientos, 'Seguimientos obtenidos correctamente.');
    }

    // ── GET /seguimientos/{id} ────────────────────────────────
    public function show(Request $request, Response $response, array $args): Response
    {
        $seguimiento = $this->seguimientoRepository->getById((int) $args['id']);

        if (!$seguimiento) {
            return $this->error($response, 'Seguimiento no encontrado.', 404);
        }

        return $this->success($response, $seguimiento, 'Seguimiento obtenido correctamente.');
    }

    // ── GET /seguimientos/historial/{programacionId} ──────────
    public function historial(Request $request, Response $response, array $args): Response
    {
        $historial = $this->seguimientoRepository->getHistorial((int) $args['programacionId']);

        return $this->success($response, $historial, 'Historial obtenido correctamente.');
    }

    // ── POST /seguimientos ────────────────────────────────────
    // Registrar novedad manualmente
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $required = ['programacion_viaje_id', 'estado', 'novedad'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error($response, "El campo '{$field}' es obligatorio.", 400);
            }
        }

        $estadosValidos = ['programado', 'en_transito', 'retrasado', 'finalizado', 'cancelado'];

        if (!in_array($data['estado'], $estadosValidos)) {
            return $this->error($response, 'Estado no válido.', 400);
        }

        // Verificar que la programacion existe en ms-rutas
        $programacion = $this->getProgramacion(
            (int) $data['programacion_viaje_id'],
            $request->getHeaderLine('Authorization')
        );

        if (!$programacion) {
            return $this->error($response, 'La programación especificada no existe.', 404);
        }

        $seguimiento = $this->seguimientoRepository->registrar(
            (int) $data['programacion_viaje_id'],
            $data['estado'],
            $data['novedad']
        );

        // Actualizar estado en ms-rutas
        $this->actualizarEstadoProgramacion(
            (int) $data['programacion_viaje_id'],
            $data['estado'],
            $request->getHeaderLine('Authorization')
        );

        return $this->success($response, $seguimiento, 'Novedad registrada correctamente.', 201);
    }

    // ── POST /viajes/{programacionId}/iniciar ─────────────────
    public function iniciar(Request $request, Response $response, array $args): Response
    {
        $programacionId = (int) $args['programacionId'];
        $token          = $request->getHeaderLine('Authorization');

        $programacion = $this->getProgramacion($programacionId, $token);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        // Validar que no esté cancelado
        if ($programacion['estado'] === 'cancelado') {
            return $this->error($response, 'No se puede iniciar un viaje cancelado.', 400);
        }

        // Validar que esté programado
        if ($programacion['estado'] !== 'programado') {
            return $this->error($response, 'El viaje ya fue iniciado o no está en estado programado.', 400);
        }

        $seguimiento = $this->seguimientoRepository->registrar(
            $programacionId,
            'en_transito',
            'Viaje iniciado.'
        );

        $this->actualizarEstadoProgramacion($programacionId, 'en_transito', $token);

        return $this->success($response, $seguimiento, 'Viaje iniciado correctamente.');
    }

    // ── POST /viajes/{programacionId}/finalizar ───────────────
    public function finalizar(Request $request, Response $response, array $args): Response
    {
        $programacionId = (int) $args['programacionId'];
        $token          = $request->getHeaderLine('Authorization');
        $data           = $request->getParsedBody();

        $programacion = $this->getProgramacion($programacionId, $token);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        // Validar que esté en tránsito o retrasado
        if (!in_array($programacion['estado'], ['en_transito', 'retrasado'])) {
            return $this->error($response, 'No se puede finalizar un viaje que no ha sido iniciado.', 400);
        }

        $novedad = $data['novedad'] ?? 'Viaje finalizado exitosamente.';

        $seguimiento = $this->seguimientoRepository->registrar(
            $programacionId,
            'finalizado',
            $novedad
        );

        $this->actualizarEstadoProgramacion($programacionId, 'finalizado', $token);

        return $this->success($response, $seguimiento, 'Viaje finalizado correctamente.');
    }

    // ── POST /viajes/{programacionId}/cancelar ────────────────
    public function cancelar(Request $request, Response $response, array $args): Response
    {
        $programacionId = (int) $args['programacionId'];
        $token          = $request->getHeaderLine('Authorization');
        $data           = $request->getParsedBody();

        $programacion = $this->getProgramacion($programacionId, $token);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        // No cancelar si ya está finalizado o cancelado
        if (in_array($programacion['estado'], ['finalizado', 'cancelado'])) {
            return $this->error($response, 'El viaje ya está finalizado o cancelado.', 400);
        }

        $novedad = $data['novedad'] ?? 'Viaje cancelado.';

        $seguimiento = $this->seguimientoRepository->registrar(
            $programacionId,
            'cancelado',
            $novedad
        );

        $this->actualizarEstadoProgramacion($programacionId, 'cancelado', $token);

        return $this->success($response, $seguimiento, 'Viaje cancelado correctamente.');
    }

    // ── POST /viajes/{programacionId}/retrasar ────────────────
    public function retrasar(Request $request, Response $response, array $args): Response
    {
        $programacionId = (int) $args['programacionId'];
        $token          = $request->getHeaderLine('Authorization');
        $data           = $request->getParsedBody();

        $programacion = $this->getProgramacion($programacionId, $token);

        if (!$programacion) {
            return $this->error($response, 'Programación no encontrada.', 404);
        }

        if ($programacion['estado'] !== 'en_transito') {
            return $this->error($response, 'Solo se puede registrar retraso en viajes en tránsito.', 400);
        }

        if (empty($data['novedad'])) {
            return $this->error($response, 'Debe indicar el motivo del retraso.', 400);
        }

        $seguimiento = $this->seguimientoRepository->registrar(
            $programacionId,
            'retrasado',
            $data['novedad']
        );

        $this->actualizarEstadoProgramacion($programacionId, 'retrasado', $token);

        return $this->success($response, $seguimiento, 'Retraso registrado correctamente.');
    }

    // ── HELPERS ───────────────────────────────────────────────

    private function getProgramacion(int $id, string $token): ?array
    {
        $ch = curl_init("{$this->msRutasUrl}/programaciones/{$id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token,
            'Content-Type: application/json',
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) return null;

        $data = json_decode($body, true);
        return $data['data'] ?? null;
    }

    private function actualizarEstadoProgramacion(int $id, string $estado, string $token): void
    {
        $ch = curl_init("{$this->msRutasUrl}/programaciones/{$id}/estado");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['estado' => $estado]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token,
            'Content-Type: application/json',
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
