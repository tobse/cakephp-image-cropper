<?php
declare(strict_types=1);

namespace ImageCropper\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use ImageCropper\Utility\ImageCropper;
use InvalidArgumentException;

/**
 * ImageCropper\Utility\ImageCropper Test Case
 */
class ImageCropperTest extends TestCase
{
    protected ImageCropper $cropper;

    /**
     * @var array<int, string>
     */
    protected array $tempFiles = [];

    public function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required for these tests.');
        }
        $this->cropper = new ImageCropper();
    }

    public function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    public function testCropInPlaceProducesRequestedDimensions(): void
    {
        $path = $this->createImage(100, 80, 'png');

        $result = $this->cropper->crop($path, 10, 20, 40, 30);

        $this->assertSame($path, $result);
        [$width, $height] = getimagesize($path);
        $this->assertSame(40, $width);
        $this->assertSame(30, $height);
    }

    public function testCropWritesToSeparateDestination(): void
    {
        $source = $this->createImage(120, 120, 'jpg');
        $dest = $this->tempPath('jpg');

        $this->cropper->crop($source, 0, 0, 50, 60, $dest);

        [$sw, $sh] = getimagesize($source);
        $this->assertSame(120, $sw, 'Source must stay untouched.');
        $this->assertSame(120, $sh);
        [$dw, $dh] = getimagesize($dest);
        $this->assertSame(50, $dw);
        $this->assertSame(60, $dh);
    }

    public function testCropClampsRectangleToImageBounds(): void
    {
        $path = $this->createImage(60, 60, 'png');

        $this->cropper->crop($path, 40, 40, 100, 100);

        [$width, $height] = getimagesize($path);
        $this->assertSame(20, $width);
        $this->assertSame(20, $height);
    }

    public function testCropPreservesPngTransparency(): void
    {
        $path = $this->createImage(40, 40, 'png');

        $this->cropper->crop($path, 0, 0, 20, 20);

        $info = getimagesize($path);
        $this->assertSame(IMAGETYPE_PNG, $info[2]);
    }

    public function testInvalidDimensionsThrow(): void
    {
        $path = $this->createImage(40, 40, 'png');

        $this->expectException(InvalidArgumentException::class);
        $this->cropper->crop($path, 0, 0, 0, 10);
    }

    public function testMissingSourceThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cropper->crop('/does/not/exist.png', 0, 0, 10, 10);
    }

    public function testNonImageFileThrows(): void
    {
        $path = $this->tempPath('png');
        file_put_contents($path, 'not an image');

        $this->expectException(InvalidArgumentException::class);
        $this->cropper->crop($path, 0, 0, 10, 10);
    }

    /**
     * Create a solid-colour test image and track it for cleanup.
     */
    protected function createImage(int $width, int $height, string $format): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 120, 60, 200));
        $path = $this->tempPath($format);
        if ($format === 'jpg') {
            imagejpeg($image, $path);
        } else {
            imagepng($image, $path);
        }
        imagedestroy($image);

        return $path;
    }

    protected function tempPath(string $format): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ic_') . '.' . $format;
        $this->tempFiles[] = $path;

        return $path;
    }
}
