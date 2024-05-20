<?php

use Psr7Middlewares\Middleware\TrailingSlash;

$app->add(new TrailingSlash(true));