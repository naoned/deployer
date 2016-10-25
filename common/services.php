<?php

use Pimple\Container;
use Naoned\Deployer\Recipes\Providers\ServiceProvider;

$container = new Container();

$container->register(new ServiceProvider());
