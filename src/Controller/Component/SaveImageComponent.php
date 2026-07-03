<?php
declare(strict_types=1);

namespace ImageCropper\Controller\Component;

use Cake\Controller\Component;
use ImageCropper\Utility\ImageCropper;
use Psr\Http\Message\UploadedFileInterface;

/**
 * SaveImage component.
 *
 * Reads the crop rectangle that {@see \ImageCropper\View\Helper\CropperHelper}
 * rendered as hidden fields and crops the freshly uploaded file *in place*
 * before the application persists it. This keeps the component agnostic of the
 * upload/storage strategy the host app uses (Proffer, Josegonzalez/Upload,
 * plain move_uploaded_file, ...).
 *
 * Usage inside a controller:
 * ```
 * $this->loadComponent('ImageCropper.SaveImage');
 * // ...
 * $this->SaveImage->process('image');
 * $entity = $this->Table->patchEntity($entity, $this->request->getData());
 * ```
 */
class SaveImageComponent extends Component
{
    /**
     * Default configuration.
     *
     * - `suffixes`: hidden field name suffixes appended to the file field name.
     * - `quality`: JPEG/WEBP output quality forwarded to the cropper.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'suffixes' => [
            'x' => '_crop_x',
            'y' => '_crop_y',
            'width' => '_crop_width',
            'height' => '_crop_height',
        ],
        'quality' => 90,
    ];

    /**
     * Crop the uploaded file for the given field using the posted crop data.
     *
     * @param string $field The name of the file upload field (e.g. `image`).
     * @return bool True when a file was cropped, false when nothing had to be done.
     */
    public function process(string $field): bool
    {
        $request = $this->getController()->getRequest();
        $file = $request->getUploadedFile($field);
        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return false;
        }

        /** @var array<string, string> $suffixes */
        $suffixes = $this->getConfig('suffixes');
        $x = $request->getData($field . $suffixes['x']);
        $y = $request->getData($field . $suffixes['y']);
        $width = $request->getData($field . $suffixes['width']);
        $height = $request->getData($field . $suffixes['height']);
        if ($x === null || $y === null || $width === null || $height === null) {
            return false;
        }

        $uri = $file->getStream()->getMetadata('uri');
        if (!is_string($uri) || $uri === '') {
            return false;
        }

        $cropper = new ImageCropper((int)$this->getConfig('quality'));
        $cropper->crop($uri, (int)$x, (int)$y, (int)$width, (int)$height);

        return true;
    }
}
