<?php

namespace Naoned\Deployer\Recipes\Servers;

class RedHat
{
    public function __construct()
    {
        $distro = run('cat /etc/redhat-release')->toString();

        if (preg_match('/release 6/', $distro)) {
            ENV('VERSION_NUMBER', 6);
        } else if (preg_match('/release 7/', $distro)) {
            ENV('VERSION_NUMBER', 7);
        } else {
            throw new \RuntimeException(sprintf('Could not determine the version number from : %s', $distro));
        }

        $this->versionNumber = ENV('VERSION_NUMBER');
    }

    public function installRepositories(array $repositories)
    {
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
        writeln("➤ Installing <info>$dep</info>");
        run("yum install -y $name");
    }

    public function installMysql()
    {
        writeln("➤ Installing <info>mysql-server</info>");
        run('yum install -y mysql-server');
        writeln('➤ Starting mysql');
        run('/sbin/service mysqld start');
        run('chkconfig httpd on');
        writeln('We recommand running <info>mysql_secure_installation</info> to setup a root password.');
    }

    public function configureApache(array $config)
    {
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
    }
}
