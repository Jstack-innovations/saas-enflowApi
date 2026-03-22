<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../../vendor/autoload.php";

$mailConfig = require __DIR__ . "/../../SECURE/mail_config.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$data = json_decode(file_get_contents("php://input"), true);

$emailOrPhone = $data['email_or_phone'] ?? '';
$password = $data['password'] ?? '';

if (!$emailOrPhone || !$password) {
    echo json_encode(['success'=>false,'message'=>'All fields required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR phone=? LIMIT 1");
$stmt->bind_param("ss",$emailOrPhone,$emailOrPhone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1){
    echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password,$user['password'])){
    echo json_encode(['success'=>false,'message'=>'Invalid credentials']);
    exit;
}

if ($user['status'] !== 'active'){
    echo json_encode(['success'=>false,'message'=>'Please verify email']);
    exit;
}

/*
==============================
OTP VERIFICATION LOGIC
==============================
*/

$user_id = $user['id'];
$code = rand(1000, 9999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

/* Delete old codes */
$conn->query("DELETE FROM login_verifications WHERE user_id=$user_id");

/* Insert new code */
$stmt = $conn->prepare("
INSERT INTO login_verifications (user_id, code, expires_at)
VALUES (?,?,?)
");
$stmt->bind_param("iss", $user_id, $code, $expires_at);
$stmt->execute();

/* Send email */
$to = $user['email'];
$subject = "Your Login Verification Code";

$message = "
Hello {$user['full_name']},

Your 4-digit login verification code is: $code

This code expires in 5 minutes.

If this wasn't you, ignore this email.
";

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Timeout = 10; // stop waiting after 10 seconds
    $mail->SMTPDebug = 0;

    $mail->Host = $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $mailConfig['port'];

  // ======REMOVE THIS FOR PRODUCTION DEPLOYMENT=====
    $mail->SMTPOptions = [
        'ssl'=>[
            'verify_peer'=>false,
            'verify_peer_name'=>false,
            'allow_self_signed'=>true
        ]
    ];
  //==================================================

    $mail->setFrom(
        $mailConfig['from_email'],
        $mailConfig['from_name']
    );

    $mail->addAddress($to, $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = $subject;

    $mail->Body = "
    <h2>Artisan Grills Login Verification</h2>

    <p>Hello {$user['full_name']},</p>

    <p>Your 4-digit login verification code is:</p>

    <h1 style='letter-spacing:5px'>{$code}</h1>

    <p>This code expires in 5 minutes.</p>
    ";

    //$mail->send();
if(!$mail->send()){
    echo json_encode([
        "success"=>false,
        "message"=>"Verification email failed. Please try again."
    ]);
    exit;
}

} catch(Exception $e){
    echo json_encode([
        "success"=>false,
        "message"=>"Mail server error: " . $e->getMessage()
    ]);
    exit;
}

/* Return response */
echo json_encode([
    "success"=>true,
    "requires_verification"=>true,
    "user_id"=>$user_id
]);
exit;
?>
