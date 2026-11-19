<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public read-only update endpoint for installations of this CMS.
 *
 * The release catalog lives in web/updates/latest.json. Clients send their
 * installed version and PHP version, then download the advertised package
 * through package($filename), which never serves arbitrary paths.
 */
class Update_api extends CI_Controller
{
    public function check()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->output->set_header('Allow: GET')->set_status_header(405);
            return;
        }

        $manifest = $this->load_manifest();
        if ($manifest === NULL) {
            $this->json(array('status' => 'error', 'message' => 'Update catalog is unavailable.'), 503);
            return;
        }

        $current_version = trim((string) $this->input->get('version', TRUE));
        if ($current_version === '' || !$this->is_version($current_version)) {
            $this->json(array('status' => 'error', 'message' => 'A valid version query parameter is required.'), 400);
            return;
        }

        $client_php = trim((string) $this->input->get('php', TRUE));
        if ($client_php !== '' && !preg_match('/\A\d+(?:\.\d+){1,3}\z/', $client_php)) {
            $client_php = '';
        }

        $latest_version = (string) $manifest['version'];
        $update_available = version_compare($latest_version, $current_version, '>');
        $target = NULL;
        if ($update_available) {
            $target = $this->next_compatible_release($manifest, $current_version);
        }
        $compatible = $target !== NULL;
        if ($target !== NULL && $client_php !== '' && $target['min_php'] !== ''
            && version_compare($client_php, (string) $target['min_php'], '<')) {
            $compatible = FALSE;
        }

        $payload = array(
            'status' => 'ok',
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'target_version' => $target !== NULL ? $target['version'] : '',
            'update_available' => $update_available && $target !== NULL,
            'compatible' => $compatible,
            'checked_at' => gmdate('c'),
        );
        if ($update_available && $target !== NULL) {
            $payload['release'] = $this->release_payload($target);
        }

        $this->json($payload, 200);
    }

    /**
     * Pick the nearest release the installed version can apply directly.
     * Delta packages declare the version they were built from; when the latest
     * package needs a newer base, clients are advanced one release at a time.
     */
    private function next_compatible_release($latest, $current_version)
    {
        if (version_compare((string) $latest['requires_version'], $current_version, '<=')) {
            return $this->package_available($latest) ? $latest : NULL;
        }
        $candidates = array();
        foreach ($this->load_releases() as $release) {
            if (version_compare($release['version'], $current_version, '>')
                && version_compare($release['version'], $latest['version'], '<=')
                && version_compare((string) $release['requires_version'], $current_version, '<=')
                && $this->package_available($release)) {
                $candidates[] = $release;
            }
        }
        if (!$candidates) {
            return NULL;
        }
        usort($candidates, function ($left, $right) {
            return version_compare($left['version'], $right['version']);
        });
        return $candidates[0];
    }

    private function package_available($manifest)
    {
        if (empty($manifest['package'])) {
            return FALSE;
        }
        $path = FCPATH . 'updates' . DIRECTORY_SEPARATOR . $manifest['package'];
        return is_file($path) && is_readable($path);
    }

    public function package($filename = '')
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->output->set_header('Allow: GET')->set_status_header(405);
            return;
        }
        $filename = (string) $filename;
        if (!preg_match('/\Acms-\d+\.\d+\.\d+\.zip\z/', $filename)) {
            show_404();
            return;
        }

        $allowed = FALSE;
        foreach ($this->load_releases() as $release) {
            if (!empty($release['package']) && hash_equals((string) $release['package'], $filename)) {
                $allowed = TRUE;
                break;
            }
        }
        if (!$allowed) {
            show_404();
            return;
        }

        $path = FCPATH . 'updates' . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($path) || !is_readable($path)) {
            show_404();
            return;
        }

        $size = filesize($path);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) $size);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    /** Latest catalog plus every archived release manifest, keyed by version. */
    private function load_releases()
    {
        $releases = array();
        $latest = $this->load_manifest();
        if ($latest !== NULL) {
            $releases[$latest['version']] = $latest;
        }
        $directory = FCPATH . 'updates' . DIRECTORY_SEPARATOR . 'releases';
        if (is_dir($directory)) {
            foreach ((array) glob($directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'release.json') as $file) {
                $release = $this->read_release_manifest($file);
                if ($release !== NULL && !isset($releases[$release['version']])) {
                    $releases[$release['version']] = $release;
                }
            }
        }
        uksort($releases, 'version_compare');
        return $releases;
    }

    /** Normalize an archived release.json into the latest.json shape. */
    private function read_release_manifest($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return NULL;
        }
        $contents = file_get_contents($path);
        $data = $contents === FALSE ? NULL : json_decode($contents, TRUE);
        if (!is_array($data) || !isset($data['version']) || !$this->is_version((string) $data['version'])) {
            return NULL;
        }
        $package = isset($data['package']) && is_array($data['package']) ? $data['package'] : array();
        return array(
            'version' => (string) $data['version'],
            'package' => isset($package['name']) && is_string($package['name']) ? $package['name'] : '',
            'sha256' => isset($package['sha256']) && is_string($package['sha256']) ? strtolower($package['sha256']) : '',
            'size' => isset($package['size']) ? (int) $package['size'] : 0,
            'min_php' => isset($data['min_php']) ? (string) $data['min_php'] : '',
            'requires_version' => isset($data['requires_version']) ? (string) $data['requires_version'] : '0.0.0',
            'changelog' => isset($data['changelog']) ? (string) $data['changelog'] : '',
            'released_at' => isset($data['released_at']) ? (string) $data['released_at'] : '',
            'migrations' => isset($data['migrations']) && is_array($data['migrations'])
                ? array_values(array_filter($data['migrations'], 'is_string')) : array(),
        );
    }

    private function load_manifest()
    {
        $path = FCPATH . 'updates' . DIRECTORY_SEPARATOR . 'latest.json';
        if (!is_file($path) || !is_readable($path)) {
            return NULL;
        }
        $contents = file_get_contents($path);
        if ($contents === FALSE) {
            return NULL;
        }
        $data = json_decode($contents, TRUE);
        if (!is_array($data) || !isset($data['version']) || !$this->is_version((string) $data['version'])) {
            return NULL;
        }
        $data['version'] = (string) $data['version'];
        $data['package'] = isset($data['package']) && is_string($data['package']) ? $data['package'] : '';
        $data['sha256'] = isset($data['sha256']) && is_string($data['sha256'])
            ? strtolower($data['sha256']) : '';
        if ($data['sha256'] !== '' && !preg_match('/\A[a-f0-9]{64}\z/', $data['sha256'])) {
            return NULL;
        }
        $data['min_php'] = isset($data['min_php']) ? (string) $data['min_php'] : '';
        $data['requires_version'] = isset($data['requires_version']) ? (string) $data['requires_version'] : '0.0.0';
        $data['changelog'] = isset($data['changelog']) ? (string) $data['changelog'] : '';
        $data['released_at'] = isset($data['released_at']) ? (string) $data['released_at'] : '';
        $data['migrations'] = isset($data['migrations']) && is_array($data['migrations'])
            ? array_values(array_filter($data['migrations'], 'is_string')) : array();
        return $data;
    }

    private function release_payload($manifest)
    {
        $package_url = '';
        if (!empty($manifest['package_url']) && is_string($manifest['package_url'])) {
            $package_url = $manifest['package_url'];
        } elseif ($manifest['package'] !== '') {
            $package_url = base_url('api/update/package/' . rawurlencode($manifest['package']));
        }
        return array(
            'version' => $manifest['version'],
            'released_at' => $manifest['released_at'],
            'changelog' => $manifest['changelog'],
            'min_php' => $manifest['min_php'],
            'requires_version' => $manifest['requires_version'],
            'package_url' => $package_url,
            'sha256' => $manifest['sha256'],
            'size' => isset($manifest['size']) ? (int) $manifest['size'] : 0,
            'migrations' => $manifest['migrations'],
        );
    }

    private function is_version($value)
    {
        return is_string($value) && preg_match('/\A\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?\z/', $value) === 1;
    }

    private function json($payload, $status)
    {
        $this->output
            ->set_status_header($status)
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
