<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Upload_guard
 *
 * Small, reusable upload-acceptance checks shared by the various file-upload
 * paths (QSL cards, eQSL, ...). Kept free of controller/model state so it can be
 * loaded and called from anywhere via $this->load->library('upload_guard').
 */
class Upload_guard {

    /** Minimum free space (bytes) that must remain after a write: 1 GB. */
    const DEFAULT_BUFFER = 1073741824; // 1 * 1024 * 1024 * 1024

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

    /**
     * Returns the extension implied by the file's actual content ('jpg',
     * 'png', 'gif'), or null if it is not a raster image we accept. Used to
     * normalize client filenames whose extension doesn't match the real
     * payload (e.g. a JPEG named .png), which the Upload library's
     * ext-vs-mime cross-check would otherwise reject.
     *
     * @param string $tmp_name $_FILES[<field>]['tmp_name']
     */
    public function real_image_ext($tmp_name) {
        $info = @getimagesize($tmp_name);
        if ($info === false) {
            return null;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG: return 'jpg';
            case IMAGETYPE_PNG:  return 'png';
            case IMAGETYPE_GIF:  return 'gif';
            default:             return null;
        }
    }

    /**
     * Aligns the extension of an uploaded image's client filename with the
     * file's actual content (e.g. a JPEG named .png becomes *.jpg), in place
     * in $_FILES. The Upload library's ext-vs-mime cross-check would
     * otherwise reject such files, and the honest extension ensures the
     * stored file is served with the correct Content-Type. Non-images are
     * left untouched, so the existing rejection paths apply unchanged.
     *
     * @param string $field Key in the $_FILES array
     */
    public function normalize_image_ext($field) {
        if (empty($_FILES[$field]['tmp_name'])) {
            return;
        }
        $ext = $this->real_image_ext($_FILES[$field]['tmp_name']);
        if ($ext !== null) {
            $_FILES[$field]['name'] = 'image.' . $ext;
        }
    }
}
