<?php
/**
 * Claude API Proxy – SeelenRevolte
 * Datei auf den Strato-Server hochladen (selber Ordner wie die HTML-Dateien)
 * Der API-Key bleibt serverseitig und ist für Besucher unsichtbar.
 */

// Nur Anfragen von deiner eigenen Domain erlauben
$allowed_origin = 'https://seelenrevolte.de';
$api_key        = 'ANTHROPIC_API_KEY_HIER'; // <-- hier deinen Claude-Key eintragen

// CORS-Header
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Browser-Preflight beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Eingabe lesen und prüfen
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['messages']) || !is_array($input['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige Anfrage']);
    exit;
}

// Nur den Prompt weiterleiten – Modell und Token serverseitig festlegen
$payload = json_encode([
    'model'      => 'claude-sonnet-4-20250514',
    'max_tokens' => 1000,
    'messages'   => $input['messages']
]);

// Anfrage an Claude API
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_TIMEOUT        => 30
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($http_code);
echo $response;
