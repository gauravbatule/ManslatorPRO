<?php
// Set headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

// Get API key
$api_key = getenv("GROQ_API_KEY");
if (empty($api_key)) {
    http_response_code(500);
    echo json_encode(["error" => "API key not configured"]);
    exit;
}

$system_prompt = <<<EOT
You are Manslator — a sharp, emotionally aware translator who decodes what women really mean and replies with witty, confident, and smooth comebacks. 
You always:
1. Decode what she actually means — the hidden emotion or subtext.
2. Give a short, human, clever reply that fits the vibe — flirty, funny, smart, or deep.
3. Stay natural, not robotic.
Style rules:
- Keep replies short, punchy, and real.
- Match her vibe (tease, comfort, joke, or call out playfully).
- No cringe. No overexplaining.
- Format like:
  **What She Said:** <user text>
  **What She Means:** <decoded meaning>
  **Smart Reply:** <your reply>
EOT;

// Get and validate input
$inputJSON = file_get_contents("php://input");
if ($inputJSON === false) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request body"]);
    exit;
}

$data = json_decode($inputJSON, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

$user_text = trim($data["text"] ?? "");
if (empty($user_text)) {
    http_response_code(400);
    echo json_encode(["error" => "No input text provided"]);
    exit;
}

// Rate limiting (basic)
if (strlen($user_text) > 500) {
    http_response_code(400);
    echo json_encode(["error" => "Text too long (max 500 characters)"]);
    exit;
}

// Prepare API request
$payload = [
    "model" => "llama3-8b-8192",
    "temperature" => 0.9,
    "max_completion_tokens" => 512,
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $user_text]
    ]
];

// Initialize cURL
$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
if ($ch === false) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to initialize API request"]);
    exit;
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($response === false || !empty($curlError)) {
    http_response_code(500);
    echo json_encode(["error" => "API request failed: " . $curlError]);
    exit;
}

// Handle HTTP errors
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(["error" => "API returned error code: $httpCode"]);
    exit;
}

// Parse response
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(["error" => "Invalid API response"]);
    exit;
}

// Extract and return the reply
$reply = $result["choices"][0]["message"]["content"] ?? null;
if (empty($reply)) {
    http_response_code(500);
    echo json_encode(["error" => "No response from AI"]);
    exit;
}

echo json_encode([
    "reply" => $reply
]);
?>