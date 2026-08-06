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
| `cancelLabel` | string         | `Cancel`     | Label of the modal's cancel button.                     |
| `applyLabel`  | string         | `Apply crop` | Label of the modal's apply button.                      |
| `closeLabel`  | string         | `Close`      | `aria-label` of the modal's × button.                   |
| `preview`     | bool           | `true`       | Show a live preview next to the crop area.              |
| `accept`      | string         | `image/*`    | `accept` attribute of the file input.                   |

All other options are forwarded to the underlying file input (e.g. `label`,
`class`, `required`).

### Translations

Title and button labels default to translated strings from the `image_cropper`
domain; a German translation ships with the plugin
(`resources/locales/de/image_cropper.po`). Add your own locale by providing an
`image_cropper.po` in your app's `resources/locales/<locale>/` folder, or
override the labels per field via the options above.

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
nothing to do (no upload, no crop data, or an empty/zero-sized crop rectangle —
e.g. when the user selected a file but cancelled the crop modal), so uncropped
uploads still pass through untouched.

## Styling & theming

### How the look is defined

The plugin ships **plain CSS only — there is no PHP or JavaScript configuration
for the appearance, and nothing needs to be configured** to use it. The bundled
stylesheet `webroot/css/image-cropper.css` contains two parts:

1. The stock [Cropper.js styles](https://github.com/fengyuanchen/cropperjs)
   (crop box, grid lines, drag handles) — compiled in from
   `cropperjs/dist/cropper.css`.
2. The plugin's own modal and button styles, authored in
   `resources/css/image-cropper.css`.

Every class the plugin creates is prefixed with `ic-`, so the styles never
collide with your application CSS or a framework like Bootstrap or Tailwind.
The design is intentionally neutral (white dialog, gray borders, blue primary
button) and works on any page without further setup. The modal overlay uses
`z-index: 1080`, which matches Bootstrap's modal layer.

| Class                 | Element                                        |
|-----------------------|------------------------------------------------|
| `.ic-modal`           | Full-screen overlay (backdrop)                 |
| `.ic-modal__dialog`   | The dialog box                                 |
| `.ic-modal__header`   | Header bar containing title and close button   |
| `.ic-modal__title`    | Modal heading (`<h2>`)                         |
| `.ic-modal__close`    | “×” close button                               |
| `.ic-modal__body`     | Scrollable content area                        |
| `.ic-modal__stage`    | Container of the image being cropped           |
| `.ic-modal__image`    | The `<img>` Cropper.js attaches to             |
| `.ic-modal__preview`  | Live preview pane (when `preview` is enabled)  |
| `.ic-modal__footer`   | Footer bar containing the action buttons       |
| `.ic-btn`             | Base button                                    |
| `.ic-btn--secondary`  | “Cancel” button                                |
| `.ic-btn--primary`    | “Apply crop” button                            |

### Matching your application's look and feel

**Option A — override the `ic-` classes (recommended).** Keep the plugin's
stylesheet and layer a few rules in your own CSS on top of it. Because the
plugin CSS is appended to the `css` view block, it is usually printed *after*
your application stylesheet — so either load your overrides through the same
block, or rely on selector specificity (e.g. prefix with `body`). A Bootstrap
example:

```css
.ic-btn--primary {
    background: var(--bs-primary);
}

.ic-btn--secondary {
    color: var(--bs-secondary-color);
    background: var(--bs-secondary-bg);
    border-color: var(--bs-border-color);
}

.ic-modal__dialog {
    border-radius: var(--bs-border-radius-lg);
    font-family: var(--bs-body-font-family);
}
```

This is enough for most projects: the layout of the modal stays as shipped and
only colors, fonts and radii are adapted.

**Option B — replace the stylesheet entirely.** Disable the automatic asset
injection and ship your own CSS (and optionally your own JS):

```php
// src/View/AppView.php
$this->loadHelper('Form', [
    'className' => 'ImageCropper.Cropper',
    'autoInclude' => false,
]);
```

With `autoInclude` disabled the helper renders only the form controls; you are
then responsible for loading **both** the script and a stylesheet yourself,
including the Cropper.js core styles (`cropperjs/dist/cropper.css`) — without
them the crop box has no visible frame or handles. The plugin's compiled bundle
is still available under `/image_cropper/js/image-cropper.js` if you only want
to swap the CSS:

```php
// your layout or template
$this->Html->script('ImageCropper.image-cropper', ['block' => true, 'defer' => true]);
$this->Html->css('my-cropper-theme', ['block' => true]); // must include the Cropper.js core styles
```

The markup the script generates (see the class table above) is stable, so a
custom stylesheet only needs to target the `ic-` classes.

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
