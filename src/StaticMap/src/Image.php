<?php

namespace DantSu\PHPImageEditor;

include 'Geometry2D.php';
use DantSu\PHPImageEditor\Geometry2D;

/**
 * DantSu\PHPImageEditor\Image is PHP library to easily edit image with GD extension. Resize, crop, merge, draw, and many more options !
 *
 * @package DantSu\PHPImageEditor
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-image-editor Github page of this project
 */
class Image {

    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'right';
    const ALIGN_TOP = 'top';
    const ALIGN_MIDDLE = 'middle';
    const ALIGN_BOTTOM = 'bottom';

    /**
     * @var $image resource|\GdImage
     */
    private $image;
    /**
     * @var $type int
     */
    private $type;
    /**
     * @var $width int
     */
    private $width;
    /**
     * @var $height int
     */
    private $height;

    public function __clone() {
        $srcInstance = $this->image;
        $this
            ->resetCanvas($this->width, $this->height)
            ->pasteGdImageOn($srcInstance, $this->width, $this->height, 0, 0);
    }

    /**
     * Return the image width
     *
     * @return int Image width
     */
    public function getWidth(): int {
        return $this->width;
    }

    /**
     * Return the image height
     *
     * @return int Image height
     */
    public function getHeight(): int {
        return $this->height;
    }

    /**
     * Return the image type
     * Image type : 1 GIF; 2 JPG; 3 PNG
     *
     * @return int Image type
     */
    public function getType(): int {
        return $this->type;
    }

    /**
     * Return image resource
     * @return resource|\GdImage Image resource
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Return true if $image is a resource or a GDImage instance
     * @param resource|\GdImage A variable to be tested
     * @return bool True if $image is a resource or a GDImage instance
     */
    public static function isGdImage($image): bool {
        return \is_resource($image) || (\is_object($image) && $image instanceof \GdImage);
    }

    /**
     * Return true if image is initialized
     *
     * @return bool Is image initialized
     */
    public function isImageDefined(): bool {
        return static::isGdImage($this->image);
    }

    //===============================================================================================================================
    //============================================================CREATE/DESTROY=====================================================
    //===============================================================================================================================

    /**
     * (Static method) Create a new image with transparent background
     *
     * @param int $width Pixel width of the image
     * @param int $height Pixel height of the image
     * @return Image Return Image instance
     */
    public static function newCanvas(int $width, int $height): Image {
        return (new Image)->resetCanvas($width, $height);
    }

    /**
     * Create a new image with transparent background
     *
     * @param int $width Pixel width of the image
     * @param int $height Pixel height of the image
     * @return $this Fluent interface
     */
    public function resetCanvas(int $width, int $height): Image {
        if (($this->image = \imagecreatetruecolor($width, $height)) === false) {
            $this->resetFields();
            return $this;
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);
        \imagefill($this->image, 0, 0, \imagecolorallocatealpha($this->image, 0, 0, 0, 127));

        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * (Static method) Open image from local path or URL.
     *
     * @param string $path Path to the image file
     * @return Image Return Image instance
     */
    public static function fromPath(string $path): Image {
        return (new Image)->path($path);
    }

    /**
     * Open image from local path or URL.
     *
     * @param string $path Path to the image file
     * @return $this Fluent interface
     */
    public function path(string $path): Image {
        $imageSize = \getimagesize($path);

        if ($imageSize === false) {
            return $this;
        }

        list($this->width, $this->height, $this->type, $attr) = $imageSize;

        switch ($this->type) {
            case 1:
                $this->image = \imagecreatefromgif($path);
                break;
            case 2:
                $this->image = \imagecreatefromjpeg($path);
                break;
            case 3:
                $this->image = \imagecreatefrompng($path);
                break;
        }

        if ($this->image === false) {
            return $this->resetFields();
        }

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * (Static method) Open an uploaded image from html form (using $file["tmp_name"]).
     *
     * @param array $file File array from html form
     * @return Image Return Image instance
     */
    public static function fromForm(array $file): Image {
        return (new Image)->form($file);
    }

    /**
     * Open an uploaded image from html form (using $file["tmp_name"]).
     *
     * @param array $file File array from html form
     * @return $this Fluent interface
     */
    public function form(array $file): Image {
        if (isset($file) && isset($file["name"]) && $file["name"] != "") {
            $this->path($file["tmp_name"]);
        }
        return $this;
    }

    /**
     * (Static method) Create an Image instance from image raw data.
     *
     * @param string $data Raw data of the image
     * @return Image Return Image instance
     */
    public static function fromData(string $data): Image {
        return (new Image)->data($data);
    }

    /**
     * Create an Image instance from image raw data.
     *
     * @param string $data Raw data of the image
     * @return $this Fluent interface
     */
    public function data(string $data): Image {
        if (($this->image = \imagecreatefromstring($data)) === false) {
            return $this->resetFields();
        }

        $this->width = \imagesx($this->image);
        $this->height = \imagesy($this->image);
        $this->type = 3;

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * (Static method) Create an Image instance from base64 image data.
     *
     * @param string $base64 Base64 data of the image
     * @return Image Return Image instance
     */
    public static function fromBase64(string $base64): Image {
        return (new Image)->base64($base64);
    }

    /**
     * Create an Image instance from base64 image data.
     *
     * @param string $base64 Base64 data of the image
     * @return $this Fluent interface
     */
    public function base64(string $base64): Image {
        return $this->data(\base64_decode($base64));
    }

    /**
     * (Static method) Open image from URL with cURL.
     *
     * @param string $url Url of the image file
     * @param array $curlOptions cURL options
     * @param bool $failOnError If true, throw an exception if the url cannot be loaded
     * @return Image Return Image instance
     * @throws \Exception
     */
    public static function fromCurl(string $url, array $curlOptions = [], bool $failOnError = false): Image {
        return (new Image)->curl($url, $curlOptions, $failOnError);
    }

    /**
     * Open image from URL with cURL.
     *
     * @param string $url Url of the image file
     * @param array $curlOptions cURL options
     * @param bool $failOnError If true, throw an exception if the url cannot be loaded
     * @return $this Fluent interface
     * @throws \Exception
     */
    public function curl(string $url, array $curlOptions = [], bool $failOnError = false): Image {
        $defaultCurlOptions = [
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0',
            CURLOPT_REFERER => \strtolower($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 5,
        ];

        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_URL, $url);
        \curl_setopt_array($curl, $defaultCurlOptions + $curlOptions);

        $image = \curl_exec($curl);

        if ($failOnError && \curl_errno($curl)) {
            throw new \Exception(\curl_error($curl));
        }

        \curl_close($curl);

        if ($image === false) {
            return $this->resetFields();
        }

        return $this->data($image);
    }


    /**
     * Destroy image
     *
     * @return $this Fluent interface
     */
    public function destroy(): Image {
        if ($this->isImageDefined()) {
            \imagedestroy($this->image);
        }
        $this->resetFields();
        return $this;
    }

    /**
     * Reset private fields
     *
     * @return $this Fluent interface
     */
    private function resetFields(): Image {
        $this->image = null;
        $this->type = null;
        $this->width = null;
        $this->height = null;
        return $this;
    }




    //===============================================================================================================================
    //============================================================UTILS==============================================================
    //===============================================================================================================================

    /**
     * Convert horizontal `Image::ALIGN_...` to int position.
     *
     * @param int|string $posX Pixel position or `Image::ALIGN_...` constant
     * @param int $width Width of the element to align
     * @return int Horizontal pixel position
     */
    private function convertPosX($posX, int $width = 0): int {
        switch ($posX) {
            case static::ALIGN_LEFT:
                return 0;
            case static::ALIGN_CENTER:
                return \round($this->width / 2 - $width / 2);
            case static::ALIGN_RIGHT:
                return $this->width - $width;
        }
        return \round($posX);
    }

    /**
     * Convert vertical `Image::ALIGN_...` to int position.
     *
     * @param int|string $posY Pixel position or `Image::ALIGN_...` constant
     * @param int $height Height of the element to align
     * @return int Vertical pixel position
     */
    private function convertPosY($posY, int $height = 0): int {
        switch ($posY) {
            case static::ALIGN_TOP:
                return 0;
            case static::ALIGN_MIDDLE:
                return \round($this->height / 2 - $height / 2);
            case static::ALIGN_BOTTOM:
                return $this->height - $height;
        }
        return \round($posY);
    }

    //===============================================================================================================================
    //=================================================RESIZING/ROTATE/TRUNCATE======================================================
    //===============================================================================================================================


    /**
     * Rotate counterclockwise the image
     *
     * @param float $angle Angle in degrees
     * @return $this Fluent interface
     */
    public function rotate(float $angle): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        if (($image = \imagerotate($this->image, Geometry2D::degrees0to360($angle), $this->colorAllocate('#000000FF'), 0)) !== false) {
            $this->image = $image;
            $this->width = \imagesx($this->image);
            $this->height = \imagesy($this->image);
        }
        return $this;
    }

    /**
     * Resize the image keeping the proportions.
     *
     * @param int $width Max width
     * @param int $height Max height
     * @return $this Fluent interface
     */
    public function resizeProportion(int $width, int $height): Image {
        $finalWidth = $width;
        $finalHeight = \round($this->height * $width / $this->width);

        if ($finalHeight > $height) {
            $finalWidth = \round($this->width * $height / $this->height);
            $finalHeight = $height;
        }

        return $this->resize($finalWidth, $finalHeight);
    }

    /**
     * Downscale the image keeping the proportions.
     *
     * @param int $maxWidth Max width
     * @param int $maxHeight Max height
     * @return $this Fluent interface
     */
    public function downscaleProportion(int $maxWidth, int $maxHeight): Image {
        if ($this->width > $maxWidth || $this->height > $maxHeight) {
            if ($this->width > $this->height) {
                $finalHeight = \round($this->height * $maxWidth / $this->width);
                $finalWidth = $maxWidth;

                if ($finalHeight > $maxHeight) {
                    $finalWidth = \round($this->width * $maxHeight / $this->height);
                    $finalHeight = $maxHeight;
                }
            } else {
                $finalWidth = \round($this->width * $maxHeight / $this->height);
                $finalHeight = $maxHeight;
            }
        } else {
            $finalWidth = $this->width;
            $finalHeight = $this->height;
        }

        return $this->resize($finalWidth, $finalHeight);
    }

    /**
     * Resize the image.
     *
     * @param int $width Target width
     * @param int $height Target height
     * @return $this Fluent interface
     */
    public function resize(int $width, int $height): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        // Vermeide unnötiges Resampling
        if ($this->width == $width && $this->height == $height) {
            return $this; // Keine Änderung notwendig
        }

        $image = Image::newCanvas($width, $height)->getImage();

        if (\imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height) !== false) {
            $this->image = $image;
            $this->width = $width;
            $this->height = $height;
        }
        return $this;
    }

    /**
     * Downscale the image keeping the proportions then crop to fit to $width and $height params.
     * Use $posX and $posY to select the cropping zone (You can use `Image::ALIGN_...`).
     *
     * @param int $width Max width
     * @param int $height Max height
     * @param int|string $posX Left crop position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param int|string $posY Top crop position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @return $this Fluent interface
     */
    public function downscaleAndCrop(int $width, int $height, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image {
        if ($this->width < $width) {
            $width = $this->width;
        }
        if ($this->height < $height) {
            $height = $this->height;
        }

        $finalWidth = \round($this->width * $height / $this->height);
        $finalHeight = $height;

        if ($finalWidth < $width) {
            $finalHeight = \round($this->height * $width / $this->width);
            $finalWidth = $width;
        }

        if ($this->downscaleProportion($finalWidth, $finalHeight)) {
            $this->crop($width, $height, $posX, $posY);
        }

        return $this;
    }

    /**
     * Crop to fit to $width and $height params.
     * Use $posX and $posY to select the cropping zone (You can use `Image::ALIGN_...`).
     *
     * @param int $width Target width
     * @param int $height Target height
     * @param int|string $posX Left crop position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param int|string $posY Top crop position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @return $this Fluent interface
     */
    public function crop(int $width, int $height, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        if ($this->width < $width) {
            $width = $this->width;
        }
        if ($this->height < $height) {
            $height = $this->height;
        }

        //==============================================

        $posX = $this->convertPosX($posX, $width);
        $posY = $this->convertPosY($posY, $height);

        //==============================================

        if ($posX < 0) {
            $posX = 0;
        }

        if ($posX + $width > $this->width) {
            $posX = $this->width - $width;
        }

        if ($posY < 0) {
            $posY = 0;
        }

        if ($posY + $height > $this->height) {
            $posY = $this->height - $height;
        }

        //==============================================

        if (
            ($image = \imagecreatetruecolor($width, $height)) !== false &&
            \imagealphablending($image, false) !== false &&
            \imagesavealpha($image, true) !== false &&
            ($transparent = $this->colorAllocate('#000000FF')) !== false &&
            \imagefill($image, 0, 0, $transparent) !== false &&
            \imagecopyresampled($image, $this->image, 0, 0, $posX, $posY, $width, $height, $width, $height) !== false
        ) {
            $this->image = $image;
            $this->width = $width;
            $this->height = $height;
        }

        return $this;
    }

    //===============================================================================================================================
    //==========================================================COLOR================================================================
    //===============================================================================================================================

    /**
     * Format the string color.
     *
     * @param string $stringColor Hexadecimal string color
     * @return string Formatted hexadecimal string color
     */
    private static function formatColor(string $stringColor): string {
        $stringColor = \trim(\str_replace('#', '', $stringColor));
        switch (\mb_strlen($stringColor)) {
            case 3:
                $r = \substr($stringColor, 0, 1);
                $g = \substr($stringColor, 1, 1);
                $b = \substr($stringColor, 2, 1);
                return $r . $r . $g . $g . $b . $b . '00';
            case 6:
                return $stringColor . '00';
            case 8:
                return $stringColor;
            default:
                return '00000000';
        }
    }

    /**
     * Allocate a new color to the image.
     *
     * @param string $color Hexadecimal string color
     * @return int|false Color id
     */
    private function colorAllocate(string $color) {
        $color = static::formatColor($color);
        $red = \hexdec(\substr($color, 0, 2));
        $green = \hexdec(\substr($color, 2, 2));
        $blue = \hexdec(\substr($color, 4, 2));
        $alpha = \floor(\hexdec(\substr($color, 6, 2)) / 2);

        $colorId = \imagecolorexactalpha($this->image, $red, $green, $blue, $alpha);
        if ($colorId === -1) {
            $colorId = \imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha);
        }

        return $colorId;
    }


    //===============================================================================================================================
    //==========================================================PASTE================================================================
    //===============================================================================================================================

    /**
     * Paste the image at $posX and $posY position (You can use `Image::ALIGN_...`).
     *
     * @param Image $image Image instance to be paste on
     * @param int|string $posX Left position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param int|string $posY Top position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @return $this Fluent interface
     */
    public function pasteOn(Image $image, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image {
        if (!$this->isImageDefined() || !$image->isImageDefined()) {
            return $this;
        }

        return $this->pasteGdImageOn($image->getImage(), $image->getWidth(), $image->getHeight(), $posX, $posY);
    }

    /**
     * Paste the image at $posX and $posY position (You can use `Image::ALIGN_...`).
     *
     * @param resource|\GdImage $image Image resource
     * @param int $imageWidth Image width to paste
     * @param int $imageHeight Image height to paste
     * @param int|string $posX Left position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param int|string $posY Top position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @return $this Fluent interface
     */
    public function pasteGdImageOn($image, int $imageWidth, int $imageHeight, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image {
        if (!$this->isImageDefined() || !static::isGdImage($image)) {
            return $this;
        }

        $posX = $this->convertPosX($posX, $imageWidth);
        $posY = $this->convertPosY($posY, $imageHeight);

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);
        \imagecopy($this->image, $image, $posX, $posY, 0, 0, $imageWidth, $imageHeight);
        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * Use a grayscale image (`$mask`) to apply transparency to the image.
     *
     * @param Image $mask Image instance of the grayscale alpha mask
     * @return $this Fluent interface
     */
    public function alphaMask(Image $mask): Image {
        if (!$this->isImageDefined() || !$mask->isImageDefined()) {
            return $this;
        }

        $this->downscaleAndCrop($mask->getWidth(), $mask->getHeight(), static::ALIGN_CENTER, static::ALIGN_MIDDLE);

        if (($newImage = \imagecreatetruecolor($mask->getWidth(), $mask->getHeight())) === false) {
            return $this;
        }
        \imagealphablending($newImage, false);
        \imagesavealpha($newImage, true);

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                $alpha = \floor((255 - \imagecolorat($mask->getImage(), $j, $i) & 0xFF) / 2);
                $red = 0;
                $green = 0;
                $blue = 0;

                if ($alpha != 127) {
                    $rgb = \imagecolorat($this->image, $j, $i);
                    $alpha = 127 - \ceil((127 - (($rgb >> 24) & 0x7F)) * (127 - $alpha) / 127);
                }

                if ($alpha != 127) {
                    $red = ($rgb >> 16) & 0xFF;
                    $green = ($rgb >> 8) & 0xFF;
                    $blue = $rgb & 0xFF;
                }

                $newColor = \imagecolorexactalpha($newImage, $red, $green, $blue, $alpha);
                if ($newColor === -1) {
                    $newColor = \imagecolorallocatealpha($newImage, $red, $green, $blue, $alpha);
                }

                if (!\imagesetpixel($newImage, $j, $i, $newColor)) {
                    return $this;
                }
            }
        }

        \imagedestroy($this->image);
        $this->image = $newImage;

        return $this;
    }

    /**
     * change the image opacity
     *
     * @param float $opacity Opacity (0 to 1)
     * @return $this Fluent interface
     */
    public function setOpacity(float $opacity): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);
        \imagefilter($this->image, IMG_FILTER_COLORIZE, 0, 0, 0, \round(127 * (1 - $opacity)));

        return $this;
    }

    //===============================================================================================================================
    //=========================================================POST PROD=============================================================
    //===============================================================================================================================

    /**
     * Apply a grayscale filter on the image.
     *
     * @return $this Fluent interface
     */
    public function grayscale(): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        \imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Write text on the image.
     *
     * @param string $string Text to be added on the image
     * @param string $fontPath Path to the TTF file
     * @param float $fontSize Font size
     * @param string $color Hexadecimal string color
     * @param float|string $posX Left position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param float|string $posY Top position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @param float|string $anchorX Horizontal anchor of the text. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param float|string $anchorY Vertical anchor of the text. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @param float $rotation Counterclockwise text rotation in degrees
     * @param float $letterSpacing add space between letters
     * @return $this Fluent interface
     */
    public function writeText(string $string, string $fontPath, float $fontSize, string $color = 'ffffff', $posX = 0, $posY = 0, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE, float $rotation = 0, float $letterSpacing = 0, bool $wrap = false): Image {
        $this->writeTextAndGetBoundingBox($string, $fontPath, $fontSize, $color, $posX, $posY, $anchorX, $anchorY, $rotation, $letterSpacing, $wrap);
        return $this;
    }

    /**
     * Write text on the image and get the bounding box of the text in the image.
     *
     * @param string $string Text to be added on the image
     * @param string $fontPath Path to the TTF file
     * @param float $fontSize Font size
     * @param string $color Hexadecimal string color
     * @param float|string $posX Left position in pixel. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param float|string $posY Top position in pixel. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @param float|string $anchorX Horizontal anchor of the text. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param float|string $anchorY Vertical anchor of the text. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @param float $rotation Counterclockwise text rotation in degrees
     * @param float $letterSpacing add space between letters
     * @return array Bounding box positions of the text
     */
    public function writeTextAndGetBoundingBox(string $string, string $fontPath, float $fontSize, string $color = 'ffffff', $posX = 0, $posY = 0, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE, float $rotation = 0, float $letterSpacing = 0, bool $wrap = false): array {
        if (!$this->isImageDefined()) {
            return [];
        }

        $posX = $this->convertPosX($posX);
        $posY = $this->convertPosY($posY);

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return [];
        }

        if (
            $anchorX == static::ALIGN_LEFT ||
            $anchorX == static::ALIGN_CENTER ||
            $anchorX == static::ALIGN_RIGHT ||
            $anchorY == static::ALIGN_TOP ||
            $anchorY == static::ALIGN_MIDDLE ||
            $anchorY == static::ALIGN_BOTTOM
        ) {
            if (
                ($newImg = \imagecreatetruecolor(1, 1)) === false ||
                ($posText = $this->imagettftextWithSpacing($newImg, $fontSize, $rotation, 0, 0, $color, $fontPath, $string, $letterSpacing)) === false
            ) {
                return [];
            }
            \imagedestroy($newImg);

            $xMin = 0;
            $xMax = 0;
            $yMin = 0;
            $yMax = 0;
            for ($i = 0; $i < 8; $i += 2) {
                if ($posText[$i] < $xMin) {
                    $xMin = $posText[$i];
                }
                if ($posText[$i] > $xMax) {
                    $xMax = $posText[$i];
                }
                if ($posText[$i + 1] < $yMin) {
                    $yMin = $posText[$i + 1];
                }
                if ($posText[$i + 1] > $yMax) {
                    $yMax = $posText[$i + 1];
                }
            }

            $sizeWidth = $xMax - $xMin;
            $sizeHeight = $yMax - $yMin;

            switch ($anchorX) {
                case static::ALIGN_LEFT:
                    $posX = $posX - $xMin;
                    break;
                case static::ALIGN_CENTER:
                    $posX = $posX - $sizeWidth / 2 - $xMin;
                    break;
                case static::ALIGN_RIGHT:
                    $posX = $posX - $sizeWidth - $xMin;
                    break;
            }
            switch ($anchorY) {
                case static::ALIGN_TOP:
                    $posY = $posY - $yMin;
                    break;
                case static::ALIGN_MIDDLE:
                    $posY = $posY - $sizeHeight / 2 - $yMin;
                    break;
                case static::ALIGN_BOTTOM:
                    $posY = $posY - $sizeHeight - $yMin;
                    break;
            }
        }

        $posText = $this->imagettftextWithSpacing($this->image, $fontSize, $rotation, $posX, $posY, $color, $fontPath, $string, $letterSpacing);

        if ($posText === false) {
            return [];
        }

        if ($wrap) {
            $imageWidth = \imagesx($this->image);
            $posTextLeft = $this->imagettftextWithSpacing($this->image, $fontSize, $rotation, $posX - $imageWidth, $posY, $color, $fontPath, $string, $letterSpacing);
            $posTextRight = $this->imagettftextWithSpacing($this->image, $fontSize, $rotation, $posX + $imageWidth, $posY, $color, $fontPath, $string, $letterSpacing);
        }   

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return [
            'top-left' => [
                'x' => $posText[6],
                'y' => $posText[7]
            ],
            'top-right' => [
                'x' => $posText[4],
                'y' => $posText[5]
            ],
            'bottom-left' => [
                'x' => $posText[0],
                'y' => $posText[1]
            ],
            'bottom-right' => [
                'x' => $posText[2],
                'y' => $posText[3]
            ],
            'baseline' => [
                'x' => $posX,
                'y' => $posY
            ]
        ];
    }

    /**
     * @param $image
     * @param float $size
     * @param float $angle
     * @param float $x
     * @param float $y
     * @param int $color
     * @param string $font
     * @param string $text
     * @param float $spacing
     * @return array|false
     */
    private function imagettftextWithSpacing($image, float $size, float $angle, float $x, float $y, int $color, string $font, string $text, float $spacing = 0) {
        if ($spacing == 0) {
            return \imagettftext($image, $size, $angle, \round($x), \round($y), $color, $font, $text);
        } else {
            $length = \mb_strlen($text);

            if ($length == 0) {
                return false;
            }

            $letterPos = ['x' => $x, 'y' => $y];
            $textWidth = $spacing * ($length - 1);
            $top = 0;
            $bottom = 0;

            for ($i = 0; $i < $length; ++$i) {
                $char = \mb_substr($text, $i, 1);
                \imagettftext($image, $size, $angle, \round($letterPos['x']), \round($letterPos['y']), $color, $font, $char);
                $bbox = \imagettfbbox($size, 0, $font, $char);
                $letterPos = Geometry2D::getDstXY($letterPos['x'], $letterPos['y'], $angle, $spacing + $bbox[2]);

                $textWidth += $bbox[2];
                if ($top > $bbox[5]) {
                    $top = $bbox[5];
                }
                if ($bottom < $bbox[1]) {
                    $bottom = $bbox[1];
                }
            }

            $bottomLeft = Geometry2D::getDstXY($x, $y, $angle - 90, $bottom);
            $bottomRight = Geometry2D::getDstXY($bottomLeft['x'], $bottomLeft['y'], $angle, $textWidth);
            $topLeft = Geometry2D::getDstXY($x, $y, $angle + 90, \abs($top));
            $topRight = Geometry2D::getDstXY($topLeft['x'], $topLeft['y'], $angle, $textWidth);

            return [$bottomLeft['x'], $bottomLeft['y'], $bottomRight['x'], $bottomRight['y'], $topRight['x'], $topRight['y'], $topLeft['x'], $topLeft['y']];
        }
    }

    /**
     * Draw a rectangle.
     *
     * @param int $left Left position in pixel
     * @param int $top Top position in pixel
     * @param int $right Right position in pixel
     * @param int $bottom Bottom position in pixel
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawRectangle(int $left, int $top, int $right, int $bottom, string $color): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if (($bottom - $top) <= 1.5) {
            \imageline($this->image, $left, $top, $right, $top, $color);
        } elseif (($right - $left) <= 1.5) {
            \imageline($this->image, $left, $top, $left, $bottom, $color);
        } else {
            \imagefilledrectangle($this->image, $left, $top, $right, $bottom, $color);
        }
        return $this;
    }

    /**
     * Draw a polygon.
     *
     * @param int[] $points Array of polygon's points [x1, y1, x2, y2, x3, y3...]
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawPolygon(array $points, string $color = '000000', $antialias = false): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        if ($antialias) {
            \imageantialias($this->image, true);
            if (PHP_MAJOR_VERSION >= 8) {
                \imagepolygon($this->image, $points, $color);
            } else {
                \imagepolygon($this->image, $points, \count($points) / 2, $color);
            }
        }

        if (PHP_MAJOR_VERSION >= 8) {
            \imagefilledpolygon($this->image, $points, $color);
        } else {
            \imagefilledpolygon($this->image, $points, \count($points) / 2, $color);
        }

        if ($antialias) {
            \imageantialias($this->image, false);
        }

        return $this;
    }

    /**
     * Draw a Line from `$originX, $originY` to `$dstX, $dstY`.
     *
     * @param int $originX Horizontal start position in pixel
     * @param int $originY Vertical start position in pixel
     * @param int $dstX Horizontal destination in pixel
     * @param int $dstY Vertical destination in pixel
     * @param int $weight Line weight in pixel
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawLine(int $originX, int $originY, int $dstX, int $dstY, int $weight, string $color = '#000000'): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawLineWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $color);
    }

    /**
     * Draw a line using angle and length.
     *
     * @param int $originX Horizontal start position in pixel
     * @param int $originY Vertical start position in pixel
     * @param float $angle Counterclockwise angle in degrees
     * @param float $length Line length in pixel
     * @param int $weight Line weight in pixel
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawLineWithAngle(int $originX, int $originY, float $angle, float $length, int $weight, string $color = '#000000'): Image {
        $angle = Geometry2D::degrees0to360($angle);

        $points1 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle - 90), \floor($weight / 2));
        $points2 = Geometry2D::getDstXY($points1['x'], $points1['y'], $angle, $length);
        $points4 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle + 90), \floor($weight / 2));
        $points3 = Geometry2D::getDstXY($points4['x'], $points4['y'], $angle, $length);

        return $this->drawPolygon(
            [
                \round($points1['x']),
                \round($points1['y']),
                \round($points2['x']),
                \round($points2['y']),
                \round($points3['x']),
                \round($points3['y']),
                \round($points4['x']),
                \round($points4['y'])
            ],
            $color,
            true
        );
    }

    /**
     * Draw an arrow with angle and length.
     *
     * @param int $originX Horizontal start position in pixel
     * @param int $originY Vertical start position in pixel
     * @param float $angle Counterclockwise angle in degrees
     * @param float $length Line length in pixel
     * @param int $weight Line weight in pixel
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawArrowWithAngle(int $originX, int $originY, float $angle, float $length, int $weight, string $color = '#000000'): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $headOrigin = Geometry2D::getDstXYRounded($originX, $originY, Geometry2D::degrees0to360($angle), \round($length - $weight / 2));
        $this->drawLineWithAngle($headOrigin['x'], $headOrigin['y'], Geometry2D::degrees0to360($angle + 150), \round($length / 10), $weight, $color);
        $this->drawLineWithAngle($headOrigin['x'], $headOrigin['y'], Geometry2D::degrees0to360($angle - 150), \round($length / 10), $weight, $color);
        return $this->drawLineWithAngle($originX, $originY, $angle, $length, $weight, $color);
    }


    /**
     * Draw and arrow from `$originX, $originY` to `$dstX, $dstY`.
     *
     * @param int $originX Horizontal start position in pixel
     * @param int $originY Vertical start position in pixel
     * @param int $dstX Horizontal destination in pixel
     * @param int $dstY Vertical destination in pixel
     * @param int $weight Line weight in pixel
     * @param string $color Hexadecimal string color
     * @return $this Fluent interface
     */
    public function drawArrow(int $originX, int $originY, int $dstX, int $dstY, int $weight, string $color = '#000000'): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawArrowWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $color);
    }

    /**
     * Draw a circle.
     *
     * @param int $posX Left position of the circle in pixel
     * @param int $posY Top position of the circle in pixel
     * @param int $diameter Circle diameter in pixel
     * @param string $color Hexadecimal string color
     * @param string $anchorX Horizontal anchor of the text. You can use `Image::ALIGN_LEFT`, `Image::ALIGN_CENTER`, `Image::ALIGN_RIGHT`
     * @param string $anchorY Vertical anchor of the text. You can use `Image::ALIGN_TOP`, `Image::ALIGN_MIDDLE`, `Image::ALIGN_BOTTOM`
     * @return $this Fluent interface
     */
    public function drawCircle(int $posX, int $posY, int $diameter, string $color = '#FFFFFF', string $anchorX = Image::ALIGN_CENTER, string $anchorY = Image::ALIGN_MIDDLE): Image {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        switch ($anchorX) {
            case static::ALIGN_LEFT:
                $posX = \round($posX + $diameter / 2);
                break;
            case static::ALIGN_CENTER:
                break;
            case static::ALIGN_RIGHT:
                $posX = \round($posX - $diameter / 2);
                break;
        }

        switch ($anchorY) {
            case static::ALIGN_TOP:
                $posY = \round($posY + $diameter / 2);
                break;
            case static::ALIGN_MIDDLE:
                break;
            case static::ALIGN_BOTTOM:
                $posY = \round($posY - $diameter / 2);
                break;
        }

        \imagefilledellipse($this->image, $posX, $posY, $diameter, $diameter, $color);
        return $this;
    }

    //===============================================================================================================================
    //=========================================================GET PICTURE===========================================================
    //===============================================================================================================================

    /**
     * Save the image to PNG file.
     *
     * @param string $path Path to the PNG image file
     * @return bool return true if success
     */
    public function savePNG(string $path): bool {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagepng($this->image, $path);
    }

    /**
     * Save the image to JPG file.
     *
     * @param string $path Path to the JPG image file
     * @param int $quality JPG quality : 0 to 100
     * @return bool return true if success
     */
    public function saveJPG(string $path, int $quality = -1): bool {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagejpeg($this->image, $path, $quality);
    }

    /**
     * Save the image to GIF file.
     *
     * @param string $path Path to the GIF image file
     * @return bool return true if success
     */
    public function saveGIF(string $path): bool {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagegif($this->image, $path);
    }

    /**
     * Display in PNG format.
     */
    public function displayPNG() {
        if ($this->isImageDefined()) {
            \imagepng($this->image);
        }
    }

    /**
     * Display in JPG format.
     *
     * @param int $quality JPG quality : 0 to 100
     */
    public function displayJPG(int $quality = -1) {
        if ($this->isImageDefined()) {
            \imagejpeg($this->image, null, $quality);
        }
    }

    /**
     * Display in GIF format.
     */
    public function displayGIF() {
        if ($this->isImageDefined()) {
            \imagegif($this->image);
        }
    }

    /**
     * Get image raw data
     *
     * @param callable $imgFunction Image function to be called
     * @return string Data
     */
    private function getData(callable $imgFunction): string {
        if (!$this->isImageDefined()) {
            return '';
        }

        \ob_start();
        $imgFunction();
        $imageData = \ob_get_contents();
        \ob_end_clean();

        return $imageData;
    }

    /**
     * Get image PNG raw data
     *
     * @return string Data
     */
    public function getDataPNG(): string {
        return $this->getData(function () {
            $this->displayPNG();
        });
    }

    /**
     * Get image JPG raw data
     *
     * @param int $quality JPG quality : 0 to 100
     * @return string Data
     */
    public function getDataJPG(int $quality = -1): string {
        return $this->getData(function () use ($quality) {
            $this->displayJPG($quality);
        });
    }

    /**
     * Get image GIF raw data
     *
     * @return string Data
     */
    public function getDataGIF(): string {
        return $this->getData(function () {
            $this->displayGIF();
        });
    }

    /**
     * Get image PNG base64 data
     *
     * @return string Data
     */
    public function getBase64PNG(): string {
        return \base64_encode($this->getDataPNG());
    }

    /**
     * Get image JPG base64 data
     *
     * @param int $quality JPG quality : 0 to 100
     * @return string Data
     */
    public function getBase64JPG(int $quality = -1): string {
        return \base64_encode($this->getDataJPG($quality));
    }

    /**
     * Get image GIF base64 data
     *
     * @return string Data
     */
    public function getBase64GIF(): string {
        return \base64_encode($this->getDataGIF());
    }

    /**
     * Get image PNG base64 data for <img src=""> tag.
     *
     * @return string Data
     */
    public function getBase64SourcePNG(): string {
        return 'data:image/png;base64,' . $this->getBase64PNG();
    }

    /**
     * Get image JPG base64 data for <img src=""> tag.
     *
     * @param int $quality JPG quality : 0 to 100
     * @return string Data
     */
    public function getBase64SourceJPG(int $quality = -1): string {
        return 'data:image/jpeg;base64,' . $this->getBase64JPG($quality);
    }

    /**
     * Get image GIF base64 data for <img src=""> tag.
     *
     * @return string Data
     */
    public function getBase64SourceGIF(): string {
        return 'data:image/gif;base64,' . $this->getBase64GIF();
    }
}
