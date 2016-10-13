<?php

require_once __DIR__ . '/../common/naoned_common.php';

/**
 * Redefine rsync task. There is a bug with local server in previous task, we handle it in this new implementation
 */
// @codingStandardsIgnoreStart
task('rsync', function () {
    $config = get('rsync');
    $src    = env('rsync_src');
    while (is_callable($src)) {
        $src = $src();
    }
    if (!trim($src)) {
        // if $src is not set here rsync is going to do a directory listing
        // exiting with code 0, since only doing a directory listing clearly
        // is not what we want to achieve we need to throw an exception
        throw new \RuntimeException('You need to specify a source path.');
    }
    $dst = getDistantPath();
    if (!trim($dst)) {
        // if $dst is not set here we are going to sync to root
        // and even worse - depending on rsync flags and permission -
        // might end up deleting everything we have write permission to
        throw new \RuntimeException('You need to specify a destination path.');
    }
    $server = \Deployer\Task\Context::get()->getServer();
    if ($server instanceof \Deployer\Server\Local) {
        runLocally("rsync -{$config['flags']} {{rsync_options}}{{rsync_excludes}}{{rsync_includes}}{{rsync_filter}} '$src/' '$dst/'", $config['timeout']);
    } else {
        $server = $server->getConfiguration();
        $host = $server->getHost();
        $port = $server->getPort() ? ' -p' . $server->getPort() : '';
        $identityFile = $server->getPrivateKey() ? ' -i ' . $server->getPrivateKey() : '';
        $user = !$server->getUser() ? '' : $server->getUser() . '@';
        runLocally("rsync -{$config['flags']} -e 'ssh$port$identityFile' {{rsync_options}}{{rsync_excludes}}{{rsync_includes}}{{rsync_filter}} '$src/' '$user$host:$dst/'", $config['timeout']);
    }
})->desc('Rsync local->remote');
// @codingStandardsIgnoreEnd