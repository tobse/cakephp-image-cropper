<?php
declare(strict_types=1);

namespace ImageCropper\View\Helper;

use Cake\View\Helper\FormHelper;

/**
 * Cropper form helper.
 *
 * Drop-in replacement for the core {@see \Cake\View\Helper\FormHelper} that adds
 * a `cropper` control type. Load it aliased as `Form` in your `AppView`:
 *
 * ```
 * $this->loadHelper('Form', ['className' => 'ImageCropper.Cropper']);
 * ```
 *
 * and then render a cropping upload field with the familiar syntax:
 *
 * ```
 * echo $this->Form->control('image', [
 *     'type' => 'cropper',
 *     'options' => ['aspectRatio' => '16/9', 'modalTitle' => 'Crop the header'],
 * ]);
 * ```
 *
 * The control renders a native file input plus four hidden fields that receive
 * the selected crop rectangle (in natural pixels). All modal/preview UI is
 * created by the bundled front-end script, so no markup framework is required.
 */
class CropperHelper extends FormHelper
{
    /**
     * Hidden field name suffixes. Must match SaveImageComponent::$_defaultConfig.
     *
     * @var array<string, string>
     */
    protected const SUFFIXES = [
        'x' => '_crop_x',
        'y' => '_crop_y',
        'width' => '_crop_width',
        'height' => '_crop_height',
    ];

    /**
     * Whether the plugin assets were already queued for this request.
     *
     * @var bool
     */
    protected bool $assetsIncluded = false;

    /**
     * Render the cropper widget (file input + hidden crop fields).
     *
     * Invoked by {@see \Cake\View\Helper\FormHelper::control()} when the control
     * `type` is `cropper`, so `control()` still wraps it with the usual label
     * and input container.
     *
     * @param string $fieldName Name of the file field, e.g. `image`.
     * @param array<string, mixed> $options Control options; cropper settings go
     *   under the `options` key (`aspectRatio`, `width`, `height`, `modalTitle`,
     *   `preview`).
     * @return string
     */
    public function cropper(string $fieldName, array $options = []): string
    {
        $cropperOptions = (array)($options['options'] ?? []);
        unset($options['options'], $options['labelOptions'], $options['type']);

        $this->includeAssets();

        $ids = [];
        $hidden = '';
        foreach (self::SUFFIXES as $key => $suffix) {
            $name = $fieldName . $suffix;
            $id = $this->_domId($name);
            $ids[$key] = $id;
            $this->unlockField($name);
            $hidden .= $this->hidden($name, ['id' => $id, 'value' => '']);
        }

        $fileOptions = $options + [
            'type' => 'file',
            'accept' => $cropperOptions['accept'] ?? 'image/*',
            'data-image-cropper' => '1',
            'data-cropper-x' => $ids['x'],
            'data-cropper-y' => $ids['y'],
            'data-cropper-width' => $ids['width'],
            'data-cropper-height' => $ids['height'],
        ];

        $aspectRatio = $this->resolveAspectRatio($cropperOptions);
        if ($aspectRatio !== null) {
            $fileOptions['data-cropper-aspect-ratio'] = $aspectRatio;
        }
        if (isset($cropperOptions['modalTitle'])) {
            $fileOptions['data-cropper-modal-title'] = (string)$cropperOptions['modalTitle'];
        }
        if (array_key_exists('preview', $cropperOptions)) {
            $fileOptions['data-cropper-preview'] = $cropperOptions['preview'] ? '1' : '0';
        }

        return $this->file($fieldName, $fileOptions) . $hidden;
    }

    /**
     * Normalise the aspect ratio option to a `width/height` string.
     *
     * Accepts an explicit `aspectRatio` (float or `"16/9"` string) or a
     * `width`/`height` pair from which the ratio is derived.
     *
     * @param array<string, mixed> $cropperOptions Cropper settings.
     * @return string|null
     */
    protected function resolveAspectRatio(array $cropperOptions): ?string
    {
        if (isset($cropperOptions['aspectRatio'])) {
            return (string)$cropperOptions['aspectRatio'];
        }
        if (isset($cropperOptions['width'], $cropperOptions['height'])) {
            return (int)$cropperOptions['width'] . '/' . (int)$cropperOptions['height'];
        }

        return null;
    }

    /**
     * Queue the compiled CSS and JS from the plugin webroot exactly once.
     *
     * Relies on the `css`/`script` view blocks, so make sure your layout echoes
     * `$this->fetch('css')` and `$this->fetch('script')`.
     *
     * @return void
     */
    public function includeAssets(): void
    {
        if ($this->assetsIncluded || !$this->getConfig('autoInclude', true)) {
            return;
        }
        $this->assetsIncluded = true;
        $this->Html->css('ImageCropper.image-cropper', ['block' => true]);
        $this->Html->script('ImageCropper.image-cropper', ['block' => true, 'defer' => true]);
    }
}
