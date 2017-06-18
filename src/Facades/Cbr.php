<?php

namespace Scorpion\Cbr\Facades;

use Illuminate\Support\Facades\Facade;

class Cbr extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cbr';
    }
}