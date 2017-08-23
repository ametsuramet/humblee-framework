<?php

namespace Amet\Humblee\Providers\RouteProviders;

class RouteCollection extends \SplObjectStorage
{
   
    public function attachRoute(Route $attachObject)
    {
        parent::attach($attachObject, null);
    }

    public function all()
    {
        $temp = array();
        foreach ($this as $route) {
            $temp[] = $route;
        }

        return $temp;
    }
}
