<?php
use Illuminate\Pagination\Paginator;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

$container = \App\Loader::getContainer();

$container['view'] = function( $container ){
    return new \Slim\Views\Blade(
        $container['config_render']['template_path'],
        $container['config_render']['template_cache_path']
    );
};

$pathsToTemplates = [$container['config_render']['template_path']];
$pathToCompiledTemplates = $container['config_render']['template_cache_path'];

// Dependencies
$filesystem = new Filesystem;
$eventDispatcher = new Dispatcher(new Container);

// Create View Factory capable of rendering PHP and Blade templates
$viewResolver = new EngineResolver;
$bladeCompiler = new BladeCompiler($filesystem, $pathToCompiledTemplates);

$viewResolver->register('blade', function () use ($bladeCompiler) {
    return new CompilerEngine($bladeCompiler);
});

$viewFinder = new FileViewFinder($filesystem, $pathsToTemplates);
$viewFactory = new Factory($viewResolver, $viewFinder, $eventDispatcher);

Paginator::viewFactoryResolver(function () use ($viewFactory) {
    return $viewFactory;
});
