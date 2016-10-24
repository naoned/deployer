<?php

use Naoned\Deployer\Recipes\Servers\Debian;
use Naoned\Deployer\Recipes\Servers\RedHat;


$container = new Pimple\Container();
$container['debian'] = function() {
    return new Debian;
};
$container['redhat'] = function() {
    return new RedHat;
};


task('server:install:repositories', function() use ($container) {
    $repositories = get('repositories')[env('os_like')];
    $container[env('os_like')]->installRepositories($repositories);
});

task('server:install:common:dependencies', function() use ($container) {
    $dependencies = get('common_dependencies')[env('os_like')];
    $container[env('os_like')]->installDependencies($dependencies);
});

task('server:install:mysql', function() use ($container) {
    $installMysql = askConfirmation('Would you like to install mysql on this server ?', true);

    if (!$installMysql) {
        writeln("➤ Skipping the installation of Mysql. You will still need to provide a mysql server.");
        return;
    }

    $container[env('os_like')]->installMysql();
});

task('server:configure:apache', function() use ($container) {
    $config = get('apache_config');
    $container[env('os_like')]->configureApache($config);
});
