<?php
declare(strict_types=1);

namespace ImageCropper\Utility;

use InvalidArgumentException;
use RuntimeException;

/**
 * Small, dependency-free image cropper built on top of the GD extension.
 *
 * The class takes a source image, a crop rectangle expressed in *natural*
 * (original) pixels - exactly what the front-end reports via Cropper.js - and
 * writes the cropped result back to disk while preserving the original image
 * format (JPEG, PNG, GIF and WEBP including transparency).
 */
class ImageCropper
{
    /**
     * Image types this utility is able to read and write.
     *
     * @var array<int, int>
     */
    protected const SUPPORTED_TYPES = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_GIF,
        IMAGETYPE_WEBP,
    ];

    /**
     * JPEG/WEBP output quality (0-100).
     *
     * @var int
     */
    protected int $quality;

    /**
     * @param int $quality JPEG/WEBP output quality (0-100), defaults to 90.
     */
    public function __construct(int $quality = 90)
    {
        $this->quality = max(0, min(100, $quality));
    }

    /**
     * Crop the given source image and write the result to $destPath.
     *
     * When $destPath is null the source file is overwritten in place, which is
     * the common case for the file that a form upload just moved into a temp
     * location.
     *
     * @param string $sourcePath Absolute path to the readable source image.
     * @param int $x Left offset of the crop rectangle in natural pixels.
     * @param int $y Top offset of the crop rectangle in natural pixels.
     * @param int $width Width of the crop rectangle in natural pixels.
     * @param int $height Height of the crop rectangle in natural pixels.
     * @param string|null $destPath Optional destination path; defaults to $sourcePath.
     * @return string The path the cropped image was written to.
     * @throws \InvalidArgumentException When the input is missing or unsupported.
     * @throws \RuntimeException When GD fails to process the image.
     */
    public function crop(
        string $sourcePath,
        int $x,
        int $y,
        int $width,
        int $height,
        ?string $destPath = null,
    ): string {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new InvalidArgumentException(sprintf('Source image "%s" is not readable.', $sourcePath));
        }
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Crop width and height must be greater than zero.');
        }

        $info = getimagesize($sourcePath);
        if ($info === false) {
            throw new InvalidArgumentException(sprintf('File "%s" is not a valid image.', $sourcePath));
        }
        [$sourceWidth, $sourceHeight, $type] = $info;
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported image type for "%s".', $sourcePath));
        }

        // Clamp the crop rectangle to the image bounds so slightly out-of-range
        // coordinates coming from the browser never produce a corrupt canvas.
        $x = max(0, min($x, $sourceWidth - 1));
        $y = max(0, min($y, $sourceHeight - 1));
        $width = min($width, $sourceWidth - $x);
        $height = min($height, $sourceHeight - $y);

        $source = $this->createFromFile($sourcePath, $type);
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            imagedestroy($source);
            throw new RuntimeException('Unable to allocate the crop canvas.');
        }
        $this->preserveTransparency($canvas, $type);

        if (!imagecopy($canvas, $source, 0, 0, $x, $y, $width, $height)) {
            imagedestroy($source);
            imagedestroy($canvas);
            throw new RuntimeException('Cropping the image failed.');
        }

        $destPath ??= $sourcePath;
        $this->writeToFile($canvas, $destPath, $type);

        imagedestroy($source);
        imagedestroy($canvas);

        return $destPath;
    }

    /**
     * Create a GD image resource from a file for the given image type.
     *
     * @param string $path Source path.
     * @param int $type One of the IMAGETYPE_* constants.
     * @return \GdImage
     */
    protected function createFromFile(string $path, int $type): \GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };
        if ($image === false) {
            throw new RuntimeException(sprintf('GD could not read the image "%s".', $path));
        }

        return $image;
    }

    /**
     * Keep an alpha channel for formats that support transparency.
     *
     * @param \GdImage $canvas The destination canvas.
     * @param int $type One of the IMAGETYPE_* constants.
     * @return void
     */
    protected function preserveTransparency(\GdImage $canvas, int $type): void
    {
        if (!in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return;
        }
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $transparent);
        }
    }

    /**
     * Write a GD image resource to disk using the encoder for the given type.
     *
     * @param \GdImage $canvas The image to write.
     * @param string $path Destination path.
     * @param int $type One of the IMAGETYPE_* constants.
     * @return void
     * @throws \RuntimeException When encoding fails.
     */
    protected function writeToFile(\GdImage $canvas, string $path, int $type): void
    {
        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($canvas, $path, $this->quality),
            IMAGETYPE_PNG => imagepng($canvas, $path),
            IMAGETYPE_GIF => imagegif($canvas, $path),
            IMAGETYPE_WEBP => imagewebp($canvas, $path, $this->quality),
            default => false,
        };
        if ($ok === false) {
            throw new RuntimeException(sprintf('Writing the cropped image to "%s" failed.', $path));
        }
    }
}
