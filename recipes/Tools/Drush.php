<?php

namespace Naoned\Deployer\Recipes\Tools;

use Deployer\Task\Context;

class Drush
{
    public function __construct($drushPath)
    {
        $this->rootDir   = env('deploy_path') . '/current';
        $this->drushPath = $drushPath;
        $this->server    = Context::get()->getServer();

        // Check drush is alive
        try {
            $status = runInContext($this->server, sprintf("cd %s && php %s --version", $this->rootDir, $this->drushPath));
        } catch (Exception $e) {
            writeln(sprintf('<fg=red>✘</fg=red> <fg=red>Could not find drush in %s</fg=red>', DRUSH_PATH));

            if (isVerbose()) {
                throw $e;
            }

            die;
        }
    }

    /**
     * Execute a drush command on the current release
     * @param  string $cmdName   Cmdname, https://drushcommands.com/
     * @param  array  $arguments Command arguments
     * @return Deployer\Type\Result;
     */
    public function execute($cmdName, array $arguments = [])
    {
        set_time_limit(90);
        $cmd = sprintf('cd %s && php %s %s %s', $this->rootDir, $this->drushPath, $cmdName, implode(' ', $arguments));

        if (isVerbose()) {
            writeln('<info>' . $cmd . '</info>');
        }

        try {
            $result = run($cmd);
        } catch (Exception $e) {
            if (isVerbose()) {
                throw $e;
            }

            return null;
        }

        // Drush seems to send exit code before output ???
        // Anyway, this mostly does not work and display empty strings
        // if (isVerbose()) {
        //     writeln($result);
        // }

        return $result;
    }
}
