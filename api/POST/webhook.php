<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../SECURE/db.php';
require_once __DIR__ . '/../SECURE/resendMail.php';

$flw_secret_hash = $_SERVER['HTTP_VERIF_HASH'] ?? '';

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid payload"]);
    exit;
}

$event = $data['event'] ?? '';

if ($event !== 'charge.completed') {
    http_response_code(200);
    echo json_encode(["status" => "ignored"]);
    exit;
}

$tx_data        = $data['data'] ?? [];
$transaction_id = (int)($tx_data['id'] ?? 0);
$tx_ref         = $tx_data['tx_ref'] ?? '';
$tx_status      = $tx_data['status'] ?? '';
$tx_currency    = $tx_data['currency'] ?? '';

if ($tx_status !== 'successful' || $tx_currency !== 'NGN' || !$transaction_id) {
    http_response_code(200);
    echo json_encode(["status" => "ignored", "reason" => "not successful or wrong currency"]);
    exit;
}

// ── Route by tx_ref format ──────────────────────────────────────────
// Order:   enFlowAi_{tenant_id}_{order_id}_...
// Session: TBL-{tableNo}-{5chars}

if (preg_match('/^enFlowAi_(\d+)_(\d+)_/', $tx_ref, $matches)) {
    $tenant_id = (int)$matches[1];
    $order_id  = (int)$matches[2];
    handleOrderPayment($conn, $transaction_id, $tx_ref, $tenant_id, $order_id, $flw_secret_hash);

} elseif (preg_match('/^TBL-/', $tx_ref)) {
    handleSessionClose($conn, $transaction_id, $tx_ref, $flw_secret_hash);
    
} elseif (preg_match('/^TABLE_/', $tx_ref)) {
    handleReservationPayment($conn, $transaction_id, $tx_ref, $flw_secret_hash);
    
} else {
    http_response_code(200);
    echo json_encode(["status" => "error", "message" => "Unrecognised tx_ref format"]);
}


// ════════════════════════════════════════════════════════════════════
// HANDLER 1 — Order Payment (confirm-order logic)
// ════════════════════════════════════════════════════════════════════
function handleOrderPayment($conn, $transaction_id, $tx_ref, $tenant_id, $order_id, $flw_secret_hash) {

    $keyStmt = $conn->prepare("
        SELECT flutterwave_secret_key, flutterwave_webhook_hash,
               notification_email, telegram_bot_token, telegram_chat_id
        FROM tenants WHERE id = ?
    ");
    $keyStmt->bind_param("i", $tenant_id);
    $keyStmt->execute();
    $tenant = $keyStmt->get_result()->fetch_assoc();
    $keyStmt->close();

    if (!$tenant) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Tenant not found"]);
        exit;
    }

    if ($flw_secret_hash !== ($tenant['flutterwave_webhook_hash'] ?? '')) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid signature"]);
        exit;
    }

    $secretKey   = $tenant['flutterwave_secret_key'];
    $notifyEmail = $tenant['notification_email'] ?? '';
    $botToken    = $tenant['telegram_bot_token'] ?? '';
    $chatId      = $tenant['telegram_chat_id'] ?? '';

    // Local: SSL disabled (AWebServer) — Production: enable SSL block below
    // CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response  = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "curl error: $curlError"]);
        exit;
    }

    $verified = json_decode($response, true);

    if (
        !$verified ||
        ($verified['status'] ?? '') !== 'success' ||
        ($verified['data']['status'] ?? '') !== 'successful'
    ) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Verification failed"]);
        exit;
    }

    $verified_amount = (float)$verified['data']['amount'];

    $orderStmt = $conn->prepare("
        SELECT total_amount, status, order_type, table_no
        FROM paid_orders WHERE id = ? AND tenant_id = ?
    ");
    $orderStmt->bind_param("ii", $order_id, $tenant_id);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Order not found"]);
        exit;
    }

    if ($order['status'] === 'paid') {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Already confirmed"]);
        exit;
    }

    if (abs((float)$order['total_amount'] - $verified_amount) > 0.01) {
        http_response_code(200);
        echo json_encode([
            "status"  => "error",
            "message" => "Amount mismatch",
            "db"      => $order['total_amount'],
            "flw"     => $verified_amount
        ]);
        exit;
    }

    $orderType = $order['order_type'];
    $tableNo   = $order['table_no'];

    $cartStmt = $conn->prepare("
        SELECT menu_id, quantity, menu_name as name
        FROM paid_order_items WHERE paid_order_id = ? AND tenant_id = ?
    ");
    $cartStmt->bind_param("ii", $order_id, $tenant_id);
    $cartStmt->execute();
    $cartRows = $cartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cartStmt->close();

    $conn->begin_transaction();

    try {
        $upStmt = $conn->prepare("
            UPDATE paid_orders
            SET status = 'paid', payment_ref = ?
            WHERE id = ? AND status = 'payment_pending' AND tenant_id = ?
        ");
        $upStmt->bind_param("sii", $transaction_id, $order_id, $tenant_id);
        $upStmt->execute();

        if ($upStmt->affected_rows === 0) {
            $conn->rollback();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Already confirmed by parallel process"]);
            exit;
        }

        if ($orderType === 'table' && !empty($tableNo)) {
            $tStmt = $conn->prepare("
                INSERT INTO booked_tables (tenant_id, table_id, booked)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE booked = 1
            ");
            $tStmt->bind_param("ii", $tenant_id, $tableNo);
            $tStmt->execute();
        }

        $stockStmt = $conn->prepare("
            UPDATE menu_stock
            SET stock = stock - ?,
                available = CASE WHEN stock - ? <= 0 THEN 0 ELSE 1 END
            WHERE menu_id = ? AND stock >= ? AND tenant_id = ?
        ");

        foreach ($cartRows as $item) {
            $qty = (int)$item['quantity'];
            $id  = (int)$item['menu_id'];
            $stockStmt->bind_param("iiiii", $qty, $qty, $id, $qty, $tenant_id);
            $stockStmt->execute();

            if ($stockStmt->affected_rows === 0) {
                throw new Exception("Stock error: " . $item['name']);
            }
        }

        $conn->commit();

        $message = "
✅ *New Order Confirmed!*

🧾 *Order ID:* #{$order_id}
💳 *Transaction ID:* {$transaction_id}
💰 *Amount:* ₦" . number_format($verified_amount, 2) . "
📦 *Order Type:* {$orderType}
" . ($tableNo ? "🪑 *Table:* {$tableNo}" : "") . "
🔔 _Confirmed via webhook_
";

        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "chat_id"    => $chatId,
            "text"       => $message,
            "parse_mode" => "Markdown"
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);

        sendEmail(
            $notifyEmail,
            "New Order Confirmed — #" . $order_id,
            "
            <h2>✅ New Order Confirmed</h2>
            <p><b>Order ID:</b> #{$order_id}</p>
            <p><b>Transaction ID:</b> {$transaction_id}</p>
            <p><b>Amount:</b> ₦" . number_format($verified_amount, 2) . "</p>
            <p><b>Order Type:</b> {$orderType}</p>
            " . ($tableNo ? "<p><b>Table:</b> {$tableNo}</p>" : "") . "
            <p><small>Confirmed via webhook</small></p>
            "
        );

        http_response_code(200);
        echo json_encode(["status" => "success", "order_id" => $order_id]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}


// ════════════════════════════════════════════════════════════════════
// HANDLER 2 — Session Close (close-session logic)
// ════════════════════════════════════════════════════════════════════
function handleSessionClose($conn, $transaction_id, $tx_ref, $flw_secret_hash) {

    // Session tx_ref = session_code, so we look up tenant via paid_orders
    $lookupStmt = $conn->prepare("
        SELECT o.id, o.tenant_id, o.total_amount, o.status,
               t.flutterwave_secret_key, t.flutterwave_webhook_hash,
               t.notification_email, t.telegram_bot_token, t.telegram_chat_id
        FROM paid_orders o
        JOIN tenants t ON t.id = o.tenant_id
        WHERE o.session_code = ? AND o.status = 'open'
        LIMIT 1
    ");
    $lookupStmt->bind_param("s", $tx_ref);
    $lookupStmt->execute();
    $row = $lookupStmt->get_result()->fetch_assoc();
    $lookupStmt->close();

    if (!$row) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Session not found or already closed"]);
        exit;
    }

    if ($flw_secret_hash !== ($row['flutterwave_webhook_hash'] ?? '')) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid signature"]);
        exit;
    }

    $tenant_id   = (int)$row['tenant_id'];
    $order_id    = (int)$row['id'];
    $secretKey   = $row['flutterwave_secret_key'];
    $notifyEmail = $row['notification_email'] ?? '';
    $botToken    = $row['telegram_bot_token'] ?? '';
    $chatId      = $row['telegram_chat_id'] ?? '';
    $db_amount   = (float)$row['total_amount'];

    // Local: SSL disabled — Production: enable SSL block below
    // CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response  = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "curl error: $curlError"]);
        exit;
    }

    $verified = json_decode($response, true);

    if (
        !$verified ||
        ($verified['status'] ?? '') !== 'success' ||
        ($verified['data']['status'] ?? '') !== 'successful'
    ) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Verification failed"]);
        exit;
    }

    $flutter_amount = (float)$verified['data']['amount'];

    if (abs($flutter_amount - $db_amount) > 0.01) {
        http_response_code(200);
        echo json_encode([
            "status"  => "error",
            "message" => "Amount mismatch",
            "db"      => $db_amount,
            "flw"     => $flutter_amount
        ]);
        exit;
    }

    $conn->begin_transaction();

    try {
        $updateStmt = $conn->prepare("
            UPDATE paid_orders
            SET status = 'paid', payment_ref = ?
            WHERE session_code = ? AND status = 'open' AND tenant_id = ?
        ");
        $updateStmt->bind_param("ssi", $transaction_id, $tx_ref, $tenant_id);
        $updateStmt->execute();

        if ($updateStmt->affected_rows === 0) {
            $conn->rollback();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Already confirmed by parallel process"]);
            exit;
        }

        $conn->commit();

        $message = "✅ *Session Closed — Payment Confirmed!*\n\n🧾 *Order ID:* #{$order_id}\n💳 *Transaction ID:* {$transaction_id}\n💰 *Amount:* ₦" . number_format($flutter_amount, 2) . "\n🔑 *Session:* {$tx_ref}\n🔔 _Confirmed via webhook_";

        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "chat_id"    => $chatId,
            "text"       => $message,
            "parse_mode" => "Markdown"
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);

        sendEmail(
            $notifyEmail,
            "Session Closed — Order #{$order_id}",
            "<h2>✅ Session Closed</h2>
            <p><b>Order ID:</b> #{$order_id}</p>
            <p><b>Transaction ID:</b> {$transaction_id}</p>
            <p><b>Amount:</b> ₦" . number_format($flutter_amount, 2) . "</p>
            <p><b>Session Code:</b> {$tx_ref}</p>
            <p><small>Confirmed via webhook</small></p>"
        );

        http_response_code(200);
        echo json_encode(["status" => "success", "order_id" => $order_id]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

function handleReservationPayment($conn, $transaction_id, $tx_ref, $flw_secret_hash) {

    $lookupStmt = $conn->prepare("
        SELECT r.id, r.tenant_id, r.table_id, r.name, r.email, r.phone,
               r.booking_date, r.amount, r.reservation_code,
               t.flutterwave_secret_key, t.flutterwave_webhook_hash,
               t.notification_email, t.telegram_bot_token, t.telegram_chat_id
        FROM reservations r
        JOIN tenants t ON t.id = r.tenant_id
        WHERE r.transaction_id = ? AND r.status = 0
        LIMIT 1
    ");
    $lookupStmt->bind_param("s", $tx_ref);
    $lookupStmt->execute();
    $row = $lookupStmt->get_result()->fetch_assoc();
    $lookupStmt->close();

    if (!$row) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Reservation not found or already confirmed"]);
        exit;
    }

    if ($flw_secret_hash !== ($row['flutterwave_webhook_hash'] ?? '')) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid signature"]);
        exit;
    }

    $tenant_id        = (int)$row['tenant_id'];
    $reservation_id   = (int)$row['id'];
    $tableId          = (int)$row['table_id'];
    $secretKey        = $row['flutterwave_secret_key'];
    $notifyEmail      = $row['notification_email'] ?? '';
    $botToken         = $row['telegram_bot_token'] ?? '';
    $chatId           = $row['telegram_chat_id'] ?? '';
    $reservation_code = $row['reservation_code'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response  = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "curl error: $curlError"]);
        exit;
    }

    $verified = json_decode($response, true);

    if (
        !$verified ||
        ($verified['status'] ?? '') !== 'success' ||
        ($verified['data']['status'] ?? '') !== 'successful'
    ) {
        http_response_code(200);
        echo json_encode(["status" => "error", "message" => "Verification failed"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Confirm reservation
        $upStmt = $conn->prepare("
            UPDATE reservations SET status = 1, transaction_id = ?
            WHERE id = ? AND status = 0 AND tenant_id = ?
        ");
        $upStmt->bind_param("sii", $transaction_id, $reservation_id, $tenant_id);
        $upStmt->execute();

        if ($upStmt->affected_rows === 0) {
            $conn->rollback();
            echo json_encode(["status" => "success", "message" => "Already confirmed"]);
            exit;
        }

        // Book the table
        $tStmt = $conn->prepare("
            INSERT INTO booked_tables (tenant_id, table_id, booked)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE booked = 1
        ");
        $tStmt->bind_param("ii", $tenant_id, $tableId);
        $tStmt->execute();

        $conn->commit();

        $amount      = (float)$verified['data']['amount'];
        $bookingDate = $row['booking_date'];
        $name        = $row['name'];
        $phone       = $row['phone'];
        $email       = $row['email'];

        $message = "📅 *New Table Reservation\!*\n\n🎟️ *Code:* {$reservation_code}\n👤 *Name:* {$name}\n📧 *Email:* {$email}\n📞 *Phone:* {$phone}\n🪑 *Table ID:* {$tableId}\n📆 *Date:* {$bookingDate}\n💰 *Amount:* ₦{$amount}\n🔔 _Confirmed via webhook_";

        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "chat_id"    => $chatId,
            "text"       => $message,
            "parse_mode" => "MarkdownV2"
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);

        require_once __DIR__ . '/../SECURE/resendMail.php';
        sendEmail(
            $notifyEmail,
            "New Table Reservation - $reservation_code",
            "<h2>📅 New Table Reservation</h2>
            <p><b>Reservation Code:</b> $reservation_code</p>
            <p><b>Name:</b> $name</p>
            <p><b>Email:</b> $email</p>
            <p><b>Phone:</b> $phone</p>
            <p><b>Table ID:</b> $tableId</p>
            <p><b>Booking Date:</b> $bookingDate</p>
            <p><b>Amount Paid:</b> ₦$amount</p>
            <p><b>Transaction ID:</b> $transaction_id</p>
            <p><small>Confirmed via webhook</small></p>"
        );

        http_response_code(200);
        echo json_encode(["status" => "success", "reservation_id" => $reservation_id]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}