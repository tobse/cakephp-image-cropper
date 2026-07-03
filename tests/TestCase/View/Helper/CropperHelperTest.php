<?php
declare(strict_types=1);

namespace ImageCropper\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use ImageCropper\View\Helper\CropperHelper;

/**
 * ImageCropper\View\Helper\CropperHelper Test Case
 */
class CropperHelperTest extends TestCase
{
    protected View $view;

    protected CropperHelper $Form;

    public function setUp(): void
    {
        parent::setUp();
        $this->view = new View();
        $this->Form = new CropperHelper($this->view);
    }

    public function tearDown(): void
    {
        unset($this->Form, $this->view);
        parent::tearDown();
    }

    public function testControlRendersCropperFileInput(): void
    {
        $output = $this->Form->control('image', ['type' => 'cropper']);

        $this->assertStringContainsString('type="file"', $output);
        $this->assertStringContainsString('name="image"', $output);
        $this->assertStringContainsString('data-image-cropper="1"', $output);
        $this->assertStringContainsString('accept="image/*"', $output);
    }

    public function testControlRendersHiddenCropFields(): void
    {
        $output = $this->Form->control('image', ['type' => 'cropper']);

        foreach (['image_crop_x', 'image_crop_y', 'image_crop_width', 'image_crop_height'] as $name) {
            $this->assertStringContainsString('name="' . $name . '"', $output);
        }
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testAspectRatioFromExplicitOption(): void
    {
        $output = $this->Form->control('image', [
            'type' => 'cropper',
            'options' => ['aspectRatio' => '16/9'],
        ]);

        $this->assertStringContainsString('data-cropper-aspect-ratio="16/9"', $output);
    }

    public function testAspectRatioDerivedFromWidthAndHeight(): void
    {
        $output = $this->Form->control('image', [
            'type' => 'cropper',
            'options' => ['width' => 600, 'height' => 500],
        ]);

        $this->assertStringContainsString('data-cropper-aspect-ratio="600/500"', $output);
    }

    public function testModalTitleAndPreviewOptions(): void
    {
        $output = $this->Form->control('image', [
            'type' => 'cropper',
            'options' => ['modalTitle' => 'Crop header', 'preview' => false],
        ]);

        $this->assertStringContainsString('data-cropper-modal-title="Crop header"', $output);
        $this->assertStringContainsString('data-cropper-preview="0"', $output);
    }

    public function testDataAttributesReferenceHiddenFieldIds(): void
    {
        $output = $this->Form->control('image', ['type' => 'cropper']);

        $this->assertStringContainsString('data-cropper-x="image-crop-x"', $output);
        $this->assertStringContainsString('data-cropper-height="image-crop-height"', $output);
    }

    public function testAssetsAreQueuedIntoViewBlocks(): void
    {
        $this->Form->control('image', ['type' => 'cropper']);

        $this->assertStringContainsString('image-cropper.css', $this->view->fetch('css'));
        $this->assertStringContainsString('image-cropper.js', $this->view->fetch('script'));
    }

    public function testStandardControlTypesStillWork(): void
    {
        $output = $this->Form->control('title', ['type' => 'text']);

        $this->assertStringContainsString('type="text"', $output);
        $this->assertStringContainsString('name="title"', $output);
    }
}
