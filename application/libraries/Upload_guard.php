<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Upload_guard
 *
 * Small, reusable upload-acceptance checks shared by the various file-upload
 * paths (QSL cards, eQSL, ...). Kept free of controller/model state so it can be
 * loaded and called from anywhere via $this->load->library('upload_guard').
 */
class Upload_guard {

    /** Share of the volume size that must remain free after a write: 10%. */
    const DEFAULT_RESERVE_RATIO = 0.1;

    /**
     * Returns true only if $path is on a volume that, after writing
     * $incoming_bytes, would still have at least $reserve_ratio of its total
     * size free. Relative to the volume size so small volumes stay usable.
     *
     * @param string $path           Target directory the file will be written to
     * @param int    $incoming_bytes Size of the file about to be written
     * @param float  $reserve_ratio  Share of the volume to keep free (default 10%)
     */
    public function has_free_space($path, $incoming_bytes, $reserve_ratio = self::DEFAULT_RESERVE_RATIO) {
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if ($free === false || $total === false || $total <= 0) {
            return false; // can't determine free space -> fail safe
        }
        return ($free - (int) $incoming_bytes) >= ($total * $reserve_ratio);
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
