<?php

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

$basePath = __DIR__ . "/api";

/* API ROOT */
if ($uri === '' || $uri === '/') {
    echo json_encode(["message" => "API running"]);
    exit;
}



if ($uri === "/env") {
    require $basePath . "/env.php";
    exit;
}

/* MAIN ROUTES */
/*GET ROUTES*/
if ($uri === "/menu") {
    require $basePath . "/GET/CORS/MenuJson.php";
    exit;
}

if ($uri === "/banner") {
    require $basePath . "/GET/CORS/BannerJson.php";
    exit;
}

if ($uri === "/offer") {
    require $basePath . "/GET/CORS/OffersJson.php";
    exit;
}

if ($uri === "/table") {
    require $basePath . "/GET/CORS/TablesJson.php";
    exit;
}

if ($uri === "/tax") {
    require $basePath . "/GET/CORS/TaxJson.php";
    exit;
}

if ($uri === "/getBooked") {
    require $basePath . "/GET/CORS/get-booked.php";
    exit;
}

if ($uri === "/loggedInOrders") {
    require $basePath . "/GET/CORS/getLoggedinOrders.php";
    exit;
}

if ($uri === "/orders") {
    require $basePath . "/GET/CORS/get_order.php";
    exit;
}

if ($uri === "/reservationSuccess") {
    require $basePath . "/GET/CORS/reservation_success.php";
    exit;
}

if ($uri === "/tracking") {
    require $basePath . "/GET/CORS/track_order.php";
    exit;
}

/* POST ROUTES */

if ($uri === "/bookTable") {
    require $basePath . "/POST/book_table.php";
    exit;
}

if ($uri === "/saveOrder") {
    require $basePath . "/POST/save_paid_order.php";
    exit;
}

if ($uri === "/login") {
    require $basePath . "/POST/AUTH/login.php";
    exit;
}

if ($uri === "/resendOtp") {
    require $basePath . "/POST/AUTH/resend-otp.php";
    exit;
}

if ($uri === "/signUp") {
    require $basePath . "/POST/AUTH/signup.php";
    exit;
}

if ($uri === "/session") {
    require $basePath . "/POST/AUTH/session_validate.php";
    exit;
}

if ($uri === "/verifyLogin") {
    require $basePath . "/POST/AUTH/verify-login.php";
    exit;
}

if ($uri === "/verify") {
    require $basePath . "/POST/AUTH/verify.php";
    exit;
}


/* PUT ROUTES */

/* SECURE ROUTES */
if ($uri === "/flutterwave") {
    require $basePath . "/SECURE/flutterwave-key.php";
    exit;
}

if ($uri === "/mail") {
    require $basePath . "/SECURE/mail_config.php";
    exit;
}



/* ADMINS ROUTES */

/*GET ROUTES */

if ($uri === "/admin") {
    require $basePath . "/admins/GET/admins.php";
    exit;
}

if ($uri === "/checkSession") {
    require $basePath . "/admins/GET/check_session.php";
    exit;
}

if ($uri === "/fetchOrder") {
    require $basePath . "/admins/GET/fetch_order.php";
    exit;
}

if ($uri === "/getMenu") {
    require $basePath . "/admins/GET/get_menu.php";
    exit;
}

if ($uri === "/getReservations") {
    require $basePath . "/admins/GET/get_reservations.php";
    exit;
}

if ($uri === "/getTable") {
    require $basePath . "/admins/GET/get_tables.php";
    exit;
}

if ($uri === "/getTax") {
    require $basePath . "/admins/GET/get_tax.php";
    exit;
}

if ($uri === "/getOrder") {
    require $basePath . "/admins/GET/orders.php";
    exit;
}

if ($uri === "/UandV") {
    require $basePath . "/admins/GET/users_and_verifications.php";
    exit;
}


/* POST ROUTES */


if ($uri === "/adminAddOrder") {
    require $basePath . "/admins/POST/add_order.php";
    exit;
}

if ($uri === "/adminLogin") {
    require $basePath . "/admins/POST/login.php";
    exit;
}

if ($uri === "/adminLogout") {
    require $basePath . "/admins/POST/logout.php";
    exit;
}

if ($uri === "/adminBanner") {
    require $basePath . "/admins/POST/save_banner.php";
    exit;
}

if ($uri === "/adminOffer") {
    require $basePath . "/admins/POST/save_offers.php";
    exit;
}


/* PUT ROUTES */


if ($uri === "/adminEditOrder") {
    require $basePath . "/admins/PUT/edit_order.php";
    exit;
}

if ($uri === "/adminUpdateMenu") {
    require $basePath . "/admins/PUT/update_menu.php";
    exit;
}

if ($uri === "/adminUpdateOrderStatus") {
    require $basePath . "/admins/PUT/update_order_status.php";
    exit;
}

if ($uri === "/adminUpdateReservations") {
    require $basePath . "/admins/PUT/update_reservations.php";
    exit;
}

if ($uri === "/adminUpdateTable") {
    require $basePath . "/admins/PUT/update_table.php";
    exit;
}

if ($uri === "/adminUpdateTax") {
    require $basePath . "/admins/PUT/update_tax.php";
    exit;
}

if ($uri === "/adminUpdateUser") {
    require $basePath . "/admins/PUT/update_user.php";
    exit;
}



/* DELETE ROUTES */


if ($uri === "/adminDeleteOrder") {
    require $basePath . "/admins/DELETE/delete_order.php";
    exit;
}

if ($uri === "/adminDeleteUser") {
    require $basePath . "/admins/DELETE/delete_user.php";
    exit;
}




/* 404 */
http_response_code(404);
echo json_encode(["error" => "Route not found"]);
