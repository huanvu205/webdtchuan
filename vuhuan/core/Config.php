<?php
/**
 * CẤU HÌNH WEBSITE VŨ HUÂN SHOP
 * Hỗ trợ: Local + InfinityFree + BKNS
 */

// =====================================================
// 1. ĐỌC FILE .env
// =====================================================

// Đặt file .env ở thư mục cha của thư mục chứa Config.php.
// Ví dụ: /home/vuhuansh/.env
$appEnvPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

if (is_readable($appEnvPath)) {
    foreach (file($appEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $envLine) {

        $envLine = preg_replace('/^\xEF\xBB\xBF/', '', $envLine);
        $envLine = trim($envLine);

        if (
            $envLine === '' ||
            $envLine[0] === '#' ||
            strpos($envLine, '=') === false
        ) {
            continue;
        }

        [$envName, $envValue] = explode('=', $envLine, 2);

        $envName = trim($envName);

        if (strpos($envName, 'export ') === 0) {
            $envName = trim(substr($envName, 7));
        }

        $envValue = trim($envValue);

        if ($envName === '') {
            continue;
        }

        $existingValue = getenv($envName);

        if ($existingValue !== false && $existingValue !== '') {
            continue;
        }

        if (
            strlen($envValue) >= 2 &&
            (
                ($envValue[0] === '"' && substr($envValue, -1) === '"') ||
                ($envValue[0] === "'" && substr($envValue, -1) === "'")
            )
        ) {
            $envValue = substr($envValue, 1, -1);
        }

        if (function_exists('putenv')) {
            @putenv($envName . '=' . $envValue);
        }

        $_ENV[$envName] = $envValue;
        $_SERVER[$envName] = $envValue;
    }
}

// =====================================================
// 2. HÀM ĐỌC BIẾN MÔI TRƯỜNG
// =====================================================

if (!function_exists('appEnv')) {
    function appEnv($name, $default = '') {

        $value = getenv($name);

        if ($value === false || $value === '') {
            $value = $_ENV[$name] ?? $_SERVER[$name] ?? $default;
        }

        return $value;
    }
}

// =====================================================
// 3. NHẬN DIỆN HOSTING
// =====================================================

$host = strtolower($_SERVER['HTTP_HOST'] ?? '');

$host = preg_replace('/:\d+$/', '', $host);

// Local
$isLocal = (
    PHP_SAPI === 'cli' ||
    $host === 'localhost' ||
    $host === '127.0.0.1'
);

// BKNS: nhận diện domain chính và subdomain
$isBkns = (
    $host === 'vuhuanshop.online' ||
    $host === 'www.vuhuanshop.online' ||
    substr($host, -strlen('.vuhuanshop.online')) === '.vuhuanshop.online'
);

// InfinityFree: các domain production còn lại
$isInfinity = (!$isLocal && !$isBkns);

define('APP_ENV', $isLocal ? 'local' : 'production');

// =====================================================
// 4. CẤU HÌNH DATABASE
// =====================================================

if ($isBkns) {

    // -----------------------------------------------
    // DATABASE BKNS
    // Domain: vuhuanshop.online
    // -----------------------------------------------

    define('DB_HOST', 'localhost');
    define('DB_USER', 'vuhuansh_shopuser');
    define('DB_PASS', (string) appEnv('BKNS_DB_PASS', ''));
    define('DB_NAME', 'vuhuansh_vuhuansh_shopnew');
    define('DB_TYPE', 'mysql');

} elseif ($isInfinity) {

    // -----------------------------------------------
    // DATABASE INFINITYFREE
    // -----------------------------------------------

    define('DB_HOST', 'sql207.infinityfree.com');
    define('DB_USER', 'if0_39800571');
    define('DB_PASS', (string) appEnv('INFINITY_DB_PASS', ''));
    define('DB_NAME', 'if0_39800571_shopbandienthoai');
    define('DB_TYPE', 'mysql');

} else {

    // -----------------------------------------------
    // DATABASE LOCAL - SQL SERVER
    // -----------------------------------------------

    define('DB_HOST', 'LAPTOP-64QR9LTM\\VUVANHUAN');
    define('DB_USER', '');
    define('DB_PASS', '');
    define('DB_NAME', 'webbandienthoaimoi');
    define('DB_TYPE', 'mssql_ps');
}

// =====================================================
// 5. CẤU HÌNH ỨNG DỤNG
// =====================================================

define('APP_NAME', 'Vũ Huân Shop');

define(
    'GEMINI_API_KEY',
    (string) appEnv('GEMINI_API_KEY', '')
);

define(
    'GEMINI_MODEL',
    (string) appEnv('GEMINI_MODEL', 'gemini-3.5-flash-lite')
);

// =====================================================
// 6. TỰ ĐỘNG NHẬN DIỆN HTTP / HTTPS
// =====================================================

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    ($_SERVER['SERVER_PORT'] ?? 80) == 443 ||
    (
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
    ) ||
    (
        isset($_SERVER['HTTP_CF_VISITOR']) &&
        strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false
    ) ||
    (
        isset($_SERVER['HTTP_X_FORWARDED_SSL']) &&
        strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on'
    ) ||
    (
        isset($_SERVER['HTTP_X_FORWARDED_PORT']) &&
        $_SERVER['HTTP_X_FORWARDED_PORT'] == 443
    )
);

$protocol = $isHttps ? 'https://' : 'http://';

$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

define('APP_URL', $protocol . $currentHost);

define('UPLOAD_DIR', 'public/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// =====================================================
// 7. SESSION
// =====================================================

define('SESSION_TIMEOUT', 30 * 60);

// =====================================================
// 8. EMAIL
// =====================================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);

define('SMTP_USER', (string) appEnv('SMTP_USER', ''));
define('SMTP_PASS', (string) appEnv('SMTP_PASS', ''));

define('ADMIN_EMAIL', (string) appEnv('ADMIN_EMAIL', ''));

// =====================================================
// 9. PHÂN QUYỀN
// =====================================================

define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_CUSTOMER', 'customer');
define('ROLE_GUEST', 'guest');

// =====================================================
// 10. SẢN PHẨM
// =====================================================

define('PRODUCTS_PER_PAGE', 15);
define('REVIEWS_PER_PAGE', 5);

// =====================================================
// 11. VẬN CHUYỂN
// =====================================================

define('SHIPPING_FEE_FREE_AMOUNT', 500000);
define('SHIPPING_FEE_STANDARD', 30000);

// =====================================================
// 12. VNPAY
// =====================================================

define('VNPAY_TMN_CODE', (string) appEnv('VNPAY_TMN_CODE', ''));

define('VNPAY_HASH_SECRET', (string) appEnv('VNPAY_HASH_SECRET', ''));

define(
    'VNPAY_URL',
    (string) appEnv(
        'VNPAY_URL',
        'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
    )
);

// =====================================================
// 13. TELEGRAM
// =====================================================

define('TELEGRAM_BOT_TOKEN', (string) appEnv('TELEGRAM_BOT_TOKEN', ''));

define('TELEGRAM_CHAT_ID', (string) appEnv('TELEGRAM_CHAT_ID', ''));

define(
    'TELEGRAM_API_URL',
    'https://telegram-proxy.vuhuan123890.workers.dev'
);

// =====================================================
// 14. GOOGLE OAUTH
// =====================================================

define(
    'GOOGLE_CLIENT_ID',
    (string) appEnv('GOOGLE_CLIENT_ID', '')
);

define(
    'GOOGLE_CLIENT_SECRET',
    (string) appEnv('GOOGLE_CLIENT_SECRET', '')
);

define(
    'GOOGLE_REDIRECT_URI',
    APP_URL . '/auth/google-callback'
);

// =====================================================
// 15. FACEBOOK OAUTH - HIỆN ĐANG TẮT
// =====================================================

define('FACEBOOK_APP_ID', '');
define('FACEBOOK_APP_SECRET', '');

define(
    'FACEBOOK_REDIRECT_URI',
    APP_URL . '/auth/facebook-callback'
);