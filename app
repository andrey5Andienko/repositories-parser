#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Commands\RepositoryParser;
use Symfony\Component\Console\Application;

$app = new Application;

$app->add(new RepositoryParser);

$app->run();
