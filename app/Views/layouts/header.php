<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDesc ?? (APP_NAME . ' - Chuỗi bán lẻ điện thoại, laptop, tablet, phụ kiện công nghệ chính hãng giá tốt nhất Việt Nam')) ?>">
    <meta name="keywords" content="<?= e($metaKeywords ?? 'điện thoại chính hãng, iphone, samsung, xiaomi, laptop, phụ kiện giá rẻ, vũ huân shop') ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Vũ Huân Shop">
    <meta name="app-url" content="<?= APP_URL ?>">
    <meta name="csrf-token" content="<?= function_exists('csrfToken') && !isset($this) ? trim(strip_tags(csrfToken())) : $this->generateCsrfToken() ?>">
    <title><?= e($title ?? APP_NAME) ?></title>
    <meta name="google-site-verification" content="google747c1c911645ecc3" />

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= APP_URL . ($_SERVER['REQUEST_URI'] ?? '') ?>">

    <!-- Open Graph / Facebook / Zalo -->
    <meta property="og:type" content="<?= isset($product) ? 'product' : 'website' ?>">
    <meta property="og:url" content="<?= APP_URL . ($_SERVER['REQUEST_URI'] ?? '') ?>">
    <meta property="og:title" content="<?= e($title ?? APP_NAME) ?>">
    <meta property="og:description" content="<?= e($metaDesc ?? (APP_NAME . ' - Điện thoại, Laptop chính hãng, bảo hành 12 tháng, 1 đổi 1 30 ngày.')) ?>">
    <meta property="og:image" content="<?= isset($product['main_image']) ? productImage($product['main_image']) : (APP_URL . '/public/images/logo.png') ?>">
    <meta property="og:site_name" content="<?= APP_NAME ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title ?? APP_NAME) ?>">
    <meta name="twitter:description" content="<?= e($metaDesc ?? (APP_NAME . ' - Điện thoại, Laptop chính hãng.')) ?>">
    <meta name="twitter:image" content="<?= isset($product['main_image']) ? productImage($product['main_image']) : (APP_URL . '/public/images/logo.png') ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/public/css/style.css?v=<?= time() ?>" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/public/images/favicon.png">
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="spinner"></div>
</div>

<!-- ===== Thanh thông báo đầu trang (Announcement Bar) ===== -->
<?php
$announcementEnabled = getSetting('announcement_enabled', '0') === '1';
$announcementText = trim((string)getSetting('announcement_text', ''));
$announcementLink = trim((string)getSetting('announcement_link', ''));
$announcementStart = trim((string)getSetting('announcement_start', ''));
$announcementEnd = trim((string)getSetting('announcement_end', ''));
$announcementVisible = $announcementEnabled && $announcementText !== '';
if ($announcementVisible) {
    $now = time();
    if ($announcementStart !== '' && strtotime($announcementStart) !== false && $now < strtotime($announcementStart)) {
        $announcementVisible = false;
    }
    if ($announcementEnd !== '' && strtotime($announcementEnd) !== false && $now >= strtotime($announcementEnd)) {
        $announcementVisible = false;
    }
}
?>
<?php if ($announcementVisible): ?>
<div class="announcement-bar" id="announcement-bar">
    <div class="container d-flex align-items-center justify-content-center gap-2 py-2" style="position:relative;">
        <i class="fas fa-bullhorn" style="font-size:13px;"></i>
        <?php if ($announcementLink !== ''): ?>
        <a href="<?= url(ltrim($announcementLink, '/')) ?>" style="color:#fff;text-decoration:none;font-size:13.5px;font-weight:600;">
            <?= e($announcementText) ?>
        </a>
        <?php else: ?>
        <span style="color:#fff;font-size:13.5px;font-weight:600;"><?= e($announcementText) ?></span>
        <?php endif; ?>
        <button type="button" id="announcement-close" aria-label="Đóng thông báo" style="position:absolute;right:12px;background:none;border:none;color:rgba(255,255,255,.8);cursor:pointer;font-size:15px;line-height:1;padding:2px;">&times;</button>
    </div>
</div>
<script>
(function(){
    try {
        var bar = document.getElementById('announcement-bar');
        if (!bar) return;
        var m = document.cookie.match(/(?:^|; )announcement_dismissed=([^;]*)/);
        if (m && m[1] === '1') { bar.remove(); return; }
        document.getElementById('announcement-close')?.addEventListener('click', function(){
            bar.remove();
            var secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = 'announcement_dismissed=1; Max-Age=86400; Path=/; SameSite=Lax' + secure;
        });
    } catch(e) {}
})();
</script>
<style>
.announcement-bar { background: linear-gradient(90deg, #b91c1c, #dc2626, #b91c1c); text-align:center; }
</style>
<?php endif; ?>

<!-- ===== Top Bar: hotline, chính sách, theo dõi đơn ===== -->
<div class="header-top d-none d-lg-block">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex">
                <a href="tel:<?= e(str_replace(['.', ' ', '-'], '', getSetting('shop_hotline', '0336.120.405'))) ?>"><i class="fas fa-phone-volume"></i> Hotline: <strong class="topbar-hotline"><?= e(getSetting('shop_hotline', '0336.120.405')) ?></strong></a>
                <a href="<?= url('policies/warranty') ?>"><i class="fas fa-shield-halved"></i> Bảo hành chính hãng</a>
                <a href="<?= url('policies/returns') ?>"><i class="fas fa-rotate-left"></i> Đổi trả trong 30 ngày</a>
            </div>
            <div class="d-flex">
                <a href="<?= url('orders/track') ?>"><i class="fas fa-truck-fast"></i> Theo dõi đơn hàng</a>
                <a href="<?= url('warranty') ?>"><i class="fas fa-magnifying-glass"></i> Tra cứu bảo hành</a>
                <a href="<?= url('news') ?>"><i class="fas fa-newspaper"></i> Tin tức</a>
            </div>
        </div>
    </div>
</div>

<!-- ===== Main Navbar ===== -->
<nav class="navbar navbar-main navbar-expand-lg">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url() ?>">
            <img src="<?= APP_URL ?>/public/images/logo.png" alt="Vũ Huân Shop" style="height: 38px; width: 38px; object-fit: contain; border-radius: 9px;">
            <span>Vũ Huân <span>Shop</span></span>
        </a>

        <!-- Search (Desktop) -->
        <form action="<?= url('search') ?>" method="GET" class="search-wrapper desktop-search-wrapper d-none d-lg-flex align-items-center mb-0">
            <input type="search" name="q" class="search-input" id="search-input" placeholder="Bạn đang tìm điện thoại, laptop, phụ kiện nào..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            <button type="submit" class="search-btn" aria-label="Tìm kiếm">
                <i class="fas fa-search"></i>
            </button>
            <div class="search-suggestions" id="search-suggestions"></div>
        </form>

        <!-- Right Nav -->
        <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto justify-content-end">
            <!-- Wishlist -->
            <?php if (isLoggedIn()): ?>
            <a href="<?= url('wishlist') ?>" class="btn text-white position-relative d-none d-lg-inline-flex align-items-center justify-content-center" title="Sản phẩm yêu thích">
                <i class="far fa-heart fs-5"></i>
            </a>
            <?php endif; ?>

            <!-- Compare -->
            <a href="<?= url('compare') ?>" class="btn text-white d-none d-lg-inline-flex align-items-center justify-content-center" title="So sánh sản phẩm">
                <i class="fas fa-scale-balanced fs-6"></i>
            </a>

            <!-- Cart (Ẩn trên mobile vì đã có Bottom App Bar) -->
            <a href="<?= url('cart') ?>" class="btn text-white cart-icon position-relative d-none d-lg-inline-flex align-items-center justify-content-center" title="Giỏ hàng">
                <i class="fas fa-cart-shopping fs-5"></i>
                <span class="cart-count" style="<?= getCartCount() > 0 ? '' : 'display:none' ?>"><?= getCartCount() ?></span>
            </a>

            <!-- User Menu -->
            <?php if (isLoggedIn()): ?>
            <div class="dropdown">
                <button class="btn text-white dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" style="border:none; background:none; padding: 4px 8px;">
                    <?php if (!empty(currentUser()['avatar'])): ?>
                        <img src="<?= productImage(currentUser()['avatar']) ?>" alt="Avatar" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1.5px solid rgba(255,255,255,0.8);">
                    <?php else: ?>
                        <i class="fas fa-circle-user fs-4"></i>
                    <?php endif; ?>
                    <span class="d-none d-md-inline fw-semibold" style="font-size:13px"><?= e(currentUser()['full_name'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header text-truncate" style="max-width:200px"><?= e(currentUser()['email'] ?? '') ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('user/profile') ?>"><i class="fas fa-user me-2 text-primary"></i>Tài khoản của tôi</a></li>
                    <li><a class="dropdown-item" href="<?= url('orders') ?>"><i class="fas fa-box-open me-2 text-primary"></i>Đơn hàng của tôi</a></li>
                    <li><a class="dropdown-item" href="<?= url('wishlist') ?>"><i class="fas fa-heart me-2 text-primary"></i>Yêu thích</a></li>
                    <?php if (isStaff()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('admin/dashboard') ?>"><i class="fas fa-gauge-high me-2 text-warning"></i>Trang quản trị</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>"><i class="fas fa-arrow-right-from-bracket me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a href="<?= url('auth/login') ?>" class="btn text-white d-flex align-items-center gap-1" title="Đăng nhập" style="padding: 4px 8px;">
                <i class="fas fa-circle-user fs-4"></i> <span class="d-none d-sm-inline fw-semibold" style="font-size:13px">Đăng nhập</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Search Bar (Luôn hiển thị trực tiếp 100% trên điện thoại như Shopee/CellphoneS) -->
    <div class="mobile-search-bar-row d-lg-none w-100 px-3 pb-2 pt-1">
        <form action="<?= url('search') ?>" method="GET" class="mobile-search-form position-relative w-100 m-0">
            <div class="position-relative w-100">
                <input type="search" name="q" class="mobile-search-input" id="search-input-mobile" placeholder="Tìm điện thoại, laptop, phụ kiện..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" class="mobile-search-submit" aria-label="Tìm kiếm">
                    <i class="fas fa-search"></i>
                </button>
                <div class="search-suggestions" id="search-suggestions-mobile"></div>
            </div>
        </form>
    </div>
</nav>

<!-- ===== Category Navigation (Desktop) ===== -->
<nav class="nav-categories d-none d-lg-block">
    <div class="container">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?category=1">
                    <i class="fas fa-mobile-screen"></i> Điện thoại
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?category=2">
                    <i class="fas fa-laptop"></i> Laptop
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?category=3">
                    <i class="fas fa-tablet-screen"></i> Máy tính bảng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?category=4">
                    <i class="fas fa-clock"></i> Đồng hồ thông minh
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?category=5">
                    <i class="fas fa-headphones"></i> Phụ kiện
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('products') ?>?sort=best_selling">
                    <i class="fas fa-fire" style="color:#F97316"></i> Bán chạy
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-extra" href="<?= url('products') ?>?sort=newest">
                    <i class="fas fa-bolt"></i> Hàng mới về
                </a>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link nav-link-extra" href="<?= url('news') ?>">
                    <i class="fas fa-newspaper"></i> Tin tức
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-extra" href="<?= url('warranty') ?>">
                    <i class="fas fa-shield-halved"></i> Tra cứu bảo hành
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-extra" href="<?= url('orders/track') ?>">
                    <i class="fas fa-truck-fast"></i> Theo dõi đơn hàng
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Flash Messages -->
<div class="container mt-3">
    <?php showFlash(); ?>
</div>
