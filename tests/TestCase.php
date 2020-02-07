<?php

namespace Arrowsgm\Amped\Tests;


use Arrowsgm\Amped\AmpedLaravelServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            AmpedLaravelServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setBasePath(__DIR__ . '/..');
    }
}