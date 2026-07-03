<?php
declare(strict_types=1);

namespace ImageCropper\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ImageCropper\Controller\Component\SaveImageComponent;
use Laminas\Diactoros\UploadedFile;

/**
 * ImageCropper\Controller\Component\SaveImageComponent Test Case
 */
class SaveImageComponentTest extends TestCase
{
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

    public function testProcessCropsUploadedFileInPlace(): void
    {
        $path = $this->createImage(100, 100);
        $component = $this->componentFor([
            'image_crop_x' => '10',
            'image_crop_y' => '10',
            'image_crop_width' => '40',
            'image_crop_height' => '30',
        ], new UploadedFile($path, filesize($path), UPLOAD_ERR_OK, 'photo.png', 'image/png'));

        $this->assertTrue($component->process('image'));
        [$width, $height] = getimagesize($path);
        $this->assertSame(40, $width);
        $this->assertSame(30, $height);
    }

    public function testProcessReturnsFalseWithoutCropData(): void
    {
        $path = $this->createImage(80, 80);
        $component = $this->componentFor(
            [],
            new UploadedFile($path, filesize($path), UPLOAD_ERR_OK, 'photo.png', 'image/png'),
        );

        $this->assertFalse($component->process('image'));
        [$width] = getimagesize($path);
        $this->assertSame(80, $width, 'File must be left untouched.');
    }

    public function testProcessReturnsFalseWhenNoFileUploaded(): void
    {
        $path = $this->createImage(80, 80);
        $component = $this->componentFor(
            ['image_crop_x' => '0', 'image_crop_y' => '0', 'image_crop_width' => '10', 'image_crop_height' => '10'],
            new UploadedFile($path, 0, UPLOAD_ERR_NO_FILE, '', ''),
        );

        $this->assertFalse($component->process('image'));
    }

    /**
     * Build a SaveImage component bound to a controller carrying the given
     * post data and uploaded file.
     *
     * @param array<string, string> $post Parsed body data.
     * @param \Laminas\Diactoros\UploadedFile $file Uploaded file for the `image` field.
     */
    protected function componentFor(array $post, UploadedFile $file): SaveImageComponent
    {
        $request = (new ServerRequest(['post' => $post]))
            ->withUploadedFiles(['image' => $file]);
        $controller = new Controller($request);

        return new SaveImageComponent(new ComponentRegistry($controller));
    }

    protected function createImage(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 200, 90));
        $path = tempnam(sys_get_temp_dir(), 'ic_') . '.png';
        imagepng($image, $path);
        imagedestroy($image);
        $this->tempFiles[] = $path;

        return $path;
    }
}
