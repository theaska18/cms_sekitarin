<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Public metadata and APK download for the Sekitar mobile app. */
class Sek_app extends Sek_api_base
{
    public function latest()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        $path = FCPATH . 'data/app/latest.json';
        $data = array(
            'version' => '0.0.0',
            'build' => 0,
            'notes' => '',
            'url' => '/api/v1/app/download',
        );
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), TRUE);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }
        $data['available'] = is_file(FCPATH . 'data/app/sekitar.apk');
        $this->api_response->ok(array('app' => $data));
    }

    public function download()
    {
        if (!$this->require_method('GET')) {
            return;
        }
        $path = FCPATH . 'data/app/sekitar.apk';
        if (!is_file($path) || !is_readable($path)) {
            $this->api_response->error('not_found', 'APK belum tersedia.', 404);
            return;
        }
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="sekitar.apk"');
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
