<?php

/**
 * Envío de correo saliente vía el Web App de Google Apps Script publicado en la cuenta
 * institucional (mail.uniatlantico.edu.co). El script recibe {para, asunto, cuerpo} como texto
 * plano (JSON) y responde {status: 'success'|'error', message}.
 */
class Correo
{
    private const URL_APPSCRIPT = 'https://script.google.com/a/macros/mail.uniatlantico.edu.co/s/AKfycbyFvaGYohk_-0gRcHzZFcgmfY2g_o7Q0xnXbyMFXASWlE78fRElrLDHvqhxYgeJzkA6/exec';

    public function enviar(string $para, string $asunto, string $cuerpo): bool
    {
        $payload = json_encode([
            'para' => $para,
            'asunto' => $asunto,
            'cuerpo' => $cuerpo,
        ]);

        $curl = curl_init(self::URL_APPSCRIPT);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: text/plain;charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $respuesta = curl_exec($curl);
        $errorCurl = curl_errno($curl);
        curl_close($curl);

        if ($errorCurl !== 0 || $respuesta === false) {
            return false;
        }

        $datos = json_decode($respuesta, true);

        return is_array($datos) && ($datos['status'] ?? '') === 'success';
    }
}
