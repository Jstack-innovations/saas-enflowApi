<?php

function sendEmail($to, $subject, $body) {

   // $apiKey = getenv("RESEND_API_KEY");
    $apiKey = "re_VKZwjXXd_A5Bvidoiwn3NpTbMqeQRjzTA"; // paste your key here temporarily

    $data = [
        "from" => "Grillux <onboarding@resend.dev>", 
        "to" => is_array($to) ? $to : [$to],
        "subject" => $subject,
        "html" => $body
    ];

    $ch = curl_init("https://api.resend.com/emails");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ["status" => "error", "message" => $error];
    }

    curl_close($ch);

    return json_decode($response, true);
}

