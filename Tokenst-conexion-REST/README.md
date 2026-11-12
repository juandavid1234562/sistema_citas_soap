# Conexión REST -> SOAP: Validación de Tokens con Clave Secreta

## Contexto y Cumplimiento del Requerimiento (Punto 6)
Este módulo implementa el **Enfoque 2** visto en clase para el manejo de tokens en la arquitectura SOAP:
- **Enfoque 1 (Opaque / Token en BD):** Implementado en el login SOAP tradicional (`wsse:UsernameToken -> tokens en MySQL`).
- **Enfoque 2 (Token Criptográfico sin estado con Clave Secreta Compartida):** La API REST genera el token utilizando una clave secreta simétrica conocida por ambos sistemas (`HMAC-SHA256`). Al recibirlo, la aplicación SOAP valida matemáticamente su autenticidad y vigencia mediante la misma clave secreta, sin requerir acceso a sesiones ni bases de datos intermedias.

---

## Archivos del Módulo en SOAP (`Tokenst-conexion-REST/`)
1. **`ConfiguracionSecreto.php`**: Define la clave secreta compartida (`CLAVE_SECRETA`) y parámetros de vigencia.
2. **`ValidadorTokenSecreto.php`**: Clase que recibe el token en formato `[payload_base64].[firma_hmac]`, recalcula la firma con la clave secreta usando `hash_hmac('sha256')`, aplica comparación contra ataques de tiempo (`hash_equals`), verifica la expiración y retorna el resultado:
   - `valido`: Booleano (`true` o `false`).
   - `codigo`: Código numérico (200 si es válido, 401 si la firma no coincide o es inválido, 410 si ha expirado).
   - `mensaje`: Descripción detallada del diagnóstico.
   - `usuario`: Identidad autenticada.
   - `expiracion`: Fecha y hora de expiración.
3. **`probar_validacion_rest.php`**: Script de pruebas automatizadas en consola para verificar los 4 casos:
   - Caso 1: Token legítimo emitido por REST (Resultado: Válido).
   - Caso 2: Token manipulado o alterado (Resultado: Inválido - Firma rechazada).
   - Caso 3: Token generado con clave secreta equivocada (Resultado: Inválido).
   - Caso 4: Token expirado (Resultado: Inválido - Sesión vencida).

---

## Operación SOAP Expuesta en el Servidor (`server.php`)
Se agregó la operación pública en el WSDL:
- **Método**: `validarToken($token)`
- **Parámetro**: `token` (xsd:string)
- **Retorno**: `RespuestaValidacion` (`valido`, `codigo`, `mensaje`, `usuario`, `expiracion`)

Además, la función `svcValidarToken($token)` en `AuthService.php` fue actualizada para admitir de forma transparente tanto tokens de base de datos como tokens firmados con la clave secreta en la cabecera `<AuthHeader>` para todas las operaciones protegidas del servicio SOAP (`consultarCitas`, `registrarCita`, etc.).

---

## Ejecución de Pruebas
Para ejecutar las pruebas del lado SOAP:
```bash
php Tokenst-conexion-REST/probar_validacion_rest.php
```
