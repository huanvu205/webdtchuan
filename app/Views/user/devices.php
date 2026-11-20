<?php
$devices = $devices ?? [];
$events = $events ?? [];
$currentSession = $currentSession ?? '';
$isAdminDevices = ($accountType ?? '') === 'admin';
if ($isAdminDevices) {
    require_once __DIR__ . '/../admin/layouts/header.php';
} else {
    require_once __DIR__ . '/../layouts/header.php';
}
?>
<style>
.login-devices{max-width:1040px;margin:30px auto 60px;padding:0 16px;color:#172033}.ld-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:24px}.ld-head h1{font-size:clamp(24px,4vw,32px);font-weight:800;margin:0 0 8px}.ld-head p{color:#687386;margin:0}.ld-revoke-all{border:1px solid #f1b7b7;background:#fff;color:#b42323;border-radius:10px;padding:11px 15px;font-weight:700;white-space:nowrap}.ld-grid{display:grid;gap:13px}.ld-card{display:flex;gap:16px;align-items:center;padding:18px;background:#fff;border:1px solid #e7eaf0;border-radius:15px;box-shadow:0 5px 16px #17203308}.ld-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:14px;background:#f2f4f8;color:#49566b;font-size:20px;flex:0 0 auto}.ld-card.current .ld-icon{background:#eaf7ef;color:#16834a}.ld-info{min-width:0;flex:1}.ld-name{font-weight:750;font-size:15px}.ld-meta{color:#687386;font-size:13px;line-height:1.6;margin-top:4px;overflow-wrap:anywhere}.ld-current{display:inline-flex;padding:3px 8px;margin-left:8px;border-radius:99px;background:#eaf7ef;color:#16834a;font-size:11px;font-weight:750}.ld-revoke{border:0;background:#fff0f0;color:#b42323;border-radius:9px;padding:9px 12px;font-weight:700;white-space:nowrap}.ld-section{font-weight:800;margin:30px 0 13px;font-size:18px}.ld-empty{padding:24px;border:1px dashed #ccd2dc;border-radius:14px;color:#687386;background:#fff}.ld-event{display:grid;grid-template-columns:1fr auto;gap:5px 12px;padding:14px 16px;border-bottom:1px solid #edf0f4}.ld-event:last-child{border-bottom:0}.ld-event-list{background:#fff;border:1px solid #e7eaf0;border-radius:14px;overflow:hidden}.ld-event small{color:#687386}.ld-event .failed{color:#b42323;font-weight:700}.ld-event .ok{color:#16834a;font-weight:700}@media(max-width:600px){.ld-head{display:block}.ld-revoke-all{margin-top:16px;width:100%}.ld-card{align-items:flex-start;padding:14px;gap:11px}.ld-icon{width:42px;height:42px}.ld-current{display:table;margin:5px 0 0}.ld-revoke{padding:8px 9px;font-size:12px}.ld-meta{font-size:12px}}
.ld-confirm-backdrop{position:fixed;inset:0;z-index:1100;background:#10182899;display:none;place-items:center;padding:18px}.ld-confirm-backdrop.open{display:grid}.ld-confirm{width:min(100%,400px);padding:24px;background:#fff;border-radius:17px;box-shadow:0 20px 60px #10182833}.ld-confirm h3{font-size:18px;font-weight:800}.ld-confirm p{color:#687386;font-size:14px;line-height:1.55}.ld-confirm-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:20px}.ld-confirm-actions button{border:0;border-radius:9px;padding:10px 14px;font-weight:700}.ld-confirm-cancel{background:#f1f3f6;color:#344054}.ld-confirm-ok{background:#c62828;color:#fff}
</style>
<section class="login-devices">
    <header class="ld-head"><div><h1><i class="fa-solid fa-laptop-shield me-2" style="color:#c62828"></i>Thiết bị đăng nhập</h1><p>Kiểm tra và thu hồi quyền truy cập trên thiết bị của bạn.</p></div>
        <?php if (count($devices) > 1): ?><form method="POST" action="<?= url('user/devices/revoke-others') ?>" data-confirm="Đăng xuất tất cả thiết bị khác?">
            <?= csrfToken() ?><button class="ld-revoke-all" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất thiết bị khác</button>
        </form><?php endif; ?>
    </header>
    <section class="ld-grid" aria-label="Danh sách thiết bị">
        <?php foreach ($devices as $device): $current = ($device['session_id'] ?? '') === $currentSession; ?>
            <article class="ld-card <?= $current ? 'current' : '' ?>">
                <div class="ld-icon"><i class="fa-solid <?= preg_match('/Điện thoại/i', $device['device_name']) ? 'fa-mobile-screen' : (preg_match('/Máy tính bảng/i', $device['device_name']) ? 'fa-tablet-screen-button' : 'fa-desktop') ?>"></i></div>
                <div class="ld-info"><div class="ld-name"><?= e($device['device_name']) ?><?php if ($current): ?><span class="ld-current">Thiết bị hiện tại</span><?php endif; ?></div>
                    <div class="ld-meta"><?= e($device['operating_system']) ?> · <?= e($device['browser']) ?><br>IP <?= e($device['ip_address']) ?> · Đăng nhập gần nhất <?= e(date('d/m/Y H:i', strtotime($device['last_login_at']))) ?></div>
                </div>
                <form method="POST" action="<?= url('user/devices/revoke/' . (int) $device['id']) ?>" data-confirm="Thu hồi thiết bị này? Phiên đăng nhập của thiết bị sẽ bị vô hiệu hóa.">
                    <?= csrfToken() ?><button type="submit" class="ld-revoke"><i class="fa-solid fa-right-from-bracket me-1"></i><?= $current ? 'Đăng xuất' : 'Thu hồi' ?></button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$devices): ?><div class="ld-empty">Chưa có thiết bị đăng nhập được ghi nhận.</div><?php endif; ?>
    </section>
    <h2 class="ld-section">Lịch sử đăng nhập</h2>
    <div class="ld-event-list">
        <?php foreach ($events as $event): ?><div class="ld-event"><span><strong><?= e($event['device_name']) ?></strong> · <?= e($event['event_type']) ?></span><span class="<?= $event['success'] ? 'ok' : 'failed' ?>"><?= $event['success'] ? 'Thành công' : 'Thất bại' ?></span><small><?= e($event['operating_system']) ?> · <?= e($event['browser']) ?> · IP <?= e($event['ip_address']) ?></small><small><?= e(date('d/m/Y H:i:s', strtotime($event['created_at']))) ?></small></div><?php endforeach; ?>
        <?php if (!$events): ?><div class="ld-empty" style="border:0">Chưa có lịch sử đăng nhập.</div><?php endif; ?>
    </div>
</section>
<div class="ld-confirm-backdrop" id="ld-confirm-backdrop" role="dialog" aria-modal="true" aria-labelledby="ld-confirm-title"><div class="ld-confirm"><h3 id="ld-confirm-title">Xác nhận đăng xuất thiết bị</h3><p id="ld-confirm-message"></p><div class="ld-confirm-actions"><button class="ld-confirm-cancel" type="button">Hủy</button><button class="ld-confirm-ok" type="button">Xác nhận</button></div></div></div>
<script>
(()=>{const modal=document.getElementById('ld-confirm-backdrop'),message=document.getElementById('ld-confirm-message');let pendingForm=null;const close=()=>{modal.classList.remove('open');pendingForm=null;};document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(form.dataset.confirmed)return;event.preventDefault();pendingForm=form;message.textContent=form.dataset.confirm;modal.classList.add('open');modal.querySelector('.ld-confirm-cancel').focus();}));modal.querySelector('.ld-confirm-cancel').addEventListener('click',close);modal.addEventListener('click',event=>{if(event.target===modal)close();});modal.querySelector('.ld-confirm-ok').addEventListener('click',()=>{if(!pendingForm)return;pendingForm.dataset.confirmed='1';const button=pendingForm.querySelector('button');if(button){button.disabled=true;button.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý';}pendingForm.requestSubmit();});document.addEventListener('keydown',event=>{if(event.key==='Escape'&&modal.classList.contains('open'))close();});})();
</script>
<?php
if ($isAdminDevices) {
    require_once __DIR__ . '/../admin/layouts/footer.php';
} else {
    require_once __DIR__ . '/../layouts/footer.php';
}
?>
