<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-click update runner.
 *
 * The server catalog returns a release with a ZIP package. This library
 * downloads it, verifies SHA-256, backs up the database and affected files,
 * overlays the package, applies SQL migrations, records the result, and
 * restores the backups when any step fails.
 */
class Update_store
{
    const STATUS_PENDING = 'pending';
    const STATUS_DOWNLOADING = 'downloading';
    const STATUS_BACKING_UP = 'backing_up';
    const STATUS_INSTALLING = 'installing';
    const STATUS_MIGRATING = 'migrating';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_ROLLED_BACK = 'rolled_back';

    private $db;
    private $config;
    private $cache_path;
    private $lock_path;
    private $maintenance_path;
    private $property_store;

    public function __construct($db = NULL)
    {
        if ($db === NULL) {
            $CI =& get_instance();
            if (!isset($CI->db)) {
                $CI->load->database();
            }
            $db = $CI->db;
        }
        $this->db = $db;
        $CI =& get_instance();
        $CI->config->load('version', TRUE, TRUE);
        $this->config = $CI->config;
        $this->cache_path = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'updates';
        $this->lock_path = $this->cache_path . DIRECTORY_SEPARATOR . 'update.lock';
        $this->maintenance_path = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'maintenance.flag';
        $CI->load->library('property_store', NULL, 'update_property_store');
        $this->property_store = $CI->update_property_store;
    }

    public function installed_version()
    {
        $version = $this->config->item('app_version', 'version');
        if (!is_string($version) || !preg_match('/\A\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?\z/', $version)) {
            return '0.0.0';
        }
        return $version;
    }

    public function channel()
    {
        $channel = $this->config->item('app_update_channel', 'version');
        return is_string($channel) && $channel !== '' ? $channel : 'stable';
    }

    /** Configured API endpoint, or the product default when the property is absent. */
    public function update_link()
    {
        $link = '';
        if ($this->property_store !== NULL && $this->property_store->schema_available()) {
            $property = $this->property_store->find('link_update');
            if (is_array($property) && isset($property['value'])) {
                $link = trim((string) $property['value']);
            }
        }
        if ($link === '') {
            $link = 'https://cms.ckamal.com/api/update/check';
        }
        return $link;
    }

    /** Ask the update server whether a newer release exists. */
    public function check()
    {
        $current = $this->installed_version();
        $link = $this->update_link();
        if (!$this->is_http_url($link)) {
            return array('ok' => FALSE, 'error' => 'invalid_link', 'current_version' => $current);
        }

        $separator = strpos($link, '?') === FALSE ? '?' : '&';
        $url = $link . $separator . http_build_query(array(
            'version' => $current,
            'php' => PHP_VERSION,
            'channel' => $this->channel(),
        ));

        $response = $this->http_get($url, 10);
        if ($response['error'] !== '' || $response['status'] < 200 || $response['status'] >= 300) {
            return array(
                'ok' => FALSE,
                'error' => 'request_failed',
                'detail' => $response['error'] !== '' ? $response['error'] : 'HTTP ' . $response['status'],
                'current_version' => $current,
            );
        }
        $payload = json_decode($response['body'], TRUE);
        if (!is_array($payload) || !isset($payload['status']) || $payload['status'] !== 'ok') {
            return array('ok' => FALSE, 'error' => 'invalid_response', 'current_version' => $current);
        }

        $latest = isset($payload['latest_version']) ? trim((string) $payload['latest_version']) : '';
        if (!$this->is_version($latest)) {
            return array('ok' => FALSE, 'error' => 'invalid_version', 'current_version' => $current);
        }
        $available = version_compare($latest, $current, '>');
        if (isset($payload['update_available'])) {
            $available = $available && (bool) $payload['update_available'];
        }
        $release = NULL;
        if ($available && isset($payload['release']) && is_array($payload['release'])) {
            $release = $this->normalize_release($payload['release'], $link);
            if ($release === NULL) {
                return array('ok' => FALSE, 'error' => 'invalid_release', 'current_version' => $current);
            }
        }

        return array(
            'ok' => TRUE,
            'error' => '',
            'current_version' => $current,
            'latest_version' => $latest,
            'target_version' => $release !== NULL ? $release['version'] : '',
            'update_available' => $available,
            'release' => $release,
            'compatible' => isset($payload['compatible']) ? (bool) $payload['compatible'] : TRUE,
            'checked_at' => gmdate('c'),
        );
    }

    /** Statuses that should block another run. Stale rows older than an hour are ignored. */
    public function active_update()
    {
        if (!$this->schema_available()) {
            return NULL;
        }
        $query = $this->db->select('update_id, version, status, started_on')
            ->from('sys_update')
            ->where_in('status', array(
                self::STATUS_PENDING, self::STATUS_DOWNLOADING, self::STATUS_BACKING_UP,
                self::STATUS_INSTALLING, self::STATUS_MIGRATING,
            ))
            ->order_by('update_id', 'DESC')
            ->limit(1)
            ->get();
        if ($query === FALSE || $query->num_rows() === 0) {
            return NULL;
        }
        $row = $query->row_array();
        if (!empty($row['started_on'])) {
            $started = strtotime((string) $row['started_on']);
            if ($started !== FALSE && $started < time() - 3600) {
                return NULL;
            }
        }
        return $row;
    }

    public function history_count()
    {
        if (!$this->schema_available()) {
            return 0;
        }
        $count = $this->db->from('sys_update')->count_all_results();
        return $count === FALSE ? 0 : (int) $count;
    }

    public function history($limit = 25, $offset = 0)
    {
        if (!$this->schema_available()) {
            return array();
        }
        $limit = min(100, max(1, (int) $limit));
        $offset = max(0, (int) $offset);
        $query = $this->db->select('update_id, version, previous_version, status, changelog,'
                . ' started_on, finished_on, created_by')
            ->from('sys_update')
            ->order_by('update_id', 'DESC')
            ->limit($limit, $offset)
            ->get();
        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    public function migrations_for($update_id)
    {
        if (!$this->schema_available() || (int) $update_id < 1) {
            return array();
        }
        $query = $this->db->select('migration_name, applied_on')
            ->from('sys_update_migration')
            ->where('update_id', (int) $update_id)
            ->order_by('update_migration_id', 'ASC')
            ->get();
        return $query === FALSE ? array() : $query->result_array();
    }

    public function schema_available()
    {
        return $this->db->table_exists('sys_update') && $this->db->table_exists('sys_update_migration');
    }

    /** Create the update tables on an existing installation without them. */
    public function bootstrap_schema()
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $statements = array(
                "CREATE TABLE IF NOT EXISTS `sys_update` ("
                . " `update_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,"
                . " `version` VARCHAR(32) NOT NULL,"
                . " `previous_version` VARCHAR(32) NOT NULL,"
                . " `status` VARCHAR(24) NOT NULL DEFAULT 'pending',"
                . " `package_url` VARCHAR(512) NOT NULL,"
                . " `checksum` VARCHAR(64) NOT NULL,"
                . " `changelog` TEXT NULL,"
                . " `log` LONGTEXT NULL,"
                . " `started_on` DATETIME NULL,"
                . " `finished_on` DATETIME NULL,"
                . " `created_by` INT UNSIGNED NOT NULL DEFAULT 0,"
                . " PRIMARY KEY (`update_id`),"
                . " KEY `status` (`status`),"
                . " KEY `version` (`version`)"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS `sys_update_migration` ("
                . " `update_migration_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,"
                . " `update_id` BIGINT UNSIGNED NOT NULL,"
                . " `migration_name` VARCHAR(191) NOT NULL,"
                . " `checksum` VARCHAR(64) NOT NULL,"
                . " `applied_on` DATETIME NOT NULL,"
                . " PRIMARY KEY (`update_migration_id`),"
                . " UNIQUE KEY `uk_sys_update_migration` (`update_id`, `migration_name`),"
                . " CONSTRAINT `fk_sys_update_migration_update_id` FOREIGN KEY (`update_id`)"
                . " REFERENCES `sys_update` (`update_id`) ON UPDATE CASCADE ON DELETE CASCADE"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            );
            foreach ($statements as $statement) {
                if ($this->db->query($statement) === FALSE) {
                    $error = $this->db->error();
                    return is_array($error) && isset($error['message']) ? $error['message'] : 'query failed';
                }
            }

            $constraint = $this->db->query(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS'
                . ' WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?'
                . ' AND CONSTRAINT_TYPE = \'FOREIGN KEY\' LIMIT 1',
                array($this->db->database, 'sys_update_migration', 'fk_sys_update_migration_update_id')
            );
            if ($constraint !== FALSE && $constraint->num_rows() === 0) {
                if ($this->db->query(
                    'ALTER TABLE `sys_update_migration` ADD CONSTRAINT `fk_sys_update_migration_update_id`'
                    . ' FOREIGN KEY (`update_id`) REFERENCES `sys_update` (`update_id`)'
                    . ' ON UPDATE CASCADE ON DELETE CASCADE'
                ) === FALSE) {
                    $error = $this->db->error();
                    return is_array($error) && isset($error['message']) ? $error['message'] : 'foreign key failed';
                }
            }

            if ($this->db->table_exists('sys_property')) {
                if ($this->db->query(
                    "INSERT INTO sys_property (property_code, property_name, description, value)"
                    . " SELECT 'link_update', 'Link Update', 'Alamat API pemeriksaan update aplikasi.',"
                    . " 'https://cms.ckamal.com/api/update/check' FROM DUAL"
                    . " WHERE NOT EXISTS (SELECT 1 FROM sys_property WHERE property_code='link_update')"
                ) === FALSE) {
                    $error = $this->db->error();
                    return is_array($error) && isset($error['message']) ? $error['message'] : 'property insert failed';
                }
            }
            return '';
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    /** Runtime capabilities required by the one-click runner. */
    public function runner_checks()
    {
        $mysqldump = trim((string) @shell_exec('command -v mysqldump 2>/dev/null'));
        $mysql = trim((string) @shell_exec('command -v mysql 2>/dev/null'));
        return array(
            'writable' => is_writable(APPPATH . 'config') && is_writable(FCPATH),
            'archive' => class_exists('PharData') || class_exists('ZipArchive'),
            'exec' => function_exists('exec'),
            'mysql_client' => $mysqldump !== '' && $mysql !== '',
        );
    }

    /**
     * Run one release.
     *
     * @return array{ok:bool,error:string,update_id:int,log:string}
     */
    public function run($release, $created_by = 0)
    {
        if (!$this->schema_available()) {
            return $this->result(FALSE, 'schema_unavailable', 0, '');
        }
        if (!is_array($release)) {
            return $this->result(FALSE, 'invalid_release', 0, '');
        }
        $release = $this->normalize_release($release, $this->update_link());
        if ($release === NULL) {
            return $this->result(FALSE, 'invalid_release', 0, '');
        }
        $current = $this->installed_version();
        if (!version_compare($release['version'], $current, '>')) {
            return $this->result(FALSE, 'not_newer', 0, '');
        }
        if ($release['requires_version'] !== '' && version_compare($current, $release['requires_version'], '<')) {
            return $this->result(FALSE, 'requires_newer_base', 0, '');
        }
        if ($release['min_php'] !== '' && version_compare(PHP_VERSION, $release['min_php'], '<')) {
            return $this->result(FALSE, 'php_unsupported', 0, '');
        }
        if ($this->active_update() !== NULL) {
            return $this->result(FALSE, 'update_in_progress', 0, '');
        }
        if (!$this->acquire_lock()) {
            return $this->result(FALSE, 'update_in_progress', 0, '');
        }
        // No active row exists, so a maintenance flag at this point is stale.
        $this->disable_maintenance();
        $maintenance = FALSE;
        register_shutdown_function(function () use (&$maintenance) {
            if ($maintenance) {
                @unlink($this->maintenance_path);
            }
            @unlink($this->lock_path);
        });

        $now = app_now();
        $inserted = $this->db->insert('sys_update', array(
            'version' => $release['version'],
            'previous_version' => $current,
            'status' => self::STATUS_PENDING,
            'package_url' => $release['package_url'],
            'checksum' => $release['sha256'],
            'changelog' => $release['changelog'],
            'log' => '',
            'started_on' => $now,
            'created_by' => (int) $created_by,
        ));
        if ($inserted !== TRUE) {
            $this->release_lock();
            return $this->result(FALSE, 'history_write_failed', 0, '');
        }
        $update_id = (int) $this->db->insert_id();
        $log = '[' . gmdate('c') . '] Memulai update ' . $current . ' -> ' . $release['version'] . "\n";
        $workspace = $this->cache_path . DIRECTORY_SEPARATOR . 'run-' . $update_id . '-' . gmdate('YmdHis');
        $archive = $workspace . DIRECTORY_SEPARATOR . 'package.zip';
        $db_dump = $workspace . DIRECTORY_SEPARATOR . 'database.sql';
        $file_backup = $workspace . DIRECTORY_SEPARATOR . 'files';
        $created_list = $workspace . DIRECTORY_SEPARATOR . 'created.json';
        $tables_snapshot = $workspace . DIRECTORY_SEPARATOR . 'tables.json';
        $maintenance = FALSE;
        $apply_started = FALSE;
        $db_backed_up = FALSE;

        try {
            $this->ensure_directory($workspace);
            $this->set_status($update_id, self::STATUS_DOWNLOADING);
            $this->append_log($update_id, $log, 'Mengunduh paket update.');
            $download = $this->download_package($release, $archive);
            if ($download !== '') {
                throw new RuntimeException($download);
            }
            $this->append_log($update_id, $log, 'Paket terverifikasi SHA-256.');

            $package = $this->read_package($archive, $release);
            if (!$this->is_array_package($package)) {
                throw new RuntimeException($package);
            }

            $this->set_status($update_id, self::STATUS_BACKING_UP);
            $this->append_log($update_id, $log, 'Membuat backup database.');
            $snapshot_error = $this->database_tables_snapshot($tables_snapshot);
            if ($snapshot_error !== '') {
                throw new RuntimeException($snapshot_error);
            }
            $db_error = $this->backup_database($db_dump);
            if ($db_error !== '') {
                throw new RuntimeException($db_error);
            }
            $db_backed_up = TRUE;
            $this->append_log($update_id, $log, 'Membackup file yang akan diganti.');
            $backup_error = $this->backup_files($package['files'], $package['delete'], $file_backup, $created_list);
            if ($backup_error !== '') {
                throw new RuntimeException($backup_error);
            }

            $this->set_status($update_id, self::STATUS_INSTALLING);
            $this->enable_maintenance();
            $maintenance = TRUE;
            $apply_started = TRUE;
            $this->append_log($update_id, $log, 'Menyalin file paket.');
            $apply_error = $this->apply_files($archive, $package['files'], $package['delete']);
            if ($apply_error !== '') {
                throw new RuntimeException($apply_error);
            }

            $this->set_status($update_id, self::STATUS_MIGRATING);
            $this->append_log($update_id, $log, 'Menjalankan migration paket.');
            $migration_error = $this->apply_migrations($update_id, $archive, $package['migrations'], $log);
            if ($migration_error !== '') {
                throw new RuntimeException($migration_error);
            }

            $this->write_version($release['version'], $release['channel']);
            $this->append_log($update_id, $log, 'Versi terpasang diperbarui menjadi ' . $release['version'] . '.');
            $this->set_status($update_id, self::STATUS_SUCCESS, TRUE);
            $this->disable_maintenance();
            $maintenance = FALSE;
            @unlink($archive);
            $this->prune_workspaces($workspace);
            $this->release_lock();
            return $this->result(TRUE, '', $update_id, $log);
        } catch (Throwable $error) {
            $message = $error->getMessage();
            if ($message === '') {
                $message = get_class($error);
            }
            $this->append_log($update_id, $log, 'Gagal: ' . $message);
            $restored = TRUE;
            if ($apply_started) {
                $this->append_log($update_id, $log, 'Memulai rollback.');
                $restored = $this->restore_files($file_backup, $created_list);
                if ($restored === '') {
                    $restored = TRUE;
                } else {
                    $this->append_log($update_id, $log, 'Rollback file gagal: ' . $restored);
                    $restored = FALSE;
                }
            }
            if ($db_backed_up) {
                $db_error = $this->restore_database($db_dump);
                if ($db_error !== '') {
                    $this->append_log($update_id, $log, 'Rollback database gagal: ' . $db_error);
                    $restored = FALSE;
                } else {
                    $drop_error = $this->drop_created_tables($tables_snapshot);
                    if ($drop_error !== '') {
                        $this->append_log($update_id, $log, 'Rollback tabel baru gagal: ' . $drop_error);
                        $restored = FALSE;
                    }
                }
            }
            // A database restore replaces the history row with the pre-update
            // copy, so update the row again after restoring.
            $this->set_status($update_id, $restored ? self::STATUS_ROLLED_BACK : self::STATUS_FAILED, TRUE);
            if ($maintenance) {
                $this->disable_maintenance();
            }
            $this->release_lock();
            return $this->result(FALSE, 'update_failed', $update_id, $log);
        }
    }

    private function normalize_release($release, $base_link)
    {
        if (!is_array($release)) {
            return NULL;
        }
        $version = isset($release['version']) ? trim((string) $release['version']) : '';
        $package_url = isset($release['package_url']) ? trim((string) $release['package_url']) : '';
        $sha256 = isset($release['sha256']) ? strtolower(trim((string) $release['sha256'])) : '';
        if (!$this->is_version($version) || $sha256 === '' || !preg_match('/\A[a-f0-9]{64}\z/', $sha256)) {
            return NULL;
        }
        $package_url = $this->resolve_url($package_url, $base_link);
        if (!$this->is_http_url($package_url)) {
            return NULL;
        }
        $package_url = $this->upgrade_package_url($package_url, $base_link);
        $migrations = array();
        if (isset($release['migrations']) && is_array($release['migrations'])) {
            foreach ($release['migrations'] as $name) {
                if (!is_string($name) || !preg_match('/\A[A-Za-z0-9._-]+\.sql\z/', $name)) {
                    return NULL;
                }
                $migrations[] = $name;
            }
        }
        return array(
            'version' => $version,
            'package_url' => $package_url,
            'sha256' => $sha256,
            'size' => isset($release['size']) ? max(0, (int) $release['size']) : 0,
            'changelog' => isset($release['changelog']) ? (string) $release['changelog'] : '',
            'released_at' => isset($release['released_at']) ? (string) $release['released_at'] : '',
            'min_php' => isset($release['min_php']) ? (string) $release['min_php'] : '',
            'requires_version' => isset($release['requires_version']) ? (string) $release['requires_version'] : '',
            'migrations' => $migrations,
            'channel' => isset($release['channel']) ? (string) $release['channel'] : $this->channel(),
        );
    }

    private function http_get($url, $timeout)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_USERAGENT => 'CMSNivo-Updater/1.0',
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ));
            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = $body === FALSE ? (string) curl_error($curl) : '';
            curl_close($curl);
            return array('status' => $status, 'body' => $body === FALSE ? '' : (string) $body, 'error' => $error);
        }
        $context = stream_context_create(array('http' => array('timeout' => $timeout, 'user_agent' => 'CMSNivo-Updater/1.0')));
        $body = @file_get_contents($url, FALSE, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int) $match[1];
        }
        return array('status' => $status, 'body' => $body === FALSE ? '' : (string) $body, 'error' => $body === FALSE ? 'request failed' : '');
    }

    private function download_package($release, $target)
    {
        $this->ensure_directory(dirname($target));
        $handle = @fopen($target, 'wb');
        if ($handle === FALSE) {
            return 'Paket update tidak dapat ditulis.';
        }
        $error = '';
        if (function_exists('curl_init')) {
            $curl = curl_init($release['package_url']);
            curl_setopt_array($curl, array(
                CURLOPT_FILE => $handle,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_USERAGENT => 'CMSNivo-Updater/1.0',
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ));
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($ok === FALSE) {
                $error = (string) curl_error($curl);
            } elseif ($status < 200 || $status >= 300) {
                $error = 'HTTP ' . $status;
            }
            curl_close($curl);
        } else {
            $body = @file_get_contents($release['package_url']);
            if ($body === FALSE) {
                $error = 'request failed';
            } elseif (fwrite($handle, $body) === FALSE) {
                $error = 'write failed';
            }
        }
        fclose($handle);
        if ($error !== '') {
            @unlink($target);
            return 'Unduhan gagal: ' . $error;
        }
        if (!is_file($target) || filesize($target) < 1) {
            @unlink($target);
            return 'Paket update kosong.';
        }
        if ($release['size'] > 0 && filesize($target) !== $release['size']) {
            @unlink($target);
            return 'Ukuran paket tidak sesuai.';
        }
        $hash = hash_file('sha256', $target);
        if (!is_string($hash) || !hash_equals($release['sha256'], strtolower($hash))) {
            @unlink($target);
            return 'Checksum paket tidak cocok.';
        }
        return '';
    }

    /**
     * Read the ZIP catalog and manifest.
     *
     * @return array|string package array on success, error message on failure
     */
    private function read_package($archive, $release)
    {
        $manifest_raw = $this->read_archive_entry($archive, 'manifest.json');
        if ($manifest_raw === NULL) {
            return 'Paket tidak memiliki manifest.json.';
        }
        $manifest = json_decode($manifest_raw, TRUE);
        if (!is_array($manifest) || !isset($manifest['version'])
            || (string) $manifest['version'] !== $release['version']) {
            return 'Manifest paket tidak sesuai versi rilis.';
        }
        $migration_names = array();
        $manifest_migrations = isset($manifest['migrations']) && is_array($manifest['migrations'])
            ? $manifest['migrations'] : array();
        foreach ($manifest_migrations as $name) {
            if (!is_string($name) || !preg_match('/\A[A-Za-z0-9._-]+\.sql\z/', $name)) {
                return 'Nama migration pada manifest tidak valid.';
            }
            $migration_names[] = $name;
        }
        if (!$migration_names && $release['migrations']) {
            $migration_names = $release['migrations'];
        }

        $entries = $this->archive_entries($archive);
        if (!is_array($entries)) {
            return $entries;
        }
        $files = array();
        $available_migrations = array();
        foreach ($entries as $entry) {
            if (strpos($entry, 'files/') === 0) {
                $relative = substr($entry, 6);
                if (strpos($relative, 'web/') === 0) {
                    $relative = substr($relative, 4);
                }
                if (!$this->is_safe_relative_path($relative)) {
                    return 'Paket berisi path yang tidak diizinkan: ' . $relative;
                }
                $files[$relative] = $entry;
            } elseif (strpos($entry, 'database/migrations/') === 0) {
                $name = basename($entry);
                if (preg_match('/\A[A-Za-z0-9._-]+\.sql\z/', $name)) {
                    $available_migrations[$name] = $entry;
                }
            }
        }
        if (!$migration_names) {
            $migration_names = array_keys($available_migrations);
            sort($migration_names, SORT_STRING);
        }
        foreach ($migration_names as $name) {
            if (!isset($available_migrations[$name])) {
                return 'Migration paket tidak ditemukan: ' . $name;
            }
        }

        $delete = array();
        if (isset($manifest['delete']) && is_array($manifest['delete'])) {
            foreach ($manifest['delete'] as $relative) {
                if (!is_string($relative) || !$this->is_safe_relative_path($relative)) {
                    return 'Daftar hapus paket tidak valid.';
                }
                $delete[] = $relative;
            }
        }
        if (!$files && !$migration_names && !$delete) {
            return 'Paket tidak berisi file atau migration.';
        }
        return array('files' => $files, 'migrations' => $migration_names, 'delete' => $delete);
    }

    private function is_array_package($package)
    {
        return is_array($package) && isset($package['files'], $package['migrations'], $package['delete']);
    }

    private function archive_entries($archive)
    {
        $entries = array();
        if (class_exists('PharData')) {
            try {
                $phar = new PharData($archive);
                $prefix = 'phar://' . str_replace('\\', '/', realpath($archive)) . '/';
                foreach (new RecursiveIteratorIterator($phar) as $file) {
                    if ($file->isDir()) {
                        continue;
                    }
                    $path = str_replace('\\', '/', $file->getPathname());
                    if (strpos($path, $prefix) !== 0) {
                        continue;
                    }
                    $entries[] = substr($path, strlen($prefix));
                }
            } catch (Throwable $error) {
                return 'Paket bukan ZIP yang valid.';
            }
        } elseif (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($archive) !== TRUE) {
                return 'Paket bukan ZIP yang valid.';
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (is_string($name) && substr($name, -1) !== '/') {
                    $entries[] = str_replace('\\', '/', $name);
                }
            }
            $zip->close();
        } else {
            return 'Ekstensi pembaca ZIP tidak tersedia.';
        }
        if (!$entries) {
            return 'Paket ZIP kosong.';
        }
        return array_values(array_unique($entries));
    }

    private function read_archive_entry($archive, $entry)
    {
        $entry = str_replace('\\', '/', (string) $entry);
        if (!$this->is_safe_archive_entry($entry)) {
            return NULL;
        }
        if (class_exists('PharData')) {
            try {
                $path = 'phar://' . str_replace('\\', '/', realpath($archive)) . '/' . $entry;
                if (!is_file($path)) {
                    return NULL;
                }
                $contents = file_get_contents($path);
                return $contents === FALSE ? NULL : $contents;
            } catch (Throwable $error) {
                return NULL;
            }
        }
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($archive) !== TRUE) {
                return NULL;
            }
            $contents = $zip->getFromName($entry);
            $zip->close();
            return $contents === FALSE ? NULL : $contents;
        }
        return NULL;
    }

    private function is_safe_archive_entry($entry)
    {
        return $entry !== '' && $entry[0] !== '/' && strpos($entry, "\0") === FALSE
            && !preg_match('#(^|/)\.\.?(/|$)#', $entry);
    }

    private function backup_files($files, $delete, $backup_dir, $created_list)
    {
        $created = array();
        foreach (array_keys($files) as $relative) {
            $error = $this->backup_one($relative, $backup_dir, $created);
            if ($error !== '') {
                return $error;
            }
        }
        foreach ($delete as $relative) {
            $error = $this->backup_one($relative, $backup_dir, $created);
            if ($error !== '') {
                return $error;
            }
        }
        $written = @file_put_contents($created_list, json_encode($created));
        return $written === FALSE ? 'Daftar file baru tidak dapat ditulis.' : '';
    }

    private function backup_one($relative, $backup_dir, &$created)
    {
        $target = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($target)) {
            $created[] = $relative;
            return '';
        }
        $backup = $backup_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $this->ensure_directory(dirname($backup));
        if (!@copy($target, $backup)) {
            return 'File tidak dapat dibackup: ' . $relative;
        }
        return '';
    }

    private function apply_files($archive, $files, $delete)
    {
        foreach ($files as $relative => $entry) {
            $contents = $this->read_archive_entry($archive, $entry);
            if ($contents === NULL) {
                return 'File paket tidak dapat dibaca: ' . $entry;
            }
            $target = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $this->ensure_directory(dirname($target));
            $temporary = $target . '.update-' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($temporary, $contents) === FALSE) {
                return 'File tidak dapat ditulis: ' . $relative;
            }
            if (!@rename($temporary, $target)) {
                @unlink($temporary);
                return 'File tidak dapat diganti: ' . $relative;
            }
        }
        foreach ($delete as $relative) {
            $target = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_file($target) && !@unlink($target)) {
                return 'File tidak dapat dihapus: ' . $relative;
            }
        }
        return '';
    }

    private function restore_files($backup_dir, $created_list)
    {
        if (!is_dir($backup_dir)) {
            return 'Backup file tidak tersedia.';
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backup_dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($backup_dir) + 1);
            $target = FCPATH . $relative;
            $this->ensure_directory(dirname($target));
            if (!@copy($file->getPathname(), $target)) {
                return 'File tidak dapat dipulihkan: ' . $relative;
            }
        }
        $created = array();
        if (is_file($created_list)) {
            $decoded = json_decode((string) file_get_contents($created_list), TRUE);
            if (is_array($decoded)) {
                $created = $decoded;
            }
        }
        foreach ($created as $relative) {
            if (!is_string($relative)) {
                continue;
            }
            $target = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_file($target)) {
                @unlink($target);
            }
        }
        return '';
    }

    private function apply_migrations($update_id, $archive, $migrations, &$log)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            foreach ($migrations as $name) {
                $sql = $this->read_archive_entry($archive, 'database/migrations/' . $name);
                if ($sql === NULL) {
                    return 'Migration tidak dapat dibaca: ' . $name;
                }
                $checksum = hash('sha256', $sql);
                $existing = $this->db->select('checksum')
                    ->from('sys_update_migration')
                    ->where('migration_name', $name)
                    ->get();
                if ($existing === FALSE) {
                    return 'Riwayat migration tidak dapat dibaca: ' . $name;
                }
                if ($existing->num_rows() > 0) {
                    foreach ($existing->result_array() as $row) {
                        if (!hash_equals((string) $row['checksum'], $checksum)) {
                            return 'Migration dengan nama sama sudah tercatat dengan checksum berbeda: ' . $name;
                        }
                    }
                    $this->append_log($update_id, $log, 'Migration dilewati (sudah tercatat): ' . $name);
                    continue;
                }
                foreach ($this->split_sql($sql) as $statement) {
                    if ($this->db->query($statement) === FALSE) {
                        $db_error = $this->db->error();
                        $detail = is_array($db_error) && isset($db_error['message']) ? $db_error['message'] : 'query failed';
                        return 'Migration gagal (' . $name . '): ' . $detail;
                    }
                }
                $this->db->insert('sys_update_migration', array(
                    'update_id' => (int) $update_id,
                    'migration_name' => $name,
                    'checksum' => $checksum,
                    'applied_on' => app_now(),
                ));
                $this->append_log($update_id, $log, 'Migration diterapkan: ' . $name);
            }
            return '';
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    /** Split a SQL file into statements while respecting quotes and comments. */
    private function split_sql($sql)
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', (string) $sql);
        $statements = array();
        $buffer = '';
        $length = strlen($sql);
        $single = FALSE;
        $double = FALSE;
        $backtick = FALSE;
        $line_comment = FALSE;
        $block_comment = FALSE;
        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : '';
            if ($line_comment) {
                if ($char === "\n") {
                    $line_comment = FALSE;
                    $buffer .= "\n";
                }
                continue;
            }
            if ($block_comment) {
                if ($char === '*' && $next === '/') {
                    $block_comment = FALSE;
                    $index++;
                }
                continue;
            }
            if (!$single && !$double && !$backtick) {
                if ($char === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) {
                    $line_comment = TRUE;
                    $index++;
                    continue;
                }
                if ($char === '#') {
                    $line_comment = TRUE;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $block_comment = TRUE;
                    $index++;
                    continue;
                }
            }
            if (($single || $double) && $char === '\\' && $next !== '') {
                $buffer .= $char . $next;
                $index++;
                continue;
            }
            if ($char === "'" && !$double && !$backtick) {
                if ($single && $next === "'") {
                    $buffer .= "''";
                    $index++;
                    continue;
                }
                $single = !$single;
                $buffer .= $char;
                continue;
            }
            if ($char === '"' && !$single && !$backtick) {
                if ($double && $next === '"') {
                    $buffer .= '""';
                    $index++;
                    continue;
                }
                $double = !$double;
                $buffer .= $char;
                continue;
            }
            if ($char === '`' && !$single && !$double) {
                $backtick = !$backtick;
                $buffer .= $char;
                continue;
            }
            if ($char === ';' && !$single && !$double && !$backtick) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }
        return $statements;
    }

    private function backup_database($target)
    {
        $binary = trim((string) @shell_exec('command -v mysqldump 2>/dev/null'));
        if ($binary === '' || !function_exists('exec')) {
            return 'Backup database tidak tersedia (mysqldump tidak ditemukan).';
        }
        $defaults = $this->database_defaults_file();
        if (!is_string($defaults)) {
            return $defaults;
        }
        $version = trim((string) @shell_exec(escapeshellarg($binary) . ' --version 2>/dev/null'));
        $gtid_option = stripos($version, 'mariadb') === FALSE ? ' --set-gtid-purged=OFF' : '';
        $command = escapeshellarg($binary)
            . ' --defaults-extra-file=' . escapeshellarg($defaults)
            . ' --single-transaction --quick --skip-ssl --no-tablespaces'
            . ' --routines --triggers --events --default-character-set=utf8mb4'
            . $gtid_option . ' ' . escapeshellarg((string) $this->db->database)
            . ' > ' . escapeshellarg($target) . ' 2>&1';
        $output = array();
        $code = 0;
        @exec($command, $output, $code);
        @unlink($defaults);
        if ($code !== 0 || !is_file($target) || filesize($target) < 1) {
            return 'Backup database gagal: ' . implode(' ', array_slice($output, 0, 3));
        }
        return '';
    }

    private function restore_database($dump)
    {
        if (!is_file($dump)) {
            return 'Dump database tidak tersedia.';
        }
        $binary = trim((string) @shell_exec('command -v mysql 2>/dev/null'));
        if ($binary === '' || !function_exists('exec')) {
            return 'Pemulihan database tidak tersedia (mysql tidak ditemukan).';
        }
        $defaults = $this->database_defaults_file();
        if (!is_string($defaults)) {
            return $defaults;
        }
        $version = trim((string) @shell_exec(escapeshellarg($binary) . ' --version 2>/dev/null'));
        $tls_option = stripos($version, 'mariadb') === FALSE ? ' --ssl-mode=DISABLED' : ' --skip-ssl';
        $command = escapeshellarg($binary)
            . ' --defaults-extra-file=' . escapeshellarg($defaults)
            . $tls_option
            . ' --default-character-set=utf8mb4 ' . escapeshellarg((string) $this->db->database)
            . ' < ' . escapeshellarg($dump) . ' 2>&1';
        $output = array();
        $code = 0;
        @exec($command, $output, $code);
        @unlink($defaults);
        return $code === 0 ? '' : 'Pemulihan database gagal: ' . implode(' ', array_slice($output, 0, 3));
    }

    private function database_tables_snapshot($path)
    {
        $query = $this->db->query(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            array((string) $this->db->database)
        );
        if ($query === FALSE) {
            return 'Daftar tabel database tidak dapat dibaca.';
        }
        $tables = array();
        foreach ($query->result_array() as $row) {
            if (isset($row['TABLE_NAME'])) {
                $tables[] = (string) $row['TABLE_NAME'];
            }
        }
        return @file_put_contents($path, json_encode($tables)) === FALSE
            ? 'Snapshot tabel database tidak dapat ditulis.'
            : '';
    }

    private function drop_created_tables($snapshot_path)
    {
        if (!is_file($snapshot_path)) {
            return '';
        }
        $decoded = json_decode((string) file_get_contents($snapshot_path), TRUE);
        if (!is_array($decoded)) {
            return 'Snapshot tabel database tidak valid.';
        }
        $before = array_fill_keys(array_map('strval', $decoded), TRUE);
        $query = $this->db->query(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            array((string) $this->db->database)
        );
        if ($query === FALSE) {
            return 'Daftar tabel database tidak dapat dibaca.';
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($query->result_array() as $row) {
                $table = isset($row['TABLE_NAME']) ? (string) $row['TABLE_NAME'] : '';
                if ($table === '' || isset($before[$table])) {
                    continue;
                }
                if ($this->db->query('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`') === FALSE) {
                    return 'Tabel baru tidak dapat dihapus: ' . $table;
                }
            }
            return '';
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
            $this->db->db_debug = $debug;
        }
    }

    private function database_defaults_file()
    {
        $contents = "[client]\n"
            . 'host=' . $this->ini_value($this->db->hostname) . "\n"
            . 'port=' . (int) (isset($this->db->port) && $this->db->port ? $this->db->port : 3306) . "\n"
            . 'user=' . $this->ini_value($this->db->username) . "\n"
            . 'password=' . $this->ini_value($this->db->password) . "\n";
        $path = $this->cache_path . DIRECTORY_SEPARATOR . '.db-' . bin2hex(random_bytes(6)) . '.cnf';
        $this->ensure_directory(dirname($path));
        if (@file_put_contents($path, $contents) === FALSE) {
            return 'Konfigurasi sementara database tidak dapat ditulis.';
        }
        @chmod($path, 0600);
        return $path;
    }

    private function ini_value($value)
    {
        return '"' . str_replace(array('\\', '"', "\r", "\n"), array('\\\\', '\\"', '', ''), (string) $value) . '"';
    }

    private function write_version($version, $channel)
    {
        $channel = is_string($channel) && $channel !== '' ? $channel : 'stable';
        $contents = "<?php\n"
            . "defined('BASEPATH') OR exit('No direct script access allowed');\n\n"
            . "/*\n"
            . "| -------------------------------------------------------------------\n"
            . "| APPLICATION VERSION\n"
            . "| -------------------------------------------------------------------\n"
            . "| The installed release. Update packages replace this file with the\n"
            . "| version they install. Keep the value in semantic version form.\n"
            . "*/\n"
            . '$config[\'app_version\'] = ' . var_export($version, TRUE) . ";\n"
            . '$config[\'app_update_channel\'] = ' . var_export($channel, TRUE) . ";\n";
        $path = APPPATH . 'config' . DIRECTORY_SEPARATOR . 'version.php';
        $temporary = $path . '.update.tmp';
        if (@file_put_contents($temporary, $contents) === FALSE || !@rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('File versi tidak dapat diperbarui.');
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, TRUE);
        }
        // Keep the in-memory config current so chained updates see the new base.
        $this->config->set_item('app_version', $version, 'version');
        $this->config->set_item('app_update_channel', $channel, 'version');
    }

    private function set_status($update_id, $status, $finished = FALSE)
    {
        $data = array('status' => $status);
        if ($finished) {
            $data['finished_on'] = app_now();
        }
        $this->db->where('update_id', (int) $update_id)->update('sys_update', $data);
    }

    private function append_log($update_id, &$log, $message)
    {
        $line = '[' . gmdate('c') . '] ' . $message . "\n";
        $log .= $line;
        if (strlen($log) > 60000) {
            $log = substr($log, -60000);
        }
        $this->db->where('update_id', (int) $update_id)->update('sys_update', array('log' => $log));
    }

    private function enable_maintenance()
    {
        $this->ensure_directory(dirname($this->maintenance_path));
        if (@file_put_contents($this->maintenance_path, (string) time()) === FALSE) {
            throw new RuntimeException('Maintenance mode tidak dapat diaktifkan.');
        }
    }

    private function disable_maintenance()
    {
        if (is_file($this->maintenance_path)) {
            @unlink($this->maintenance_path);
        }
    }

    private function acquire_lock()
    {
        $this->ensure_directory($this->cache_path);
        $handle = @fopen($this->lock_path, 'x');
        if ($handle === FALSE) {
            $mtime = @filemtime($this->lock_path);
            if ($mtime !== FALSE && $mtime < time() - 3600) {
                @unlink($this->lock_path);
                $handle = @fopen($this->lock_path, 'x');
            }
        }
        if ($handle === FALSE) {
            return FALSE;
        }
        fwrite($handle, json_encode(array('pid' => getmypid(), 'time' => time())));
        fclose($handle);
        return TRUE;
    }

    private function release_lock()
    {
        if (is_file($this->lock_path)) {
            @unlink($this->lock_path);
        }
    }

    private function ensure_directory($path)
    {
        if (!is_dir($path) && !@mkdir($path, 0750, TRUE) && !is_dir($path)) {
            throw new RuntimeException('Direktori tidak dapat dibuat: ' . basename($path));
        }
    }

    private function prune_workspaces($current)
    {
        $directories = glob($this->cache_path . DIRECTORY_SEPARATOR . 'run-*', GLOB_ONLYDIR);
        if (!is_array($directories)) {
            return;
        }
        rsort($directories, SORT_STRING);
        $kept = 0;
        foreach ($directories as $directory) {
            $kept++;
            if ($directory === $current || $kept <= 5) {
                continue;
            }
            $this->remove_directory($directory);
        }
    }

    private function remove_directory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($directory);
    }

    private function is_safe_relative_path($path)
    {
        if (!is_string($path) || $path === '' || $path[0] === '/'
            || strpos($path, "\0") !== FALSE || strpos($path, '\\') !== FALSE
            || preg_match('#(^|/)\.\.?(/|$)#', $path)) {
            return FALSE;
        }
        $lower = strtolower($path);
        if (preg_match('#(^|/)\.env(\..*)?$#', $lower)
            || preg_match('#(^|/)(database_backup|backup|backups)(/|$)#', $lower)
            || preg_match('#(^|/)data(/|$)#', $lower)
            || preg_match('#(^|/)updates(/|$)#', $lower)
            || strpos($lower, 'application/cache/') === 0
            || strpos($lower, 'application/logs/') === 0
            || strpos($lower, 'application/cache') === 0
            || strpos($lower, 'application/logs') === 0) {
            return FALSE;
        }
        return TRUE;
    }

    /** Upgrade an HTTP package URL when the configured API endpoint is HTTPS. */
    private function upgrade_package_url($package_url, $base_link)
    {
        $base = parse_url($base_link);
        $package = parse_url($package_url);
        if (!is_array($base) || !is_array($package)
            || !isset($base['scheme'], $base['host'], $package['scheme'], $package['host'])
            || strtolower((string) $base['scheme']) !== 'https'
            || strtolower((string) $package['scheme']) !== 'http'
            || strtolower((string) $base['host']) !== strtolower((string) $package['host'])) {
            return $package_url;
        }
        return preg_replace('#\Ahttp://#i', 'https://', $package_url);
    }

    private function is_http_url($url)
    {
        if (!is_string($url) || $url === '') {
            return FALSE;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return FALSE;
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return FALSE;
        }
        // Production endpoints must use TLS. Plain HTTP is accepted only for
        // a loopback development updater.
        if ($scheme === 'http') {
            $host = strtolower($parts['host']);
            return in_array($host, array('localhost', '127.0.0.1', '::1'), TRUE)
                || substr($host, -6) === '.local';
        }
        return TRUE;
    }

    private function resolve_url($url, $base)
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('#\Ahttps?://#i', $url)) {
            return $url;
        }
        $base_parts = parse_url($base);
        if (!is_array($base_parts) || empty($base_parts['scheme']) || empty($base_parts['host'])) {
            return '';
        }
        if ($url[0] === '/') {
            return $base_parts['scheme'] . '://' . $base_parts['host']
                . (isset($base_parts['port']) ? ':' . $base_parts['port'] : '') . $url;
        }
        $path = isset($base_parts['path']) ? $base_parts['path'] : '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $base_parts['scheme'] . '://' . $base_parts['host']
            . (isset($base_parts['port']) ? ':' . $base_parts['port'] : '')
            . ($directory === '' || $directory === '.' ? '' : $directory) . '/' . $url;
    }

    private function is_version($value)
    {
        return is_string($value) && preg_match('/\A\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?\z/', $value) === 1;
    }

    private function result($ok, $error, $update_id, $log)
    {
        return array('ok' => (bool) $ok, 'error' => $error, 'update_id' => (int) $update_id, 'log' => $log);
    }
}
