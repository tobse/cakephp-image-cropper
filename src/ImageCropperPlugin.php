<?php
declare(strict_types=1);

namespace ImageCropper;

use Cake\Core\BasePlugin;

/**
 * ImageCropper plugin.
 *
 * The plugin only ships view helpers, a controller component and a small GD
 * based utility together with the compiled front-end assets that live in the
 * plugin's webroot. It therefore disables every framework hook it does not
 * need so that including it stays cheap and side-effect free.
 */
class ImageCropperPlugin extends BasePlugin
{
    /**
     * The plugin does not register any routes.
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * The plugin does not add any middleware.
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * The plugin does not register any console commands.
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * The plugin has no bootstrap logic.
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = false;

    /**
     * The plugin has no services to register in the container.
     *
     * @var bool
     */
    protected bool $servicesEnabled = false;
}
