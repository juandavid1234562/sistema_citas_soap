<?php
require_once __DIR__ . '/../vendor/econea/nusoap/src/nusoap.php';
require_once __DIR__ . '/../services/CitasService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/HeaderSeguridad.php';

// -----------------------------------------------------------------------------
// 1. CONTROL DE NAVEGACIÓN WEB (Vista HTML si se ingresa por navegador)
// -----------------------------------------------------------------------------
$isWsdlQuery = isset($_GET['wsdl']) || isset($_GET['WSDL']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$isWsdlQuery) {
    header("Content-Type: text/html; charset=utf-8");
    require_once __DIR__ . '/../views/server_status.php';
    exit();
}

// -----------------------------------------------------------------------------
// 1.1 LECTURA DE LA PETICIÓN Y DE SU CABECERA DE SEGURIDAD (WS-Security / token)
// -----------------------------------------------------------------------------
$POST_DATA = file_get_contents("php://input");
$SEGURIDAD = extraerSeguridad($POST_DATA); // ['username', 'password', 'token']

// -----------------------------------------------------------------------------
// 2. CONFIGURACIÓN DEL SERVIDOR SOAP (NuSOAP & UTF-8)
// -----------------------------------------------------------------------------
$namespace = "ConsultorioSOAP";
$server = new soap_server();

$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;

$server->configureWSDL('Servidor Web SOAP - Consultorio Medico', $namespace);

// -----------------------------------------------------------------------------
// 3. TIPOS COMPLEJOS (el "contrato" de datos que aparece en <types> del WSDL)
// -----------------------------------------------------------------------------

// --- Entidades ---
$server->wsdl->addComplexType(
    'Profesional', 'complexType', 'struct', 'all', '',
    array(
        'id'           => array('name' => 'id',           'type' => 'xsd:int'),
        'documento'    => array('name' => 'documento',    'type' => 'xsd:string'),
        'nombre'       => array('name' => 'nombre',       'type' => 'xsd:string'),
        'especialidad' => array('name' => 'especialidad', 'type' => 'xsd:string'),
        'estado'       => array('name' => 'estado',       'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'Paciente', 'complexType', 'struct', 'all', '',
    array(
        'id'        => array('name' => 'id',        'type' => 'xsd:int'),
        'documento' => array('name' => 'documento', 'type' => 'xsd:string'),
        'nombre'    => array('name' => 'nombre',    'type' => 'xsd:string'),
        'telefono'  => array('name' => 'telefono',  'type' => 'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'Cita', 'complexType', 'struct', 'all', '',
    array(
        'id'             => array('name' => 'id',             'type' => 'xsd:int'),
        'profesional_id' => array('name' => 'profesional_id', 'type' => 'xsd:int'),
        'profesional'    => array('name' => 'profesional',    'type' => 'xsd:string'),
        'paciente_id'    => array('name' => 'paciente_id',    'type' => 'xsd:int'),
        'paciente'       => array('name' => 'paciente',       'type' => 'xsd:string'),
        'fecha'          => array('name' => 'fecha',          'type' => 'xsd:string'),
        'hora'           => array('name' => 'hora',           'type' => 'xsd:string'),
        'estado'         => array('name' => 'estado',         'type' => 'xsd:string'),
    )
);

// --- Respuesta genérica para operaciones de escritura ---
$server->wsdl->addComplexType(
    'Respuesta', 'complexType', 'struct', 'all', '',
    array(
        'codigo'  => array('name' => 'codigo',  'type' => 'xsd:int'),
        'mensaje' => array('name' => 'mensaje', 'type' => 'xsd:string'),
        'id'      => array('name' => 'id',      'type' => 'xsd:int'),
    )
);

// --- Resultado del login ---
$server->wsdl->addComplexType(
    'Sesion', 'complexType', 'struct', 'all', '',
    array(
        'token'           => array('name' => 'token',           'type' => 'xsd:string'),
        'expiraEnMinutos' => array('name' => 'expiraEnMinutos', 'type' => 'xsd:int'),
    )
);

// --- Resultado de validación de token (Punto 6) ---
$server->wsdl->addComplexType(
    'RespuestaValidacion', 'complexType', 'struct', 'all', '',
    array(
        'valido'     => array('name' => 'valido',     'type' => 'xsd:boolean'),
        'codigo'     => array('name' => 'codigo',     'type' => 'xsd:int'),
        'mensaje'    => array('name' => 'mensaje',    'type' => 'xsd:string'),
        'usuario'    => array('name' => 'usuario',    'type' => 'xsd:string'),
        'expiracion' => array('name' => 'expiracion', 'type' => 'xsd:string'),
    )
);

// --- Arreglos (listas) de cada entidad ---
$server->wsdl->addComplexType(
    'ProfesionalArray', 'complexType', 'array', '', 'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Profesional[]')),
    'tns:Profesional'
);

$server->wsdl->addComplexType(
    'PacienteArray', 'complexType', 'array', '', 'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Paciente[]')),
    'tns:Paciente'
);

$server->wsdl->addComplexType(
    'CitaArray', 'complexType', 'array', '', 'SOAP-ENC:Array',
    array(),
    array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Cita[]')),
    'tns:Cita'
);

// -----------------------------------------------------------------------------
// 4. MANEJO CENTRALIZADO DE EXCEPCIONES -> soap_fault
// -----------------------------------------------------------------------------
function ejecutar(callable $accion, $protegida = true) {
    try {
        if ($protegida) {
            // Ninguna operación protegida se ejecuta sin un token vigente en el header
            svcValidarToken($GLOBALS['SEGURIDAD']['token']);
        }
        return $accion();
    } catch (ServicioException $e) {
        // Error esperado y controlado (validación, no encontrado, duplicado, regla de negocio)
        return new soap_fault($e->getFaultCode(), '', $e->getMessage());
    } catch (PDOException $e) {
        error_log('[PDOException] ' . $e->getMessage());
        // 1062 = violación de UNIQUE en MySQL (ej. dos peticiones simultáneas con el mismo documento)
        if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
            return new soap_fault('Client', '', 'Ya existe un registro con esos datos únicos (documento o franja horaria).');
        }
        return new soap_fault('Server', '', 'Error interno de base de datos.');
    } catch (Throwable $e) {
        error_log('[Throwable] ' . $e->getMessage());
        return new soap_fault('Server', '', 'Error inesperado del servidor.');
    }
}

// -----------------------------------------------------------------------------
// 5. OPERACIONES EXPUESTAS (delegan a la capa de servicio a través de ejecutar())
// -----------------------------------------------------------------------------
function autenticar() {
    // Operación PÚBLICA: las credenciales llegan en <wsse:UsernameToken>, no como parámetros
    return ejecutar(fn() => svcAutenticar($GLOBALS['SEGURIDAD']['username'], $GLOBALS['SEGURIDAD']['password']), false);
}

// Punto 6: Método que valida el token generado desde REST usando la clave secreta
function validarToken($token) {
    // Operación PÚBLICA: valida el token recibido mediante la clave secreta y devuelve si es válido o no
    return ejecutar(fn() => svcValidarTokenSecreto($token), false);
}

function registrarProfesional($documento, $nombre, $especialidad, $estado) {
    return ejecutar(fn() => svcRegistrarProfesional($documento, $nombre, $especialidad, $estado));
}
function consultarProfesional($id) {
    return ejecutar(fn() => svcConsultarProfesional($id));
}
function listarProfesionales() {
    return ejecutar(fn() => svcListarProfesionales());
}
function actualizarProfesional($id, $nombre, $especialidad, $estado) {
    return ejecutar(fn() => svcActualizarProfesional($id, $nombre, $especialidad, $estado));
}
function registrarPaciente($documento, $nombre, $telefono) {
    return ejecutar(fn() => svcRegistrarPaciente($documento, $nombre, $telefono));
}
function listarPacientes() {
    return ejecutar(fn() => svcListarPacientes());
}
function registrarCita($profesional_id, $paciente_id, $fecha, $hora, $estado) {
    return ejecutar(fn() => svcRegistrarCita($profesional_id, $paciente_id, $fecha, $hora, $estado));
}
function consultarCitas($profesional_id, $fecha) {
    return ejecutar(fn() => svcConsultarCitas($profesional_id, $fecha));
}

// -----------------------------------------------------------------------------
// 6. REGISTRO DE MÉTODOS (9 OPERACIONES)
// -----------------------------------------------------------------------------

// Autenticación (pública)
$server->register(
    'autenticar',
    array(),
    array('return' => 'tns:Sesion'),
    $namespace, false, 'rpc', 'encoded', 'Login: recibe wsse:UsernameToken en el header y devuelve un token'
);

// Punto 6: Validación de Token con Clave Secreta desde REST (pública)
$server->register(
    'validarToken',
    array('token' => 'xsd:string'),
    array('return' => 'tns:RespuestaValidacion'),
    $namespace, false, 'rpc', 'encoded', 'Punto 6: Valida el token generado desde REST usando la clave secreta e indica si es valido o no'
);

// Profesionales
$server->register(
    'registrarProfesional',
    array('documento' => 'xsd:string', 'nombre' => 'xsd:string', 'especialidad' => 'xsd:string', 'estado' => 'xsd:string'),
    array('return' => 'tns:Respuesta'),
    $namespace, false, 'rpc', 'encoded', 'Registrar un profesional'
);

$server->register(
    'consultarProfesional',
    array('id' => 'xsd:int'),
    array('return' => 'tns:Profesional'),
    $namespace, false, 'rpc', 'encoded', 'Consultar profesional por ID'
);

$server->register(
    'listarProfesionales',
    array(),
    array('return' => 'tns:ProfesionalArray'),
    $namespace, false, 'rpc', 'encoded', 'Listar todos los profesionales'
);

$server->register(
    'actualizarProfesional',
    array('id' => 'xsd:int', 'nombre' => 'xsd:string', 'especialidad' => 'xsd:string', 'estado' => 'xsd:string'),
    array('return' => 'tns:Respuesta'),
    $namespace, false, 'rpc', 'encoded', 'Actualizar datos del profesional'
);

// Pacientes
$server->register(
    'registrarPaciente',
    array('documento' => 'xsd:string', 'nombre' => 'xsd:string', 'telefono' => 'xsd:string'),
    array('return' => 'tns:Respuesta'),
    $namespace, false, 'rpc', 'encoded', 'Registrar un paciente'
);

$server->register(
    'listarPacientes',
    array(),
    array('return' => 'tns:PacienteArray'),
    $namespace, false, 'rpc', 'encoded', 'Listar todos los pacientes'
);

// Citas Médicas
$server->register(
    'registrarCita',
    array('profesional_id' => 'xsd:int', 'paciente_id' => 'xsd:int', 'fecha' => 'xsd:string', 'hora' => 'xsd:string', 'estado' => 'xsd:string'),
    array('return' => 'tns:Respuesta'),
    $namespace, false, 'rpc', 'encoded', 'Agendar cita medica'
);

$server->register(
    'consultarCitas',
    array('profesional_id' => 'xsd:int', 'fecha' => 'xsd:string'),
    array('return' => 'tns:CitaArray'),
    $namespace, false, 'rpc', 'encoded', 'Consultar citas filtradas'
);

// -----------------------------------------------------------------------------
// 7. ATENCIÓN DE PETICIONES SOAP
// -----------------------------------------------------------------------------
$server->service($POST_DATA);
exit();
