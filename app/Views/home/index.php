<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
// Giá trị mặc định phòng khi biến chưa được truyền từ controller (tránh lỗi undefined)
$banners          = $banners ?? [];
$sideBanners      = $sideBanners ?? [];
$categories       = $categories ?? [];
$featuredProducts = $featuredProducts ?? [];
$newProducts      = $newProducts ?? [];
$bestSelling      = $bestSelling ?? [];
$hotDeals         = $hotDeals ?? [];
$recentNews       = $recentNews ?? [];
$brands           = $brands ?? [];
$flashSaleEnd     = $flashSaleEnd ?? date('Y-m-d 23:59:59');
$flashSaleEnabled = $flashSaleEnabled ?? true;
$flashSaleTitle   = $flashSaleTitle ?? 'FLASH SALE Hôm nay';
$flashSaleDescription = $flashSaleDescription ?? '';
$totalProducts    = $totalProducts ?? 0;
?>

<style>
.hero-banner-img { height: 430px; object-fit: cover; border-radius: 14px; }
.hero-banner-default { min-height: 430px; }
@media (max-width: 991px) {
    .hero-banner-img { height: 300px !important; }
    .hero-banner-default { min-height: 300px; padding: 28px !important; }
    .hero-banner-default h2 { font-size: 24px !important; }
}
@media (max-width: 768px) {
    .hero-banner-img { height: 180px !important; border-radius: 12px; }
    .hero-banner-default { min-height: 180px; padding: 20px !important; }
    .hero-banner-default h2 { font-size: 20px !important; }
    .hero-banner-default p { font-size: 13px !important; margin-bottom: 12px !important; }
}
@media (max-width: 480px) {
    .hero-banner-img { height: 160px !important; }
}
</style>

<!-- ===== Hero Section: Slider + Side Banners ===== -->
<div class="container-fluid px-3 px-xl-4 mt-3">
    <div class="row g-3">
        <!-- Main Slider -->
        <div class="col-lg-8">
            <?php if (!empty($banners)): ?>
            <div id="heroCarousel" class="carousel slide hero-slider" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($banners as $i => $banner): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" <?= $i === 0 ? 'class="active"' : '' ?> aria-label="Banner <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($banners as $i => $banner): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <a href="<?= e($banner['link'] ?? '#') ?>">
                            <img src="<?= productImage($banner['image']) ?>" class="d-block w-100 hero-banner-img" alt="<?= e($banner['title']) ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiIGZpbGw9IiNmNWY1ZjUiLz48Y2lyY2xlIGN4PSIyMDAiIGN5PSIxNjAiIHI9IjUwIiBmaWxsPSIjZTBlMGUwIi8+PGNpcmNsZSBjeD0iMjAwIiBjeT0iMTYwIiByPSIzMCIgZmlsbD0iIzk5OTk5OSIvPjxwYXRoIGQ9Ik0xNDAgMjIwSDI2MlYyODBoLTEyMlYyMjB6IiBmaWxsPSIjOTk5OTk5Ii8+PC9zdmc+'">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
            <?php else: ?>
            <!-- Default Banner nếu chưa có banner trong DB -->
            <div class="hero-banner-default">
                <div style="position:relative;z-index:1">
                    <span style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.3);color:#fff;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:6px 14px;border-radius:999px;margin-bottom:16px">
                        <i class="fas fa-bolt" style="color:#FCD34D"></i> <?= number_format($totalProducts) ?>+ sản phẩm chính hãng
                    </span>
                    <h2 style="color:#fff;font-size:34px;font-weight:800;line-height:1.25;margin-bottom:12px;letter-spacing:-0.5px">Công nghệ đỉnh cao<br>Giá cạnh tranh hấp dẫn</h2>
                    <p style="color:rgba(255,255,255,0.85);font-size:15px;margin-bottom:22px;max-width:480px">iPhone, Samsung, Xiaomi, laptop gaming và phụ kiện chính hãng — bảo hành 12 tháng, 1 đổi 1 trong 30 ngày.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= url('products') ?>" class="btn btn-warning btn-lg fw-bold rounded-pill px-4" style="color:#7F1D1D">Mua ngay <i class="fas fa-arrow-right ms-1"></i></a>
                        <a href="<?= url('products') ?>?sort=best_selling" class="btn btn-lg rounded-pill px-4 text-white" style="border:1.5px solid rgba(255,255,255,0.5)">Bán chạy nhất</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Side Banners -->
        <div class="col-lg-4 d-none d-lg-block">
            <div class="d-flex flex-column gap-3 h-100 justify-content-between">
                <?php
                $sideBannerDefaults = [
                    ['title' => 'iPhone 16 Pro Max - Giảm đến 30%', 'link' => url('products') . '?brand=1', 'eyebrow' => 'HOT DEAL HÔM NAY', 'class' => 'promo-banner-blue'],
                    ['title' => 'MSI & Asus ROG - Trả góp 0%', 'link' => url('products') . '?category=2', 'eyebrow' => 'LAPTOP GAMING', 'class' => 'promo-banner-green'],
                ];
                for ($i = 0; $i < 2; $i++):
                    $sideBanner = $sideBanners[$i] ?? null;
                    $promo = $sideBannerDefaults[$i];
                    $promoTitle = !empty($sideBanner['title']) ? $sideBanner['title'] : $promo['title'];
                    $promoLink = !empty($sideBanner['link']) ? $sideBanner['link'] : ($sideBanner ? url('products') : $promo['link']);
                    $promoImage = !empty($sideBanner['image']) ? productImage($sideBanner['image']) : '';
                ?>
                <?php if ($promoImage): ?>
                    <a href="<?= e($promoLink) ?>" class="d-block side-banner-card" style="height:209px; border-radius:14px; overflow:hidden; position:relative;">
                        <img src="<?= e($promoImage) ?>" alt="<?= e($promoTitle) ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.35s ease;" loading="lazy" onerror="this.onerror=null;this.src='<?= APP_URL ?>/public/images/no-image.png'" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                    </a>
                <?php else: ?>
                    <a class="promo-banner <?= e($promo['class']) ?>" href="<?= e($promoLink) ?>" style="height:209px; margin:0; border-radius:14px;">
                        <span class="promo-banner-eyebrow"><?= e($promo['eyebrow']) ?></span>
                        <span class="promo-banner-title"><?= e($promoTitle) ?></span>
                        <span class="promo-banner-link">Xem ngay <i class="fas fa-arrow-right ms-1"></i></span>
                    </a>
                <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== Trust Badges ===== -->
<div class="container mt-3">
    <div class="trust-strip">
        <div class="row g-2">
            <?php $trustItems = [
                ['fas fa-shield-halved', '#16A34A', '#F0FDF4', 'Hàng chính hãng', 'Cam kết 100%'],
                ['fas fa-truck-fast', '#2563EB', '#EFF6FF', 'Giao hàng nhanh', 'Trong ngày nội thành'],
                ['fas fa-rotate-left', '#D97706', '#FFFBEB', 'Đổi trả dễ dàng', 'Theo điều kiện SP'],
                ['fas fa-credit-card', '#7C3AED', '#FAF5FF', 'Trả góp 0%', 'Kỳ hạn 12-24 tháng'],
                ['fas fa-headset', '#DC2626', '#FEF2F2', 'Hỗ trợ 24/7', 'Luôn sẵn sàng'],
            ]; ?>
            <?php foreach ($trustItems as $item): ?>
            <div class="col-6 col-md-4 col-lg">
                <div class="trust-item">
                    <div class="trust-item-icon" style="background:<?= $item[2] ?>">
                        <i class="<?= $item[0] ?>" style="color:<?= $item[1] ?>"></i>
                    </div>
                    <div>
                        <div class="trust-item-title"><?= $item[3] ?></div>
                        <div class="trust-item-sub"><?= $item[4] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== Categories ===== -->
<?php if (!empty($categories)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-grip"></i> Danh mục <span>nổi bật</span></h2>
        <a href="<?= url('products') ?>" class="section-link">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
        <a href="<?= url('products') ?>?category=<?= $cat['id'] ?>" class="category-card">
            <div class="category-card-icon">
                <?php
                $icons = ['Điện Thoại' => '📱', 'Laptop' => '💻', 'Máy Tính Bảng' => '📟', 'Đồng Hồ Thông Minh' => '⌚', 'Phụ Kiện' => '🎧'];
                $icon = $icons[$cat['name']] ?? '🛍️';
                echo $icon;
                ?>
            </div>
            <div class="category-card-name"><?= e($cat['name']) ?></div>
            <div class="category-card-count"><?= number_format($cat['product_count']) ?> sản phẩm</div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== Flash Sale ===== -->
<?php if ($flashSaleEnabled && !empty($hotDeals)): ?>
<div class="container mt-4">
    <div class="hot-deal-section">
        <div class="hot-deal-header">
            <div class="hot-deal-left">
                <h2 class="hot-deal-title">
                    <span class="hot-deal-icon" aria-hidden="true"><i class="fas fa-fire"></i></span>
                    <span class="hot-deal-title-text"><?= e($flashSaleTitle) ?></span>
                </h2>
                <?php if ($flashSaleDescription !== ''): ?>
                <p class="hot-deal-desc mb-0"><?= e($flashSaleDescription) ?></p>
                <?php endif; ?>
            </div>
            <div class="hot-deal-right">
                <div class="hot-deal-timer-wrap">
                    <span class="timer-label d-none d-md-inline"><i class="far fa-clock me-1"></i>Kết thúc trong</span>
                    <div class="hot-deal-timer" id="countdown-timer" data-end="<?= e($flashSaleEnd) ?>">
                        <div class="timer-unit"><span class="timer-h">00</span><small>Giờ</small></div>
                        <span class="timer-colon">:</span>
                        <div class="timer-unit"><span class="timer-m">00</span><small>Phút</small></div>
                        <span class="timer-colon">:</span>
                        <div class="timer-unit"><span class="timer-s">00</span><small>Giây</small></div>
                    </div>
                </div>
                <div class="hot-deal-nav">
                    <button type="button" class="hot-deal-nav-btn" id="hotdeal-prev" aria-label="Xem sản phẩm trước"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="hot-deal-nav-btn" id="hotdeal-next" aria-label="Xem sản phẩm sau"><i class="fas fa-chevron-right"></i></button>
                    <a href="<?= url('products') ?>" class="hot-deal-more">Xem tất cả<i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
        <div class="hot-deal-viewport">
            <div class="products-scroll hot-deal-track" id="hotdeal-track">
                <?php $GLOBALS['__product_card_index'] = 0; ?>
                <?php foreach ($hotDeals as $product): ?>
                <?php include __DIR__ . '/../partials/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== Featured Products ===== -->
<?php if (!empty($featuredProducts)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-star"></i> Sản phẩm <span>nổi bật</span></h2>
        <a href="<?= url('products') ?>" class="section-link">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
        <?php include __DIR__ . '/../partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== New Products ===== -->
<?php if (!empty($newProducts)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-bolt"></i> Sản phẩm <span>mới nhất</span></h2>
        <a href="<?= url('products') ?>?sort=newest" class="section-link">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="products-scroll">
        <?php foreach ($newProducts as $product): ?>
        <?php include __DIR__ . '/../partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== Best Selling ===== -->
<?php if (!empty($bestSelling)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-fire"></i> Bán chạy <span>nhất</span></h2>
        <a href="<?= url('products') ?>?sort=best_selling" class="section-link">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="products-grid">
        <?php foreach ($bestSelling as $product): ?>
        <?php include __DIR__ . '/../partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== Brands ===== -->
<?php if (!empty($brands)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-tags"></i> Thương hiệu <span>nổi tiếng</span></h2>
    </div>
    <div class="brands-strip d-flex align-items-center gap-1 flex-wrap justify-content-center">
        <?php foreach ($brands as $brand): ?>
        <a href="<?= url('products') ?>?brand=<?= $brand['id'] ?>" class="brand-item flex-column text-center" style="min-width:90px">
            <?php if ($brand['logo']): ?>
            <img src="<?= productImage($brand['logo']) ?>" alt="<?= e($brand['name']) ?>" style="height:32px;object-fit:contain" loading="lazy" onerror="this.onerror=null;this.src='<?= APP_URL ?>/public/images/no-image.png'">
            <?php else: ?>
            <div style="font-size:13px;font-weight:800;color:#334155"><?= e($brand['name']) ?></div>
            <?php endif; ?>
            <div style="font-size:10px;color:#94A3B8;margin-top:4px;font-weight:600"><?= number_format($brand['product_count']) ?> SP</div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== News ===== -->
<?php if (!empty($recentNews)): ?>
<div class="container mt-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-newspaper"></i> Tin tức <span>công nghệ</span></h2>
        <a href="<?= url('news') ?>" class="section-link">Xem tất cả <i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="row g-3">
        <?php foreach ($recentNews as $news): ?>
        <div class="col-6 col-md-3">
            <div class="news-card">
                <div class="news-card-img">
                    <a href="<?= url('news/' . $news['slug']) ?>">
                        <img src="<?= productImage($news['featured_image']) ?>" alt="<?= e($news['title']) ?>" loading="lazy" onerror="this.src='<?= APP_URL ?>/public/images/no-image.png'">
                    </a>
                </div>
                <div class="news-card-body">
                    <div class="news-card-category"><?= e($news['category'] ?? 'Công nghệ') ?></div>
                    <h3 class="news-card-title">
                        <a href="<?= url('news/' . $news['slug']) ?>"><?= e($news['title']) ?></a>
                    </h3>
                    <div class="news-card-meta">
                        <span><i class="fas fa-clock me-1"></i><?= timeAgo($news['published_at'] ?? $news['created_at']) ?></span>
                        <span><i class="fas fa-eye me-1"></i><?= number_format($news['view_count']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div style="height:40px"></div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
// Initialize countdown for flash sale
document.getElementById('countdown-timer') && (function() {
    Timer.init();
})();

// Flash Sale carousel: nút trái/phải thay thanh cuộn ngang
(function initHotDealCarousel() {
    const track = document.getElementById('hotdeal-track');
    const prevBtn = document.getElementById('hotdeal-prev');
    const nextBtn = document.getElementById('hotdeal-next');
    if (!track || !prevBtn || !nextBtn) return;

    const step = () => {
        const card = track.querySelector('.product-card');
        if (!card) return 240;
        const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 14) || 14;
        return card.getBoundingClientRect().width + gap;
    };

    const syncButtons = () => {
        const maxScroll = track.scrollWidth - track.clientWidth - 2;
        prevBtn.disabled = track.scrollLeft <= 2;
        nextBtn.disabled = track.scrollLeft >= maxScroll;
    };

    prevBtn.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    nextBtn.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', syncButtons, { passive: true });
    window.addEventListener('resize', syncButtons);
    syncButtons();
})();
</script>
