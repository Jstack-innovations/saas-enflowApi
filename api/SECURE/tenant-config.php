<?php
// GET /tenant-config
// Returns branding + identity config for the current tenant

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tenant_pdo.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Tenant');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$tenantId = getTenantIdPDO($pdo);


try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            slug,
            logo,
            tagline,
            established,
            hero_stat_dishes,
            hero_stat_categories,
            hero_stat_years,
            footer_description,
            primary_color,
            social_instagram,
            social_facebook,
            social_twitter,
            social_linkedin,
            plan
        FROM tenants
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tenant) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tenant not found']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'config' => [
            'id'           => (int) $tenant['id'],
            'name'         => $tenant['name'],
            'slug'         => $tenant['slug'],
            'logo'         => $tenant['logo'],
            'tagline'      => $tenant['tagline'],
            'established'  => $tenant['established'],
            'plan'         => $tenant['plan'],
            'hero' => [
                'stat_dishes'      => $tenant['hero_stat_dishes']      ?? '—',
                'stat_categories'  => $tenant['hero_stat_categories']  ?? '—',
                'stat_years'       => $tenant['hero_stat_years']        ?? '—',
            ],
            'footer' => [
                'description' => $tenant['footer_description'],
            ],
            'brand' => [
                'primary_color' => $tenant['primary_color'] ?? '#d4a853',
            ],
            'socials' => [
                'instagram' => $tenant['social_instagram'],
                'facebook'  => $tenant['social_facebook'],
                'twitter'   => $tenant['social_twitter'],
                'linkedin'  => $tenant['social_linkedin'],
            ],
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
