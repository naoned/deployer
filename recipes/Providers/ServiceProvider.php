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

        $container['debian'] = function() {
            return new Debian();
        };
        $container['redhat'] = function() {
            return new RedHat();
        };
        $container['drupal'] = function($c) {
            return new Drupal($c['drush']);
        };
        $container['drush'] = function($c) {
            return new Drush($c['drush_path']);
        };
    }
}
