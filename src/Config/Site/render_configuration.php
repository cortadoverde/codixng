<?php 

$container = $app->getContainer();

$container['config_render'] = [
    'template_path' => ROOT . DS . 'resources/templates',
    'template_cache_path' => ROOT . DS . 'resources/cache' 
];