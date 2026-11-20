<?php
require_once __DIR__ . '/../Services/LoginDeviceSecurity.php';

class DeviceController extends Controller {
    private $security;

    public function __construct() {
        parent::__construct();
        $this->security = new LoginDeviceSecurity($this->db);
    }

    public function index() {
        $this->requireAuth();
        $accountType = $_SESSION['device_account_type'] ?? '';
        if (!in_array($accountType, ['customer', 'admin'], true)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $devices = $this->security->getDevices($this->getUserId(), $accountType);
        $events = $this->db->query('SELECT event_type, success, device_name, operating_system, browser, ip_address, created_at FROM login_events WHERE user_id = :uid ORDER BY created_at DESC LIMIT 30')
            ->bind(':uid', $this->getUserId())->resultSet();
        $this->view('user.devices', [
            'title' => 'Thiết bị đăng nhập - ' . APP_NAME,
            'devices' => $devices,
            'events' => $events,
            'accountType' => $accountType,
            'currentSession' => session_id(),
        ]);
    }

    public function revoke($id = null) {
        $this->requireAuth();
        $this->validateCsrf();
        $accountType = $_SESSION['device_account_type'] ?? '';
        if (!in_array($accountType, ['customer', 'admin'], true) || !ctype_digit((string) $id)) {
            http_response_code(400);
            exit('Yêu cầu không hợp lệ');
        }
        $device = $this->db->query('SELECT session_id FROM login_devices WHERE id = :id AND user_id = :uid AND account_type = :type AND revoked_at IS NULL')
            ->bind(':id', $id)->bind(':uid', $this->getUserId())->bind(':type', $accountType)->single();
        if (!$device) {
            setFlash('error', 'Thiết bị không tồn tại hoặc đã bị thu hồi.');
            $this->redirect(url('user/devices'));
            return;
        }
        $this->security->revokeDevice($id, $this->getUserId(), $accountType);
        if (($device['session_id'] ?? '') === session_id()) {
            setcookie('vh_device', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 'httponly' => true, 'samesite' => 'Lax']);
            unset($_SESSION['user_id'], $_SESSION['user'], $_SESSION['role'], $_SESSION['device_account_type']);
            session_regenerate_id(true);
            setFlash('success', 'Đã đăng xuất thiết bị này.');
            $this->redirect(url('auth/login'));
            return;
        }
        setFlash('success', 'Đã thu hồi thiết bị đăng nhập.');
        $this->redirect(url('user/devices'));
    }

    public function revokeOthers() {
        $this->requireAuth();
        $this->validateCsrf();
        $accountType = $_SESSION['device_account_type'] ?? '';
        if (!in_array($accountType, ['customer', 'admin'], true)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $this->security->revokeOtherDevices($this->getUserId(), $accountType, session_id());
        setFlash('success', 'Đã đăng xuất tất cả thiết bị khác.');
        $this->redirect(url('user/devices'));
    }
}
