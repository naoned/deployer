<?php

task('server:install:repositories', function() {
    $os           = env('os_like');
    $repositories = get('repositories')[$os];

    if ($os == 'debian') {
        ENV('VERSION_NAME', run('cat /etc/*-release | grep -o VERSION=.* | grep -o "(.*)" | grep -o "\w*"')->toString());

        writeln("➤ Installing <info>wget</info>");
        run('apt-get update && apt-get install -y wget');

        writeln("➤ Installing <info>apt-transport-https</info>");
        run('apt-get install -y apt-transport-https');

        foreach ($repositories as $name => $repository) {
            writeln("➤ Installing <info>$name</info> repository");
            foreach ($repository['sources'] as $source) {
                run('echo -e "\n" >> /etc/apt/sources.list.d/$name.list');
                run("echo $source >> /etc/apt/sources.list.d/$name.list");
            }
            foreach ($repository['keys'] as $key) {
                run("wget -qO- $key | apt-key add -");
            }
        }

        writeln("➤ Updating repository list");
        run('apt-get update');
    }
});

task('server:install:common:depedencies', function() {
    $os          = env('os_like');
    $depedencies = get('common_dependencies')[$os];
    $total       = count($depedencies);

    switch ($os) {
        case "debian":
            run("apt-get update --fix-missing");
        default:
            $installCmd = 'apt-get -y -qq install';
            break;
    }

    $i = 1;
    foreach ($depedencies as $key => $dep) {
        if ($total > 1) {
            writeln("➤ $i/$total - Installing <info>$dep</info>");
        } else {
            writeln("➤ Installing <info>$dep</info>");
        }
        run("$installCmd $dep");
        $i++;
    }
});

task('server:install:mysql', function() {
    $os = env('os_like');

    $installMysql = askConfirmation('Would you like to install mysql on this server ?', true);

    if (!$installMysql) {
        writeln("➤ Skipping the installation of Mysql. You will still need to provide a mysql server.");
        return;
    }

    if ($os == 'debian') {
        if (!tryRun('(dpkg --get-selections | grep mysql-server)')) {
            env('mysql_root_pwd', ask('Specify the root password for mysql:', ''));
            writeln("➤ Installing <info>mysql-server-" . env('mysql_version') . "</info>");
            run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password password {{mysql_root_pwd}}" | debconf-set-selections)');
            run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password_again password {{mysql_root_pwd}}" | debconf-set-selections)');
            run('apt-get update --fix-missing && apt-get install -y mysql-server-{{mysql_version}}');
            writeln('➤ Starting mysql');
            run('service mysql start');
        } else {
            $alreadyInstalled = true;
        }
    }

    if (isset($alreadyInstalled) && $alreadyInstalled) {
        writeln("<info>✔</info> Mysql is already installed");
    }
});

task('server:configure:apache', function() {
    $config        = get('apache_config');

    foreach ($config['a2enmod'] as $module) {
        writeln("➤ Enabling module <info>$module</info>");
        run("a2enmod $module");
    }

    writeln("➤ Restarting apache");
    run("service apache2 restart");
});