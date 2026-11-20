<?php
/**
 * SettingController - Quản lý cài đặt hệ thống (Admin)
 */
class SettingController extends Controller {
    private $settingModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAdmin();
        $this->settingModel = new SettingModel();
    }
    
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            // ===== Flash Sale =====
            $flashSaleEnabled = $this->getInput('flash_sale_enabled', '') === '1' ? '1' : '0';
            $this->settingModel->set('flash_sale_enabled', $flashSaleEnabled);
            $this->settingModel->set('flash_sale_title', sanitize($this->getInput('flash_sale_title', '')));
            $this->settingModel->set('flash_sale_description', sanitize($this->getInput('flash_sale_description', '')));
            $this->settingModel->set('flash_sale_start', date('Y-m-d H:i:s', strtotime((string)$this->getInput('flash_sale_start', '')) ?: time()));
            $flashSaleEnd = sanitize($this->getInput('flash_sale_end', ''));
            if (!empty($flashSaleEnd)) {
                // Định dạng chuẩn yyyy-MM-dd HH:mm:ss
                $flashSaleEndFormatted = date('Y-m-d H:i:s', strtotime($flashSaleEnd));
                $this->settingModel->set('flash_sale_end', $flashSaleEndFormatted);
            }

            // ===== Thông tin cửa hàng & vận chuyển =====
            $keys = [
                'shop_name', 'shop_hotline', 'shop_email', 'shop_address', 'shop_facebook',
                'shop_zalo', 'shop_tiktok', 'shop_maps_url', 'shop_opening_hours',
                'shipping_fee_standard', 'shipping_fee_free_amount',
                'shipping_free_enabled', 'shipping_delivery_time',
                'telegram_bot_token', 'telegram_chat_id', 'telegram_notify_order',
                'telegram_api_url'
            ];
            foreach ($keys as $key) {
                $val = sanitize($this->getInput($key, ''));
                $this->settingModel->set($key, $val);
            }
            // Checkbox bật/tắt miễn phí vận chuyển
            $shippingFreeEnabled = $this->getInput('shipping_free_enabled', '') === '1' ? '1' : '0';
            $this->settingModel->set('shipping_free_enabled', $shippingFreeEnabled);

            // ===== Thanh thông báo đầu trang =====
            $this->settingModel->set('announcement_enabled', $this->getInput('announcement_enabled', '') === '1' ? '1' : '0');
            $this->settingModel->set('announcement_text', sanitize($this->getInput('announcement_text', '')));
            $this->settingModel->set('announcement_link', sanitize($this->getInput('announcement_link', '')));
            $announcementStart = trim((string)$this->getInput('announcement_start', ''));
            $announcementEnd = trim((string)$this->getInput('announcement_end', ''));
            $this->settingModel->set('announcement_start', $announcementStart !== '' ? date('Y-m-d H:i:s', strtotime($announcementStart)) : '');
            $this->settingModel->set('announcement_end', $announcementEnd !== '' ? date('Y-m-d H:i:s', strtotime($announcementEnd)) : '');

            // ===== Popup khuyến mãi (nội dung/giờ hiển thị; ảnh quản lý qua module Banner "popup_center") =====
            $this->settingModel->set('popup_promo_enabled', $this->getInput('popup_promo_enabled', '') === '1' ? '1' : '0');
            $this->settingModel->set('popup_promo_title', sanitize($this->getInput('popup_promo_title', '')));
            $this->settingModel->set('popup_promo_content', sanitize($this->getInput('popup_promo_content', '')));

            // Làm mới cache cấu hình
            getSetting('', '', true);

            setFlash('success', 'Cập nhật cài đặt hệ thống thành công!');
            $this->redirect(url('admin/settings'));
            return;
        }

        $flashSaleEnd = $this->settingModel->get('flash_sale_end', date('Y-m-d 23:59:59'));
        // Chuyển đổi định dạng sang datetime-local (Y-m-d\TH:i)
        $flashSaleEndLocal = date('Y-m-d\TH:i', strtotime($flashSaleEnd));
        $flashSaleStartRaw = $this->settingModel->get('flash_sale_start', '');
        $flashSaleStartLocal = $flashSaleStartRaw ? date('Y-m-d\TH:i', strtotime($flashSaleStartRaw)) : date('Y-m-d\T08:00');
        $announcementStartRaw = $this->settingModel->get('announcement_start', '');
        $announcementEndRaw = $this->settingModel->get('announcement_end', '');

        $this->view('admin.settings.index', [
            'title' => 'Cài đặt hệ thống - Admin',
            'flashSaleEnabled' => $this->settingModel->get('flash_sale_enabled', '1'),
            'flashSaleEnd' => $flashSaleEndLocal,
            'flashSaleStart' => $flashSaleStartLocal,
            'flashSaleTitle' => $this->settingModel->get('flash_sale_title', 'FLASH SALE Hôm nay'),
            'flashSaleDescription' => $this->settingModel->get('flash_sale_description', ''),
            'shop_name' => $this->settingModel->get('shop_name', APP_NAME),
            'shop_hotline' => $this->settingModel->get('shop_hotline', '0336.120.405'),
            'shop_email' => $this->settingModel->get('shop_email', 'vuhuan123890@gmail.com'),
            'shop_address' => $this->settingModel->get('shop_address', '100+ Cửa hàng toàn quốc'),
            'shop_facebook' => $this->settingModel->get('shop_facebook', 'https://facebook.com'),
            'shop_zalo' => $this->settingModel->get('shop_zalo', ''),
            'shop_tiktok' => $this->settingModel->get('shop_tiktok', ''),
            'shop_maps_url' => $this->settingModel->get('shop_maps_url', ''),
            'shop_opening_hours' => $this->settingModel->get('shop_opening_hours', '8:00 - 21:00'),
            'shipping_fee_standard' => $this->settingModel->get('shipping_fee_standard', '30000'),
            'shipping_fee_free_amount' => $this->settingModel->get('shipping_fee_free_amount', '500000'),
            'shipping_free_enabled' => $this->settingModel->get('shipping_free_enabled', '1'),
            'shipping_delivery_time' => $this->settingModel->get('shipping_delivery_time', '1-3 ngày'),
            'announcement_enabled' => $this->settingModel->get('announcement_enabled', '0'),
            'announcement_text' => $this->settingModel->get('announcement_text', ''),
            'announcement_link' => $this->settingModel->get('announcement_link', ''),
            'announcement_start' => $announcementStartRaw ? date('Y-m-d\TH:i', strtotime($announcementStartRaw)) : '',
            'announcement_end' => $announcementEndRaw ? date('Y-m-d\TH:i', strtotime($announcementEndRaw)) : '',
            'popup_promo_enabled' => $this->settingModel->get('popup_promo_enabled', '1'),
            'popup_promo_title' => $this->settingModel->get('popup_promo_title', ''),
            'popup_promo_content' => $this->settingModel->get('popup_promo_content', ''),
            'telegram_bot_token' => $this->settingModel->get('telegram_bot_token', ''),
            'telegram_chat_id' => $this->settingModel->get('telegram_chat_id', ''),
            'telegram_notify_order' => $this->settingModel->get('telegram_notify_order', '1'),
            'telegram_api_url' => $this->settingModel->get('telegram_api_url', defined('TELEGRAM_API_URL') ? TELEGRAM_API_URL : 'https://telegram-proxy.vuhuan123890.workers.dev'),
        ]);
    }

    /**
     * Gửi tin nhắn thử nghiệm tới Telegram
     */
    public function testTelegram() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(url('admin/settings'));
            return;
        }
        $this->validateCsrf();

        $token = sanitize($this->getInput('token', ''));
        $chatId = sanitize($this->getInput('chat_id', ''));
        $apiUrl = sanitize($this->getInput('api_url', ''));

        if (!empty($apiUrl)) {
            $this->settingModel->set('telegram_api_url', $apiUrl);
        }
        if (!empty($token)) {
            $this->settingModel->set('telegram_bot_token', $token);
        }
        if (!empty($chatId)) {
            $this->settingModel->set('telegram_chat_id', $chatId);
        }
        // Làm mới cache cấu hình
        getSetting('', '', true);

        if (empty($token) || empty($chatId)) {
            setFlash('error', 'Vui lòng nhập đầy đủ Telegram Bot Token và Chat ID trước khi test.');
            $this->redirect(url('admin/settings'));
            return;
        }

        $testMsg = "🚀 <b>THÔNG BÁO TEST TỪ VŨ HUÂN SHOP</b>\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "✅ Kết nối Telegram Bot thành công mỹ mãn!\n"
                 . "⏰ Thời gian: " . date('d/m/Y H:i:s') . "\n"
                 . "🔔 Kể từ bây giờ, mọi đơn hàng mới trên web sẽ tự động gửi thông báo về đây ngay lập tức.";

        $res = sendTelegramNotification($testMsg, $token, $chatId, $apiUrl);
        
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res);
            exit;
        }

        if ($res['success']) {
            setFlash('success', 'Gửi tin nhắn test thành công! Hãy kiểm tra ứng dụng Telegram của bạn.');
        } else {
            setFlash('error', 'Lỗi khi gửi Telegram: ' . $res['message']);
        }

        $this->redirect(url('admin/settings'));
    }
}
?>
