<?php
require_once __DIR__ . '/../Helpers/Helper.php';

class LoginDeviceSecurity {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public static function describeDevice($userAgent) {
        $ua = (string) $userAgent;
        $os = 'Thiết bị khác';
        foreach ([
            '/Windows NT 10/i' => 'Windows 10/11', '/Windows NT 6\.3/i' => 'Windows 8.1',
            '/Mac OS X/i' => 'macOS', '/Android/i' => 'Android', '/iPhone|iPad|iPod/i' => 'iOS',
            '/Linux/i' => 'Linux',
        ] as $pattern => $name) {
            if (preg_match($pattern, $ua)) { $os = $name; break; }
        }
        $browser = 'Trình duyệt khác';
        foreach ([
            '/Edg\//i' => 'Microsoft Edge', '/OPR\//i' => 'Opera', '/Chrome\//i' => 'Google Chrome',
            '/Firefox\//i' => 'Mozilla Firefox', '/Safari\//i' => 'Safari',
        ] as $pattern => $name) {
            if (preg_match($pattern, $ua)) { $browser = $name; break; }
        }
        $device = preg_match('/Mobile|Android|iPhone|iPod/i', $ua) ? 'Điện thoại' : (preg_match('/iPad|Tablet/i', $ua) ? 'Máy tính bảng' : 'Máy tính');
        return ['name' => $device . ' - ' . $browser, 'os' => $os, 'browser' => $browser];
    }

    public function logEvent($user, $identifier, $success, $eventType) {
        $device = self::describeDevice($_SERVER['HTTP_USER_AGENT'] ?? '');
        $userId = is_array($user) ? ($user['id'] ?? null) : null;
        $role = is_array($user) ? ($user['role_name'] ?? '') : '';
        $accountType = in_array($role, ['admin', 'staff'], true) ? 'admin' : ($userId ? 'customer' : null);
        $this->db->query('INSERT INTO login_events (user_id, account_type, login_identifier, success, event_type, device_name, operating_system, browser, ip_address, created_at) VALUES (:uid, :type, :identifier, :success, :event, :name, :os, :browser, :ip, NOW())')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':identifier', substr((string) $identifier, 0, 190))
            ->bind(':success', $success ? 1 : 0)->bind(':event', $eventType)->bind(':name', $device['name'])
            ->bind(':os', $device['os'])->bind(':browser', $device['browser'])->bind(':ip', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45))->execute();
    }

    public function isTrustedCookie($userId, $accountType) {
        $token = $_COOKIE['vh_device'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return false;
        return (bool) $this->db->query('SELECT id FROM login_devices WHERE user_id = :uid AND account_type = :type AND token_hash = :hash AND revoked_at IS NULL')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':hash', hash('sha256', $token))->single();
    }

    public function createOtp($user, $accountType) {
        $now = time();
        $email = trim((string) ($user['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $rate = $this->db->query('SELECT COALESCE(SUM(resend_count + 1), 0) AS sent_count, MAX(last_sent_at) AS last_sent FROM login_device_otps WHERE user_id = :uid AND account_type = :type AND created_at >= :cutoff')
            ->bind(':uid', $user['id'])->bind(':type', $accountType)->bind(':cutoff', date('Y-m-d H:i:s', $now - 900))->single();
        if ($rate && ((strtotime($rate['last_sent'] ?? '') ?: 0) > $now - 60 || (int) $rate['sent_count'] >= 4)) return false;

        $device = self::describeDevice($_SERVER['HTTP_USER_AGENT'] ?? '');
        $token = bin2hex(random_bytes(32));
        $code = (string) random_int(100000, 999999);
        $inserted = $this->db->query('INSERT INTO login_device_otps (user_id, account_type, otp_hash, device_token_hash, device_name, operating_system, browser, ip_address, expires_at, attempts, resend_count, last_sent_at, consumed_at, created_at) VALUES (:uid, :type, :otp, :device_token, :name, :os, :browser, :ip, :expires, 0, :resends, NOW(), NULL, NOW())')
            ->bind(':uid', $user['id'])->bind(':type', $accountType)->bind(':otp', password_hash($code, PASSWORD_DEFAULT))
            ->bind(':device_token', hash('sha256', $token))->bind(':name', $device['name'])->bind(':os', $device['os'])
            ->bind(':browser', $device['browser'])->bind(':ip', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45))->bind(':expires', date('Y-m-d H:i:s', $now + 300))
            ->bind(':resends', 0)->execute();
        if (!$inserted) return false;
        $id = $this->db->lastInsertId();

        $safeName = e($device['name']);
        $html = '<h2>Đăng nhập từ thiết bị mới</h2><p>Mã xác minh: <strong style="font-size:24px;letter-spacing:6px">' . e($code) . '</strong></p><p>Thiết bị: ' . $safeName . '<br>Hệ điều hành: ' . e($device['os']) . '<br>Trình duyệt: ' . e($device['browser']) . '<br>IP: ' . e($_SERVER['REMOTE_ADDR'] ?? 'Không rõ') . '<br>Thời gian: ' . e(date('d/m/Y H:i:s')) . '</p><p>Mã có hiệu lực 5 phút. Nếu không phải bạn, hãy bỏ qua email này và đổi mật khẩu.</p>';
        if (!sendAppEmail($email, 'Mã xác minh đăng nhập - Vũ Huân Shop', $html)) {
            $this->db->query('UPDATE login_device_otps SET consumed_at = NOW() WHERE id = :id')->bind(':id', $id)->execute();
            return false;
        }
        return ['id' => $id, 'token' => $token, 'email' => $email];
    }

    public function resendOtp($otpId, $userId, $accountType) {
        $row = $this->db->query('SELECT * FROM login_device_otps WHERE id = :id AND user_id = :uid AND account_type = :type AND consumed_at IS NULL')
            ->bind(':id', $otpId)->bind(':uid', $userId)->bind(':type', $accountType)->single();
        if (!$row || (int) $row['resend_count'] >= 3 || strtotime($row['last_sent_at']) > time() - 60) return false;
        $user = $this->db->query('SELECT email FROM users WHERE id = :uid')->bind(':uid', $userId)->single();
        if (!$user || !filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL)) return false;
        $code = (string) random_int(100000, 999999);
        $this->db->query('UPDATE login_device_otps SET otp_hash = :otp, expires_at = :expires, attempts = 0, resend_count = resend_count + 1, last_sent_at = NOW() WHERE id = :id')
            ->bind(':otp', password_hash($code, PASSWORD_DEFAULT))->bind(':expires', date('Y-m-d H:i:s', time() + 300))->bind(':id', $otpId)->execute();
        $html = '<h2>Mã xác minh đăng nhập</h2><p>Mã mới của bạn là <strong style="font-size:24px;letter-spacing:6px">' . e($code) . '</strong>. Mã có hiệu lực 5 phút.</p>';
        return sendAppEmail($user['email'], 'Mã xác minh đăng nhập - Vũ Huân Shop', $html);
    }

    public function verifyOtp($otpId, $userId, $accountType, $code) {
        $row = $this->db->query('SELECT * FROM login_device_otps WHERE id = :id AND user_id = :uid AND account_type = :type AND consumed_at IS NULL AND expires_at > NOW()')
            ->bind(':id', $otpId)->bind(':uid', $userId)->bind(':type', $accountType)->single();
        if (!$row || (int) $row['attempts'] >= 5) return false;
        if (!preg_match('/^\d{6}$/', (string) $code) || !password_verify((string) $code, $row['otp_hash'])) {
            $this->db->query('UPDATE login_device_otps SET attempts = attempts + 1 WHERE id = :id')->bind(':id', $otpId)->execute();
            return false;
        }
        $this->db->query('UPDATE login_device_otps SET consumed_at = NOW() WHERE id = :id AND consumed_at IS NULL')->bind(':id', $otpId)->execute();
        if ($this->db->rowCount() !== 1) return false;
        return $row;
    }

    public function trustCurrentDevice($userId, $accountType, $tokenHash) {
        $device = self::describeDevice($_SERVER['HTTP_USER_AGENT'] ?? '');
        $sessionId = session_id();
        $inserted = $this->db->query('INSERT INTO login_devices (user_id, account_type, token_hash, device_name, operating_system, browser, ip_address, session_id, last_login_at, created_at, revoked_at) VALUES (:uid, :type, :hash, :name, :os, :browser, :ip, :session, NOW(), NOW(), NULL)')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':hash', $tokenHash)->bind(':name', $device['name'])
            ->bind(':os', $device['os'])->bind(':browser', $device['browser'])->bind(':ip', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45))->bind(':session', $sessionId)->execute();
        return $inserted ? $this->db->lastInsertId() : false;
    }

    public function recordTrustedLogin($userId, $accountType) {
        $token = $_COOKIE['vh_device'] ?? '';
        $device = self::describeDevice($_SERVER['HTTP_USER_AGENT'] ?? '');
        $updated = $this->db->query('UPDATE login_devices SET last_login_at = NOW(), ip_address = :ip, device_name = :name, operating_system = :os, browser = :browser, session_id = :session WHERE user_id = :uid AND account_type = :type AND token_hash = :hash AND revoked_at IS NULL')
            ->bind(':ip', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45))->bind(':name', $device['name'])->bind(':os', $device['os'])->bind(':browser', $device['browser'])
            ->bind(':session', session_id())->bind(':uid', $userId)->bind(':type', $accountType)->bind(':hash', hash('sha256', $token))->execute();
        return $updated && $this->db->rowCount() > 0;
    }

    public function isSessionActive($userId, $accountType, $sessionId) {
        return (bool) $this->db->query('SELECT id FROM login_devices WHERE user_id = :uid AND account_type = :type AND session_id = :session AND revoked_at IS NULL')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':session', $sessionId)->single();
    }

    public function getDevices($userId, $accountType) {
        return $this->db->query('SELECT * FROM login_devices WHERE user_id = :uid AND account_type = :type AND revoked_at IS NULL ORDER BY last_login_at DESC')
            ->bind(':uid', $userId)->bind(':type', $accountType)->resultSet();
    }

    public function revokeDevice($deviceId, $userId, $accountType, $excludeSession = null) {
        $sql = 'UPDATE login_devices SET revoked_at = NOW() WHERE id = :id AND user_id = :uid AND account_type = :type AND revoked_at IS NULL';
        if ($excludeSession !== null) $sql .= ' AND session_id <> :session';
        $query = $this->db->query($sql)->bind(':id', $deviceId)->bind(':uid', $userId)->bind(':type', $accountType);
        if ($excludeSession !== null) $query->bind(':session', $excludeSession);
        return $query->execute();
    }

    public function revokeOtherDevices($userId, $accountType, $sessionId) {
        return $this->db->query('UPDATE login_devices SET revoked_at = NOW() WHERE user_id = :uid AND account_type = :type AND session_id <> :session AND revoked_at IS NULL')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':session', $sessionId)->execute();
    }

    public function logOtpFailure($userId, $accountType) {
        $device = self::describeDevice($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->db->query('INSERT INTO login_events (user_id, account_type, login_identifier, success, event_type, device_name, operating_system, browser, ip_address, created_at) VALUES (:uid, :type, :identifier, 0, :event, :name, :os, :browser, :ip, NOW())')
            ->bind(':uid', $userId)->bind(':type', $accountType)->bind(':identifier', (string) $userId)->bind(':event', 'otp_failed')
            ->bind(':name', $device['name'])->bind(':os', $device['os'])->bind(':browser', $device['browser'])->bind(':ip', substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45))->execute();
    }
}
