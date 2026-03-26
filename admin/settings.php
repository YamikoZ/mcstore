<?php
$pageTitle = 'ตั้งค่าระบบ';
$db = Database::getInstance();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'CSRF token ไม่ถูกต้อง');
        redirect('admin/settings');
    }

    $action = $_POST['action'] ?? '';

    // === Save settings (AJAX or normal) ===
    if ($action === 'save_settings') {
        $settings = $_POST['settings'] ?? [];
        $changed = 0;
        foreach ($settings as $key => $value) {
            $key = preg_replace('/[^a-z0-9_]/', '', $key);
            if ($key) {
                $old = Settings::get($key);
                if ($old !== $value) {
                    Settings::set($key, $value);
                    $changed++;
                }
            }
        }
        Settings::reload();
        auditLog(Auth::id(), 'admin_settings_update', "Updated {$changed} settings");

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['success' => true, 'message' => "บันทึกเรียบร้อย ({$changed} รายการที่เปลี่ยน)", 'changed' => $changed]);
        }
        flash('success', "บันทึกเรียบร้อย ({$changed} รายการที่เปลี่ยน)");
        redirect('admin/settings' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : ''));
    }

    // === Add new setting ===
    if ($action === 'add_setting') {
        $newKey = preg_replace('/[^a-z0-9_]/', '', $_POST['new_key'] ?? '');
        $newVal = $_POST['new_value'] ?? '';
        $newCat = preg_replace('/[^a-z0-9_]/', '', $_POST['new_category'] ?? 'general');
        $newType = preg_replace('/[^a-z]/', '', $_POST['new_type'] ?? 'text');
        $newDesc = trim($_POST['new_description'] ?? '');

        if ($newKey) {
            $exists = $db->fetch("SELECT 1 FROM settings WHERE setting_key = ?", [$newKey]);
            if ($exists) {
                flash('error', "คีย์ '{$newKey}' มีอยู่แล้ว");
            } else {
                $db->execute(
                    "INSERT INTO settings (setting_key, setting_value, category, setting_type, description) VALUES (?, ?, ?, ?, ?)",
                    [$newKey, $newVal, $newCat, $newType, $newDesc]
                );
                Settings::reload();
                auditLog(Auth::id(), 'admin_settings_add', "Added setting: {$newKey}");
                flash('success', "เพิ่มการตั้งค่า '{$newKey}' เรียบร้อย");
            }
        } else {
            flash('error', 'กรุณากรอก Key');
        }
        redirect('admin/settings?tab=' . urlencode($newCat));
    }

    // === Delete setting ===
    if ($action === 'delete_setting') {
        $delKey = preg_replace('/[^a-z0-9_]/', '', $_POST['delete_key'] ?? '');
        if ($delKey) {
            $db->execute("DELETE FROM settings WHERE setting_key = ?", [$delKey]);
            Settings::reload();
            auditLog(Auth::id(), 'admin_settings_delete', "Deleted setting: {$delKey}");
            flash('success', "ลบการตั้งค่า '{$delKey}' เรียบร้อย");
        }
        redirect('admin/settings' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : ''));
    }

    // === Export settings ===
    if ($action === 'export_settings') {
        $all = $db->fetchAll("SELECT setting_key, setting_value, category, setting_type, description FROM settings ORDER BY category, setting_key");
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="mcstore_settings_' . date('Ymd_His') . '.json"');
        echo json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === Import settings ===
    if ($action === 'import_settings' && !empty($_POST['import_json'])) {
        $importData = json_decode($_POST['import_json'], true);
        if (is_array($importData)) {
            $imported = 0;
            foreach ($importData as $item) {
                if (empty($item['setting_key'])) continue;
                $key = preg_replace('/[^a-z0-9_]/', '', $item['setting_key']);
                if (!$key) continue;
                $db->execute(
                    "INSERT INTO settings (setting_key, setting_value, category, setting_type, description) VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), category = VALUES(category), setting_type = VALUES(setting_type), description = VALUES(description)",
                    [$key, $item['setting_value'] ?? '', $item['category'] ?? 'general', $item['setting_type'] ?? 'text', $item['description'] ?? '']
                );
                $imported++;
            }
            Settings::reload();
            auditLog(Auth::id(), 'admin_settings_import', "Imported {$imported} settings");
            flash('success', "นำเข้า {$imported} รายการเรียบร้อย");
        } else {
            flash('error', 'JSON ไม่ถูกต้อง กรุณาตรวจสอบรูปแบบข้อมูล');
        }
        redirect('admin/settings?tab=tools');
    }

    // === Upload image ===
    if ($action === 'upload_image') {
        $settingKey = preg_replace('/[^a-z0-9_]/', '', $_POST['setting_key'] ?? '');
        $tab = $_POST['tab'] ?? 'general';

        if ($settingKey && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                    default => 'png'
                };
                $uploadDir = BASE_PATH . '/uploads/settings/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0750, true);
                }
                $filename = $settingKey . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $imageUrl = url('uploads/settings/' . $filename);
                    Settings::set($settingKey, $imageUrl);
                    Settings::reload();
                    auditLog(Auth::id(), 'admin_settings_upload', "Uploaded image for: {$settingKey}");
                    flash('success', 'อัพโหลดรูปภาพเรียบร้อย');
                } else {
                    flash('error', 'ไม่สามารถอัพโหลดไฟล์ได้');
                }
            } else {
                flash('error', 'ไฟล์ต้องเป็นรูปภาพ (JPG, PNG, GIF, WebP, SVG) ขนาดไม่เกิน 5MB');
            }
        } else {
            flash('error', 'กรุณาเลือกไฟล์รูปภาพ');
        }
        redirect('admin/settings?tab=' . urlencode($tab));
    }

    // === Generate API key ===
    if ($action === 'generate_key') {
        $settingKey = preg_replace('/[^a-z0-9_]/', '', $_POST['setting_key'] ?? '');
        $tab = $_POST['tab'] ?? 'plugin';
        if ($settingKey) {
            $newSecret = bin2hex(random_bytes(32));
            Settings::set($settingKey, $newSecret);
            Settings::reload();
            auditLog(Auth::id(), 'admin_settings_genkey', "Generated new key for: {$settingKey}");

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                jsonResponse(['success' => true, 'message' => 'สร้าง Key ใหม่เรียบร้อย', 'value' => $newSecret]);
            }
            flash('success', 'สร้าง Key ใหม่เรียบร้อย');
        }
        redirect('admin/settings?tab=' . urlencode($tab));
    }
}

// Load all settings grouped
$allSettings = $db->fetchAll("SELECT * FROM settings ORDER BY category, setting_key");
$grouped = [];
$totalCount = 0;
foreach ($allSettings as $s) {
    $grouped[$s['category']][] = $s;
    $totalCount++;
}

$activeTab = $_GET['tab'] ?? 'general';

$tabs = [
    'general'  => ['icon' => 'fa-globe',       'label' => 'ทั่วไป',       'desc' => 'ชื่อเว็บ โลโก้ คำอธิบาย'],
    'theme'    => ['icon' => 'fa-palette',      'label' => 'ธีม/สี',       'desc' => 'สี ฟอนต์ พื้นหลัง'],
    'payment'  => ['icon' => 'fa-credit-card',  'label' => 'การเงิน',      'desc' => 'ค่าเงิน ค่าธรรมเนียม'],
    'features' => ['icon' => 'fa-toggle-on',    'label' => 'ฟีเจอร์',      'desc' => 'เปิด/ปิดระบบต่างๆ'],
    'server'   => ['icon' => 'fa-server',       'label' => 'เซิร์ฟเวอร์',  'desc' => 'IP เวอร์ชัน พอร์ต'],
    'social'   => ['icon' => 'fa-share-alt',    'label' => 'โซเชียล',      'desc' => 'Discord Facebook LINE'],
    'notify'   => ['icon' => 'fa-bell',         'label' => 'แจ้งเตือน',    'desc' => 'Webhook การแจ้งเตือน'],
    'system'   => ['icon' => 'fa-wrench',       'label' => 'ระบบ',         'desc' => 'ปิดปรับปรุง ความปลอดภัย'],
    'auth'     => ['icon' => 'fa-key',          'label' => 'ล็อกอิน',      'desc' => 'ตาราง Hash โหมด'],
    'plugin'   => ['icon' => 'fa-plug',         'label' => 'Plugin API',   'desc' => 'HMAC Secret Delivery'],
    'tools'    => ['icon' => 'fa-tools',        'label' => 'เครื่องมือ',    'desc' => 'นำเข้า ส่งออก เพิ่มค่า'],
];

// Enum options for select fields
$enumOptions = [
    'auth_hash'      => ['SHA256' => 'SHA256', 'BCRYPT' => 'BCRYPT', 'ARGON2' => 'ARGON2', 'PBKDF2' => 'PBKDF2'],
    'auth_mode'      => ['plugin' => 'Plugin (AuthMe ฯลฯ)', 'standalone' => 'Standalone'],
    'topup_fee_type' => ['none' => 'ไม่มีค่าธรรมเนียม', 'percent' => 'เปอร์เซ็นต์ (%)', 'fixed' => 'คงที่ (บาท)'],
    'font_family'    => [
        "'Prompt', sans-serif"             => 'Prompt',
        "'Plus Jakarta Sans', sans-serif"  => 'Plus Jakarta Sans',
        "'Noto Sans Thai', sans-serif"     => 'Noto Sans Thai',
        "'Inter', sans-serif"              => 'Inter',
        "'Sarabun', sans-serif"            => 'Sarabun',
    ],
];

// Sensitive fields (mask display)
$sensitiveKeys = ['tw_proxy_key', 'plugin_api_secret', 'discord_webhook'];

// Generatable keys (show generate button)
$generatableKeys = ['plugin_api_secret', 'tw_proxy_key'];

include BASE_PATH . '/layout/admin_header.php';
?>

<style>
/* ═══ Tab Navigation ═══ */
.settings-tab {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px; font-size: 0.85rem;
    transition: all 0.2s; cursor: pointer; text-decoration: none;
    color: var(--color-text); opacity: 0.55;
    border: 1px solid transparent;
}
.settings-tab:hover { opacity: 0.85; background: rgba(56,189,248,0.05); }
.settings-tab.active {
    opacity: 1; font-weight: 600; background: var(--color-primary); color: #fff;
    box-shadow: 0 2px 10px rgba(56,189,248,0.25);
}
.settings-tab .tab-count {
    margin-left: auto; font-size: 0.65rem; font-weight: 700;
    background: rgba(255,255,255,0.15); padding: 2px 7px; border-radius: 99px;
}
.settings-tab.active .tab-count { background: rgba(255,255,255,0.25); }

/* ═══ Setting Row ═══ */
.setting-row {
    padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
    display: flex; flex-direction: column; gap: 6px;
    transition: opacity 0.2s;
}
.setting-row:last-child { border-bottom: none; }
.setting-row.hidden { display: none; }
.setting-label { font-size: 0.875rem; font-weight: 600; }
.setting-key { font-size: 0.7rem; opacity: 0.35; font-family: monospace; }
.setting-desc-hint { font-size: 0.75rem; opacity: 0.45; }

/* ═══ Form Inputs ═══ */
.s-input {
    width: 100%; padding: 10px 14px; border-radius: 10px; font-size: 0.875rem;
    background: rgba(5,15,35,0.6); color: var(--color-text);
    border: 1px solid var(--color-border); transition: all 0.2s;
}
.s-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(56,189,248,0.12); outline: none; }
.s-input::placeholder { color: var(--color-text); opacity: 0.3; }
.s-input.is-changed { border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245,158,11,0.15); }

.s-select {
    width: 100%; padding: 10px 14px; border-radius: 10px; font-size: 0.875rem;
    background: rgba(5,15,35,0.6); color: var(--color-text);
    border: 1px solid var(--color-border); cursor: pointer; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
.s-select:focus { border-color: var(--color-primary); outline: none; }
.s-select option { background: #0f1e3a; color: #e0f2fe; }

.s-textarea {
    width: 100%; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem;
    background: rgba(5,15,35,0.6); color: var(--color-text);
    border: 1px solid var(--color-border); font-family: monospace; resize: vertical; min-height: 80px;
}
.s-textarea:focus { border-color: var(--color-primary); outline: none; }

/* ═══ Toggle Switch ═══ */
.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; cursor: pointer; inset: 0; border-radius: 26px;
    background: rgba(255,255,255,0.1); transition: 0.3s;
}
.toggle-slider::before {
    content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%; transition: 0.3s;
}
.toggle-switch input:checked + .toggle-slider { background: #22c55e; }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

/* ═══ Color Field ═══ */
.color-field { display: flex; align-items: center; gap: 10px; }
.color-swatch {
    width: 44px; height: 44px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.1);
    cursor: pointer; flex-shrink: 0; overflow: hidden;
}
.color-swatch input[type="color"] { width: 60px; height: 60px; border: none; cursor: pointer; margin: -8px; }

/* ═══ Image Field ═══ */
.image-field { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.image-preview {
    width: 48px; height: 48px; border-radius: 10px; object-fit: cover; flex-shrink: 0;
    background: rgba(255,255,255,0.05); border: 1px solid var(--color-border);
}

/* ═══ Cards & Buttons ═══ */
.s-card {
    border-radius: 16px; padding: 24px;
    background: var(--gradient-card); border: 1px solid var(--color-border);
}

.btn-save {
    padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 0.9rem;
    background: var(--gradient-btn); color: #fff; border: none; cursor: pointer;
    transition: all 0.3s; box-shadow: 0 4px 15px rgba(14,165,233,0.3);
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-save:hover { box-shadow: 0 6px 25px rgba(14,165,233,0.5); transform: translateY(-1px); }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.btn-save .spinner { display: none; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
.btn-save.loading .spinner { display: inline-block; }
.btn-save.loading .btn-text { display: none; }

.btn-danger {
    padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
    background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2);
    cursor: pointer; transition: all 0.2s;
}
.btn-danger:hover { background: rgba(239,68,68,0.25); }

.btn-sm {
    padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
    border: 1px solid var(--color-border); cursor: pointer; transition: all 0.2s;
    background: rgba(255,255,255,0.03); color: var(--color-text);
}
.btn-sm:hover { background: rgba(255,255,255,0.08); }
.btn-sm.btn-primary { background: rgba(56,189,248,0.1); color: var(--color-primary); border-color: rgba(56,189,248,0.2); }
.btn-sm.btn-primary:hover { background: rgba(56,189,248,0.2); }
.btn-sm.btn-warning { background: rgba(245,158,11,0.1); color: #f59e0b; border-color: rgba(245,158,11,0.2); }
.btn-sm.btn-warning:hover { background: rgba(245,158,11,0.2); }

/* ═══ Search ═══ */
.search-box { position: relative; margin-bottom: 16px; }
.search-box input {
    width: 100%; padding: 10px 14px 10px 38px; border-radius: 10px; font-size: 0.85rem;
    background: rgba(5,15,35,0.4); color: var(--color-text); border: 1px solid var(--color-border);
}
.search-box input:focus { border-color: var(--color-primary); outline: none; }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); opacity: 0.3; font-size: 0.85rem; }

/* ═══ Tool Card ═══ */
.tool-card {
    border-radius: 14px; padding: 20px;
    background: rgba(255,255,255,0.02); border: 1px solid var(--color-border);
    transition: border-color 0.2s;
}
.tool-card:hover { border-color: rgba(56,189,248,0.2); }

/* ═══ Changed Badge ═══ */
.changes-badge {
    display: none; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700;
    background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2);
    animation: pulse-badge 2s infinite;
}
.changes-badge.visible { display: inline-flex; align-items: center; gap: 4px; }

/* ═══ Animations ═══ */
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse-badge { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeIn 0.3s ease; }

/* ═══ Responsive ═══ */
@media (max-width: 1023px) {
    .settings-sidebar { display: flex; flex-direction: row; overflow-x: auto; gap: 4px !important; padding: 4px !important; }
    .settings-sidebar .settings-tab { white-space: nowrap; padding: 8px 12px; min-width: max-content; }
    .settings-sidebar .tab-desc { display: none; }
    .settings-sidebar .settings-tab .flex-1 { min-width: auto; }
}
</style>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-cog mr-2" style="color: var(--color-primary);"></i> ตั้งค่าระบบ</h1>
            <p class="text-sm opacity-50 mt-1">ทั้งหมด <?= $totalCount ?> รายการ</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="changes-badge" id="changes-badge">
                <i class="fas fa-circle text-xs"></i> <span id="changes-count">0</span> รายการเปลี่ยน
            </span>
            <form method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="export_settings">
                <button type="submit" class="btn-sm btn-primary">
                    <i class="fas fa-download mr-1"></i> Export JSON
                </button>
            </form>
            <a href="<?= url('admin') ?>" class="btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> แดชบอร์ด
            </a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Tabs -->
        <div class="lg:w-60 flex-shrink-0">
            <div class="s-card settings-sidebar flex flex-col gap-1 p-2 lg:sticky lg:top-20">
                <?php foreach ($tabs as $tabKey => $tab):
                    $count = count($grouped[$tabKey] ?? []);
                ?>
                <a href="?tab=<?= $tabKey ?>" class="settings-tab <?= $activeTab === $tabKey ? 'active' : '' ?>">
                    <i class="fas <?= $tab['icon'] ?> w-5 text-center text-sm"></i>
                    <div class="flex-1 min-w-0">
                        <div><?= $tab['label'] ?></div>
                        <div class="tab-desc text-xs opacity-50 font-normal" style="line-height: 1.2; margin-top: 1px;"><?= $tab['desc'] ?></div>
                    </div>
                    <?php if ($count > 0): ?>
                        <span class="tab-count"><?= $count ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">

            <?php if ($activeTab === 'tools'): ?>
            <!-- ═══════════════════════════════════════ -->
            <!-- ═══ Tools Tab ═══ -->
            <!-- ═══════════════════════════════════════ -->
            <div class="space-y-6 fade-in">

                <!-- Add new setting -->
                <div class="s-card">
                    <h3 class="font-bold mb-4"><i class="fas fa-plus-circle mr-2" style="color: var(--color-primary);"></i> เพิ่มการตั้งค่าใหม่</h3>
                    <form method="POST" id="add-setting-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_setting">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 opacity-60">Key <span class="text-red-400">*</span></label>
                                <input type="text" name="new_key" required placeholder="my_setting_key" class="s-input" pattern="[a-z0-9_]+" title="ใช้ a-z, 0-9 และ _ เท่านั้น">
                                <p class="text-xs opacity-30 mt-1">ใช้ตัวอักษรพิมพ์เล็ก ตัวเลข และ _ เท่านั้น</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 opacity-60">Value</label>
                                <input type="text" name="new_value" class="s-input" placeholder="ค่าเริ่มต้น">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 opacity-60">หมวด</label>
                                <select name="new_category" class="s-select">
                                    <?php foreach ($tabs as $k => $t): if ($k === 'tools') continue; ?>
                                        <option value="<?= $k ?>"><?= $t['label'] ?> (<?= $k ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 opacity-60">ประเภท</label>
                                <select name="new_type" class="s-select">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="boolean">Boolean (เปิด/ปิด)</option>
                                    <option value="color">Color</option>
                                    <option value="image">Image URL</option>
                                    <option value="json">JSON</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-semibold mb-1.5 opacity-60">คำอธิบาย</label>
                            <input type="text" name="new_description" class="s-input" placeholder="คำอธิบายสั้นๆ สำหรับแสดงในหน้าตั้งค่า">
                        </div>
                        <button type="submit" class="btn-save text-sm" style="padding: 10px 24px;">
                            <i class="fas fa-plus mr-1"></i> <span class="btn-text">เพิ่มการตั้งค่า</span>
                        </button>
                    </form>
                </div>

                <!-- Import -->
                <div class="s-card">
                    <h3 class="font-bold mb-4"><i class="fas fa-file-import mr-2" style="color: #f59e0b;"></i> นำเข้าการตั้งค่า (JSON)</h3>
                    <form method="POST" id="import-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="import_settings">
                        <textarea name="import_json" rows="6" class="s-textarea mb-3" id="import-json-field" placeholder='[{"setting_key":"...","setting_value":"...","category":"...","setting_type":"...","description":"..."}]'></textarea>
                        <div class="flex items-center gap-3 mb-4">
                            <button type="button" class="btn-sm" onclick="validateImportJson()">
                                <i class="fas fa-check-circle mr-1"></i> ตรวจสอบ JSON
                            </button>
                            <span id="json-validation-result" class="text-xs"></span>
                        </div>
                        <button type="submit" class="btn-save text-sm" style="padding: 10px 24px; background: linear-gradient(135deg, #f59e0b, #d97706);" id="import-btn">
                            <i class="fas fa-file-import mr-1"></i> <span class="btn-text">นำเข้า</span>
                            <span class="spinner"></span>
                        </button>
                    </form>
                </div>

                <!-- All settings overview -->
                <div class="s-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold"><i class="fas fa-list mr-2" style="color: var(--color-secondary);"></i> ภาพรวมทั้งหมด (<?= $totalCount ?>)</h3>
                        <div class="search-box" style="margin-bottom: 0; max-width: 250px;">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="ค้นหา key..." oninput="filterOverviewTable(this.value)">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs" id="overview-table">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-2 px-2 font-semibold">Key</th>
                                    <th class="text-left py-2 px-2 font-semibold">หมวด</th>
                                    <th class="text-left py-2 px-2 font-semibold">ประเภท</th>
                                    <th class="text-left py-2 px-2 font-semibold">ค่า</th>
                                    <th class="text-right py-2 px-2 font-semibold">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allSettings as $s): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition overview-row" data-key="<?= e(strtolower($s['setting_key'])) ?>">
                                    <td class="py-1.5 px-2 font-mono"><?= e($s['setting_key']) ?></td>
                                    <td class="py-1.5 px-2">
                                        <a href="?tab=<?= e($s['category']) ?>" class="px-1.5 py-0.5 rounded text-xs hover:opacity-80 transition" style="background: rgba(56,189,248,0.1); color: var(--color-primary);">
                                            <?= e($s['category']) ?>
                                        </a>
                                    </td>
                                    <td class="py-1.5 px-2 opacity-50"><?= e($s['setting_type']) ?></td>
                                    <td class="py-1.5 px-2 opacity-60 truncate max-w-[200px]"><?= in_array($s['setting_key'], $sensitiveKeys) && $s['setting_value'] ? '••••••••' : e(mb_substr($s['setting_value'], 0, 50)) ?></td>
                                    <td class="py-1.5 px-2 text-right">
                                        <a href="?tab=<?= e($s['category']) ?>" class="text-xs opacity-40 hover:opacity-80 transition" title="แก้ไข">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ═══════════════════════════════════════ -->
            <!-- ═══ Settings Form ═══ -->
            <!-- ═══════════════════════════════════════ -->
            <form method="POST" id="settings-form" class="fade-in">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">

                <div class="s-card">
                    <!-- Tab header -->
                    <div class="flex items-center justify-between mb-5 pb-4 border-b border-white/5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--gradient-btn);">
                                <i class="fas <?= $tabs[$activeTab]['icon'] ?? 'fa-cog' ?> text-white"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-lg"><?= $tabs[$activeTab]['label'] ?? 'ตั้งค่า' ?></h2>
                                <p class="text-xs opacity-40"><?= $tabs[$activeTab]['desc'] ?? '' ?></p>
                            </div>
                        </div>
                        <span class="text-xs opacity-30"><?= count($grouped[$activeTab] ?? []) ?> รายการ</span>
                    </div>

                    <!-- Search (show if > 3 items) -->
                    <?php if (count($grouped[$activeTab] ?? []) > 3): ?>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="setting-search" placeholder="ค้นหาการตั้งค่า..." oninput="filterSettings(this.value)">
                    </div>
                    <?php endif; ?>

                    <?php if (isset($grouped[$activeTab]) && !empty($grouped[$activeTab])): ?>
                    <div id="settings-list">
                        <?php foreach ($grouped[$activeTab] as $setting):
                            $key = $setting['setting_key'];
                            $val = $setting['setting_value'];
                            $type = $setting['setting_type'];
                            $desc = $setting['description'] ?: $key;
                            $isSensitive = in_array($key, $sensitiveKeys);
                            $isGeneratable = in_array($key, $generatableKeys);
                            $isEnum = isset($enumOptions[$key]);
                        ?>
                        <div class="setting-row" data-search="<?= e(strtolower($key . ' ' . $desc)) ?>" data-key="<?= e($key) ?>">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="setting-label"><?= e($desc) ?></div>
                                    <div class="setting-key"><?= e($key) ?></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($isGeneratable): ?>
                                    <button type="button" class="btn-sm btn-warning" onclick="generateKey('<?= e($key) ?>')" title="สร้าง Key ใหม่">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($isSensitive): ?>
                                    <button type="button" class="btn-sm" onclick="copyFieldValue('<?= e($key) ?>')" title="คัดลอก">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn-danger" onclick="deleteSetting('<?= e($key) ?>', '<?= e($activeTab) ?>')" title="ลบ">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <?php if ($isEnum): ?>
                                <!-- Enum/Select -->
                                <select name="settings[<?= e($key) ?>]" class="s-select" data-original="<?= e($val) ?>">
                                    <?php foreach ($enumOptions[$key] as $optVal => $optLabel): ?>
                                        <option value="<?= e($optVal) ?>" <?= $val === $optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($type === 'boolean'): ?>
                                <!-- Toggle -->
                                <div class="flex items-center gap-3">
                                    <input type="hidden" name="settings[<?= e($key) ?>]" value="0" data-toggle-hidden="<?= e($key) ?>">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="settings[<?= e($key) ?>]" value="1" <?= $val === '1' ? 'checked' : '' ?> data-original="<?= e($val) ?>">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="text-sm font-medium <?= $val === '1' ? 'text-green-400' : 'opacity-40' ?>" data-toggle-label>
                                        <?= $val === '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                                    </span>
                                </div>

                            <?php elseif ($type === 'color'): ?>
                                <!-- Color -->
                                <div class="color-field">
                                    <div class="color-swatch" style="background: <?= e($val) ?>;">
                                        <input type="color" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', $val) ? $val : '#000000') ?>"
                                               onchange="syncColorField(this)">
                                    </div>
                                    <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($val) ?>" class="s-input flex-1" data-original="<?= e($val) ?>"
                                           oninput="syncColorSwatch(this)" placeholder="#000000">
                                </div>

                            <?php elseif ($type === 'number'): ?>
                                <!-- Number -->
                                <input type="number" name="settings[<?= e($key) ?>]" value="<?= e($val) ?>" class="s-input" step="any" data-original="<?= e($val) ?>">

                            <?php elseif ($type === 'image'): ?>
                                <!-- Image with upload -->
                                <div class="image-field">
                                    <?php if ($val): ?>
                                        <img src="<?= e($val) ?>" class="image-preview" alt="" onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <div class="image-preview flex items-center justify-center">
                                            <i class="fas fa-image opacity-20"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0 space-y-2">
                                        <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($val) ?>" class="s-input" placeholder="URL รูปภาพ" data-original="<?= e($val) ?>">
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="btn-sm btn-primary" onclick="document.getElementById('upload-<?= e($key) ?>').click()">
                                                <i class="fas fa-upload mr-1"></i> อัพโหลด
                                            </button>
                                            <?php if ($val): ?>
                                            <button type="button" class="btn-sm" onclick="window.open('<?= e($val) ?>', '_blank')" title="ดูรูปเต็ม">
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden upload form -->
                                <form method="POST" enctype="multipart/form-data" id="upload-form-<?= e($key) ?>" style="display:none;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="upload_image">
                                    <input type="hidden" name="setting_key" value="<?= e($key) ?>">
                                    <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                                    <input type="file" id="upload-<?= e($key) ?>" accept="image/*" onchange="this.closest('form').submit()">
                                </form>

                            <?php elseif ($type === 'json' || strlen($val) > 120): ?>
                                <!-- Textarea -->
                                <textarea name="settings[<?= e($key) ?>]" rows="4" class="s-textarea" data-original="<?= e($val) ?>"><?= e($val) ?></textarea>
                                <?php if ($type === 'json'): ?>
                                <div class="flex gap-2 mt-1">
                                    <button type="button" class="btn-sm" onclick="formatJson(this)" title="จัดรูปแบบ JSON">
                                        <i class="fas fa-code mr-1"></i> Format JSON
                                    </button>
                                </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Text -->
                                <div class="relative">
                                    <input type="<?= $isSensitive ? 'password' : 'text' ?>" name="settings[<?= e($key) ?>]"
                                           value="<?= e($val) ?>" class="s-input <?= $isSensitive ? 'pr-10' : '' ?>"
                                           id="field-<?= e($key) ?>" data-original="<?= e($val) ?>">
                                    <?php if ($isSensitive): ?>
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-40 hover:opacity-80 transition"
                                                onclick="toggleFieldVisibility('<?= e($key) ?>')">
                                            <i class="fas fa-eye" id="eye-<?= e($key) ?>"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- No results message (hidden by default) -->
                    <div id="no-results" style="display:none;" class="text-center py-8">
                        <i class="fas fa-search text-3xl opacity-20 mb-3"></i>
                        <p class="opacity-40">ไม่พบการตั้งค่าที่ค้นหา</p>
                    </div>

                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-3xl opacity-20 mb-3"></i>
                            <p class="opacity-40">ไม่มีการตั้งค่าในหมวดนี้</p>
                            <a href="?tab=tools" class="text-xs mt-2 inline-block" style="color: var(--color-primary);">
                                <i class="fas fa-plus mr-1"></i> เพิ่มการตั้งค่าใหม่
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($grouped[$activeTab])): ?>
                    <!-- Save bar -->
                    <div class="mt-6 pt-5 border-t border-white/5 flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn-save" id="save-btn">
                                <span class="btn-text"><i class="fas fa-save mr-2"></i> บันทึกการตั้งค่า</span>
                                <span class="spinner"></span>
                            </button>
                            <span id="save-status" class="text-xs opacity-0 transition-opacity duration-300"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="reset" class="btn-sm" id="reset-btn">
                                <i class="fas fa-undo mr-1"></i> รีเซ็ต
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </form>

            <?php endif; ?>

            <?php if ($activeTab === 'theme'): ?>
            <!-- ═══ Theme Live Preview ═══ -->
            <div class="s-card mt-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold"><i class="fas fa-eye mr-2" style="color: var(--color-accent);"></i> ตัวอย่างธีม (Live)</h3>
                    <span class="text-xs opacity-30">อัพเดตอัตโนมัติเมื่อเปลี่ยนสี</span>
                </div>
                <div id="theme-preview" class="rounded-xl p-5 border border-white/10 transition-all duration-300" style="background: var(--color-bg);">
                    <!-- Navbar preview -->
                    <div class="flex items-center gap-3 mb-3 px-3 py-2 rounded-lg" id="preview-navbar" style="background: rgba(8,20,45,0.9); border-bottom: 1px solid var(--color-border);">
                        <i class="fas fa-cube" id="preview-icon" style="color: var(--color-primary);"></i>
                        <span class="font-bold text-sm" id="preview-sitename" style="color: var(--color-primary);"><?= e($siteName ?? 'MCStore') ?></span>
                        <span class="ml-auto text-xs opacity-40">navbar</span>
                    </div>
                    <!-- Card preview -->
                    <div class="rounded-xl p-4 mb-3" id="preview-card" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                        <h4 class="font-bold text-sm mb-1" id="preview-title" style="color: var(--color-text);">ตัวอย่าง Card</h4>
                        <p class="text-xs" id="preview-desc" style="color: var(--color-text); opacity: 0.6;">ข้อความตัวอย่างสำหรับแสดงผลธีม</p>
                        <div class="flex gap-2 mt-3">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold" id="preview-btn-primary" style="background: var(--gradient-btn); color: #fff;">Primary</span>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold" id="preview-btn-accent" style="background: var(--color-accent); color: #000;">Accent</span>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold" id="preview-btn-secondary" style="background: var(--color-secondary); color: #fff;">Secondary</span>
                        </div>
                    </div>
                    <div class="text-xs opacity-30 text-center">ตัวอย่างจะอัพเดตแบบ real-time | บันทึกเพื่อใช้งานจริง</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ Delete Setting Form (hidden) ═══ -->
<form method="POST" id="delete-form" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_setting">
    <input type="hidden" name="delete_key" id="delete-key">
    <input type="hidden" name="tab" id="delete-tab">
</form>

<!-- ═══ Generate Key Form (hidden) ═══ -->
<form method="POST" id="generate-key-form" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="generate_key">
    <input type="hidden" name="setting_key" id="generate-key-field">
    <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
</form>

<script>
// ═══ Search filter ═══
function filterSettings(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#settings-list .setting-row').forEach(row => {
        const text = row.dataset.search || '';
        const show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const noResults = document.getElementById('no-results');
    if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
}

// ═══ Overview table filter ═══
function filterOverviewTable(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.overview-row').forEach(row => {
        row.style.display = (!q || row.dataset.key.includes(q)) ? '' : 'none';
    });
}

// ═══ Toggle label update ═══
document.querySelectorAll('.toggle-switch input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', function() {
        const label = this.closest('.setting-row').querySelector('[data-toggle-label]');
        if (label) {
            label.textContent = this.checked ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            label.className = this.checked ? 'text-sm font-medium text-green-400' : 'text-sm font-medium opacity-40';
        }
    });
});

// ═══ Color field sync ═══
function syncColorField(picker) {
    const field = picker.closest('.color-field');
    const textInput = field.querySelector('.s-input');
    textInput.value = picker.value;
    picker.closest('.color-swatch').style.background = picker.value;
    textInput.dispatchEvent(new Event('input', { bubbles: true }));
    updateThemePreview();
}

function syncColorSwatch(input) {
    const swatch = input.closest('.color-field').querySelector('.color-swatch');
    swatch.style.background = input.value;
    if (/^#[0-9a-f]{6}$/i.test(input.value)) {
        swatch.querySelector('input[type="color"]').value = input.value;
    }
    updateThemePreview();
}

// ═══ Toggle field visibility ═══
function toggleFieldVisibility(key) {
    const field = document.getElementById('field-' + key);
    const eye = document.getElementById('eye-' + key);
    if (field.type === 'password') {
        field.type = 'text';
        eye.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        eye.className = 'fas fa-eye';
    }
}

// ═══ Copy field value ═══
function copyFieldValue(key) {
    const field = document.getElementById('field-' + key);
    if (field) {
        const val = field.value;
        navigator.clipboard.writeText(val).then(() => {
            Swal.fire({ icon: 'success', title: 'คัดลอกแล้ว!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
        }).catch(() => {
            field.type = 'text';
            field.select();
            document.execCommand('copy');
            field.type = 'password';
            Swal.fire({ icon: 'success', title: 'คัดลอกแล้ว!', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
        });
    }
}

// ═══ Delete setting with SweetAlert2 ═══
function deleteSetting(key, tab) {
    Swal.fire({
        title: 'ลบการตั้งค่า?',
        html: '<span style="font-family:monospace; font-size:0.85rem; opacity:0.7;">' + key + '</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-key').value = key;
            document.getElementById('delete-tab').value = tab;
            document.getElementById('delete-form').submit();
        }
    });
}

// ═══ Generate API key ═══
function generateKey(key) {
    Swal.fire({
        title: 'สร้าง Key ใหม่?',
        text: 'Key เดิมจะถูกแทนที่ด้วย Key ใหม่ที่สุ่มขึ้นมา',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-sync-alt mr-1"></i> สร้างใหม่',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('generate-key-field').value = key;
            document.getElementById('generate-key-form').submit();
        }
    });
}

// ═══ Format JSON ═══
function formatJson(btn) {
    const textarea = btn.closest('.setting-row').querySelector('textarea');
    if (textarea) {
        try {
            const parsed = JSON.parse(textarea.value);
            textarea.value = JSON.stringify(parsed, null, 2);
            Swal.fire({ icon: 'success', title: 'จัดรูปแบบแล้ว', timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'JSON ไม่ถูกต้อง', text: e.message, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        }
    }
}

// ═══ Validate import JSON ═══
function validateImportJson() {
    const field = document.getElementById('import-json-field');
    const result = document.getElementById('json-validation-result');
    try {
        const data = JSON.parse(field.value);
        if (Array.isArray(data)) {
            const valid = data.filter(i => i.setting_key).length;
            result.innerHTML = '<i class="fas fa-check-circle text-green-400 mr-1"></i><span class="text-green-400">' + valid + ' รายการที่ถูกต้อง</span>';
        } else {
            result.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-400 mr-1"></i><span class="text-yellow-400">ต้องเป็น Array ของ objects</span>';
        }
    } catch (e) {
        result.innerHTML = '<i class="fas fa-times-circle text-red-400 mr-1"></i><span class="text-red-400">JSON ไม่ถูกต้อง: ' + e.message + '</span>';
    }
}

// ═══ Import confirmation ═══
const importForm = document.getElementById('import-form');
if (importForm) {
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const field = document.getElementById('import-json-field');
        if (!field.value.trim()) {
            Swal.fire({ icon: 'warning', title: 'กรุณาวาง JSON ก่อน' });
            return;
        }
        try {
            const data = JSON.parse(field.value);
            if (!Array.isArray(data)) throw new Error('ต้องเป็น Array');
            Swal.fire({
                title: 'นำเข้าการตั้งค่า?',
                html: 'พบ <b>' + data.length + '</b> รายการ<br><span class="text-xs opacity-60">ค่าที่มี key ซ้ำจะถูกเขียนทับ</span>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-file-import mr-1"></i> นำเข้า',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    importForm.submit();
                }
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'JSON ไม่ถูกต้อง', text: e.message });
        }
    });
}

// ═══ AJAX Save (with fallback to normal submit) ═══
const form = document.getElementById('settings-form');
if (form) {
    let changeCount = 0;

    // Track changes
    function updateChangeCount() {
        changeCount = 0;
        form.querySelectorAll('[data-original]').forEach(el => {
            let current;
            if (el.type === 'checkbox') {
                current = el.checked ? '1' : '0';
            } else {
                current = el.value;
            }
            if (current !== el.dataset.original) {
                changeCount++;
                el.classList.add('is-changed');
            } else {
                el.classList.remove('is-changed');
            }
        });
        const badge = document.getElementById('changes-badge');
        const countEl = document.getElementById('changes-count');
        if (badge && countEl) {
            countEl.textContent = changeCount;
            badge.classList.toggle('visible', changeCount > 0);
        }
    }

    form.addEventListener('input', updateChangeCount);
    form.addEventListener('change', updateChangeCount);

    // Reset handler
    const resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            form.querySelectorAll('[data-original]').forEach(el => {
                if (el.type === 'checkbox') {
                    el.checked = el.dataset.original === '1';
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    el.value = el.dataset.original;
                }
                el.classList.remove('is-changed');
            });
            // Reset color swatches
            form.querySelectorAll('.color-field').forEach(cf => {
                const input = cf.querySelector('.s-input');
                const swatch = cf.querySelector('.color-swatch');
                if (input && swatch) {
                    swatch.style.background = input.value;
                    const picker = swatch.querySelector('input[type="color"]');
                    if (picker && /^#[0-9a-f]{6}$/i.test(input.value)) picker.value = input.value;
                }
            });
            changeCount = 0;
            const badge = document.getElementById('changes-badge');
            if (badge) badge.classList.remove('visible');
            Swal.fire({ icon: 'info', title: 'รีเซ็ตแล้ว', timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
        });
    }

    // AJAX submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const saveBtn = document.getElementById('save-btn');
        const saveStatus = document.getElementById('save-status');

        saveBtn.classList.add('loading');
        saveBtn.disabled = true;

        const formData = new FormData(form);
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.classList.remove('loading');
            saveBtn.disabled = false;

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย!', text: data.message, timer: 2000, showConfirmButton: false });
                // Update originals
                form.querySelectorAll('[data-original]').forEach(el => {
                    if (el.type === 'checkbox') {
                        el.dataset.original = el.checked ? '1' : '0';
                    } else {
                        el.dataset.original = el.value;
                    }
                    el.classList.remove('is-changed');
                });
                changeCount = 0;
                const badge = document.getElementById('changes-badge');
                if (badge) badge.classList.remove('visible');

                if (saveStatus) {
                    saveStatus.textContent = '✓ บันทึกแล้ว ' + new Date().toLocaleTimeString('th-TH');
                    saveStatus.style.opacity = '1';
                    saveStatus.style.color = '#22c55e';
                    setTimeout(() => { saveStatus.style.opacity = '0'; }, 5000);
                }
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message || 'ไม่สามารถบันทึกได้' });
            }
        })
        .catch(() => {
            // Fallback to normal form submit
            form.removeEventListener('submit', arguments.callee);
            form.submit();
        });
    });

    // Unsaved changes warning
    window.addEventListener('beforeunload', (e) => {
        if (changeCount > 0) { e.preventDefault(); e.returnValue = ''; }
    });
}

// ═══ Keyboard shortcut: Ctrl+S to save ═══
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const f = document.getElementById('settings-form');
        if (f) {
            f.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    }
});

// ═══ Live Theme Preview ═══
function updateThemePreview() {
    const preview = document.getElementById('theme-preview');
    if (!preview) return;

    const getVal = (key) => {
        const el = document.querySelector('[data-key="' + key + '"] .s-input, [data-key="' + key + '"] input[name="settings[' + key + ']"]');
        return el ? el.value : null;
    };

    const primary = getVal('primary_color');
    const secondary = getVal('secondary_color');
    const accent = getVal('accent_color');
    const bg = getVal('bg_color');
    const text = getVal('text_color');

    if (primary) {
        const icon = document.getElementById('preview-icon');
        const name = document.getElementById('preview-sitename');
        const btnPrimary = document.getElementById('preview-btn-primary');
        if (icon) icon.style.color = primary;
        if (name) name.style.color = primary;
        if (btnPrimary) btnPrimary.style.background = 'linear-gradient(135deg, ' + primary + ', ' + adjustColor(primary, -30) + ')';
    }
    if (secondary) {
        const btnSec = document.getElementById('preview-btn-secondary');
        if (btnSec) btnSec.style.background = secondary;
    }
    if (accent) {
        const btnAccent = document.getElementById('preview-btn-accent');
        if (btnAccent) btnAccent.style.background = accent;
    }
    if (bg) {
        preview.style.background = bg;
    }
    if (text) {
        const title = document.getElementById('preview-title');
        const desc = document.getElementById('preview-desc');
        if (title) title.style.color = text;
        if (desc) desc.style.color = text;
    }
}

function adjustColor(hex, amount) {
    hex = hex.replace('#', '');
    let r = Math.max(0, Math.min(255, parseInt(hex.substring(0, 2), 16) + amount));
    let g = Math.max(0, Math.min(255, parseInt(hex.substring(2, 4), 16) + amount));
    let b = Math.max(0, Math.min(255, parseInt(hex.substring(4, 6), 16) + amount));
    return '#' + [r, g, b].map(c => c.toString(16).padStart(2, '0')).join('');
}

// Listen for color changes on theme tab
document.querySelectorAll('.color-field .s-input').forEach(input => {
    input.addEventListener('input', updateThemePreview);
});
</script>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
