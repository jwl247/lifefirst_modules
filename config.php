<?php
/**
 * Life First — Shared Config
 * Reads credentials from environment. Never hardcode secrets here.
 *
 * On phoenix-ext, set via /etc/lifefirst/lifefirst.env (loaded by Apache SetEnv
 * directives in /etc/apache2/sites-available/lifefirst.conf).
 *
 * For Frank dispatch, the same env vars must be exported before calling PHP.
 */

if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
}
if (!defined('CLAUDE_MODEL')) {
    define('CLAUDE_MODEL', 'claude-sonnet-4-6');
}

// Ollama self-hosted AI — primary for Life First, no API cost, no data egress
if (!defined('OLLAMA_URL')) {
    define('OLLAMA_URL', getenv('OLLAMA_URL') ?: 'http://localhost:11434');
}
if (!defined('OLLAMA_MODEL_LIFEFIRST')) {
    define('OLLAMA_MODEL_LIFEFIRST', getenv('OLLAMA_MODEL_LIFEFIRST') ?: 'llama3.1');
}

if (!function_exists('callOllama')) {
    function callOllama(string $system, array $messages, string $model = OLLAMA_MODEL_LIFEFIRST): array {
        $context = '';
        foreach (array_slice($messages, 0, -1) as $turn) {
            $role = $turn['role'] === 'assistant' ? 'Assistant' : 'User';
            $context .= "{$role}: {$turn['content']}\n";
        }
        $prompt = end($messages)['content'] ?? '';
        if ($context) {
            $prompt = $context . "User: " . $prompt;
        }

        $payload = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'system' => $system,
            'stream' => false,
            'options' => ['temperature' => 0.7, 'num_predict' => 512],
        ]);

        $ch = curl_init(OLLAMA_URL . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err || $status !== 200) {
            return ['error' => $err ?: "Ollama HTTP {$status}"];
        }

        $data = json_decode($raw, true);
        return ['content' => $data['response'] ?? '', 'source' => 'ollama'];
    }
}

if (!function_exists('getDB')) {
    function getDB() {
        $host = getenv('LF_DB_HOST') ?: 'localhost';
        $user = getenv('LF_DB_USER') ?: 'lifefirst';
        $pass = getenv('LF_DB_PASS') ?: '';
        $name = getenv('LF_DB_NAME') ?: 'lifefirst';
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}
