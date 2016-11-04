<?php

require_once __DIR__ . '/../common/services.php';

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

task('server:configure:php', function() use ($container) {
    $config = get('php_config');
    if (!empty($config)) {
        $container[env('os_like')]->configurePhp($config);
    } else {
        writeln("<fg=red>✘</fg=red> No config file found for PHP.");
    }
});
