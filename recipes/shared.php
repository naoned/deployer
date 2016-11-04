<?php

/**
 * Override default deployer deploy:shared as to not remove the source
 * but use it as the base for the symlink
 */
task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";

    foreach (get('shared_dirs') as $dir) {
        // If the dir already exists in shared
        if (run("if [ -d $(echo $sharedPath/$dir) ]; then echo 'true'; fi")->toBool()) {
            // Remove from source.
            run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");
        } else {
            // Use current release dir as base for shared one
            run("if [ -d $(echo {{release_path}}/$dir) ]; then mv {{release_path}}/$dir $sharedPath/$dir; fi");
        }

        // Create shared dir if it does not exist.
        run("mkdir -p $sharedPath/$dir");

        // Create path to shared dir in release dir if it does not exist.
        // (symlink will not create the path and will fail otherwise)
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$dir {{release_path}}/$dir");
    }

    foreach (get('shared_files') as $file) {
        $dirname = dirname($file);

        // If the file already exists in shared
        if (run("if [ -f $(echo $sharedPath/$file) ]; then echo 'true'; fi")->toBool()) {
            // Remove from source.
            run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");
        } else {
            // Use current release file as base for shared one
            run("if [ -f $(echo {{release_path}}/$file) ]; then mv {{release_path}}/$file $sharedPath/$file; fi");
        }

        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");

        // Create dir of shared file
        run("mkdir -p $sharedPath/" . $dirname);

        // Touch shared
        run("if [ ! -f $(echo {{release_path}}/$file) ]; then touch $sharedPath/$file; fi");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$file {{release_path}}/$file");
    }
})->desc('Creating symlinks for shared files');
