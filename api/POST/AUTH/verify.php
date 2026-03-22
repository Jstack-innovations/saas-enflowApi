<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
   http_response_code(200);
    exit();
}

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT id, phone FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $phone = $user['phone'];

    // Update user to active
    $update = $conn->prepare("UPDATE users 
        SET status='active', verification_token=NULL 
        WHERE id=?");
    $update->bind_param("i", $userId);
    $update->execute();

    // Claim all past paid_orders with the same phone
    $claim = $conn->prepare("UPDATE paid_orders 
        SET user_id=? 
        WHERE phone=? AND user_id IS NULL");
    $claim->bind_param("is", $userId, $phone);
    $claim->execute();

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Verification Success | Artisan Grills</title>
      <link href="https://fonts.googleapis.com/css2?family=Sacramento&display=swap" rel="stylesheet">
      <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #fff8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            color: #333;
        }

        /* Floating steam bubbles */
        .steam {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            background: radial-gradient(circle, #FFDAB9 0%, transparent 70%);
            animation: float 10s linear infinite;
        }
        .steam:nth-child(1){ width: 150px; height: 150px; left: 10%; top: 60%; animation-duration: 12s; }
        .steam:nth-child(2){ width: 100px; height: 100px; left: 70%; top: 50%; animation-duration: 8s; }
        .steam:nth-child(3){ width: 120px; height: 120px; left: 40%; top: 70%; animation-duration: 14s; }

        @keyframes float {
            0% { transform: translateY(0) translateX(0) rotate(0deg); }
            50% { transform: translateY(-50px) translateX(20px) rotate(45deg); }
            100% { transform: translateY(0) translateX(0) rotate(0deg); }
        }

        .container {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .header {
            font-family: 'Sacramento', cursive;
            font-size: 48px;
            color: #A0522D;
            margin-bottom: 20px;
        }
        .message {
            font-size: 18px;
            margin-bottom: 30px;
        }
        .login-btn {
            display: inline-block;
            background-color: #FF7043;
            color: #fff;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .login-btn:hover { background-color: #E64A19; }

        /* Success checkmark animation */
        .checkmark {
          width: 100px;
          height: 100px;
          border-radius: 50%;
          display: flex;
          justify-content: center;
          align-items: center;
          margin: 0 auto 20px auto;
          background: #4CAF50;
          animation: pop 0.5s ease-out forwards;
        }
        .checkmark::after {
          content: "";
          width: 25px;
          height: 50px;
          border-left: 5px solid #fff;
          border-bottom: 5px solid #fff;
          transform: rotate(-45deg);
          animation: draw 0.5s ease-out forwards 0.5s;
        }
        @keyframes pop {
          0% { transform: scale(0); }
          100% { transform: scale(1); }
        }
        @keyframes draw {
          0% { height: 0; width: 0; opacity: 0; }
          100% { height: 50px; width: 25px; opacity: 1; }
        }

        /* Responsive */
        @media(max-width:400px){
          .header { font-size: 36px; }
          .message { font-size: 16px; }
          .login-btn { padding: 12px 25px; }
        }
      </style>
    </head>
    <body>
      <!-- Steam floating animations -->
      <div class="steam"></div>
      <div class="steam"></div>
      <div class="steam"></div>

      <div class="container">
        <div class="checkmark"></div>
        <div class="header">Artisan Grills</div>
        <div class="message">
          Your email has been verified successfully!<br>
          Please login on the app to view all your orders.
        </div>
        <a href="https://yourappurl.com/login" class="login-btn">Open App</a>
      </div>
    </body>
    </html>
    <?php

} else {
    echo "<h2>Invalid or expired token.</h2>";
}
?>
