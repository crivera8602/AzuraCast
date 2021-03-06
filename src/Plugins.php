<?php
namespace App;

use Azura\Container;
use Azura\EventDispatcher;
use Composer\Autoload\ClassLoader;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Plugins
{
    /** @var array An array of all plugins and their capabilities. */
    protected array $plugins = [];

    public function __construct($base_dir)
    {
        $this->loadDirectory($base_dir);
    }

    public function loadDirectory($dir): void
    {
        $plugins = (new Finder())
            ->ignoreUnreadableDirs()
            ->directories()
            ->depth('== 0')
            ->in($dir);

        foreach ($plugins as $plugin_dir) {
            /** @var SplFileInfo $plugin_dir */
            $plugin_prefix = $plugin_dir->getRelativePathname();
            $plugin_namespace = 'Plugin\\' . Inflector::classify($plugin_prefix) . '\\';

            $this->plugins[$plugin_prefix] = [
                'namespace' => $plugin_namespace,
                'path' => $plugin_dir->getPathname(),
            ];
        }
    }

    /**
     * Add plugin namespace classes (and any Composer dependencies) to the global include list.
     *
     * @param ClassLoader $autoload
     */
    public function registerAutoloaders(ClassLoader $autoload): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/vendor/autoload.php')) {
                require($plugin_path . '/vendor/autoload.php');
            }

            $autoload->addPsr4($plugin['namespace'], $plugin_path . '/src');
        }
    }

    /**
     * Register or override any services contained in the global Dependency Injection container.
     *
     * @param array $diDefinitions
     *
     * @return array
     */
    public function registerServices(array $diDefinitions = []): array
    {
        foreach ($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/services.php')) {
                $services = include $plugin_path . '/services.php';
                $diDefinitions = array_merge($diDefinitions, $services);
            }
        }

        return $diDefinitions;
    }

    /**
     * Register custom events that the plugin overrides with the Event Dispatcher.
     *
     * @param EventDispatcher $dispatcher
     */
    public function registerEvents(EventDispatcher $dispatcher): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin_path = $plugin['path'];

            if (file_exists($plugin_path . '/events.php')) {
                call_user_func(include($plugin_path . '/events.php'), $dispatcher);
            }
        }
    }
}
