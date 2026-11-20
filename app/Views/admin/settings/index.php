<?php
$flashSaleEnd             = $flashSaleEnd ?? date('Y-m-d\TH:i');
$flashSaleStart           = $flashSaleStart ?? date('Y-m-d\T08:00');
$flashSaleEnabled         = $flashSaleEnabled ?? '1';
$flashSaleTitle           = $flashSaleTitle ?? 'FLASH SALE Hôm nay';
$flashSaleDescription     = $flashSaleDescription ?? '';
$shop_name                = $shop_name ?? APP_NAME;
$shop_hotline             = $shop_hotline ?? '0336.120.405';
$shop_email               = $shop_email ?? 'vuhuan123890@gmail.com';
$shop_address             = $shop_address ?? '100+ Cửa hàng toàn quốc';
$shop_facebook            = $shop_facebook ?? 'https://facebook.com';
$shop_zalo                = $shop_zalo ?? '';
$shop_tiktok              = $shop_tiktok ?? '';
$shop_maps_url            = $shop_maps_url ?? '';
$shop_opening_hours       = $shop_opening_hours ?? '8:00 - 21:00';
$shipping_fee_standard    = $shipping_fee_standard ?? '30000';
$shipping_fee_free_amount = $shipping_fee_free_amount ?? '500000';
$shipping_free_enabled    = $shipping_free_enabled ?? '1';
$shipping_delivery_time   = $shipping_delivery_time ?? '1-3 ngày';
$announcement_enabled     = $announcement_enabled ?? '0';
$announcement_text        = $announcement_text ?? '';
$announcement_link        = $announcement_link ?? '';
$announcement_start       = $announcement_start ?? '';
$announcement_end         = $announcement_end ?? '';
$popup_promo_enabled      = $popup_promo_enabled ?? '1';
$popup_promo_title        = $popup_promo_title ?? '';
$popup_promo_content      = $popup_promo_content ?? '';
$telegram_bot_token       = $telegram_bot_token ?? (defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '');
$telegram_chat_id         = $telegram_chat_id ?? (defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '');
$telegram_notify_order    = $telegram_notify_order ?? '1';
$telegram_api_url         = $telegram_api_url ?? (defined('TELEGRAM_API_URL') ? TELEGRAM_API_URL : 'https://api.telegram.org');

require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center justify-content-between">
        <div class="col-sm-6">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Cài đặt hệ thống</h1>
            <p class="text-muted small mb-0">Cấu hình thời gian chạy các chương trình marketing và các tùy chọn hệ thống.</p>
        </div>
    </div>
    
    <form action="<?= url('admin/settings') ?>" method="POST">
        <?= csrfToken() ?>
        
        <div class="row g-4">
            <!-- Cấu hình Flash Sale -->
            <div class="col-lg-5">
                <div class="admin-card card border-0 shadow-sm h-100" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;">
                    <div class="admin-card-header card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f0f0f0 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-bolt text-warning fs-5"></i>
                            <h5 class="admin-card-title card-title mb-0 fw-bold">Cấu hình Flash Sale</h5>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="flash_sale_enabled" name="flash_sale_enabled" value="1" <?= $flashSaleEnabled == '1' ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                            <label class="form-check-label fw-semibold small ms-1" for="flash_sale_enabled">Bật</label>
                        </div>
                    </div>
                    <div class="admin-card-body card-body p-4">
                        <div class="mb-3">
                            <label for="flash_sale_title" class="form-label fw-semibold text-dark small mb-2">Tiêu đề chương trình</label>
                            <input type="text" class="form-control" id="flash_sale_title" name="flash_sale_title"
                                   value="<?= e($flashSaleTitle) ?>" placeholder="VD: FLASH SALE Hôm nay" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                        </div>
                        <div class="mb-3">
                            <label for="flash_sale_description" class="form-label fw-semibold text-dark small mb-2">Mô tả chương trình</label>
                            <textarea class="form-control" id="flash_sale_description" name="flash_sale_description" rows="2"
                                      placeholder="VD: Giảm giá sốc các dòng điện thoại hot nhất ngày hôm nay!" style="border-radius:10px; padding:11px; border: 1.5px solid #eee; resize:none;"><?= e($flashSaleDescription) ?></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="flash_sale_start" class="form-label fw-semibold text-dark small mb-2">Bắt đầu</label>
                                <input type="datetime-local" class="form-control" id="flash_sale_start" name="flash_sale_start"
                                       value="<?= e($flashSaleStart) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-6">
                                <label for="flash_sale_end" class="form-label fw-semibold text-dark small mb-2">Kết thúc</label>
                                <input type="datetime-local" class="form-control" id="flash_sale_end" name="flash_sale_end"
                                       value="<?= e($flashSaleEnd) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                        </div>
                        <div class="form-text mt-0 text-muted small" style="font-size:0.78rem;">
                            <i class="fas fa-info-circle me-1"></i>Sau thời gian kết thúc, bộ đếm ngược Flash Sale trên trang chủ tự động dừng và khối Flash Sale ẩn đi. Sản phẩm áp dụng được quản lý bằng công tắc "Hot Deal / Flash Sale hôm nay" trong từng sản phẩm (Admin → Sản phẩm → Sửa).
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cấu hình Chung Cửa Hàng -->
            <div class="col-lg-7">
                <div class="admin-card card border-0 shadow-sm h-100" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;">
                    <div class="admin-card-header card-header bg-white py-3 border-0" style="border-bottom: 1px solid #f0f0f0 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-store text-danger fs-5"></i>
                            <h5 class="admin-card-title card-title mb-0 fw-bold">Cấu hình thông tin cửa hàng & vận chuyển</h5>
                        </div>
                    </div>
                    <div class="admin-card-body card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="shop_name" class="form-label fw-semibold text-dark small mb-2">Tên cửa hàng</label>
                                <input type="text" class="form-control" id="shop_name" name="shop_name"
                                       value="<?= e($shop_name) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shop_hotline" class="form-label fw-semibold text-dark small mb-2">Hotline liên hệ</label>
                                <input type="text" class="form-control" id="shop_hotline" name="shop_hotline"
                                       value="<?= e($shop_hotline) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shop_email" class="form-label fw-semibold text-dark small mb-2">Email cửa hàng</label>
                                <input type="email" class="form-control" id="shop_email" name="shop_email"
                                       value="<?= e($shop_email) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shop_zalo" class="form-label fw-semibold text-dark small mb-2">Zalo liên hệ</label>
                                <input type="text" class="form-control" id="shop_zalo" name="shop_zalo"
                                       placeholder="VD: 0918841790 hoặc link zalo.me/..." value="<?= e($shop_zalo) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-12">
                                <label for="shop_address" class="form-label fw-semibold text-dark small mb-2">Địa chỉ cửa hàng</label>
                                <input type="text" class="form-control" id="shop_address" name="shop_address"
                                       value="<?= e($shop_address) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-12">
                                <label for="shop_maps_url" class="form-label fw-semibold text-dark small mb-2">Google Maps URL</label>
                                <input type="url" class="form-control" id="shop_maps_url" name="shop_maps_url"
                                       placeholder="https://maps.google.com/..." value="<?= e($shop_maps_url) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shop_tiktok" class="form-label fw-semibold text-dark small mb-2">TikTok URL</label>
                                <input type="url" class="form-control" id="shop_tiktok" name="shop_tiktok"
                                       placeholder="https://tiktok.com/@..." value="<?= e($shop_tiktok) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shop_opening_hours" class="form-label fw-semibold text-dark small mb-2">Giờ mở cửa</label>
                                <input type="text" class="form-control" id="shop_opening_hours" name="shop_opening_hours"
                                       placeholder="VD: 8:00 - 21:00 (kể cả CN)" value="<?= e($shop_opening_hours) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-12">
                                <label for="shop_facebook" class="form-label fw-semibold text-dark small mb-2">Facebook Page URL</label>
                                <input type="url" class="form-control" id="shop_facebook" name="shop_facebook"
                                       value="<?= e($shop_facebook) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_fee_standard" class="form-label fw-semibold text-dark small mb-2">Phí ship mặc định (₫)</label>
                                <input type="number" class="form-control" id="shipping_fee_standard" name="shipping_fee_standard"
                                       value="<?= e($shipping_fee_standard) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_fee_free_amount" class="form-label fw-semibold text-dark small mb-2">Đơn hàng tối thiểu miễn phí ship (₫)</label>
                                <input type="number" class="form-control" id="shipping_fee_free_amount" name="shipping_fee_free_amount"
                                       value="<?= e($shipping_fee_free_amount) ?>" required style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6">
                                <label for="shipping_delivery_time" class="form-label fw-semibold text-dark small mb-2">Thời gian giao hàng dự kiến</label>
                                <input type="text" class="form-control" id="shipping_delivery_time" name="shipping_delivery_time"
                                       placeholder="VD: 1-3 ngày" value="<?= e($shipping_delivery_time) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="shipping_free_enabled" name="shipping_free_enabled" value="1" <?= $shipping_free_enabled == '1' ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                                    <label class="form-check-label fw-semibold small ms-1" for="shipping_free_enabled">Bật miễn phí vận chuyển</label>
                                    <div class="form-text text-muted" style="font-size:0.75rem;">Tắt sẽ luôn tính phí ship mặc định.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cấu hình Thông Báo Website -->
            <div class="col-12">
                <div class="admin-card card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;">
                    <div class="admin-card-header card-header bg-white py-3 border-0" style="border-bottom: 1px solid #f0f0f0 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:34px; height:34px; border-radius:9px; background:#FEF2F2; color:#DC2626; display:flex; align-items:center; justify-content:center; font-size:15px;"><i class="fas fa-bullhorn"></i></span>
                            <div>
                                <h5 class="admin-card-title card-title mb-0 fw-bold">Thông báo Website</h5>
                                <span class="text-muted" style="font-size:12px;">Thanh thông báo đầu trang và Popup khuyến mãi hiển thị cho khách truy cập</span>
                            </div>
                        </div>
                    </div>
                    <div class="admin-card-body card-body p-4">
                        <div class="row g-4">
                            <!-- Thanh thông báo đầu trang -->
                            <div class="col-lg-6">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-dark small"><i class="fas fa-bars me-1 text-danger"></i>Thanh thông báo đầu trang</h6>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="announcement_enabled" name="announcement_enabled" value="1" <?= $announcement_enabled == '1' ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                                        <label class="form-check-label fw-semibold small ms-1" for="announcement_enabled">Bật</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="announcement_text" class="form-label fw-semibold text-dark small mb-2">Nội dung thông báo</label>
                                    <input type="text" class="form-control" id="announcement_text" name="announcement_text"
                                           placeholder="VD: Miễn phí giao hàng cho đơn từ 500K!" value="<?= e($announcement_text) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                                </div>
                                <div class="mb-3">
                                    <label for="announcement_link" class="form-label fw-semibold text-dark small mb-2">Liên kết khi bấm vào (không bắt buộc)</label>
                                    <input type="text" class="form-control" id="announcement_link" name="announcement_link"
                                           placeholder="VD: products hoặc news/sale-..." value="<?= e($announcement_link) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="announcement_start" class="form-label fw-semibold text-dark small mb-2">Hiển thị từ</label>
                                        <input type="datetime-local" class="form-control" id="announcement_start" name="announcement_start"
                                               value="<?= e($announcement_start) ?>" style="border-radius:10px; padding:10px; border: 1.5px solid #eee;">
                                    </div>
                                    <div class="col-6">
                                        <label for="announcement_end" class="form-label fw-semibold text-dark small mb-2">Đến</label>
                                        <input type="datetime-local" class="form-control" id="announcement_end" name="announcement_end"
                                               value="<?= e($announcement_end) ?>" style="border-radius:10px; padding:10px; border: 1.5px solid #eee;">
                                    </div>
                                </div>
                                <div class="form-text text-muted small" style="font-size:0.75rem;">
                                    <i class="fas fa-info-circle me-1"></i>Để trống 2 mốc thời gian = hiển thị vĩnh viễn khi bật.
                                </div>
                            </div>

                            <!-- Popup khuyến mãi -->
                            <div class="col-lg-6">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-dark small"><i class="fas fa-window-maximize me-1 text-danger"></i>Popup khuyến mãi</h6>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="popup_promo_enabled" name="popup_promo_enabled" value="1" <?= $popup_promo_enabled == '1' ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                                        <label class="form-check-label fw-semibold small ms-1" for="popup_promo_enabled">Bật</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="popup_promo_title" class="form-label fw-semibold text-dark small mb-2">Tiêu đề popup</label>
                                    <input type="text" class="form-control" id="popup_promo_title" name="popup_promo_title"
                                           placeholder="VD: Khuyến mãi tháng 11" value="<?= e($popup_promo_title) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee;">
                                </div>
                                <div class="mb-3">
                                    <label for="popup_promo_content" class="form-label fw-semibold text-dark small mb-2">Nội dung popup</label>
                                    <textarea class="form-control" id="popup_promo_content" name="popup_promo_content" rows="2"
                                              placeholder="VD: Giảm đến 50% cho điện thoại hot. Nhập mã SALE11..." style="border-radius:10px; padding:11px; border: 1.5px solid #eee; resize:none;"><?= e($popup_promo_content) ?></textarea>
                                </div>
                                <div class="alert alert-light border mb-0 p-3" style="border-radius:10px; font-size:12.5px;">
                                    <i class="fas fa-image text-primary me-1"></i><strong>Hình ảnh popup:</strong> tải lên qua menu <a href="<?= url('admin/banners') ?>" class="fw-semibold">Banners → vị trí "Popup giữa trang"</a>. Popup sẽ tự động ẩn khi hết thời gian của thanh thông báo (nếu thiết lập) và chỉ hiện 1 lần mỗi phiên trình duyệt của khách.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cấu hình Thông Báo Telegram Bot -->
            <div class="col-12">
                <div class="admin-card card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;">
                    <div class="admin-card-header card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f0f0f0 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:34px; height:34px; border-radius:50%; background:#0088cc; color:#fff; display:flex; align-items:center; justify-content:center; font-size:16px;"><i class="fab fa-telegram-plane"></i></span>
                            <div>
                                <h5 class="admin-card-title card-title mb-0 fw-bold">Thông báo đơn hàng mới qua Telegram Bot</h5>
                                <span class="text-muted" style="font-size:12px;">Tự động gửi tin nhắn báo điện thoại chủ shop ngay khi có khách đặt đơn mới</span>
                            </div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="telegram_notify_order" name="telegram_notify_order" value="1" <?= (!empty($telegram_notify_order) && $telegram_notify_order == '1') ? 'checked' : '' ?> style="cursor:pointer; width:40px; height:20px;">
                            <label class="form-check-label fw-semibold small ms-1" for="telegram_notify_order">Bật thông báo</label>
                        </div>
                    </div>
                    <div class="admin-card-body card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="telegram_bot_token" class="form-label fw-semibold text-dark small mb-2">Telegram Bot Token</label>
                                <input type="text" class="form-control" id="telegram_bot_token" name="telegram_bot_token" 
                                       placeholder="Ví dụ: 7123456789:AAFl..." 
                                       value="<?= e(!empty($telegram_bot_token) ? $telegram_bot_token : (defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '')) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee; font-family:monospace;">
                                <div class="form-text text-muted" style="font-size:0.75rem;">
                                    Tạo bot trên Telegram qua <strong>@BotFather</strong> để lấy API Token miễn phí.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="telegram_chat_id" class="form-label fw-semibold text-dark small mb-2">Telegram Chat ID / Group ID</label>
                                <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id" 
                                       placeholder="Ví dụ: 123456789 hoặc -100123456789" 
                                       value="<?= e($telegram_chat_id) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee; font-family:monospace;">
                                <div class="form-text text-muted" style="font-size:0.75rem;">
                                    Chat với <strong>@userinfobot</strong> hoặc thêm bot vào nhóm để lấy ID người nhận.
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="telegram_api_url" class="form-label fw-semibold text-dark small mb-2">Telegram API Gateway / Proxy URL (Dành cho Hosting InfinityFree)</label>
                                <input type="text" class="form-control" id="telegram_api_url" name="telegram_api_url" 
                                       placeholder="https://telegram-proxy.vuhuan123890.workers.dev" 
                                       value="<?= e(!empty($telegram_api_url) ? $telegram_api_url : (defined('TELEGRAM_API_URL') ? TELEGRAM_API_URL : 'https://telegram-proxy.vuhuan123890.workers.dev')) ?>" style="border-radius:10px; padding:11px; border: 1.5px solid #eee; font-family:monospace;">
                                <div class="form-text text-muted" style="font-size:0.75rem;">
                                    Đang dùng Proxy Cloudflare Worker: <code>https://telegram-proxy.vuhuan123890.workers.dev</code> để vượt tường lửa chặn Telegram của InfinityFree.
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0 p-3" style="border-radius:10px; font-size:13px;">
                            <i class="fas fa-lightbulb text-warning me-2"></i><strong>Lợi ích:</strong> Ngay khi có đơn hàng, bạn sẽ nhận được tin nhắn Telegram gồm Tên khách, Số điện thoại (bấm gọi ngay), Địa chỉ và Sản phẩm đã mua. Giúp chốt đơn trong vòng 5 phút!
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary py-2 px-4 fw-semibold" style="border-radius:10px;" onclick="testTelegramBot()">
                    <i class="fab fa-telegram-plane me-2"></i>Gửi tin nhắn thử nghiệm (Test Bot)
                </button>

                <button type="submit" class="btn btn-danger py-3 px-5" style="background:#e53935; border:none; border-radius:10px; font-weight:700; transition:all 0.2s; box-shadow: 0 4px 15px rgba(229,57,53,0.3);">
                    <i class="fas fa-save me-2"></i>Lưu tất cả cấu hình
                </button>
            </div>
        </div>
    </form>

    <!-- Hidden form for testing Telegram -->
    <form id="testTelegramForm" action="<?= url('admin/settings/test-telegram') ?>" method="POST" style="display:none;">
        <?= csrfToken() ?>
        <input type="hidden" name="token" id="test_token">
        <input type="hidden" name="chat_id" id="test_chat_id">
        <input type="hidden" name="api_url" id="test_api_url">
    </form>
</div>

<script>
async function testTelegramBot() {
    const token = document.getElementById('telegram_bot_token').value.trim();
    const chatId = document.getElementById('telegram_chat_id').value.trim();
    let apiUrl = document.getElementById('telegram_api_url') ? document.getElementById('telegram_api_url').value.trim() : '';
    if (!apiUrl) {
        apiUrl = 'https://telegram-proxy.vuhuan123890.workers.dev';
    }
    apiUrl = apiUrl.replace(/\/+$/, '').replace(/\/bot\/?$/i, '');

    if (!token || !chatId) {
        alert('Vui lòng nhập đầy đủ Telegram Bot Token và Chat ID trước khi bấm thử nghiệm!');
        return;
    }

    const testBtn = event.currentTarget || document.querySelector('button[onclick="testTelegramBot()"]');
    const origHtml = testBtn ? testBtn.innerHTML : '';
    if (testBtn) {
        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi tin nhắn...';
    }

    const testMsg = "🚀 <b>THÔNG BÁO TEST TỪ VŨ HUÂN SHOP</b>\n"
                  + "━━━━━━━━━━━━━━━━━━━━\n"
                  + "✅ Kết nối Telegram Bot thành công mỹ mãn!\n"
                  + "⏰ Thời gian: " + new Date().toLocaleString('vi-VN') + "\n"
                  + "🔔 Kể từ bây giờ, mọi đơn hàng mới trên web sẽ tự động gửi thông báo về đây ngay lập tức.";

    try {
        // Lớp 1: Gửi qua máy chủ web (Server cURL)
        const formData = new FormData(document.getElementById('testTelegramForm'));
        formData.set('token', token);
        formData.set('chat_id', chatId);
        formData.set('api_url', apiUrl);

        let serverSuccess = false;
        let serverErrorMsg = '';

        try {
            const resp = await fetch('<?= url('admin/settings/test-telegram') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                serverSuccess = true;
            } else {
                serverErrorMsg = data.message || 'Lỗi server';
            }
        } catch (e) {
            serverErrorMsg = e.message;
        }

        if (serverSuccess) {
            alert('🎉 CHÚC MỪNG!\nĐã gửi tin nhắn test thành công qua máy chủ! Hãy kiểm tra ứng dụng Telegram của bạn.');
            if (testBtn) { testBtn.disabled = false; testBtn.innerHTML = origHtml; }
            return;
        }

        // Lớp 2: Nếu server InfinityFree bị chặn DNS/cURL, tự động gửi trực tiếp từ trình duyệt qua Cloudflare Worker
        console.warn('Server cURL thất bại (' + serverErrorMsg + '), kích hoạt gửi trực tiếp từ trình duyệt qua Proxy...');
        
        const directUrl = `${apiUrl}/bot${token}/sendMessage`;
        const directResp = await fetch(directUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'chat_id': chatId,
                'text': testMsg,
                'parse_mode': 'HTML'
            })
        });

        const directData = await directResp.json();
        if (directData.ok) {
            alert('🎉 THÀNH CÔNG RỰC RỠ!\nTin nhắn đã gửi thành công tới Telegram của bạn!\n\n(Hệ thống tự động kích hoạt đường truyền trực tiếp từ trình duyệt giúp vượt qua tường lửa chặn cURL của hosting InfinityFree).');
        } else {
            alert('❌ Telegram báo lỗi: ' + (directData.description || 'Không thể gửi tin nhắn') + '\n\nLưu ý: Bạn cần mở bot trên Telegram và bấm START thì bot mới gửi được tin nhắn cho bạn.');
        }

    } catch (err) {
        alert('❌ Lỗi kết nối: ' + err.message + '\nVui lòng kiểm tra lại đường mạng hoặc link Proxy.');
    } finally {
        if (testBtn) {
            testBtn.disabled = false;
            testBtn.innerHTML = origHtml;
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
