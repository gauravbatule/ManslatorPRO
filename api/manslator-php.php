<?php
// Load simple .env file so XAMPP picks up credentials without extra config
function loadEnvFile(string $path): void {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

    $valueParts = explode('=', $line, 2);
    $name = trim($valueParts[0]);
        $value = isset($valueParts[1]) ? trim($valueParts[1]) : '';

        $valueLength = strlen($value);
        if ($valueLength >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[$valueLength - 1];
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === '\'' && $lastChar === '\'')) {
                $value = substr($value, 1, -1);
            }
        }
        if ($name === '') {
            continue;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

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

// Get API credentials and configuration
$api_key = getenv("GROQ_API_KEY");
if (empty($api_key)) {
    http_response_code(500);
    echo json_encode(["error" => "API key not configured"]);
    exit;
}

$model = getenv("GROQ_MODEL") ?: "openai/gpt-oss-120b";
$temperature = getenv("GROQ_TEMPERATURE");
$temperature = $temperature === false || $temperature === '' ? 1.0 : (float) $temperature;
$top_p = getenv("GROQ_TOP_P");
$top_p = $top_p === false || $top_p === '' ? 1.0 : (float) $top_p;
$max_completion_tokens = getenv("GROQ_MAX_COMPLETION_TOKENS");
$max_completion_tokens = $max_completion_tokens === false || $max_completion_tokens === '' ? 8192 : (int) $max_completion_tokens;
$reasoning_effort = getenv("GROQ_REASONING_EFFORT");

$system_prompt = <<<EOT
You are Manslator — a sharp, emotionally aware translator who decodes what women really mean and replies with witty, confident, and smooth comebacks.

Mission:
1. Diagnose the hidden subtext and emotional intent behind her words.
2. Craft a short, human, irresistibly confident reply that mirrors her vibe.
3. Share one concise micro-advice insight so the user knows how to deliver the reply.

Language:
- Always speak in Hinglish — a natural blend of Hindi and English that sounds authentic, playful, and contemporary.
- Preserve any Hinglish phrasing from the user where it helps the flow, and avoid pure English unless the tone demands it.

Output contract:
- Return ONLY a valid JSON object — no prose, markdown, or extra keys.
- JSON schema (all string values):
    {
        "what_she_said": original text (keep pronouns & tone),
        "what_she_means": decoded meaning with emotional context,
        "smart_reply": the exact reply line to send back,
        "tone": one of ["playful", "affectionate", "bold", "supportive", "candid", "reassuring"],
        "confidence": one of ["low", "medium", "high"],
        "micro_advice": tiny coaching tip (<= 120 characters)
    }
- Keep everything punchy, natural, and no longer than two sentences per field (except the reply, which can be one or two lines max).
- Avoid emojis unless they elevate the vibe. Never be cringe, needy, or generic.

Reference playbook (calibrate tone & wit — do NOT copy verbatim, but stay in this league):
1. Q: "Me or the most beautiful girl in the world?" → A: "The most beautiful girl in the world… kyunki wo tu hi toh hai. 😌"
2. Q: "Would you date your ex if we breakup?" → A: "Haan, kyunki tu hi meri ex banegi. 😉"
3. Q: "Who do you love more — me or yourself?" → A: "Mujhe apne aap se bhi zyada tu pasand hai. ❤️"
4. Q: "If I go away forever?" → A: "Tab bhi tu mere har khayal ke beech mein hogi."
5. Q: "Who’s your crush right now?" → A: "Same as yesterday — tu. 😎"
6. Q: "If you had one wish?" → A: "Tera 'always' mil jaaye bas. 💫"
7. Q: "What if I fall for someone else?" → A: "Firse uth ke mere paas aana. 😉"
8. Q: "Main zyada pretty lagti hoon ya smart?" → A: "Pretty bhi tu, smart bhi tu — matlab overpowered character hai tu. 😅"
9. Q: "Tum mujhe deserve karte ho?" → A: "Karna nahi padta, bas feel hota hai."
10. Q: "If I say 'I hate you'?" → A: "Phir bhi sunna accha lagega, kyunki tu hi keh rahi hai."

If the user text lacks context, infer confidently but stay grounded. Never lecture. Never downplay her feelings. Stay slick.
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
    "model" => $model,
    "temperature" => $temperature,
    "top_p" => $top_p,
    "max_completion_tokens" => $max_completion_tokens,
    "stream" => false,
    "stop" => null,
    "response_format" => ["type" => "json_object"],
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $user_text]
    ]
];

if ($reasoning_effort !== false && $reasoning_effort !== null && $reasoning_effort !== '') {
    $payload["reasoning_effort"] = $reasoning_effort;
}

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
    $decodedError = json_decode($response, true);
    $message = null;

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedError)) {
        $message = $decodedError['error']['message'] ?? $decodedError['message'] ?? $decodedError['error'] ?? null;
    }

    if (!$message && is_string($response) && $response !== '') {
        $message = mb_substr($response, 0, 600);
    }

    http_response_code($httpCode);
    echo json_encode([
        "error" => "API returned error code: $httpCode",
        "details" => $message
    ]);
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
if ($reply === null || $reply === '') {
    http_response_code(500);
    echo json_encode(["error" => "No response from AI"]);
    exit;
}

$structured_reply = json_decode($reply, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($structured_reply)) {
    http_response_code(500);
    echo json_encode([
        "error" => "AI returned invalid JSON",
        "raw" => $reply
    ]);
    exit;
}

$normalize = static function ($value) {
    if (is_string($value)) {
        return trim($value);
    }
    return '';
};

$response_payload = [
    "whatSheSaid" => $normalize($structured_reply["what_she_said"] ?? $user_text),
    "whatSheMeans" => $normalize($structured_reply["what_she_means"] ?? ''),
    "smartReply" => $normalize($structured_reply["smart_reply"] ?? ''),
    "tone" => $normalize($structured_reply["tone"] ?? ''),
    "confidence" => $normalize($structured_reply["confidence"] ?? ''),
    "microAdvice" => $normalize($structured_reply["micro_advice"] ?? '')
];

echo json_encode([
    "reply" => $response_payload
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>