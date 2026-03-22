<?php

require_once __DIR__ . "/vendor/autoload.php";

use Resend\Resend;

$resend = Resend::client('re_SLYuwS7G_2zTBnXQ2wrDE64beayruP9Sy');

try {
    $response = $resend->emails->send([
        'from' => 'onboarding@resend.dev',
        'to' => 'wsamson630@gmail.com',
        'subject' => 'Hello World',
        'html' => '<p>Congrats on sending your first email!</p>'
    ]);

    echo json_encode([
        "success" => true,
        "data" => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
