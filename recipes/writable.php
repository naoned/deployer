<?php

/**
 * Override deployer implementation of deploy:writable
 */
task('deploy:writable', function () {
    $dirs = join(' ', get('writable_dirs'));
    $sudo = get('writable_use_sudo') ? 'sudo' : '';
    $httpUser = get('http_user');

    if (!empty($dirs)) {
        try {
            if (null === $httpUser) {
                $httpUser = run("ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1")->toString();
            }

            try {
                cd('{{release_path}}');
            } catch (Exception $e) {
                cd('{{deploy_path}}/current');
            }

            if (!empty($sudo)) {
                run("$sudo chmod -R 775 $dirs");
                run("$sudo chown -R `whoami`:$httpUser $dirs");
            } else {
                run("chmod -R 775 $dirs");
                run("chown -R `whoami`:$httpUser $dirs");
            }
        } catch (\RuntimeException $e) {
            $formatter = \Deployer\Deployer::get()->getHelper('formatter');

            $errorMessage = [
                "Unable to setup correct permissions for writable dirs.                  ",
                "You need to configure sudo's sudoers files to not prompt for password,",
                "or setup correct permissions manually.                                  ",
            ];
            write($formatter->formatBlock($errorMessage, 'error', true));

            throw $e;
        }
    }
})->desc('Make writable dirs');
