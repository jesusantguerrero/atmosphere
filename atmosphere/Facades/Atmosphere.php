<?php

namespace Atmosphere\Facades;

use Illuminate\Support\Facades\Facade;

class Atmosphere extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'atmosphere';
    }
}