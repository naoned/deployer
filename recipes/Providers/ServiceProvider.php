<?php

namespace Naoned\Deployer\Recipes\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Naoned\Deployer\Recipes\Servers\Debian;
use Naoned\Deployer\Recipes\Servers\RedHat;
use Naoned\Deployer\Recipes\Applications\Drupal;
use Naoned\Deployer\Recipes\Tools\Drush;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['drush_path'] = 'vendor/drush/drush/drush.php';

        // We use factory because we don't want to share an instance accross multiple servers.
        // Server configs may differ so it's safer to always start with new instances.
        $container['debian'] = $container->factory(function() {
            return new Debian();
        });
        $container['redhat'] = $container->factory(function() {
            return new RedHat();
        });
        $container['drupal'] = $container->factory(function($c) {
            return new Drupal($c['drush']);
        });
        $container['drush'] = $container->factory(function($c) {
            return new Drush($c['drush_path']);
        });
    }
}
