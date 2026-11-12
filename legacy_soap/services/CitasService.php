<?php
require_once __DIR__ . '/../models/Profesional.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/ServicioException.php';

// =============================================================================
// HELPERS DE VALIDACIÓN
// =============================================================================
const ESTADOS_PROFESIONAL = ['Activo', 'Inactivo'];
const ESTADOS_CITA        = ['Programada', 'Completada', 'Cancelada'];

function vacio($valor) {
    // Ojo: empty("0") es true en PHP, por eso comparamos contra ''
    return trim((string)$valor) === '';
}

function validarEstado($estado, array $permitidos) {
    if (!in_array($estado, $permitidos, true)) {
        throw new ServicioException("Estado inválido '$estado'. Valores permitidos: " . implode(', ', $permitidos) . ".");
    }
}

function normalizarFecha($fecha) {
    $f = DateTime::createFromFormat('Y-m-d', trim($fecha));
    if (!$f || $f->format('Y-m-d') !== trim($fecha)) {
        throw new ServicioException("Fecha inválida '$fecha'. Use el formato AAAA-MM-DD.");
    }
    return $f->format('Y-m-d');
}

function normalizarHora($hora) {
    $hora = trim($hora);
    foreach (['H:i:s', 'H:i'] as $formato) {
        $h = DateTime::createFromFormat($formato, $hora);
        if ($h && $h->format($formato) === $hora) {
            return $h->format('H:i:s'); // Siempre HH:MM:SS para que el chequeo de solapamiento sea consistente
        }
    }
    throw new ServicioException("Hora inválida '$hora'. Use el formato HH:MM o HH:MM:SS.");
}

// =============================================================================
// MAPPERS: fila de BD -> arreglo con EXACTAMENTE los campos del tipo complejo
// (así no se filtran columnas extra como created_at ni índices numéricos)
// =============================================================================
function aProfesional(array $r) {
    return [
        'id'           => (int)$r['id'],
        'documento'    => (string)$r['documento'],
        'nombre'       => (string)$r['nombre'],
        'especialidad' => (string)$r['especialidad'],
        'estado'       => (string)$r['estado'],
    ];
}

function aPaciente(array $r) {
    return [
        'id'        => (int)$r['id'],
        'documento' => (string)$r['documento'],
        'nombre'    => (string)$r['nombre'],
        'telefono'  => (string)($r['telefono'] ?? ''),
    ];
}

function aCita(array $r) {
    return [
        'id'             => (int)$r['id'],
        'profesional_id' => (int)$r['profesional_id'],
        'profesional'    => (string)$r['profesional'],
        'paciente_id'    => (int)$r['paciente_id'],
        'paciente'       => (string)$r['paciente'],
        'fecha'          => (string)$r['fecha'],
        'hora'           => (string)$r['hora'],
        'estado'         => (string)$r['estado'],
    ];
}

function respuestaOk($mensaje, $id = 0) {
    return ['codigo' => 200, 'mensaje' => $mensaje, 'id' => (int)$id];
}

// =============================================================================
// PROFESIONALES
// =============================================================================
function svcRegistrarProfesional($documento, $nombre, $especialidad, $estado) {
    if (vacio($documento) || vacio($nombre) || vacio($especialidad)) {
        throw new ServicioException('Campos obligatorios faltantes (documento, nombre, especialidad).');
    }
    $estado = vacio($estado) ? 'Activo' : trim($estado);
    validarEstado($estado, ESTADOS_PROFESIONAL);

    $model = new ProfesionalModel();
    if ($model->obtenerPorDocumento($documento)) {
        throw new ServicioException("El profesional con documento '$documento' ya existe.");
    }
    $model->registrar(trim($documento), trim($nombre), trim($especialidad), $estado);

    $nuevo = $model->obtenerPorDocumento(trim($documento));
    return respuestaOk('Profesional registrado correctamente.', $nuevo['id'] ?? 0);
}

function svcConsultarProfesional($id) {
    $prof = (new ProfesionalModel())->obtenerPorId($id);
    if (!$prof) {
        throw new ServicioException("No existe un profesional con el ID $id.");
    }
    return aProfesional($prof);
}

function svcListarProfesionales() {
    $filas = (new ProfesionalModel())->listarTodos();
    return array_map('aProfesional', $filas);
}

function svcActualizarProfesional($id, $nombre, $especialidad, $estado) {
    $model  = new ProfesionalModel();
    $actual = $model->obtenerPorId($id);
    if (!$actual) {
        throw new ServicioException("No existe un profesional con el ID $id.");
    }

    // El enunciado dice "nombre, especialidad o estado": si un campo llega vacío, se conserva el actual.
    $nombre       = vacio($nombre)       ? $actual['nombre']       : trim($nombre);
    $especialidad = vacio($especialidad) ? $actual['especialidad'] : trim($especialidad);
    $estado       = vacio($estado)       ? $actual['estado']       : trim($estado);
    validarEstado($estado, ESTADOS_PROFESIONAL);

    $model->actualizar($id, $nombre, $especialidad, $estado);
    return respuestaOk('Profesional actualizado correctamente.', $id);
}

// =============================================================================
// PACIENTES
// =============================================================================
function svcRegistrarPaciente($documento, $nombre, $telefono) {
    if (vacio($documento) || vacio($nombre)) {
        throw new ServicioException('Campos obligatorios faltantes (documento, nombre).');
    }
    $model = new PacienteModel();
    if ($model->obtenerPorDocumento($documento)) {
        throw new ServicioException("El paciente con documento '$documento' ya existe.");
    }
    $model->registrar(trim($documento), trim($nombre), trim((string)$telefono));

    $nuevo = $model->obtenerPorDocumento(trim($documento));
    return respuestaOk('Paciente registrado correctamente.', $nuevo['id'] ?? 0);
}

function svcListarPacientes() {
    $filas = (new PacienteModel())->listarTodos();
    return array_map('aPaciente', $filas);
}

// =============================================================================
// CITAS
// =============================================================================
function svcRegistrarCita($profesional_id, $paciente_id, $fecha, $hora, $estado) {
    if (!$profesional_id || !$paciente_id || vacio($fecha) || vacio($hora)) {
        throw new ServicioException('Faltan datos obligatorios para agendar la cita.');
    }
    $fecha  = normalizarFecha($fecha);
    $hora   = normalizarHora($hora);
    $estado = vacio($estado) ? 'Programada' : trim($estado);
    validarEstado($estado, ESTADOS_CITA);

    $profModel = new ProfesionalModel();
    $pacModel  = new PacienteModel();
    $citaModel = new CitaModel();

    $profesional = $profModel->obtenerPorId($profesional_id);
    if (!$profesional) {
        throw new ServicioException("El profesional con ID $profesional_id no existe.");
    }
    if ($profesional['estado'] === 'Inactivo') {
        throw new ServicioException('El profesional seleccionado está INACTIVO.');
    }
    if (!$pacModel->obtenerPorId($paciente_id)) {
        throw new ServicioException("El paciente con ID $paciente_id no existe.");
    }
    if ($citaModel->existeSolapamiento($profesional_id, $fecha, $hora)) {
        throw new ServicioException("El profesional ya tiene una cita asignada el $fecha a las $hora.");
    }

    $citaModel->registrar($profesional_id, $paciente_id, $fecha, $hora, $estado);
    return respuestaOk('Cita agendada exitosamente.');
}

function svcConsultarCitas($profesional_id, $fecha) {
    // Filtros opcionales: 0 / vacío significa "sin filtro"
    $fecha = vacio($fecha) ? null : normalizarFecha($fecha);
    $filas = (new CitaModel())->consultarPorFiltro($profesional_id, $fecha);
    return array_map('aCita', $filas);
}