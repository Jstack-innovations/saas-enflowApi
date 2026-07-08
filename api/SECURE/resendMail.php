<?php
require_once __DIR__ . '/env.php';

function sendEmail($to, $subject, $body) {

    $apiKey = getenv("RESEND_API_KEY");

    $payload = [
        "from" => "EnflowAI Teams <hello@getenflowai.online>",
        "to" => is_array($to) ? $to : [$to],
        "subject" => $subject,
        "html" => $body
    ];

    $ch = curl_init("https://api.resend.com/emails");

    // Production: SSL verification enabled
    // curl_setopt_array($ch, [
    //     CURLOPT_HTTPHEADER => [
    //         "Authorization: Bearer " . $apiKey,
    //         "Content-Type: application/json"
    //     ],
    //     CURLOPT_POST => true,
    //     CURLOPT_POSTFIELDS => json_encode($payload),
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_TIMEOUT => 10,
    //     CURLOPT_SSL_VERIFYPEER => true,
    //     CURLOPT_SSL_VERIFYHOST => 2,
    // ]);

    // Local: SSL verification disabled (AWebServer has no SSL certs bundle)
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json"
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $curlError = null;
    if (curl_errno($ch)) {
        $curlError = curl_error($ch);
    }

    curl_close($ch);

    $decoded = json_decode($response, true);

    // 🔥 HARD FAILURE CHECK (IMPORTANT)
    if ($curlError) {
        return [
            "status" => "error",
            "type" => "curl_error",
            "message" => $curlError
        ];
    }

    if ($httpCode !== 200 && $httpCode !== 202) {
        return [
            "status" => "error",
            "type" => "http_error",
            "http_code" => $httpCode,
            "response" => $decoded ?? $response
        ];
    }

    if (!isset($decoded["id"])) {
        return [
            "status" => "error",
            "type" => "resend_error",
            "response" => $decoded ?? $response
        ];
    }

    return [
        "status" => "success",
        "id" => $decoded["id"]
    ];
}