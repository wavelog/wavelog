<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Upload_guard
 *
 * Small, reusable upload-acceptance checks shared by the various file-upload
 * paths (QSL cards, eQSL, ...). Kept free of controller/model state so it can be
 * loaded and called from anywhere via $this->load->library('upload_guard').
 */
class Upload_guard {

    /** Minimum free space (bytes) that must remain after a write: 4 GB. */
    const DEFAULT_BUFFER = 4294967296; // 4 * 1024 * 1024 * 1024

    /**
     * Returns true only if $path is on a volume that, after writing
     * $incoming_bytes, would still have at least $buffer bytes free.
     *
     * @param string $path           Target directory the file will be written to
     * @param int    $incoming_bytes  Size of the file about to be written
     * @param int    $buffer          Free-space buffer to preserve (default 4 GB)
     */
    public function has_free_space($path, $incoming_bytes, $buffer = self::DEFAULT_BUFFER) {
        $free = @disk_free_space($path);
        if ($free === false) {
            return false; // can't determine free space -> fail safe
        }
        return ($free - (int) $incoming_bytes) >= $buffer;
    }

    /**
     * Returns true if $fullpath is a real raster image (magic-byte check), not
     * just a file carrying an image extension. Restricted to the formats the QSL
     * card feature accepts.
     *
     * @param string $fullpath Absolute path to the uploaded file on disk
     */
    public function is_real_image($fullpath) {
        $info = @getimagesize($fullpath);
        if ($info === false) {
            return false;
        }
        return in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF), true);
    }
}
