<!-- Back to Top -->
<button class="back-to-top" title="Lên đầu trang"><i class="fas fa-chevron-up"></i></button>

<!-- ===== Footer ===== -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <!-- About -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-logo"><i class="fas fa-mobile-screen"></i> Vũ Huân <span>Shop</span></div>
                <p class="footer-desc">
                    Chuỗi bán lẻ điện thoại, laptop, tablet, đồng hồ thông minh và phụ kiện công nghệ hàng đầu Việt Nam.
                    Cam kết hàng chính hãng 100%, giá tốt nhất thị trường, dịch vụ tận tâm.
                </p>
                <?php
                $facebookUrl = trim((string)getSetting('shop_facebook', ''));
                $facebookParts = parse_url($facebookUrl);
                $facebookHost = strtolower($facebookParts['host'] ?? '');
                $facebookPath = trim($facebookParts['path'] ?? '', '/');
                $showFacebook = ($facebookParts['scheme'] ?? '') === 'https' && in_array($facebookHost, ['facebook.com', 'www.facebook.com'], true) && $facebookPath !== '';

                $zaloRaw = trim((string)getSetting('shop_zalo', ''));
                // Zalo có thể nhập số điện thoại hoặc link zalo.me đầy đủ
                if ($zaloRaw !== '' && preg_match('/^[0-9]{9,11}$/', $zaloRaw)) {
                    $zaloUrl = 'https://zalo.me/' . $zaloRaw;
                } else {
                    $zaloUrl = (preg_match('#^https?://#i', $zaloRaw) || strpos($zaloRaw, 'zalo.me/') === 0) ? (preg_match('#^https?://#i', $zaloRaw) ? $zaloRaw : 'https://' . $zaloRaw) : '';
                }

                $tiktokUrl = trim((string)getSetting('shop_tiktok', ''));
                if ($tiktokUrl !== '' && !preg_match('#^https?://#i', $tiktokUrl)) {
                    $tiktokUrl = strpos($tiktokUrl, 'tiktok.com/') === 0 ? 'https://' . $tiktokUrl : '';
                }

                $mapsUrl = trim((string)getSetting('shop_maps_url', ''));
                if ($mapsUrl !== '' && !preg_match('#^https?://#i', $mapsUrl)) {
                    $mapsUrl = '';
                }
                ?>
                <?php if ($showFacebook || $zaloUrl !== '' || $tiktokUrl !== '' || $mapsUrl !== ''): ?>
                    <div class="social-links">
                        <?php if ($showFacebook): ?>
                        <a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if ($zaloUrl !== ''): ?>
                        <a href="<?= e($zaloUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="Zalo: <?= e($zaloRaw) ?>"><i class="fas fa-comment-dots"></i></a>
                        <?php endif; ?>
                        <?php if ($tiktokUrl !== ''): ?>
                        <a href="<?= e($tiktokUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="TikTok"><i class="fab fa-tiktok"></i></a>
                        <?php endif; ?>
                        <?php if ($mapsUrl !== ''): ?>
                        <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="social-link" title="Bản đồ Google Maps"><i class="fas fa-map-location-dot"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6>Sản phẩm</h6>
                <ul class="footer-links">
                    <li><a href="<?= url('products') ?>?category=1"><i class="fas fa-chevron-right"></i>Điện thoại</a></li>
                    <li><a href="<?= url('products') ?>?category=2"><i class="fas fa-chevron-right"></i>Laptop</a></li>
                    <li><a href="<?= url('products') ?>?category=3"><i class="fas fa-chevron-right"></i>Máy tính bảng</a></li>
                    <li><a href="<?= url('products') ?>?category=4"><i class="fas fa-chevron-right"></i>Đồng hồ thông minh</a></li>
                    <li><a href="<?= url('products') ?>?category=5"><i class="fas fa-chevron-right"></i>Phụ kiện</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6>Chính sách & Hỗ trợ</h6>
                <ul class="footer-links">
                    <li><a href="<?= url('policies/warranty') ?>"><i class="fas fa-chevron-right"></i>Chính sách bảo hành</a></li>
                    <li><a href="<?= url('policies/returns') ?>"><i class="fas fa-chevron-right"></i>Chính sách đổi trả</a></li>
                    <li><a href="<?= url('policies/shipping') ?>"><i class="fas fa-chevron-right"></i>Giao hàng & Vận chuyển</a></li>
                    <li><a href="<?= url('policies/payment') ?>"><i class="fas fa-chevron-right"></i>Phương thức thanh toán</a></li>
                    <li><a href="<?= url('policies/complaints') ?>"><i class="fas fa-chevron-right"></i>Giải quyết khiếu nại</a></li>
                    <li><a href="<?= url('policies/terms') ?>"><i class="fas fa-chevron-right"></i>Điều khoản sử dụng</a></li>
                    <li><a href="<?= url('policies/privacy') ?>"><i class="fas fa-chevron-right"></i>Chính sách bảo mật</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h6>Liên hệ</h6>
                <?php $footerAddress = trim((string)getSetting('shop_address', 'Trực Đại, Trực Ninh, Nam Định')); ?>
                <div class="footer-contact-item">
                    <i class="fas fa-location-dot"></i>
                    <span>
                        <?php if (!empty($mapsUrl)): ?>
                        <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($footerAddress) ?></a>
                        <?php else: ?>
                        <?= e($footerAddress) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-phone"></i>
                    <span>Hotline: <a href="tel:<?= str_replace(['.', ' ', '-'], '', getSetting('shop_hotline', '0336.120.405')) ?>"><?= e(getSetting('shop_hotline', '0336.120.405')) ?></a></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-envelope"></i>
                    <span><a href="mailto:<?= e(getSetting('shop_email', 'vuhuan123890@gmail.com')) ?>"><?= e(getSetting('shop_email', 'vuhuan123890@gmail.com')) ?></a></span>
                </div>
                <?php if ($zaloUrl !== ''): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-comment-dots"></i>
                    <span>Zalo: <a href="<?= e($zaloUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($zaloRaw) ?></a></span>
                </div>
                <?php endif; ?>
                <?php
                $footerOpeningHours = trim((string)getSetting('shop_opening_hours', '8:00 - 21:00'));
                $footerDeliveryTime = trim((string)getSetting('shipping_delivery_time', ''));
                ?>
                <?php if ($footerOpeningHours !== ''): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Mở cửa: <?= e($footerOpeningHours) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($footerDeliveryTime !== ''): ?>
                <div class="footer-contact-item">
                    <i class="fas fa-truck-fast"></i>
                    <span>Giao hàng: <?= e($footerDeliveryTime) ?></span>
                </div>
                <?php endif; ?>

                <!-- Newsletter -->
                <div class="mt-3">
                    <p style="font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:8px">Đăng ký nhận ưu đãi độc quyền:</p>
                    <div class="d-flex gap-2">
                        <input type="email" id="newsletter-email" class="form-control footer-newsletter-input" placeholder="Email của bạn">
                        <button id="newsletter-btn" class="btn btn-danger btn-sm" style="white-space:nowrap;font-weight:700">Đăng ký</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust Badges -->
        <div class="row mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.08)">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-4 justify-content-center">
                    <div class="footer-trust-item">
                        <div class="footer-trust-icon"><i class="fas fa-shield-halved"></i></div>
                        <span>Hàng chính hãng<br><small>100% bảo đảm</small></span>
                    </div>
                    <div class="footer-trust-item">
                        <div class="footer-trust-icon"><i class="fas fa-truck-fast"></i></div>
                        <span>Giao hàng nhanh<br><small>Trong ngày</small></span>
                    </div>
                    <div class="footer-trust-item">
                        <div class="footer-trust-icon"><i class="fas fa-rotate-left"></i></div>
                        <span>Đổi trả dễ dàng<br><small>Theo điều kiện sản phẩm</small></span>
                    </div>
                    <div class="footer-trust-item">
                        <div class="footer-trust-icon"><i class="fas fa-headset"></i></div>
                        <span>Hỗ trợ 24/7<br><small>Sẵn sàng tư vấn</small></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legal & Business Info -->
        <div class="row mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.08); font-size:12px; color:rgba(255,255,255,0.55); line-height: 1.7;">
            <div class="col-md-12">
                <p class="mb-1"><strong>HỘ KINH DOANH VŨ HUÂN STORE</strong></p>
                <p class="mb-1">Địa chỉ: Trực Đại, Trực Ninh, Tỉnh Nam Định, Việt Nam · Hotline: 0336.120.405 · Email: vuhuan123890@gmail.com</p>
            </div>
        </div>
    </div>
</footer>

<!-- ===== Mobile Bottom App Bar (5 nút) ===== -->
<div class="mobile-bottom-nav d-lg-none">
    <a href="<?= url() ?>" class="bottom-nav-item">
        <i class="fas fa-house"></i>
        <span>Trang chủ</span>
    </a>
    <a href="#offcanvasCategories" data-bs-toggle="offcanvas" class="bottom-nav-item">
        <i class="fas fa-grip"></i>
        <span>Danh mục</span>
    </a>
    <a href="<?= url('cart') ?>" class="bottom-nav-item position-relative">
        <i class="fas fa-cart-shopping"></i>
        <span class="bottom-nav-cart-badge cart-count" style="<?= getCartCount() > 0 ? '' : 'display:none' ?>"><?= getCartCount() ?></span>
        <span>Giỏ hàng</span>
    </a>
    <a href="<?= url('compare') ?>" class="bottom-nav-item">
        <i class="fas fa-scale-balanced"></i>
        <span>So sánh</span>
    </a>
    <a href="https://zalo.me/0918841790" target="_blank" rel="noopener noreferrer" class="bottom-nav-item">
        <i class="fas fa-comment-dots"></i>
        <span>Chat Zalo</span>
    </a>
</div>

<!-- ===== Offcanvas Danh Mục Nhanh Mobile ===== -->
<div class="offcanvas offcanvas-bottom rounded-top-4" tabindex="-1" id="offcanvasCategories" aria-labelledby="offcanvasCategoriesLabel" style="height: 72vh;">
    <div class="offcanvas-header border-bottom py-3">
        <h5 class="offcanvas-title fw-bold text-dark d-flex align-items-center gap-2" id="offcanvasCategoriesLabel">
            <span style="width:32px; height:32px; border-radius:9px; background:#FEF2F2; color:#DC2626; display:flex; align-items:center; justify-content:center; font-size:14px;"><i class="fas fa-grip"></i></span>
            Danh Mục Sản Phẩm
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        <div class="row g-3">
            <div class="col-6">
                <a href="<?= url('products') ?>?category=1" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#FEF2F2;color:#DC2626;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-mobile-screen"></i></div>
                    <div>
                        <div class="fw-bold small">Điện thoại</div>
                        <span class="text-muted" style="font-size:11px;">iPhone, Samsung...</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('products') ?>?category=2" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#EEF2FF;color:#4F46E5;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-laptop"></i></div>
                    <div>
                        <div class="fw-bold small">Laptop</div>
                        <span class="text-muted" style="font-size:11px;">MacBook, Gaming</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('products') ?>?category=3" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#EFF6FF;color:#2563EB;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-tablet-screen"></i></div>
                    <div>
                        <div class="fw-bold small">Tablet</div>
                        <span class="text-muted" style="font-size:11px;">iPad, Galaxy Tab</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('products') ?>?category=4" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#FFFBEB;color:#D97706;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="fw-bold small">Đồng hồ</div>
                        <span class="text-muted" style="font-size:11px;">Apple Watch...</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('products') ?>?category=5" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#FAF5FF;color:#9333EA;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-headphones"></i></div>
                    <div>
                        <div class="fw-bold small">Phụ kiện</div>
                        <span class="text-muted" style="font-size:11px;">Sạc, Cáp, Tai nghe</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('warranty') ?>" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#F0FDF4;color:#16A34A;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <div class="fw-bold small">Tra cứu BH</div>
                        <span class="text-muted" style="font-size:11px;">Bảo hành điện tử</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('news') ?>" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#FFF7ED;color:#EA580C;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-newspaper"></i></div>
                    <div>
                        <div class="fw-bold small">Tin tức</div>
                        <span class="text-muted" style="font-size:11px;">Mẹo & Đánh giá</span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('orders/track') ?>" class="d-flex align-items-center gap-3 p-3 rounded-3 text-dark text-decoration-none border bg-light shadow-sm" style="transition:all 0.2s;">
                    <div style="width:42px;height:42px;border-radius:11px;background:#ECFEFF;color:#0891B2;display:flex;align-items:center;justify-content:center;font-size:18px"><i class="fas fa-truck-fast"></i></div>
                    <div>
                        <div class="fw-bold small">Tra đơn</div>
                        <span class="text-muted" style="font-size:11px;">Vận chuyển nhanh</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== Floating Contact Widgets (Hotline & Zalo) ===== -->
<div class="floating-contact-buttons" style="position:fixed; bottom:24px; left:24px; z-index:9998; display:flex; flex-direction:column; gap:12px;">
    <!-- Zalo Button -->
    <a href="https://zalo.me/0918841790" target="_blank" rel="noopener noreferrer" class="btn-floating-zalo" title="Chat qua Zalo: 0918.841.790" style="width:48px; height:48px; border-radius:50%; background:#0068FF; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(0,104,255,0.4); text-decoration:none; transition:all 0.3s; position:relative;">
        <span style="font-weight:900; font-size:12px; color:#fff; letter-spacing:-0.5px;">Zalo</span>
    </a>

    <!-- Hotline Button with pulse ring -->
    <a href="tel:0336120405" class="btn-floating-hotline" title="Gọi Hotline: 0336.120.405" style="width:48px; height:48px; border-radius:50%; background:#DC2626; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(220,38,38,0.45); text-decoration:none; color:#fff; font-size:19px; transition:all 0.3s;">
        <i class="fas fa-phone"></i>
    </a>
</div>

<style>
/* Bottom App Bar Mobile Styles */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 58px;
    background: #ffffff;
    box-shadow: 0 -2px 14px rgba(15, 23, 42, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-around;
    z-index: 1040;
    border-top: 1px solid #F1F5F9;
}
.bottom-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748B;
    text-decoration: none;
    font-size: 10.5px;
    font-weight: 600;
    gap: 3px;
    flex: 1;
    height: 100%;
    transition: all 0.2s;
    user-select: none;
}
.bottom-nav-item i { font-size: 18px; }
.bottom-nav-item:hover, .bottom-nav-item:active { color: #DC2626; }
.bottom-nav-cart-badge {
    position: absolute;
    top: 6px;
    right: calc(50% - 18px);
    background: #DC2626;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    border-radius: 999px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 1.5px solid #fff;
}
@media (max-width: 991px) {
    body { padding-bottom: 64px !important; }
    .floating-contact-buttons { bottom: 74px !important; }
}
.btn-floating-hotline:hover, .btn-floating-zalo:hover { transform: scale(1.1); }
@keyframes pulseRing {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(220, 38, 38, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
}
.btn-floating-hotline { animation: pulseRing 2s infinite; }
</style>

<?php
$popupPromoEnabled = getSetting('popup_promo_enabled', '1') === '1';
$popupPromoTitle = trim((string)getSetting('popup_promo_title', ''));
$popupPromoContent = trim((string)getSetting('popup_promo_content', ''));
$centerPopup = null;
if ($popupPromoEnabled) {
    try {
        $centerPopup = (new BannerModel())->getActive('popup_center')[0] ?? null;
    } catch (\Throwable $e) {
        $centerPopup = null; // Không có bảng banner hoặc lỗi DB -> popup tắt an toàn
    }
    // Tự tắt khi hết thời gian của thanh thông báo (nếu Admin cấu hình thời gian kết thúc)
    if ($centerPopup) {
        $popupEnd = trim((string)getSetting('announcement_end', ''));
        if ($popupEnd !== '' && strtotime($popupEnd) !== false && time() >= strtotime($popupEnd)) {
            $centerPopup = null;
        }
    }
}
if ($centerPopup && !empty($centerPopup['image'])):
    // Ưu tiên tiêu đề đặt trong Cài đặt > Thông báo Website, fallback về tiêu đề banner
    $popupDisplayTitle = $popupPromoTitle !== '' ? $popupPromoTitle : ($centerPopup['title'] ?: 'Quảng cáo');
    $popupImageUrl = productImage($centerPopup['image']);
    $popupLink = trim($centerPopup['link'] ?? '');
?>
<div class="promo-popup-overlay" id="promo-popup" data-popup-id="<?= (int)$centerPopup['id'] ?>" aria-hidden="true">
    <div class="promo-popup-dialog" role="dialog" aria-modal="true" aria-label="<?= e($popupDisplayTitle) ?>">
        <div class="promo-popup-card">
            <button type="button" class="promo-popup-close" id="promo-popup-close" aria-label="Đóng quảng cáo">&times;</button>
            <?php if ($popupLink !== ''): ?><a href="<?= e($popupLink) ?>" class="promo-popup-image-link"><?php endif; ?>
                <img src="<?= e($popupImageUrl) ?>" alt="<?= e($popupDisplayTitle) ?>">
            <?php if ($popupLink !== ''): ?></a><?php endif; ?>
            <?php if ($popupDisplayTitle !== 'Quảng cáo' || $popupPromoContent !== ''): ?>
            <div class="promo-popup-text">
                <?php if ($popupDisplayTitle !== 'Quảng cáo'): ?>
                <div class="promo-popup-title"><?= e($popupDisplayTitle) ?></div>
                <?php endif; ?>
                <?php if ($popupPromoContent !== ''): ?>
                <div class="promo-popup-content"><?= e($popupPromoContent) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
.promo-popup-overlay{position:fixed;inset:0;z-index:2000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,.6)}
.promo-popup-overlay.is-visible{display:flex}
.promo-popup-dialog{position:relative;width:min(520px,92vw);max-height:90vh;display:flex;justify-content:center}
.promo-popup-card{width:100%;max-width:520px;max-height:90vh;overflow-y:auto;background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.35);position:relative}
.promo-popup-card img{display:block;width:100%;height:auto;max-height:60vh;object-fit:contain;border-radius:14px 14px 0 0;background:#f8fafc}
.promo-popup-image-link{display:block}
.promo-popup-text{padding:14px 18px 16px}
.promo-popup-title{font-size:16px;font-weight:800;color:#0F172A;margin-bottom:6px;line-height:1.4}
.promo-popup-content{font-size:13.5px;color:#475569;line-height:1.6;white-space:pre-line}
.promo-popup-close{position:absolute;z-index:1;top:-14px;right:-14px;width:40px;height:40px;border:2px solid #fff;border-radius:50%;background:#0F172A;color:#fff;font-size:28px;line-height:1;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.35)}
@media(max-width:600px){.promo-popup-overlay{padding:16px}.promo-popup-dialog{width:92vw}.promo-popup-close{top:-12px;right:-10px;width:38px;height:38px}.promo-popup-text{padding:12px 14px 14px}}
</style>
<script>
(()=>{const popup=document.getElementById('promo-popup');if(!popup)return;const key='dismissed-promo-popup-'+popup.dataset.popupId;try{if(sessionStorage.getItem(key))return}catch(e){};popup.classList.add('is-visible');popup.setAttribute('aria-hidden','false');const close=()=>{popup.classList.remove('is-visible');popup.setAttribute('aria-hidden','true');try{sessionStorage.setItem(key,'1')}catch(e){}};document.getElementById('promo-popup-close').addEventListener('click',close);document.addEventListener('keydown',e=>{if(e.key==='Escape'&&popup.classList.contains('is-visible'))close()});popup.addEventListener('click',e=>{if(e.target===popup)close()})})();
</script>
<?php endif; ?>

<style>
#analytics-consent{position:fixed;z-index:3000;inset:50% auto auto 50%;transform:translate(-50%,-50%);display:none;width:min(900px,calc(100vw - 40px));max-height:min(760px,calc(100dvh - 40px));overflow:auto;padding:0;border:1px solid #eceef2;border-radius:22px;background:#fff;color:#20242c;box-shadow:0 24px 80px rgba(23,32,48,.24);font-family:Inter,Arial,sans-serif}
#analytics-consent .cc-layout{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(250px,.8fr);min-height:530px}
#analytics-consent .cc-content{padding:34px 36px 30px}
#analytics-consent .cc-brand{display:flex;align-items:center;gap:9px;margin-bottom:25px;color:#cf202e;font-size:14px;font-weight:800;letter-spacing:.02em}
#analytics-consent .cc-brand-mark{display:grid;width:34px;height:34px;place-items:center;border-radius:11px;background:#fff0f1;font-size:17px}
#analytics-consent .cc-title{margin:0 0 9px;color:#20242c;font-size:25px;font-weight:800;letter-spacing:-.035em;line-height:1.2}
#analytics-consent .cc-intro{margin:0 0 21px;color:#667085;font-size:13px;line-height:1.65}
#analytics-consent .cc-groups{display:grid;gap:9px;margin:0 0 17px}
#analytics-consent .cc-group{display:flex;align-items:center;gap:12px;min-height:57px;padding:10px 12px;border:1px solid #eceef2;border-radius:12px;background:#fff}
#analytics-consent .cc-icon{display:grid;width:35px;height:35px;flex:0 0 35px;place-items:center;border-radius:10px;background:#fff3f3;color:#c92332;font-size:14px}
#analytics-consent .cc-group-copy{min-width:0;flex:1}
#analytics-consent .cc-group-title{display:block;color:#292d35;font-size:12.5px;font-weight:750;line-height:1.35}
#analytics-consent .cc-group-desc{display:block;margin-top:2px;color:#7b8492;font-size:10.5px;line-height:1.4}
#analytics-consent .cc-switch{position:relative;width:35px;height:20px;flex:0 0 35px;padding:0;border:0;border-radius:20px;background:#d8dde5;cursor:pointer;transition:background .18s}
#analytics-consent .cc-switch:after{position:absolute;top:3px;left:3px;width:14px;height:14px;border-radius:50%;background:#fff;box-shadow:0 1px 3px #0002;content:""}
#analytics-consent .cc-switch.is-on{background:#cf202e}
#analytics-consent .cc-switch.is-on:after{left:18px}
#analytics-consent .cc-switch:focus-visible{outline:3px solid rgba(201,35,50,.25);outline-offset:3px}
#analytics-consent .cc-always{flex:0 0 auto;color:#16834a;font-size:9px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
#analytics-consent .cc-customize{position:relative;margin:0}
#analytics-consent .cc-customize summary{display:grid;min-height:43px;place-items:center;padding:6px;border:1px solid #d9dde5;border-radius:10px;background:#fff;color:#424956;font-size:10.5px;font-weight:750;text-align:center;cursor:pointer;list-style:none}
#analytics-consent .cc-customize summary::-webkit-details-marker{display:none}
#analytics-consent .cc-customize summary:hover,#analytics-consent .cc-customize[open] summary{border-color:#c92332;color:#bd1e2d}
#analytics-consent .cc-customize-note{position:absolute;right:0;bottom:calc(100% + 8px);z-index:2;width:min(290px,70vw);margin:0;padding:12px;border:1px solid #eceef2;border-radius:10px;background:#fff;color:#687386;font-size:11px;line-height:1.55;box-shadow:0 8px 24px #17203318}
#analytics-consent .cc-actions{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
#analytics-consent .cc-actions button{min-height:43px;border:1px solid #d9dde5;border-radius:10px;background:#fff;color:#424956;font:700 11.5px Inter,Arial,sans-serif;cursor:pointer;transition:background .18s,border-color .18s,transform .18s}
#analytics-consent .cc-actions button:hover{transform:translateY(-1px);border-color:#c92332}
#analytics-consent .cc-actions [data-consent-action="accept"]{border-color:#c92332;background:#c92332;color:#fff}
#analytics-consent .cc-actions [data-consent-action="accept"]:hover{background:#ad1724}
#analytics-consent .cc-illustration{position:relative;display:grid;overflow:hidden;place-items:center;padding:28px;background:linear-gradient(145deg,#fff8f8,#fff0f1 64%,#ffe5e7)}
#analytics-consent .cc-illustration:before,#analytics-consent .cc-illustration:after{position:absolute;border:1px solid rgba(201,35,50,.09);border-radius:50%;content:""}
#analytics-consent .cc-illustration:before{width:320px;height:320px}
#analytics-consent .cc-illustration:after{width:245px;height:245px}
#analytics-consent .cc-art{position:relative;z-index:1;width:min(100%,300px);height:auto}
#analytics-consent .cc-art-caption{position:absolute;right:25px;bottom:24px;left:25px;color:#8e5960;font-size:11px;font-weight:650;line-height:1.5;text-align:center}
@media(max-width:700px){#analytics-consent{width:calc(100vw - 24px);max-height:calc(100dvh - 28px);border-radius:18px}#analytics-consent .cc-layout{display:block;min-height:0}#analytics-consent .cc-content{padding:22px 18px 18px}#analytics-consent .cc-brand{margin-bottom:15px}#analytics-consent .cc-title{font-size:21px}#analytics-consent .cc-intro{margin-bottom:14px;font-size:12px}#analytics-consent .cc-groups{gap:7px;margin-bottom:12px}#analytics-consent .cc-group{min-height:52px;padding:8px 9px;gap:9px}#analytics-consent .cc-icon{width:31px;height:31px;flex-basis:31px}#analytics-consent .cc-group-desc{font-size:10px}#analytics-consent .cc-actions{gap:7px}#analytics-consent .cc-actions button{min-height:41px;font-size:10px}#analytics-consent .cc-customize summary{min-height:41px;font-size:9.5px}#analytics-consent .cc-illustration{display:none}}
@media(max-width:380px){#analytics-consent .cc-content{padding:18px 14px 15px}#analytics-consent .cc-title{font-size:19px}#analytics-consent .cc-group-desc{font-size:9px}#analytics-consent .cc-actions{gap:5px}#analytics-consent .cc-actions button{font-size:9px}#analytics-consent .cc-customize summary{font-size:9px}}
</style>
<aside id="analytics-consent" style="display:none">
    <div class="cc-layout">
        <div class="cc-content">
            <div class="cc-brand"><span class="cc-brand-mark"><i class="fa-solid fa-shield-halved"></i></span> VŨ HUÂN SHOP <span style="color:#9aa1ad;font-weight:500">· Quyền riêng tư</span></div>
            <h2 class="cc-title">Bạn làm chủ cookie của mình</h2>
            <p class="cc-intro">Cookie giúp website hoạt động ổn định và mang đến trải nghiệm mua sắm phù hợp hơn. Bạn có thể chọn cách sử dụng cookie bên dưới.</p>
            <div class="cc-groups">
                <div class="cc-group"><span class="cc-icon"><i class="fa-solid fa-lock"></i></span><span class="cc-group-copy"><span class="cc-group-title">Cookie thiết yếu</span><span class="cc-group-desc">Cần thiết cho giỏ hàng, đăng nhập và thanh toán.</span></span><span class="cc-always">Luôn bật</span></div>
                <div class="cc-group"><span class="cc-icon"><i class="fa-solid fa-sliders"></i></span><span class="cc-group-copy"><span class="cc-group-title">Cookie chức năng</span><span class="cc-group-desc">Ghi nhớ tùy chọn để sử dụng website thuận tiện hơn.</span></span><button type="button" class="cc-switch" data-cookie-toggle="functional" role="switch" aria-checked="false" aria-label="Cookie chức năng" title="Bật hoặc tắt cookie chức năng"></button></div>
                <div class="cc-group"><span class="cc-icon"><i class="fa-solid fa-chart-line"></i></span><span class="cc-group-copy"><span class="cc-group-title">Cookie phân tích</span><span class="cc-group-desc">Thống kê ẩn danh để cải thiện trải nghiệm mua sắm.</span></span><button type="button" class="cc-switch" data-cookie-toggle="analytics" role="switch" aria-checked="false" aria-label="Cookie phân tích" title="Bật hoặc tắt cookie phân tích"></button></div>
                <div class="cc-group"><span class="cc-icon"><i class="fa-solid fa-bullseye"></i></span><span class="cc-group-copy"><span class="cc-group-title">Cookie quảng cáo</span><span class="cc-group-desc">Hỗ trợ nội dung và ưu đãi phù hợp hơn với bạn.</span></span><button type="button" class="cc-switch" data-cookie-toggle="marketing" role="switch" aria-checked="false" aria-label="Cookie quảng cáo" title="Bật hoặc tắt cookie quảng cáo"></button></div>
            </div>
            <div class="cc-actions">
                <button type="button" data-consent-action="reject">Từ chối tất cả</button>
                <button type="button" data-consent-action="customize">Tùy chỉnh lựa chọn</button>
                <button type="button" data-consent-action="accept">Đồng ý tất cả</button>
            </div>
        </div>
        <div class="cc-illustration" aria-hidden="true">
            <svg class="cc-art" viewBox="0 0 320 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M160 24 265 64v76c0 67-44 111-105 136C99 251 55 207 55 140V64L160 24Z" fill="#fff" stroke="#C92332" stroke-width="7" stroke-linejoin="round"/>
                <path d="M160 47 242 78v62c0 52-32 88-82 111-50-23-82-59-82-111V78l82-31Z" fill="#FFF1F2"/>
                <rect x="112" y="119" width="96" height="73" rx="15" fill="#C92332"/>
                <path d="M132 119v-15a28 28 0 0 1 56 0v15" stroke="#C92332" stroke-width="10" stroke-linecap="round"/>
                <circle cx="160" cy="151" r="8" fill="white"/><path d="M160 158v13" stroke="white" stroke-width="7" stroke-linecap="round"/>
                <circle cx="76" cy="77" r="18" fill="#fff" stroke="#F0B8BE" stroke-width="3"/><path d="m68 77 6 6 11-13" stroke="#C92332" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="244" cy="207" r="14" fill="#fff" stroke="#F0B8BE" stroke-width="3"/><path d="m238 207 4 4 8-9" stroke="#C92332" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M84 225h40M198 77h28" stroke="#E8A0A8" stroke-width="5" stroke-linecap="round"/><circle cx="91" cy="184" r="4" fill="#C92332"/><circle cx="226" cy="236" r="5" fill="#C92332"/>
            </svg>
            <div class="cc-art-caption">Thông tin của bạn được tôn trọng và bảo vệ trong mỗi trải nghiệm mua sắm.</div>
        </div>
    </div>
</aside>
<script>
(()=>{
    const key='vuhuan_cookie_consent_v1';
    const categories=['functional','analytics','marketing'];
    const defaults={essential:true,functional:false,analytics:false,marketing:false};
    const panel=document.getElementById('analytics-consent');
    if(!panel)return;
    const readConsent=()=>{
        try{
            const saved=JSON.parse(sessionStorage.getItem(key)||'null');
            if(saved&&saved.version===1)return{essential:true,functional:saved.functional===true,analytics:saved.analytics===true,marketing:saved.marketing===true};
        }catch(e){}
        return null;
    };
    let consent=readConsent()||{...defaults};
    window.vhCookieConsent=consent;
    const updateSwitches=()=>document.querySelectorAll('#analytics-consent [data-cookie-toggle]').forEach(toggle=>{
        const active=consent[toggle.dataset.cookieToggle]===true;
        toggle.classList.toggle('is-on',active);
        toggle.setAttribute('aria-checked',active?'true':'false');
    });
    const saveConsent=next=>{
        consent={essential:true,functional:next.functional===true,analytics:next.analytics===true,marketing:next.marketing===true};
        window.vhCookieConsent=consent;
        let saved=false;
        try{sessionStorage.setItem(key,JSON.stringify({version:1,functional:consent.functional,analytics:consent.analytics,marketing:consent.marketing}));saved=true}catch(e){}
        panel.remove();
        if(saved)location.reload();
    };
    if(readConsent())panel.style.display='none';else panel.style.display='block';
    updateSwitches();
    panel.querySelectorAll('[data-cookie-toggle]').forEach(toggle=>toggle.addEventListener('click',()=>{
        const category=toggle.dataset.cookieToggle;
        if(!categories.includes(category))return;
        consent[category]=!consent[category];
        updateSwitches();
    }));
    panel.querySelectorAll('[data-consent-action]').forEach(button=>button.addEventListener('click',()=>{
        const action=button.dataset.consentAction;
        if(action==='reject')saveConsent(defaults);
        else if(action==='accept')saveConsent({essential:true,functional:true,analytics:true,marketing:true});
        else if(action==='customize')saveConsent(consent);
    }));
    document.addEventListener('click',event=>{
        if(!window.vhCookieConsent||window.vhCookieConsent.analytics!==true||typeof gtag!=='function')return;
        if(event.target.closest('.btn-add-cart,[data-action="add-to-cart"]'))gtag('event','add_to_cart');
        if(event.target.closest('#submit-order'))gtag('event','begin_checkout');
    });
})();
</script>
<?php if (isset($emailSent) && empty($isPaymentFailed) && !empty($order['order_number']) && !empty($order['final_amount']) && ($_SESSION['ga_purchase_sent_order'] ?? null) !== $order['order_number']): $_SESSION['ga_purchase_sent_order'] = $order['order_number']; ?><script>if(typeof gtag==='function'&&window.vhCookieConsent&&window.vhCookieConsent.analytics===true)gtag('event','purchase',{transaction_id:<?= json_encode((string)$order['order_number']) ?>,value:<?= (float)$order['final_amount'] ?>,currency:'VND'});</script><?php endif; ?>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main JS -->
<script src="<?= APP_URL ?>/public/js/main.js?v=<?= time() ?>"></script>
</body>
</html>
