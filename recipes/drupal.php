<?php

require_once __DIR__ . '/../common/naoned_common.php';
require_once __DIR__ . '/../common/services.php';

define('DRUSH_PATH', 'vendor/drush/drush/drush.php');

task('drupal:check:bootstrap', function() use ($container) {
    $container['drupal']->drupalHealthCheck();
})->desc('Check drupal is bootstrapping and therefore installed.');

task('drupal:check:database', function() use ($container) {
    $container['drupal']->databaseHealthCheck();
})->desc('Check the database is connectable.');

task('drupal:registry:rebuild', function() use ($container) {
    $container['drupal']->databaseRegistryRebuild();
})->desc('Rebuild drupal registry of modules and functions.');

task('drupal:database:update', function () use ($container) {
    $container['drupal']->databaseUpdate();
})->desc('Run the database updates.');

task('drupal:translations:update', function () use ($container) {
    $container['drupal']->translationsUpdate();
})->desc('Update the translations.');

task('drupal:database:backup', function() use ($container) {
    $container['drupal']->databaseBackup();
})->desc('Backups the current database.');

task('drupal:database:rollback', function() use ($container) {
    $container['drupal']->databaseRollback();
})->desc('Rolls back the database to the previous release state.');

task('drupal:database:cleanup', function() use ($container) {
    $container['drupal']->databaseCleanup();
})->desc('Removes old database backups.');

task('drupal:maintenance:enable', function() use ($container) {
    $container['drupal']->maintenanceEnable();
});

task('drupal:maintenance:disable', function() use ($container) {
    $container['drupal']->maintenanceDisable();
});
