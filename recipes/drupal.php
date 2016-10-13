<?php

require_once __DIR__ . '/../common/naoned_common.php';

define('DRUSH_PATH', 'vendor/drush/drush/drush.php');

/**
 * Check drush is installed and responding.
 */
task('drupal:check:drush', function() {
    $dst       = getDistantPath();
    $cmd       = sprintf("cd $dst && php %s --version", DRUSH_PATH);
    $server    = \Deployer\Task\Context::get()->getServer();
    try {
        $status = runInContext($server, $cmd);
    } catch (Exception $e) {
        writeln(sprintf('<fg=red>✘</fg=red> <fg=red>Could not find drush in %s</fg=red>', DRUSH_PATH));
        die;
    }
})->desc('Check if drush is installed.');

/**
 * Check drupal is bootstrapping and therefore installed.
 */
task('drupal:check:bootstrap', function() {
    $dst       = getDistantPath();
    $statusCmd = sprintf("cd $dst && php %s status", DRUSH_PATH);
    $server    = \Deployer\Task\Context::get()->getServer();
    $status    = runInContext($server, $statusCmd);

    if (!preg_match('/Drupal bootstrap\s*:\s*Successful/', $status)) {
        // Drupal is not bootstraping. Assume it is not installed
        writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Drupal is not bootstrapping. Is it installed?</fg=red>'));
        die;
    }
})->desc('Check drupal is bootstrapping and therefore installed.');

/**
 * Check the database is connectable.
 */
task('drupal:check:database', function() {
    $dst    = getDistantPath();
    $cmd    = sprintf("cd $dst && php %s sql-query 'quit'", DRUSH_PATH);
    $server = \Deployer\Task\Context::get()->getServer();
    try {
        $status = runInContext($server, $cmd);
    } catch (Exception $e) {
        writeln(sprintf('<fg=red>✘</fg=red> %s', '<fg=red>Can\'t connect to the database</fg=red>'));
        die;
    }
})->desc('Check the database is connectable.');

/**
 * Rebuild drupal registry of modules and functions.
 * Just in case we moved or renamed a module.
 */
task('drupal:registry:rebuild', function() {
    $server = \Deployer\Task\Context::get()->getServer();
    $dst    = getDistantPath();
    $rebuildCmd = sprintf('cd %s && php %2$s cc drush && php %2$s rr', $dst, DRUSH_PATH);
    runInContext($server, $rebuildCmd);
})->desc('Rebuild drupal registry of modules and functions.');

/**
 * Run drupal database updates.
 */
task('drupal:database:update', function () {
    $server    = \Deployer\Task\Context::get()->getServer();
    $dst       = getDistantPath();
    $updateCmd = sprintf("cd $dst && php %s updatedb", DRUSH_PATH);
    runInContext($server, $updateCmd);
})->desc('Run the database updates.');

/**
 * Update the translations
 */
task('drupal:translations:update', function () {
    $server    = \Deployer\Task\Context::get()->getServer();
    $dst       = getDistantPath();
    $updateCmd = sprintf("cd $dst && php %1$s l10n-update-refresh && php %1$s l10n-update-refresh --mode=replace", DRUSH_PATH);
    runInContext($server, $updateCmd);
})->desc('Update the translations.');