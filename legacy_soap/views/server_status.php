<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servidor Web SOAP - Consultorio Médico</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .card { background-color: #1e293b; border-radius: 12px; padding: 32px; max-width: 800px; width: 100%; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); border: 1px solid #334155; }
        .badge { background-color: #10b981; color: #022c22; font-weight: bold; font-size: 12px; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; margin-right: 8px; }
        h1 { font-size: 24px; margin: 0 0 12px 0; display: flex; align-items: center; color: #38bdf8; }
        p { color: #94a3b8; font-size: 15px; margin-bottom: 24px; }
        h2 { font-size: 18px; color: #f1f5f9; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-top: 24px; }
        ul { list-style-type: none; padding: 0; margin: 0; }
        li { background-color: #0f172a; border: 1px solid #334155; padding: 12px 16px; border-radius: 8px; margin-bottom: 10px; font-family: monospace; font-size: 14px; color: #38bdf8; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #334155; font-size: 14px; color: #94a3b8; }
        .code-inline { background-color: #0f172a; padding: 4px 8px; border-radius: 4px; color: #e2e8f0; font-family: monospace; }
        a { color: #38bdf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1><span class="badge">ACTIVO</span> Servidor Web SOAP - Consultorio Médico</h1>
        <p>Este endpoint responde a solicitudes de Servicios Web mediante el protocolo SOAP v1.1 / v1.2.</p>

        <h2>Operaciones Disponibles:</h2>
        <ul>
            <li>registrarProfesional(documento, nombre, especialidad, estado)</li>
            <li>consultarProfesional(id)</li>
            <li>listarProfesionales()</li>
            <li>actualizarProfesional(id, nombre, especialidad, estado)</li>
            <li>registrarPaciente(documento, nombre, telefono)</li>
            <li>listarPacientes()</li>
            <li>registrarCita(profesional_id, paciente_id, fecha, hora, estado)</li>
            <li>consultarCitas(profesional_id, fecha)</li>
        </ul>

        <div class="footer">
            Descargar / Inspeccionar WSDL: <a href="server.php?wsdl" class="code-inline">server.php?wsdl</a>
        </div>
    </div>
</body>
</html>