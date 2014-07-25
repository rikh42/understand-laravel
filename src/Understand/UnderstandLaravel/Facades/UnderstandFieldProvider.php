<?php

namespace Understand\UnderstandLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class UnderstandFieldProvider extends Facade
{

    /**
     * Return facade loc accessor
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'understand.field-provider';
    }

}
