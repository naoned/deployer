<?php

namespace Naoned\Deployer\Recipes\Applications;

use Naoned\Deployer\Recipes\Tools\Drush;

class Drupal
{
    public function __construct(Drush $drush)
    {
        $this->rootDir     = env('deploy_path') . '/current';
        $this->drush       = $drush;
        $this->backupDir   = env('deploy_path') . '/current/sites/default/private';
        $this->releaseName = pathinfo(run('cd {{deploy_path}} && readlink current')->toString())['basename'];
    }

    public function drupalHealthCheck()
    {
        $status    = $this->drush->execute('status');

        if (!preg_match('/Drupal bootstrap\s*:\s*Successful/', $status)) {
            // Drupal is not bootstraping. Assume it is not installed
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Drupal is not bootstrapping. Is it installed?</fg=red>'));
            die;
        }
    }

    public function databaseHealthCheck()
    {
        if (!$this->drush->execute('sql-query', ['quit'])) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Can\'t connect to the database</fg=red>'));
            die;
        }
    }

    public function databaseBackup()
    {
        $backupFile = $this->getDatabaseGetBackupName($this->releaseName);

        if (!$this->drush->execute('sql-dump', ['>', $this->backupDir . '/' . $backupFile])) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Failed to backup the database</fg=red>'));
            die;
        }

        writeln(sprintf('<info>✔</info> %s %s', 'Database backup created', '<info>' . $this->backupDir . '/' . $backupFile . '</info>'));
    }

    public function databaseUpdate()
    {
        if (!$this->drush->execute('updatedb', ['-y'])) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Could not update the database. Try manually by going to /update.php</fg=red>'));
            die;
        }
    }

    public function databaseRollback()
    {
        $releases = env('releases_list');
        if (isset($releases[1]) && askConfirmation('This will try to rollback to a saved database and may cause data loss, are you sure you want to continue ?', true)) {
            $backupFile = $this->getDatabaseGetBackupName($releases[1]);
            $backupPath = $this->backupDir . '/' . $backupFile;

            try {
                run("stat {$backupPath}");
            } catch (\Exception $e) {
                writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>No backup found for the previous release!</fg=red>'));
                if (askConfirmation('Are you sure you want to continue ? The website may not work correctly with the current database', false)) {
                    return true;
                } else {
                    if (isVerbose()) {
                        throw $e;
                    }
                    die;
                }

            }

            if ($this->drush->execute('sql-cli', ['<', $backupPath])) {
                writeln(sprintf('<info>✔</info> %s', 'Restored to <info>' . $backupPath . '</info>'));
                run("rm -f {$backupPath}");
            } else {
                writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Failed to restore ' . $backupPath . '</fg=red>'));
                die;
            }
        }
    }

    public function databaseRegistryRebuild()
    {
        if (!$this->drush->execute('cc', ['drush']) || !$this->drush->execute('rr')) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Could not rebuild the registry</fg=red>'));
            die;
        }
    }

    private function getDatabaseGetBackupName($releaseName)
    {
        return sprintf('release_%s_backup.sql', $releaseName);
    }

    public function maintenanceEnable()
    {
        if (!$this->drush->execute('vset', ['maintenance_mode', '1']) || !$this->drush->execute('cc', ['all'])) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Could not disable the maintenance mode</fg=red>'));
            die;
        }
    }

    public function maintenanceDisable()
    {
        if (!$this->drush->execute('vset', ['maintenance_mode', '0']) || !$this->drush->execute('cc', ['all'])) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Could not disable the maintenance mode</fg=red>'));
            die;
        }
    }

    public function translationsUpdate()
    {
        writeln('This may take a while...');
        $refresh = $this->drush->execute('l10n-update-refresh');
        $update  = $this->drush->execute('l10n-update', ['--mode=replace']);

        if (!$refresh || !$update) {
            writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Failed to update translations</fg=red>'));
        }

        writeln(sprintf('<info>✔</info> %s', 'Translations updated'));
    }
}
