<?php

task('server:install:repositories', function() {
    $os           = (in_array(env('os_like'), ['redhat', 'centos'])) ? 'redhat' : env('os_like');
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
    } else if ($os == 'redhat') {
        $distro = run('cat /etc/redhat-release')->toString();

        if (preg_match('/release 6/', $distro)) {
            ENV('VERSION_NUMBER', 6);
        } else if (preg_match('/release 7/', $distro)) {
            ENV('VERSION_NUMBER', 6);
        } else {
            throw new \RuntimeException(sprintf('Could not determine the version number from : %s', $distro));
        }

        foreach ($repositories as $name => $source) {
            try {
                writeln("➤ Installing <info>$name</info> package");
                run("rpm -Uvh $source");
            } catch (Exception $e) {
                $error = $e->getProcess()->getErrorOutput();
                if (preg_match('/already installed/', $error)) {
                    writeln("<info>✔</info> Package <info>$name</info> already installed");
                } else {
                    throw $e;
                }
            }
        }
    }
});

task('server:install:common:depedencies', function() {
    $os          = (in_array(env('os_like'), ['redhat', 'centos'])) ? 'redhat' : env('os_like');
    $depedencies = get('common_dependencies')[$os];
    $total       = count($depedencies);

    if ($os == 'debian') {
        run("apt-get update --fix-missing");
        $installCmd = 'apt-get -y -qq install';
    } else if ($os == 'redhat') {
        $installCmd = 'yum -y install';
    } else {
        throw new \RuntimeException("Cannot determine the dependencies install cmd for os $os.");
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
    $os = (in_array(env('os_like'), ['redhat', 'centos'])) ? 'redhat' : env('os_like');

    $installMysql = askConfirmation('Would you like to install mysql on this server ?', true);

    if (!$installMysql) {
        writeln("➤ Skipping the installation of Mysql. You will still need to provide a mysql server.");
        return;
    }

    if ($os == 'debian') {
        if (!tryRun('(dpkg --get-selections | grep mysql-server)')) {
            $rootPwd = env('mysql_root_pwd', ask('Specify the root password for mysql:', 'root'));
            writeln("➤ Installing <info>mysql-server-" . env('mysql_version') . "</info>");
            run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password password {{mysql_root_pwd}}" | debconf-set-selections)');
            run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password_again password {{mysql_root_pwd}}" | debconf-set-selections)');
            run('apt-get update --fix-missing && apt-get install -y mysql-server-{{mysql_version}}');
            writeln('➤ Starting mysql');
            run('service mysql start');
        } else {
            $alreadyInstalled = true;
        }
    } else if ($os == 'redhat') {
        writeln("➤ Installing <info>mysql-server</info>");
        run('yum install -y mysql-server');
        writeln('➤ Starting mysql');
        run('/sbin/service mysqld start');
        run('chkconfig httpd on');
        writeln('We recommand running <info>mysql_secure_installation</info> to setup a root password.');
    } else {
        throw new \RuntimeException("Cannot install mysql on $os");
    }

    if (isset($alreadyInstalled) && $alreadyInstalled) {
        writeln("<info>✔</info> Mysql is already installed");
    }
});

task('server:configure:apache', function() {
    $config = get('apache_config');
    $os     = (in_array(env('os_like'), ['redhat', 'centos'])) ? 'redhat' : env('os_like');

    if ($os == 'debian') {
        foreach ($config['a2enmod'] as $module) {
            writeln("➤ Enabling module <info>$module</info>");
            run("a2enmod $module");
        }
        writeln("➤ Restarting apache");
        run("service apache2 restart");
    } else if($os == 'redhat') {
        $modList = explode(PHP_EOL, run('(ls -l /etc/httpd/modules/ | awk \'{print $NF}\' | grep .so)')->toString());
        foreach ($config['a2enmod'] as $module) {
            $moduleFileName = 'mod_' . $module . '.so';

            if ($module == 'php5') {
                // php5 module is named differently on centos/redhat
                $moduleFileName = 'libphp5.so';
            }

            if (!in_array($moduleFileName, $modList)) {
                throw new \RuntimeException("Cannot find module $moduleFileName in /etc/httpd/modules");
            }

            try {
                // Check if the module is not already listed in the current conf
                // TODO: Check conf.d dir too
                $moduleInHttpdConf = run("(cat /etc/httpd/conf/httpd.conf | grep -E $moduleFileName$)")->toString();
            } catch (Exception $e) {
                $moduleInHttpdConf = false;
            }

            writeln("➤ Enabling module <info>$module</info>");
            if ($moduleInHttpdConf) {
                // If the module is referenced in the conf uncomment it
                run(sprintf("sed -ri.bkp 's/^\s*#\s*(LoadModule.*%s)$/\\1/' /etc/httpd/conf/httpd.conf", $moduleFileName));
            } else {
                // Otherwise create a new conf in conf.d to enable it
                run(sprintf('(echo "LoadModule %s_module modules/%s" > /etc/httpd/conf.d/%1$s.conf)', $module, $moduleFileName));
            }
        }

        writeln("➤ Restarting apache");
        run('apachectl restart ');
    } else {
        throw new \RuntimeException("Cannot configure apache on $os");
    }
});
