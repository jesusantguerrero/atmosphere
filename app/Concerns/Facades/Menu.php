<?php

namespace App\Concerns\Facades;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Menu extends BaseFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'menu';
    }
}
