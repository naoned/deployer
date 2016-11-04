<?php

namespace Naoned\Deployer\Recipes\Servers;

class Debian
{
    private $versionName;

    public function __construct()
    {
        $this->versionName = run(
            'cat /etc/*-release | grep -o VERSION=.* | grep -o "(.*)" | grep -o "\w*"'
        )->toString();

        ENV('VERSION_NAME', $this->versionName);
        run("apt-get update --fix-missing");
    }

    public function installRepositories(array $repositories)
    {
        $this->installDependencies(['wget', 'apt-transport-https']);

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

    public function installDependencies(array $dependencies)
    {
        $total = count($dependencies);
        $i     = 1;

        foreach ($dependencies as $key => $dep) {
            if ($total > 1) {
                writeln("➤ $i/$total");
            }
            $this->installDependency($dep);
            $i++;
        }
    }

    public function installDependency($name)
    {
        writeln("➤ Installing <info>$name</info>");
        run("apt-get install -y $name");
    }

    public function installMysql()
    {
        if (tryRun('(dpkg --get-selections | grep mysql-server)')) {
            writeln("<info>✔</info> Mysql is already installed");
            return;
        }

        $rootPwd = env('mysql_root_pwd', ask('Specify the root password for mysql:', 'root'));

        run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password password {{mysql_root_pwd}}" | debconf-set-selections)');
        run('(echo "mysql-server-{{mysql_version}} mysql-server/root_password_again password {{mysql_root_pwd}}" | debconf-set-selections)');
        $this->installDependency(sprintf('mysql-server-%s', ENV('mysql_version')));
        writeln('➤ Starting mysql');
        run('service mysql start');
    }

    public function configureApache(array $config)
    {
        foreach ($config['a2enmod'] as $module) {
            writeln("➤ Enabling module <info>$module</info>");
            run("a2enmod $module");
        }

        writeln("➤ Restarting apache");
        run('apachectl restart ');
    }

    public function configurePhp($configFile)
    {
        $targetLink = '/etc/php5/apache2/conf.d/' . pathinfo($configFile)['basename'];

        run("ln -fs $configFile $targetLink");
        run("apachectl restart");
    }
}
