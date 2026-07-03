<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for ImageCropper.
 *
 * Locates CakePHP whether the plugin is installed standalone (CakePHP lives in
 * the plugin's own vendor dir) or as a dependency of a host application.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once $root . '/vendor/autoload.php';
require_once $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
