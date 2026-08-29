<?php
require_once __DIR__ . '/config.php';

if (!function_exists('thikana_db')) {
    function thikana_db(): ?PDO
    {
        global $pdo;
        return $pdo instanceof PDO ? $pdo : null;
    }
}

if (!function_exists('thikana_host_onboarding_complete')) {
    function thikana_host_onboarding_complete($userId): bool
    {
        $db = thikana_db();
        if (!$db) {
            return false;
        }

        $stmt = $db->prepare('SELECT onboarding_complete FROM users WHERE id = ? AND role = ? LIMIT 1');
        $stmt->execute([$userId, 'host']);
        $user = $stmt->fetch();

        return $user && (int) ($user['onboarding_complete'] ?? 0) === 1;
    }
}

if (!function_exists('thikana_host_dashboard_redirect')) {
    function thikana_host_dashboard_redirect($userId): string
    {
        return thikana_host_onboarding_complete($userId)
            ? 'dashboard_host.php'
            : 'host_onboarding.php';
    }
}
?>
