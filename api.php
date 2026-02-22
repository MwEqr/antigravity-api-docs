<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['messages'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request. 'messages' array is required."]);
    exit;
}

// --- CONFIGURAÇÃO GEMINI LOCAL AUTH HUB ---
// O servidor gemini-oauth-server.js roda na porta 18790 e gerencia o OAuth2
$apiUrl = 'http://127.0.0.1:18790/v1/chat/completions';

$payload = [
    "model" => $data['model'] ?? 'gemini-1.5-pro',
    "messages" => $data['messages'],
    "stream" => false
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to reach local Gemini Auth server: " . $error]);
    exit;
}

http_response_code($httpCode);
echo $response;
