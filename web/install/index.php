<?php
/**
 * Standalone installer for deployments that contain only the web/ folder.
 * It restores a MySQL dump from web/data and writes web/.env.
 */
session_start();

define('INSTALL_ROOT', dirname(__DIR__));
define('INSTALL_ENV', INSTALL_ROOT . '/.env');
define('INSTALL_DATA', INSTALL_ROOT . '/data');

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function install_env_value($value) {
    $value = (string) $value;
    if (strpbrk($value, " \t#\"'") !== FALSE) {
        return '"' . addcslashes($value, "\\\"") . '"';
    }
    return $value;
}

function install_default_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) && trim((string) $_SERVER['HTTP_HOST']) !== ''
        ? trim((string) $_SERVER['HTTP_HOST']) : 'localhost';
    $path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    $path = preg_replace('#/install(?:/.*)?$#', '/', $path);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    return $scheme . '://' . $host . rtrim($path, '/') . '/';
}

function install_backups() {
    $files = glob(INSTALL_DATA . '/*.sql.gz');
    if (!is_array($files)) {
        return array();
    }
    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });
    return $files;
}

function install_page($title, $body) {
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<style>
:root { color-scheme: light; }
* { box-sizing: border-box; }
body { margin: 0; background: #f1f5f9; color: #0f172a; font: 15px/1.55 system-ui, -apple-system, Segoe UI, sans-serif; }
main { width: min(760px, calc(100% - 32px)); margin: 48px auto; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); padding: 28px; }
.eyebrow { margin: 0 0 8px; color: #4f46e5; font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
h1 { margin: 0; font-size: 28px; letter-spacing: -.03em; }
p.lead { margin: 8px 0 24px; color: #64748b; }
h2 { margin: 24px 0 10px; font-size: 17px; }
label { display: block; margin: 12px 0 6px; font-size: 13px; font-weight: 700; color: #334155; }
input, select { width: 100%; min-height: 44px; border: 1px solid #cbd5e1; border-radius: 9px; background: #fff; padding: 9px 11px; font: inherit; color: #0f172a; }
input:focus, select:focus { border-color: #6366f1; outline: 3px solid #e0e7ff; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.actions { display: flex; gap: 10px; align-items: center; margin-top: 24px; }
button, .button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; border: 0; border-radius: 9px; background: #4f46e5; color: #fff; padding: 0 18px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
button:hover, .button:hover { background: #4338ca; }
.button.secondary { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
.alert { margin: 0 0 18px; border: 1px solid #fecdd3; border-radius: 10px; background: #fff1f2; padding: 12px 14px; color: #9f1239; }
.alert.warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }
.muted { color: #64748b; font-size: 13px; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
@media (max-width: 560px) { .grid { grid-template-columns: 1fr; } main { margin: 20px auto; } .card { padding: 20px; } }
</style>
</head>
<body><main><section class="card"><?= $body ?></section></main></body>
</html>
    <?php
}

$installed = is_file(INSTALL_ENV);
$env_password = getenv('CMS_DB_PASSWORD');
$env_configured = is_string($env_password) && trim($env_password) !== '';

if ($installed) {
    install_page('Sudah terpasang', '<p class="eyebrow">Installer</p><h1>Aplikasi sudah terpasang</h1><p class="lead">File <span class="mono">web/.env</span> sudah ada.</p><p><a class="button" href="/login">Masuk ke aplikasi</a></p>');
    exit;
}
if ($env_configured) {
    install_page('Installer dinonaktifkan', '<p class="eyebrow">Installer</p><h1>Installer dinonaktifkan</h1><div class="alert warn">Konfigurasi database terdeteksi dari environment. Hapus variabel <span class="mono">CMS_DB_*</span> jika ingin memakai installer.</div>');
    exit;
}

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}

$backups = install_backups();
$errors = array();
$success = FALSE;
$values = array(
    'host' => isset($_POST['host']) ? trim((string) $_POST['host']) : 'localhost',
    'port' => isset($_POST['port']) ? trim((string) $_POST['port']) : '3306',
    'database' => isset($_POST['database']) ? trim((string) $_POST['database']) : 'cmsdb',
    'username' => isset($_POST['username']) ? trim((string) $_POST['username']) : 'cmsdb',
    'base_url' => isset($_POST['base_url']) ? trim((string) $_POST['base_url']) : install_default_base_url(),
    'backup' => isset($_POST['backup']) ? basename((string) $_POST['backup']) : ($backups ? basename($backups[0]) : ''),
);

if (isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST') {
    $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (!hash_equals($_SESSION['install_csrf'], $token)) {
        $errors[] = 'Token form tidak valid. Muat ulang halaman installer.';
    }
    if ($values['host'] === '' || strlen($values['host']) > 255) {
        $errors[] = 'Host database wajib diisi.';
    }
    if (!ctype_digit($values['port']) || (int) $values['port'] < 1 || (int) $values['port'] > 65535) {
        $errors[] = 'Port database tidak valid.';
    }
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $values['database']) || strlen($values['database']) > 64) {
        $errors[] = 'Nama database hanya boleh huruf, angka, dan garis bawah.';
    }
    if ($values['username'] === '' || strlen($values['username']) > 128) {
        $errors[] = 'Username database wajib diisi.';
    }
    if ($password === '') {
        $errors[] = 'Password database wajib diisi.';
    }
    $values['base_url'] = rtrim($values['base_url'], '/') . '/';
    if (strlen($values['base_url']) > 255
        || !filter_var($values['base_url'], FILTER_VALIDATE_URL)
        || !in_array(strtolower((string) parse_url($values['base_url'], PHP_URL_SCHEME)), array('http', 'https'), TRUE)) {
        $errors[] = 'Base URL aplikasi tidak valid. Gunakan URL http/https tanpa query string.';
    }
    $backup_path = '';
    foreach ($backups as $candidate) {
        if (basename($candidate) === $values['backup']) {
            $backup_path = $candidate;
            break;
        }
    }
    if ($backup_path === '') {
        $errors[] = 'File backup tidak ditemukan di web/data.';
    } elseif (!is_readable($backup_path)) {
        $errors[] = 'File backup tidak dapat dibaca oleh web server. Periksa permission file.';
    } elseif (strtolower(pathinfo($backup_path, PATHINFO_EXTENSION)) !== 'gz') {
        $errors[] = 'File backup harus berformat .sql.gz.';
    }
    if (!$errors) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = @new mysqli($values['host'], $values['username'], $password, '', (int) $values['port']);
        if ($mysqli->connect_errno) {
            $errors[] = 'Koneksi database gagal: ' . $mysqli->connect_error;
        } else {
            $db = '`' . str_replace('`', '``', $values['database']) . '`';
            $exists = $mysqli->query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=' . "'" . $mysqli->real_escape_string($values['database']) . "'");
            if ($exists === FALSE) {
                $errors[] = 'Tidak dapat memeriksa database: ' . $mysqli->error;
            } elseif ($exists->num_rows === 0 && !$mysqli->query('CREATE DATABASE ' . $db . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci')) {
                $errors[] = 'Database belum ada dan tidak dapat dibuat: ' . $mysqli->error;
            }
            if (!$errors && !$mysqli->select_db($values['database'])) {
                $errors[] = 'Tidak dapat memilih database: ' . $mysqli->error;
            }
            if (!$errors) {
                $tables = $mysqli->query('SHOW TABLES');
                if ($tables === FALSE) {
                    $errors[] = 'Tidak dapat membaca daftar tabel: ' . $mysqli->error;
                } elseif ($tables->num_rows > 0) {
                    $errors[] = 'Database tujuan tidak kosong. Gunakan database baru atau kosongkan terlebih dahulu.';
                }
            }
            if (!$errors) {
                $sql = gzdecode(file_get_contents($backup_path));
                if (is_string($sql)) {
                    $sql = preg_replace('/^\/\*M![^\n]*\n?/m', '', $sql);
                }
                if ($sql === FALSE || trim($sql) === '') {
                    $errors[] = 'File backup tidak valid atau kosong.';
                } elseif (stripos($sql, 'DELIMITER') !== FALSE) {
                    $errors[] = 'Dump memakai DELIMITER dan belum didukung installer. Gunakan mysqldump tanpa stored routine/trigger atau restore lewat mysql CLI.';
                } else {
                    $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
                    if (!$mysqli->multi_query($sql)) {
                        $errors[] = 'Import backup gagal: ' . $mysqli->error;
                    } else {
                        do {
                            if ($result = $mysqli->store_result()) {
                                $result->free();
                            }
                        } while ($mysqli->more_results() && $mysqli->next_result());
                        if ($mysqli->errno) {
                            $errors[] = 'Import backup gagal: ' . $mysqli->error;
                        }
                    }
                    $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
                }
            }
            if (!$errors) {
                $check = $mysqli->query("SHOW TABLES LIKE 'app_user'");
                if ($check === FALSE || $check->num_rows === 0) {
                    $errors[] = 'Backup tidak berisi tabel aplikasi (app_user).';
                }
            }
            $mysqli->close();
        }
    }
    if (!$errors) {
        $content = "CI_BASE_URL=" . install_env_value($values['base_url']) . "\n"
            . "CMS_DB_HOST=" . install_env_value($values['host']) . "\n"
            . "CMS_DB_PORT=" . (int) $values['port'] . "\n"
            . "CMS_DB_NAME=" . install_env_value($values['database']) . "\n"
            . "CMS_DB_USER=" . install_env_value($values['username']) . "\n"
            . "CMS_DB_PASSWORD=" . install_env_value($password) . "\n"
            . "TIME_ZONE=utc\n";
        $tmp = INSTALL_ENV . '.tmp';
        if (!is_writable(INSTALL_ROOT) || file_put_contents($tmp, $content, LOCK_EX) === FALSE) {
            $errors[] = 'Tidak dapat menulis web/.env. Pastikan folder web dapat ditulis.';
        } elseif (!rename($tmp, INSTALL_ENV)) {
            @unlink($tmp);
            $errors[] = 'Tidak dapat menyimpan web/.env.';
        } else {
            @chmod(INSTALL_ENV, 0600);
            unset($_SESSION['install_csrf']);
            $success = TRUE;
        }
    }
}

if ($success) {
    install_page('Instalasi selesai', '<p class="eyebrow">Installer</p><h1>Instalasi selesai</h1><p class="lead">Database berhasil dipulihkan dan file <span class="mono">web/.env</span> sudah dibuat.</p><p><a class="button" href="/login">Masuk ke aplikasi</a></p><p class="muted">Installer sekarang terkunci.</p>');
    exit;
}

$body = '<p class="eyebrow">Installer</p><h1>Instalasi CMSNivo</h1><p class="lead">Pulihkan database dari file backup di <span class="mono">web/data</span>, lalu simpan konfigurasi ke <span class="mono">web/.env</span>.</p>';
if (!$backups) {
    $body .= '<div class="alert">Tidak ada file <span class="mono">*.sql.gz</span> di <span class="mono">web/data</span>. Letakkan file backup di folder tersebut lalu muat ulang halaman ini.</div>';
}
if ($errors) {
    $body .= '<div class="alert"><strong>Instalasi gagal.</strong><ul>';
    foreach ($errors as $error) {
        $body .= '<li>' . h($error) . '</li>';
    }
    $body .= '</ul></div>';
}
$body .= '<form method="post" autocomplete="off">';
$body .= '<input type="hidden" name="csrf" value="' . h($_SESSION['install_csrf']) . '">';
$body .= '<h2>Database</h2>';
$body .= '<label for="host">Host</label><input id="host" name="host" required value="' . h($values['host']) . '">';
$body .= '<div class="grid"><div><label for="port">Port</label><input id="port" name="port" inputmode="numeric" required value="' . h($values['port']) . '"></div><div><label for="database">Nama database</label><input id="database" name="database" required value="' . h($values['database']) . '"></div></div>';
$body .= '<label for="username">User database</label><input id="username" name="username" required value="' . h($values['username']) . '">';
$body .= '<label for="password">Password database</label><input id="password" name="password" type="password" required autocomplete="new-password">';
$body .= '<h2>Aplikasi</h2>';
$body .= '<label for="base_url">Base URL aplikasi</label><input id="base_url" name="base_url" type="url" required value="' . h($values['base_url']) . '">';
$body .= '<p class="muted">Default mengikuti URL yang sedang dipakai. Akhiran <span class="mono">/</span> akan ditambahkan otomatis.</p>';
$body .= '<h2>File backup</h2>';
$body .= '<label for="backup">Backup database</label><select id="backup" name="backup" required>';
foreach ($backups as $backup) {
    $name = basename($backup);
    $body .= '<option value="' . h($name) . '"' . ($name === $values['backup'] ? ' selected' : '') . '>' . h($name) . ' — ' . h(date('Y-m-d H:i:s', filemtime($backup))) . '</option>';
}
$body .= '</select><p class="muted">Database tujuan harus kosong. Data lama dari backup akan dipulihkan.</p>';
$body .= '<div class="actions"><button type="submit">Pulihkan &amp; Pasang</button><a class="button secondary" href="/">Batal</a></div>';
$body .= '</form>';
install_page('Instalasi CMSNivo', $body);
