<?php
/**
 * Lee el <soap:Header> del XML crudo de la petición y extrae:
 *  - username / password  -> de <wsse:Security><wsse:UsernameToken>  (WS-Security, para el login)
 *  - token                -> de <AuthToken> (o <AuthHeader><token>)   (operaciones protegidas)
 * Se usa local-name() para no depender del prefijo que escriba el cliente (wsse:, o:, etc.).
 */
function extraerSeguridad($xml) {
    $vacio = ['username' => '', 'password' => '', 'token' => ''];

    if (!is_string($xml) || trim($xml) === '' || stripos($xml, '<!DOCTYPE') !== false) {
        return $vacio; // sin cuerpo, o con DTD (se rechaza para evitar ataques XXE)
    }

    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
        return $vacio;
    }

    $xp  = new DOMXPath($dom);
    $h   = "/*[local-name()='Envelope']/*[local-name()='Header']";
    $get = function ($ruta) use ($xp) {
        $n = $xp->query($ruta);
        return ($n && $n->length > 0) ? $n->item(0)->textContent : '';
    };

    $ut = "$h/*[local-name()='Security']/*[local-name()='UsernameToken']";

    $token = trim($get("$h/*[local-name()='AuthToken']"));
    if ($token === '') {
        $token = trim($get("$h/*[local-name()='AuthHeader']/*[local-name()='token']"));
    }

    return [
        'username' => trim($get("$ut/*[local-name()='Username']")),
        'password' => $get("$ut/*[local-name()='Password']"), // la clave NO se recorta
        'token'    => $token,
    ];
}