<?php

namespace ISMS\Facades;

use Illuminate\Support\Facades\Facade;

class ISMS extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        self::clearResolvedInstance('isms');
        return 'isms';
    }
}