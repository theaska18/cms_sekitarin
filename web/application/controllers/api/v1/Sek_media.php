<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Sek_api_base.php';

/** Local media upload, authenticated serving, and deletion. */
class Sek_media extends Sek_api_base
{
    const MAX_IMAGE_BYTES = 10485760;
    const MAX_VIDEO_BYTES = 52428800;
    const MAX_DOCUMENT_BYTES = 12582912;

    public function index()
    {
        if (!$this->require_method('POST')) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }

        $purpose = strtolower(trim((string) $this->input->post('purpose')));
        if (!in_array($purpose, array('avatar', 'cover', 'post', 'chat', 'document'), TRUE)) {
            $this->api_response->error('validation_error', 'Purpose media tidak valid.', 422, array('purpose' => 'Tidak valid.'));
            return;
        }
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            $this->api_response->error('validation_error', 'File wajib dikirim pada field file.', 422, array('file' => 'Wajib diisi.'));
            return;
        }
        $file = $_FILES['file'];
        if (!empty($file['error'])) {
            $this->api_response->error('upload_failed', $this->upload_error_message((int) $file['error']), 422);
            return;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->api_response->error('upload_failed', 'Upload tidak valid.', 422);
            return;
        }

        $size = (int) $file['size'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $image_types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
        $video_types = array('video/mp4' => 'mp4', 'video/quicktime' => 'mov');
        $is_image = isset($image_types[$mime]);
        $is_video = isset($video_types[$mime]);
        $is_document = $mime === 'application/pdf';
        if ($is_video && !in_array($purpose, array('post', 'chat'), TRUE)) {
            $is_video = FALSE;
        }
        if (!$is_image && !$is_video && !($purpose === 'document' && $is_document)) {
            $this->api_response->error('validation_error', 'Tipe file tidak didukung.', 422, array('file' => 'Gunakan JPEG, PNG, WebP, MP4, atau MOV.'));
            return;
        }
        $limit = $is_image ? self::MAX_IMAGE_BYTES : ($is_video ? self::MAX_VIDEO_BYTES : self::MAX_DOCUMENT_BYTES);
        if ($size <= 0 || $size > $limit) {
            $this->api_response->error('validation_error', 'Ukuran file terlalu besar.', 422, array('file' => 'Maksimal ' . (int) ($limit / 1048576) . ' MB.'));
            return;
        }

        $width = NULL;
        $height = NULL;
        if ($is_image) {
            $dimensions = @getimagesize($file['tmp_name']);
            if ($dimensions !== FALSE) {
                $width = (int) $dimensions[0];
                $height = (int) $dimensions[1];
            }
        }

        $user_id = (int) $this->api_auth->user_id();
        $relative_dir = 'data/sekitar-media/' . $user_id;
        $absolute_dir = FCPATH . $relative_dir;
        if (!is_dir($absolute_dir) && !@mkdir($absolute_dir, 0770, TRUE) && !is_dir($absolute_dir)) {
            $this->api_response->error('server_error', 'Direktori media tidak dapat dibuat.', 500);
            return;
        }
        $extension = $is_image ? $image_types[$mime] : ($is_video ? $video_types[$mime] : 'pdf');
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $relative_path = $relative_dir . '/' . $filename;
        if (!@move_uploaded_file($file['tmp_name'], FCPATH . $relative_path)) {
            $this->api_response->error('server_error', 'File gagal disimpan.', 500);
            return;
        }
        @chmod(FCPATH . $relative_path, 0640);

        $media_id = $this->sek_media_model->create(
            $user_id,
            $purpose,
            $relative_path,
            $mime,
            $size,
            $width,
            $height,
            hash_file('sha256', FCPATH . $relative_path)
        );
        $media = $this->sek_media_model->find($media_id);
        $this->api_response->json(array('media' => $this->media_payload($media)), 201);
    }

    public function show($media_id = 0)
    {
        if (!$this->require_method(array('GET', 'DELETE'))) {
            return;
        }
        if (!$this->require_auth()) {
            return;
        }
        $media = $this->sek_media_model->find((int) $media_id);
        if (!is_array($media) || (string) $media['status'] !== 'ready') {
            $this->api_response->error('not_found', 'Media tidak ditemukan.', 404);
            return;
        }
        $owner = (int) $media['owner_user_id'] === (int) $this->api_auth->user_id();
        $public = $this->sek_media_model->used_by_active_account((int) $media['media_id']) !== NULL;
        $postVisible = (string) $media['purpose'] === 'post'
            && $this->sek_media_model->is_post_media_visible((int) $media['media_id']);
        $chatVisible = (string) $media['purpose'] === 'chat'
            && $this->sek_media_model->is_chat_media_visible(
                (int) $media['media_id'],
                (int) $this->api_auth->user_id()
            );
        if (!$owner && !$public && !$postVisible && !$chatVisible) {
            $this->api_response->error('forbidden', 'Media tidak dapat diakses.', 403);
            return;
        }

        if (strtoupper((string) $this->input->method(TRUE)) === 'DELETE') {
            $this->delete_media($media, $owner);
            return;
        }

        $base = realpath(FCPATH . 'data/sekitar-media');
        $path = realpath(FCPATH . $media['path']);
        if ($base === FALSE || $path === FALSE || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            $this->api_response->error('not_found', 'File media tidak ditemukan.', 404);
            return;
        }

        header('Content-Type: ' . $media['mime']);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }

    private function delete_media($media, $owner)
    {
        if (!$owner) {
            $this->api_response->error('forbidden', 'Hanya pemilik media yang dapat menghapus.', 403);
            return;
        }
        if ($this->sek_media_model->referenced_count((int) $media['media_id']) > 0) {
            $this->api_response->error('conflict', 'Media masih dipakai oleh profil akun.', 409);
            return;
        }
        $path = FCPATH . $media['path'];
        if (is_file($path)) {
            @unlink($path);
        }
        $this->sek_media_model->delete((int) $media['media_id']);
        $this->api_response->ok(array('status' => 'deleted'));
    }

    private function media_payload($media)
    {
        return array(
            'media_id' => (int) $media['media_id'],
            'purpose' => (string) $media['purpose'],
            'mime' => (string) $media['mime'],
            'size_bytes' => (int) $media['size_bytes'],
            'width' => $media['width'] !== NULL ? (int) $media['width'] : NULL,
            'height' => $media['height'] !== NULL ? (int) $media['height'] : NULL,
            'status' => (string) $media['status'],
            'url' => '/api/v1/media/' . (int) $media['media_id'],
            'created_at' => $this->utc($media['created_at']),
        );
    }

    private function upload_error_message($code)
    {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => 'File melebihi batas upload server.',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi batas form.',
            UPLOAD_ERR_PARTIAL => 'Upload tidak selesai.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diunggah.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi.',
        );
        return isset($messages[$code]) ? $messages[$code] : 'Upload gagal.';
    }
}
