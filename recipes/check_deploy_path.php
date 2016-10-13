<?php

task('deploy:check:path', function() {
    $server = \Deployer\Task\Context::get()->getServer();
    if ($server instanceof \Deployer\Server\Local) {
        if (strpos(__DIR__, env('deploy_path')) !== false) {
            die("\nDeploy in deploy path is forbidden.\n\n");
        }
    }
});