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

// --- CONFIGURAÇÃO GEMINI ---
// API Key obtida em: https://aistudio.google.com/app/apikey
$geminiApiKey = 'COLOQUE_SUA_API_KEY_AQUI'; 

$model = $data['model'] ?? 'gemini-3.1-pro'; // Fallback
// Mapeamento caso o dropdown venha "clawdbot"
if ($model === 'clawdbot' || strpos($model, 'gpt-') !== false || strpos($model, 'claude-') !== false) {
    $model = 'gemini-3.1-pro';
}

// O Google provê um endpoint 100% compatível com o formato OpenAI nativamente nas versões v1beta
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions';

$payload = [
    "model" => $model,
    "messages" => $data['messages'],
    "stream" => false
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $geminiApiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to reach Gemini API: " . $error]);
    exit;
}

http_response_code($httpCode);
echo $response;
