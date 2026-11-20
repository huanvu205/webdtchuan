<?php
$email = $email ?? '';
$resendAfter = (int) ($resendAfter ?? 60);
$otpExpiresAt = (int) ($otpExpiresAt ?? (time() + 300));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Xác minh thiết bị') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body.device-verify{min-height:100vh;margin:0;display:grid;place-items:center;padding:20px;background:linear-gradient(145deg,#f5f7fb,#fff1ef);font-family:Inter,system-ui,sans-serif;color:#172033}
        .dv-card{width:min(100%,470px);background:#fff;border:1px solid #e8ebf1;border-radius:22px;padding:clamp(24px,6vw,42px);box-shadow:0 22px 65px #18243a18}
        .dv-mark{width:56px;height:56px;display:grid;place-items:center;border-radius:17px;background:#fff0ef;color:#c62828;font-size:23px}
        .dv-card h1{font-size:25px;font-weight:800;letter-spacing:-.5px;margin:22px 0 8px}.dv-muted{color:#687386;font-size:14px;line-height:1.65}
        .dv-code{display:flex;gap:8px;justify-content:center;margin:27px 0 12px}.dv-code input{width:52px;height:60px;border:1px solid #d7dce5;border-radius:12px;text-align:center;font-size:24px;font-weight:750;color:#172033;outline:none}
        .dv-code input:focus{border-color:#c62828;box-shadow:0 0 0 3px #c6282818}.dv-submit{height:49px;border:0;border-radius:12px;background:#c62828;color:#fff;font-weight:700;width:100%;margin-top:12px}.dv-submit:disabled{opacity:.65}
        .dv-flash{padding:11px 13px;border-radius:10px;margin:16px 0;font-size:14px}.dv-flash.error{color:#a72121;background:#fff0f0}.dv-flash.success{color:#176b45;background:#eaf8f1}
        .dv-actions{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:20px;font-size:13px}.dv-actions a,.dv-actions button{color:#b42323;text-decoration:none;background:none;border:0;padding:0;font-weight:650}.dv-actions button:disabled{color:#8992a2;cursor:not-allowed}
        @media(max-width:390px){.dv-code{gap:5px}.dv-code input{width:45px;height:54px;font-size:21px}}
    </style>
</head>
<body class="device-verify">
    <main class="dv-card">
        <div class="dv-mark"><i class="fa-solid fa-shield-halved"></i></div>
        <h1>Xác minh thiết bị</h1>
        <p class="dv-muted">Chúng tôi phát hiện lần đăng nhập từ thiết bị mới. Nhập mã 6 số gửi đến <strong><?= e($email) ?></strong> để tiếp tục.</p>
        <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="dv-flash <?= ($flash['type'] ?? '') === 'error' ? 'error' : 'success' ?>" role="status"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>
        <form id="device-otp-form" method="POST" action="<?= url('auth/verify-device') ?>">
            <?= csrfToken() ?>
            <label class="form-label fw-semibold" for="otp-digit-1">Mã xác minh</label>
            <div class="dv-code" role="group" aria-label="Mã xác minh gồm 6 chữ số">
                <?php for ($i = 1; $i <= 6; $i++): ?><input id="otp-digit-<?= $i ?>" class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" aria-label="Chữ số <?= $i ?>" required><?php endfor; ?>
            </div>
            <input type="hidden" name="otp" id="otp-value">
            <p id="otp-error" class="text-danger small mb-1" aria-live="polite"></p>
            <p class="dv-muted small">Mã hết hạn sau <strong id="otp-countdown">05:00</strong></p>
            <button class="dv-submit" id="otp-submit" type="submit"><i class="fa-solid fa-check me-2"></i>Xác nhận và đăng nhập</button>
        </form>
        <div class="dv-actions">
            <span>Không nhận được mã? <span id="resend-timer"></span></span>
            <form method="POST" action="<?= url('auth/verify-device') ?>" id="resend-form">
                <?= csrfToken() ?><input type="hidden" name="otp_action" value="resend">
                <button id="resend-button" type="submit" disabled>Gửi lại mã</button>
            </form>
        </div>
        <div class="dv-actions"><a href="<?= url('auth/logout') ?>"><i class="fa-solid fa-arrow-left me-1"></i>Hủy và quay lại đăng nhập</a><span><i class="fa-solid fa-lock me-1"></i>Bảo mật tài khoản</span></div>
    </main>
    <script>
    (()=>{
        const digits=[...document.querySelectorAll('.otp-digit')], form=document.getElementById('device-otp-form'), hidden=document.getElementById('otp-value');
        digits[0].focus();
        digits.forEach((input,index)=>{
            input.addEventListener('input',()=>{input.value=input.value.replace(/\D/g,'').slice(-1);if(input.value&&digits[index+1])digits[index+1].focus();});
            input.addEventListener('keydown',event=>{if(event.key==='Backspace'&&!input.value&&digits[index-1])digits[index-1].focus();if(event.key==='ArrowLeft'&&digits[index-1])digits[index-1].focus();if(event.key==='ArrowRight'&&digits[index+1])digits[index+1].focus();});
            input.addEventListener('paste',event=>{event.preventDefault();const pasted=(event.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);pasted.split('').forEach((digit,i)=>digits[i].value=digit);digits[Math.min(pasted.length,5)].focus();});
        });
        form.addEventListener('submit',event=>{hidden.value=digits.map(input=>input.value).join('');if(!/^\d{6}$/.test(hidden.value)){event.preventDefault();document.getElementById('otp-error').textContent='Vui lòng nhập đủ 6 chữ số.';return;}const button=document.getElementById('otp-submit');button.disabled=true;button.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Đang xác minh...';});
        const expiresAt=<?= $otpExpiresAt ?>;const countdown=document.getElementById('otp-countdown');const tickOtp=()=>{const remain=Math.max(0,expiresAt-Math.floor(Date.now()/1000));countdown.textContent=String(Math.floor(remain/60)).padStart(2,'0')+':'+String(remain%60).padStart(2,'0');};tickOtp();setInterval(tickOtp,1000);
        let wait=<?= max(0, $resendAfter) ?>;const resend=document.getElementById('resend-button'), timer=document.getElementById('resend-timer');const update=()=>{resend.disabled=wait>0;if(wait>0){timer.textContent='(' + wait + 's)';wait--;}else{timer.textContent='';}};update();setInterval(update,1000);
        document.getElementById('resend-form').addEventListener('submit',()=>{resend.disabled=true;resend.textContent='Đang gửi...';});
    })();
    </script>
</body>
</html>
