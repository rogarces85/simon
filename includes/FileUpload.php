<?php

class FileUpload
{
    private const MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public static function validate($file, array $allowedExtensions, int $maxBytes = 5242880)
    {
        if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return false;
        }

        $expectedMime = self::MIME_BY_EXTENSION[$ext] ?? null;
        if ($expectedMime === null) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return $realMime === $expectedMime;
    }
}
