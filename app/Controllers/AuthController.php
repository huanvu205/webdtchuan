<?php
require_once __DIR__ . '/../Helpers/Helper.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/CartModel.php';
require_once __DIR__ . '/../Services/LoginDeviceSecurity.php';

/**
 * AuthController - Xác thực người dùng
 */
class AuthController extends Controller {
    /**
     * @var UserModel
     */
    private $userModel;
    private $deviceSecurity;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->deviceSecurity = new LoginDeviceSecurity($this->db);
    }
    
    public function login() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            if (!consumeTurnstileTokenOnce($_POST['cf-turnstile-response'] ?? '')) {
                setFlash('error', 'Xác minh bảo mật thất bại. Vui lòng thử lại.');
                $this->view('auth.login', ['title' => 'Đăng nhập']);
                return;
            }

            $login = sanitize($this->getInput('login', ''));
            $password = $this->getInput('password', '');
            $remember = $this->getInput('remember', false);
            $attemptKey = 'login_attempt_' . hash('sha256', strtolower($login) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $attempt = $_SESSION[$attemptKey] ?? ['count' => 0, 'until' => 0];
            if (($attempt['until'] ?? 0) > time()) {
                setFlash('error', 'Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút.');
                $this->view('auth.login', ['title' => 'Đăng nhập', 'login' => e($login)]);
                return;
            }
            if (($attempt['until'] ?? 0) <= time() && ($attempt['count'] ?? 0) >= 5) $attempt = ['count' => 0, 'until' => 0];
            
            if (empty($login) || empty($password)) {
                setFlash('error', 'Vui lòng nhập đầy đủ thông tin');
                $this->view('auth.login', ['title' => 'Đăng nhập']);
                return;
            }
            
            $user = $this->userModel->findByEmailOrUsername($login);

            if (!$user || !$this->userModel->verifyPassword($user, $password)) {
                $this->deviceSecurity->logEvent($user ?: null, $login, false, 'password_failed');
                $attempt['count'] = ($attempt['count'] ?? 0) + 1;
                if ($attempt['count'] >= 5) $attempt['until'] = time() + 900;
                $_SESSION[$attemptKey] = $attempt;
                setFlash('error', 'Email/tên đăng nhập hoặc mật khẩu không đúng');
                $this->view('auth.login', ['title' => 'Đăng nhập', 'login' => e($login)]);
                return;
            }
            
            if ($user['status'] !== 'active') {
                $this->deviceSecurity->logEvent($user, $login, false, 'account_inactive');
                setFlash('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
                $this->view('auth.login', ['title' => 'Đăng nhập']);
                return;
            }

            $accountType = in_array($user['role_name'], ['admin', 'staff'], true) ? 'admin' : 'customer';
            if (!$this->deviceSecurity->isTrustedCookie($user['id'], $accountType)) {
                $challenge = $this->deviceSecurity->createOtp($user, $accountType);
                if (!$challenge) {
                    $this->deviceSecurity->logEvent($user, $login, false, 'otp_delivery_failed');
                    setFlash('error', 'Không thể gửi mã xác minh lúc này. Vui lòng thử lại sau.');
                    $this->view('auth.login', ['title' => 'Đăng nhập - ' . APP_NAME, 'login' => e($login)]);
                    return;
                }
                $_SESSION['pending_device_auth'] = [
                    'otp_id' => $challenge['id'], 'user_id' => $user['id'], 'account_type' => $accountType,
                    'user' => $user, 'device_token' => $challenge['token'], 'email' => $challenge['email'],
                    'attempt_key' => $attemptKey, 'redirect' => $_SESSION['redirect_after_login'] ?? null,
                    'expires_at' => time() + 300,
                ];
                $_SESSION['device_otp_resends'] = 0;
                $_SESSION['device_otp_resend_at'] = time() + 60;
                $this->view('auth.verify_device', [
                    'title' => 'Xác minh thiết bị - ' . APP_NAME,
                    'email' => $this->maskEmail($challenge['email']),
                    'resendAfter' => 60,
                    'otpExpiresAt' => time() + 300,
                ]);
                return;
            }

            // Lưu session
            unset($_SESSION[$attemptKey]);
            session_regenerate_id(true);
            if (!$this->deviceSecurity->recordTrustedLogin($user['id'], $accountType)) {
                setFlash('error', 'Không thể xác nhận thiết bị đã ghi nhớ. Vui lòng xác minh lại đăng nhập.');
                $this->view('auth.login', ['title' => 'Đăng nhập - ' . APP_NAME, 'login' => e($login)]);
                return;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['device_account_type'] = $accountType;
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
                'address' => $user['address'] ?? '',
                'city' => $user['city'] ?? '',
                'district' => $user['district'] ?? '',
                'ward' => $user['ward'] ?? '',
                'avatar' => $user['avatar'],
                'role' => $user['role_name'],
            ];
            
            // Merge giỏ hàng khách với giỏ của user
            if (isset($_SESSION['cart_session_id'])) {
                $cartModel = new CartModel();
                $cartModel->mergeGuestCart($user['id'], $_SESSION['cart_session_id']);
            }
            
            // Cập nhật số lượng giỏ hàng
            $this->updateCartCount();
            
            // Cập nhật thời gian đăng nhập
            $this->userModel->updateLastLogin($user['id']);
            $this->deviceSecurity->logEvent($user, $login, true, 'login');
            
            setFlash('success', 'Đăng nhập thành công! Chào mừng ' . $user['full_name']);
            
            // Redirect theo role
            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            
            if ($user['role_name'] === 'admin' || $user['role_name'] === 'staff') {
                $this->redirect($redirect ?? url('admin/dashboard'));
            } else {
                $this->redirect($redirect ?? url());
            }
        }
        
        $this->view('auth.login', ['title' => 'Đăng nhập - ' . APP_NAME]);
    }

    public function verifyDevice() {
        $pending = $_SESSION['pending_device_auth'] ?? null;
        if (!$pending || empty($pending['otp_id']) || empty($pending['user']['id'])) {
            $this->redirect(url('auth/login'));
            return;
        }

        $secondsLeft = max(0, (int) ($_SESSION['device_otp_resend_at'] ?? time() + 60) - time());
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $this->getInput('otp_action', 'verify');
            if ($action === 'resend') {
                if ($secondsLeft <= 0 && ($_SESSION['device_otp_resends'] ?? 0) < 3) {
                    if ($this->deviceSecurity->resendOtp($pending['otp_id'], $pending['user_id'], $pending['account_type'])) {
                        $_SESSION['device_otp_resend_at'] = time() + 60;
                        $pending['expires_at'] = time() + 300;
                        $_SESSION['pending_device_auth'] = $pending;
                        $_SESSION['device_otp_resends'] = ($_SESSION['device_otp_resends'] ?? 0) + 1;
                        setFlash('success', 'Đã gửi mã xác minh mới đến email của bạn.');
                    } else {
                        setFlash('error', 'Chưa thể gửi mã mới. Hãy chờ 60 giây hoặc đã hết lượt gửi lại.');
                    }
                } else {
                    setFlash('error', 'Đã hết số lần gửi lại mã.');
                }
                $this->redirect(url('auth/verify-device'));
                return;
            }

            $code = (string) $this->getInput('otp', '');
            try {
                $verified = $this->deviceSecurity->verifyOtp($pending['otp_id'], $pending['user_id'], $pending['account_type'], $code);
            } catch (Throwable $error) {
                error_log('Device OTP verification query failed: ' . $error->getMessage());
                setFlash('error', 'Không thể xác minh mã lúc này. Vui lòng thử lại sau.');
                $this->view('auth.verify_device', ['title' => 'Xác minh thiết bị - ' . APP_NAME, 'email' => $this->maskEmail($pending['email']), 'resendAfter' => $secondsLeft, 'otpExpiresAt' => $pending['expires_at'] ?? time()]);
                return;
            }
            if (!$verified) {
                try {
                    $this->deviceSecurity->logOtpFailure($pending['user_id'], $pending['account_type']);
                } catch (Throwable $error) {
                    error_log('Device OTP failure logging failed: ' . $error->getMessage());
                }
                setFlash('error', 'Mã xác minh không đúng, đã hết hạn hoặc vượt quá số lần thử.');
                $this->view('auth.verify_device', ['title' => 'Xác minh thiết bị - ' . APP_NAME, 'email' => $this->maskEmail($pending['email']), 'resendAfter' => $secondsLeft, 'otpExpiresAt' => $pending['expires_at'] ?? time()]);
                return;
            }

            try {
                $user = $pending['user'];
                session_regenerate_id(true);
                $this->completeLoginSession($user);
                if (isset($_SESSION['cart_session_id'])) {
                    (new CartModel())->mergeGuestCart($user['id'], $_SESSION['cart_session_id']);
                }
                $this->updateCartCount();
                $deviceId = $this->deviceSecurity->trustCurrentDevice($user['id'], $pending['account_type'], hash('sha256', $pending['device_token']));
                if (!$deviceId) {
                    throw new RuntimeException('Unable to persist the verified login device.');
                }
                $this->setDeviceCookie($pending['device_token']);
                $this->userModel->updateLastLogin($user['id']);
                $this->deviceSecurity->logEvent($user, $user['email'], true, 'device_verified_login');
                $redirect = $pending['redirect'];
                unset($_SESSION['pending_device_auth'], $_SESSION['device_otp_resend_at'], $_SESSION['device_otp_resends'], $_SESSION['redirect_after_login']);
                setFlash('success', 'Đăng nhập thành công! Chào mừng ' . $user['full_name']);
                $this->redirect($redirect ?: (in_array($pending['account_type'], ['admin'], true) ? url('admin/dashboard') : url()));
            } catch (Throwable $error) {
                error_log('Device OTP login finalization failed: ' . $error->getMessage());
                $_SESSION = [];
                session_regenerate_id(true);
                setcookie('vh_device', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'), 'httponly' => true, 'samesite' => 'Lax']);
                setFlash('error', 'Mã đã được xác minh nhưng chưa thể hoàn tất đăng nhập. Vui lòng thử đăng nhập lại.');
                $this->redirect(url('auth/login'));
            }
            return;
        }

        $this->view('auth.verify_device', [
            'title' => 'Xác minh thiết bị - ' . APP_NAME,
            'email' => $this->maskEmail($pending['email']),
            'resendAfter' => $secondsLeft,
            'otpExpiresAt' => $pending['expires_at'] ?? time(),
        ]);
    }

    private function completeLoginSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['device_account_type'] = in_array($user['role_name'] ?? '', ['admin', 'staff'], true) ? 'admin' : 'customer';
        $_SESSION['user'] = [
            'id' => $user['id'], 'username' => $user['username'], 'full_name' => $user['full_name'],
            'email' => $user['email'], 'phone' => $user['phone'] ?? '', 'address' => $user['address'] ?? '',
            'city' => $user['city'] ?? '', 'district' => $user['district'] ?? '', 'ward' => $user['ward'] ?? '',
            'avatar' => $user['avatar'] ?? null, 'role' => $user['role_name'],
        ];
    }

    private function setDeviceCookie($token) {
        setcookie('vh_device', $token, [
            'expires' => time() + 31536000, 'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        $_COOKIE['vh_device'] = $token;
    }

    private function maskEmail($email) {
        $parts = explode('@', (string) $email, 2);
        if (count($parts) !== 2) return 'email đã đăng ký';
        $name = $parts[0];
        return (strlen($name) > 2 ? substr($name, 0, 2) : substr($name, 0, 1)) . str_repeat('*', max(3, min(8, strlen($name) - 1))) . '@' . $parts[1];
    }
    
    public function register() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            if (!consumeTurnstileTokenOnce($_POST['cf-turnstile-response'] ?? '')) {
                setFlash('error', 'Xác minh bảo mật thất bại. Vui lòng thử lại.');
                $this->view('auth.register', ['title' => 'Đăng ký - ' . APP_NAME, 'errors' => [], 'old' => $_POST]);
                return;
            }

            $data = [
                'username' => sanitize($this->getInput('username', '')),
                'email' => sanitize($this->getInput('email', '')),
                'full_name' => sanitize($this->getInput('full_name', '')),
                'phone' => sanitize($this->getInput('phone', '')),
                'password' => $this->getInput('password', ''),
                'confirm_password' => $this->getInput('password_confirm', ''),
            ];
            
            $errors = [];
            
            if (empty($data['username'])) $errors['username'] = 'Tên đăng nhập không được để trống';
            elseif (strlen($data['username']) < 4) $errors['username'] = 'Tên đăng nhập ít nhất 4 ký tự';
            elseif ($this->userModel->usernameExists($data['username'])) $errors['username'] = 'Tên đăng nhập đã tồn tại';
            
            if (empty($data['email'])) $errors['email'] = 'Email không được để trống';
            elseif (!validateEmail($data['email'])) $errors['email'] = 'Email không hợp lệ';
            elseif ($this->userModel->emailExists($data['email'])) $errors['email'] = 'Email đã được đăng ký';
            
            if (empty($data['full_name'])) $errors['full_name'] = 'Họ tên không được để trống';
            if (!empty($data['phone']) && !validatePhone($data['phone'])) $errors['phone'] = 'Số điện thoại không hợp lệ';
            
            if (empty($data['password'])) $errors['password'] = 'Mật khẩu không được để trống';
            elseif (strlen($data['password']) < 6) $errors['password'] = 'Mật khẩu ít nhất 6 ký tự';
            elseif ($data['password'] !== $data['confirm_password']) $errors['password_confirm'] = 'Xác nhận mật khẩu không khớp';
            
            if (!empty($errors)) {
                $this->view('auth.register', [
                    'title' => 'Đăng ký - ' . APP_NAME,
                    'errors' => $errors,
                    'old' => $data
                ]);
                return;
            }
            
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                setFlash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
                $this->redirect(url('auth/login'));
            } else {
                setFlash('error', 'Đăng ký thất bại. Vui lòng thử lại.');
                $this->view('auth.register', ['title' => 'Đăng ký - ' . APP_NAME, 'old' => $data]);
                return;
            }
        }
        
        $this->view('auth.register', ['title' => 'Đăng ký - ' . APP_NAME]);
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Xóa thông tin tài khoản và giỏ hàng khỏi session
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['user'], $_SESSION['cart_count'], $_SESSION['device_account_type'], $_SESSION['pending_device_auth'], $_SESSION['device_otp_resend_at'], $_SESSION['device_otp_resends']);

        // Đổi session ID an toàn
        session_regenerate_id(true);

        if (function_exists('setFlash')) {
            setFlash('success', 'Đã đăng xuất thành công');
        } else {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đã đăng xuất thành công'];
        }

        $redirectUrl = function_exists('url') ? url('auth/login') : (defined('APP_URL') ? APP_URL . '/auth/login' : '/auth/login');
        $this->redirect($redirectUrl);
    }
    
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            if (!consumeTurnstileTokenOnce($_POST['cf-turnstile-response'] ?? '')) {
                setFlash('error', 'Xác minh bảo mật thất bại. Vui lòng thử lại.');
                $this->view('auth.forgot_password', ['title' => 'Quên mật khẩu - ' . APP_NAME]);
                return;
            }
            $email = trim($this->getInput('email', ''));
            
            if (!validateEmail($email)) {
                $this->view('auth.forgot_password', [
                    'title' => 'Quên mật khẩu - ' . APP_NAME,
                    'errors' => ['email' => 'Email không hợp lệ'],
                ]);
                return;
            }
            
            $user = $this->userModel->findByEmail($email);
            $viewData = [
                'title' => 'Quên mật khẩu - ' . APP_NAME,
                'emailSent' => true,
                'sentToEmail' => $email,
            ];
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                if (!$this->userModel->saveResetToken($user['id'], $token)) {
                    throw new RuntimeException('Unable to save password reset token.');
                }

                if (!sendPasswordResetEmail($user, $token)) {
                    if (!$this->userModel->deleteResetTokensForUser($user['id'])) {
                        error_log('Unable to remove undelivered password reset token.');
                    }
                    $viewData['emailSent'] = false;
                    $viewData['error'] = (SMTP_USER === '' || SMTP_PASS === '')
                        ? 'Website chưa được cấu hình SMTP_USER và SMTP_PASS nên chưa thể gửi email. Vui lòng liên hệ quản trị viên.'
                        : 'Máy chủ email hiện không phản hồi. Vui lòng thử lại sau hoặc liên hệ bộ phận hỗ trợ.';
                }
            }
            
            $this->view('auth.forgot_password', $viewData);
            return;
        }
        
        $this->view('auth.forgot_password', ['title' => 'Quên mật khẩu - ' . APP_NAME]);
    }
    
    public function resetPassword() {
        $token = (string) ($_GET['token'] ?? '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            if (!consumeTurnstileTokenOnce($_POST['cf-turnstile-response'] ?? '')) {
                setFlash('error', 'Xác minh bảo mật thất bại. Vui lòng thử lại.');
                $this->view('auth.reset_password', ['title' => 'Đặt lại mật khẩu - ' . APP_NAME, 'token' => $token, 'valid' => true]);
                return;
            }
            $token = (string) $this->getInput('token', '');
            $password = $this->getInput('password', '');
            $confirm = $this->getInput('confirm_password', '');

            $userId = $this->userModel->verifyResetToken($token);
            if (!$userId) {
                $this->view('auth.forgot_password', [
                    'title' => 'Quên mật khẩu - ' . APP_NAME,
                    'error' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.',
                ]);
                return;
            }
            
            if (strlen($password) < 6) {
                setFlash('error', 'Mật khẩu ít nhất 6 ký tự');
                $this->view('auth.reset_password', ['title' => 'Đặt lại mật khẩu', 'token' => e($token)]);
                return;
            }
            
            if ($password !== $confirm) {
                setFlash('error', 'Mật khẩu xác nhận không khớp');
                $this->view('auth.reset_password', ['title' => 'Đặt lại mật khẩu', 'token' => e($token)]);
                return;
            }
            
            if (!$this->userModel->updatePassword($userId, $password)) {
                setFlash('error', 'Không thể cập nhật mật khẩu. Vui lòng thử lại.');
                $this->view('auth.reset_password', ['title' => 'Đặt lại mật khẩu - ' . APP_NAME, 'token' => $token]);
                return;
            }
            
            if (!$this->userModel->deleteResetTokensForUser($userId)) {
                error_log('Unable to invalidate password reset tokens after successful password reset.');
            }
            setFlash('success', 'Mật khẩu đã được đặt lại thành công');
            $this->redirect(url('auth/login'));
            return;
        }

        if (!$this->userModel->verifyResetToken($token)) {
            $this->view('auth.forgot_password', [
                'title' => 'Quên mật khẩu - ' . APP_NAME,
                'error' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.',
            ]);
            return;
        }
        
        $this->view('auth.reset_password', ['title' => 'Đặt lại mật khẩu - ' . APP_NAME, 'token' => $token]);
    }
    
    /**
     * Chuyển hướng người dùng đến Google OAuth 2.0
     */
    public function google() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
            return;
        }

        if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
            setFlash('error', 'Chức năng đăng nhập Google chưa được cấu hình Client ID.');
            $this->redirect(url('auth/login'));
            return;
        }

        // Tạo CSRF state để bảo vệ chống tấn công CSRF
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $redirectUri = function_exists('getGoogleRedirectUri') ? getGoogleRedirectUri() : GOOGLE_REDIRECT_URI;

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Xử lý callback sau khi người dùng xác thực từ Google
     */
    public function googleCallback() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
            return;
        }

        // Kiểm tra lỗi từ Google (người dùng bấm hủy)
        if (!empty($_GET['error'])) {
            setFlash('error', 'Bạn đã hủy đăng nhập bằng Google.');
            $this->redirect(url('auth/login'));
            return;
        }

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['google_oauth_state'] ?? '';
        unset($_SESSION['google_oauth_state']);

        // Kiểm tra tính hợp lệ của state (CSRF Protection)
        if (empty($code) || empty($state) || $state !== $savedState) {
            setFlash('error', 'Yêu cầu đăng nhập không hợp lệ hoặc phiên đã hết hạn. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        // 1. Đổi authorization code lấy access token
        $tokenData = $this->exchangeGoogleCode($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            setFlash('error', 'Không thể kết nối xác thực với Google. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        // 2. Lấy thông tin tài khoản người dùng từ Google
        $googleUser = $this->getGoogleUserInfo($tokenData['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            setFlash('error', 'Không thể lấy thông tin email từ tài khoản Google.');
            $this->redirect(url('auth/login'));
            return;
        }

        $googleId = $googleUser['sub'] ?? ($googleUser['id'] ?? '');
        $email = strtolower(trim($googleUser['email']));
        $name = trim($googleUser['name'] ?? ($googleUser['given_name'] ?? 'Khách hàng Google'));
        $picture = $googleUser['picture'] ?? null;

        // 3. Tìm tài khoản trong database
        // 3a. Tìm theo google_id
        $user = !empty($googleId) ? $this->userModel->findByGoogleId($googleId) : null;

        if (!$user) {
            // 3b. Tìm theo email
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                // Đã có tài khoản bằng email này, liên kết google_id
                $this->userModel->updateGoogleId($user['id'], $googleId, $picture);
                $user = $this->userModel->findById($user['id']);
            } else {
                // 3c. Tạo mới tài khoản
                // Tạo username duy nhất từ email
                $baseUsername = explode('@', $email)[0];
                $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);
                if (strlen($baseUsername) < 4) {
                    $baseUsername = 'user_' . substr(md5(uniqid()), 0, 6);
                }

                $username = $baseUsername;
                $counter = 1;
                while ($this->userModel->usernameExists($username)) {
                    $username = $baseUsername . '_' . $counter;
                    $counter++;
                }

                $newUserId = $this->userModel->createFromGoogle([
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $name,
                    'google_id' => $googleId,
                    'avatar' => $picture,
                ]);

                if (!$newUserId) {
                    setFlash('error', 'Không thể tạo tài khoản từ Google. Vui lòng thử lại.');
                    $this->redirect(url('auth/login'));
                    return;
                }

                $user = $this->userModel->findById($newUserId);
            }
        }

        if (!$user) {
            setFlash('error', 'Đăng nhập Google thất bại. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        $this->beginSocialDeviceLogin($user, 'Google');
    }

    /**
     * Fallback alias cho callback
     */
    public function google_callback() {
        $this->googleCallback();
    }

    /**
     * Chuyển hướng người dùng đến Facebook OAuth
     */
    public function facebook() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
            return;
        }

        if (!defined('FACEBOOK_APP_ID') || empty(FACEBOOK_APP_ID)) {
            setFlash('error', 'Chức năng đăng nhập Facebook chưa được cấu hình App ID.');
            $this->redirect(url('auth/login'));
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['facebook_oauth_state'] = $state;

        $redirectUri = function_exists('getFacebookRedirectUri') ? getFacebookRedirectUri() : (defined('FACEBOOK_REDIRECT_URI') ? FACEBOOK_REDIRECT_URI : url('auth/facebook-callback'));

        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'public_profile',
            'response_type' => 'code',
        ];

        $authUrl = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Xử lý callback sau khi người dùng xác thực từ Facebook
     */
    public function facebookCallback() {
        if ($this->isLoggedIn()) {
            $this->redirect(url());
            return;
        }

        if (!empty($_GET['error'])) {
            setFlash('error', 'Bạn đã hủy đăng nhập bằng Facebook.');
            $this->redirect(url('auth/login'));
            return;
        }

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['facebook_oauth_state'] ?? '';
        unset($_SESSION['facebook_oauth_state']);

        if (empty($code) || empty($state) || $state !== $savedState) {
            setFlash('error', 'Yêu cầu đăng nhập không hợp lệ hoặc phiên đã hết hạn. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        // 1. Đổi code lấy Access Token của Facebook
        $redirectUri = function_exists('getFacebookRedirectUri') ? getFacebookRedirectUri() : (defined('FACEBOOK_REDIRECT_URI') ? FACEBOOK_REDIRECT_URI : url('auth/facebook-callback'));
        $tokenUrl = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'client_id' => FACEBOOK_APP_ID,
            'client_secret' => FACEBOOK_APP_SECRET,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $tokenData = ($httpCode === 200 && $response) ? json_decode($response, true) : null;
        if (!$tokenData || empty($tokenData['access_token'])) {
            setFlash('error', 'Không thể kết nối xác thực với Facebook. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        $accessToken = $tokenData['access_token'];

        // 2. Lấy thông tin người dùng từ Facebook Graph API
        $userUrl = 'https://graph.facebook.com/me?fields=id,name,email,picture.width(400).height(400)&access_token=' . urlencode($accessToken);
        $chUser = curl_init($userUrl);
        curl_setopt($chUser, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chUser, CURLOPT_TIMEOUT, 15);
        curl_setopt($chUser, CURLOPT_SSL_VERIFYPEER, false);
        $userResponse = curl_exec($chUser);
        $userHttpCode = curl_getinfo($chUser, CURLINFO_HTTP_CODE);

        $fbUser = ($userHttpCode === 200 && $userResponse) ? json_decode($userResponse, true) : null;
        if (!$fbUser || empty($fbUser['id'])) {
            setFlash('error', 'Không thể lấy thông tin tài khoản từ Facebook.');
            $this->redirect(url('auth/login'));
            return;
        }

        $facebookId = $fbUser['id'];
        $name = trim($fbUser['name'] ?? 'Khách hàng Facebook');
        $email = !empty($fbUser['email']) ? strtolower(trim($fbUser['email'])) : null;
        $picture = $fbUser['picture']['data']['url'] ?? null;

        // 3. Tìm tài khoản trong database
        // 3a. Tìm theo facebook_id
        $user = $this->userModel->findByFacebookId($facebookId);

        if (!$user && $email) {
            // 3b. Tìm theo email
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $this->userModel->updateFacebookId($user['id'], $facebookId, $picture);
                $user = $this->userModel->findById($user['id']);
            }
        }

        if (!$user) {
            // 3c. Tạo mới tài khoản
            $baseUsername = $email ? explode('@', $email)[0] : 'fb_' . substr($facebookId, -6);
            $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);
            if (strlen($baseUsername) < 4) {
                $baseUsername = 'fb_user_' . substr(md5(uniqid()), 0, 5);
            }

            $username = $baseUsername;
            $counter = 1;
            while ($this->userModel->usernameExists($username)) {
                $username = $baseUsername . '_' . $counter;
                $counter++;
            }

            $userEmail = $email ?? ($username . '@facebook.user');

            $newUserId = $this->userModel->createFromFacebook([
                'username' => $username,
                'email' => $userEmail,
                'full_name' => $name,
                'facebook_id' => $facebookId,
                'avatar' => $picture,
            ]);

            if (!$newUserId) {
                setFlash('error', 'Không thể tạo tài khoản từ Facebook. Vui lòng thử lại.');
                $this->redirect(url('auth/login'));
                return;
            }

            $user = $this->userModel->findById($newUserId);
        }

        if (!$user) {
            setFlash('error', 'Đăng nhập Facebook thất bại. Vui lòng thử lại.');
            $this->redirect(url('auth/login'));
            return;
        }

        $this->beginSocialDeviceLogin($user, 'Facebook');
    }

    /**
     * Fallback alias cho facebook callback
     */
    public function facebook_callback() {
        $this->facebookCallback();
    }

    /**
     * Gửi yêu cầu cURL đổi code lấy Access Token của Google
     * @param string $code
     * @return array|null
     */
    private function exchangeGoogleCode($code) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $redirectUri = function_exists('getGoogleRedirectUri') ? getGoogleRedirectUri() : GOOGLE_REDIRECT_URI;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }

        error_log('Google OAuth Token Error (' . $httpCode . '): ' . $response);
        return null;
    }

    /**
     * Gửi yêu cầu cURL lấy thông tin userinfo từ Google
     * @param string $accessToken
     * @return array|null
     */
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }

        error_log('Google OAuth UserInfo Error (' . $httpCode . '): ' . $response);
        return null;
    }

    private function beginSocialDeviceLogin($user, $provider) {
        if (($user['status'] ?? '') !== 'active') {
            setFlash('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
            $this->redirect(url('auth/login'));
            return;
        }
        $accountType = in_array($user['role_name'] ?? '', ['admin', 'staff'], true) ? 'admin' : 'customer';
        if ($this->deviceSecurity->isTrustedCookie($user['id'], $accountType)) {
            session_regenerate_id(true);
            if (!$this->deviceSecurity->recordTrustedLogin($user['id'], $accountType)) {
                setFlash('error', 'Không thể xác nhận thiết bị đã ghi nhớ. Vui lòng đăng nhập lại.');
                $this->redirect(url('auth/login'));
                return;
            }
            $this->completeLoginSession($user);
            $this->deviceSecurity->logEvent($user, $user['email'], true, strtolower($provider) . '_login');
            $this->updateCartCount();
            $this->redirect($accountType === 'admin' ? url('admin/dashboard') : url());
            return;
        }
        $challenge = $this->deviceSecurity->createOtp($user, $accountType);
        if (!$challenge) {
            $this->deviceSecurity->logEvent($user, $user['email'], false, 'otp_delivery_failed');
            setFlash('error', 'Không thể gửi mã xác minh lúc này. Vui lòng đăng nhập lại bằng mật khẩu.');
            $this->redirect(url('auth/login'));
            return;
        }
        $_SESSION['pending_device_auth'] = [
            'otp_id' => $challenge['id'], 'user_id' => $user['id'], 'account_type' => $accountType,
            'user' => $user, 'device_token' => $challenge['token'], 'email' => $challenge['email'], 'redirect' => null,
            'expires_at' => time() + 300,
        ];
        $_SESSION['device_otp_resends'] = 0;
        $_SESSION['device_otp_resend_at'] = time() + 60;
        $this->redirect(url('auth/verify-device'));
    }
    
    private function updateCartCount() {
        $cartModel = new CartModel();
        $cart = $cartModel->getOrCreate($this->getUserId());
        $_SESSION['cart_count'] = $cartModel->getItemCount($cart['id']);
    }
}
?>
