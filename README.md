# CakePHP Image Cropper

[![CI](https://github.com/tobse/cakephp-image-cropper/actions/workflows/ci.yml/badge.svg)](https://github.com/tobse/cakephp-image-cropper/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/tobse/cakephp-image-cropper/v/stable)](https://packagist.org/packages/tobse/cakephp-image-cropper)
[![License](https://poser.pugx.org/tobse/cakephp-image-cropper/license)](LICENSE)

A small, self-contained **CakePHP 5** plugin that adds an interactive image
cropper to any upload field. Selecting a file opens a modal powered by
[Cropper.js](https://fengyuanchen.github.io/cropperjs/); the chosen rectangle is
posted alongside the file and the image is cropped server-side using PHP's GD
extension — no external image library required.

## Features

- **One-line FormHelper integration** — `$this->Form->control('image', ['type' => 'cropper'])`.
- **Framework-free front-end** — the bundled script ships its own modal and
  bundles Cropper.js; no jQuery, Vue, Bootstrap or Lodash needed on the page.
- **Server-side cropping with GD only** — no `intervention/image` dependency.
- **Storage-agnostic** — crops the uploaded temp file *in place*, so it works
  with Proffer, josegonzalez/upload, or a plain `move_uploaded_file()`.
- **Vite asset pipeline** — pre-built assets are committed, so the plugin works
  without a Node toolchain; rebuild them only if you customise the sources.

## Requirements

- PHP >= 8.1 with the `gd` extension
- CakePHP >= 5.0

## Installation

Install the plugin via [Composer](https://getcomposer.org):

```bash
composer require tobse/cakephp-image-cropper
```

Load the plugin in your application's `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('ImageCropper');
}
```

or from the command line:

```bash
bin/cake plugin load ImageCropper
```

The compiled assets are served automatically by CakePHP's asset middleware from
`/image_cropper/js/image-cropper.js` and `/image_cropper/css/image-cropper.css`.
For best performance in production, symlink the plugin assets:

```bash
bin/cake plugin assets symlink ImageCropper
```

## Setup

### 1. Load the helper

Alias the plugin's `CropperHelper` as `Form` in your `src/View/AppView.php` so
the new control type is available everywhere:

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadHelper('Form', ['className' => 'ImageCropper.Cropper']);
    $this->loadHelper('Html'); // required for asset injection
}
```

`CropperHelper` extends the core `FormHelper`, so every existing form keeps
working unchanged.

### 2. Make sure the layout outputs the asset blocks

The helper appends its CSS and JS to the `css` and `script` view blocks, so your
layout must fetch them (the default CakePHP layout already does):

```php
<?= $this->fetch('css') ?>
<!-- ... -->
<?= $this->fetch('script') ?>
```

## Usage

### In the template

```php
echo $this->Form->create($entity, ['type' => 'file']);
echo $this->Form->control('image', [
    'type' => 'cropper',
    'options' => [
        'aspectRatio' => '16/9',
        'modalTitle' => 'Crop the header image',
    ],
]);
echo $this->Form->button('Save');
echo $this->Form->end();
```

This renders a native file input, four hidden fields
(`image_crop_x`, `image_crop_y`, `image_crop_width`, `image_crop_height`) and
queues the front-end assets. The modal, preview and buttons are created by the
script at runtime.

### Cropper options

Pass cropper settings under the `options` key:

| Option        | Type           | Default      | Description                                             |
|---------------|----------------|--------------|---------------------------------------------------------|
| `aspectRatio` | string\|float  | free         | Fixed ratio, e.g. `'16/9'`, `'1'` or `1.5`.             |
| `width`       | int            | —            | Alternative to `aspectRatio`; combined with `height`.   |
| `height`      | int            | —            | Alternative to `aspectRatio`; combined with `width`.    |
| `modalTitle`  | string         | `Crop image` | Heading shown in the cropping modal.                    |
| `preview`     | bool           | `true`       | Show a live preview next to the crop area.              |
| `accept`      | string         | `image/*`    | `accept` attribute of the file input.                   |

All other options are forwarded to the underlying file input (e.g. `label`,
`class`, `required`).

### In the controller

Load the component and call `process()` before saving. It reads the posted crop
rectangle and crops the uploaded temp file in place:

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('ImageCropper.SaveImage');
}

public function add()
{
    $entity = $this->Articles->newEmptyEntity();
    if ($this->request->is('post')) {
        $this->SaveImage->process('image');
        $entity = $this->Articles->patchEntity($entity, $this->request->getData());
        if ($this->Articles->save($entity)) {
            // handle the (now cropped) uploaded file as usual
        }
    }
    $this->set(compact('entity'));
}
```

`process()` returns `true` when a file was cropped and `false` when there was
nothing to do (no upload or no crop data), so uncropped uploads still pass
through untouched.

## Building the front-end assets

The compiled bundles in `webroot/` are committed to the repository, so you only
need a Node toolchain if you want to change the JavaScript or CSS sources under
`resources/`. The pipeline uses [Vite](https://vitejs.dev):

```bash
npm install      # install dev dependencies (Vite + Cropper.js)
npm run build    # bundle resources/ into webroot/
npm run dev      # rebuild on change while developing
```

`npm run build` emits a dependency-free IIFE to
`webroot/js/image-cropper.js` and a stylesheet to
`webroot/css/image-cropper.css`. Commit the rebuilt files together with your
source changes.

## Running the tests

```bash
composer install
composer test          # PHPUnit
composer cs-check      # coding standard (CakePHP)
composer stan          # PHPStan static analysis
composer check         # all of the above
```

The GD-dependent test cases skip themselves automatically when the `gd`
extension is not available.

## Contributing

Bug reports and pull requests are welcome. Please read
[CONTRIBUTING.md](CONTRIBUTING.md) for the development workflow and coding
standards before opening a pull request.

## License

This plugin is released under the [MIT License](LICENSE).
