<?php

namespace Caspian;

class Base
{
    /**
     *
     * Application static instance
     * @var \Caspian\Application
     *
     */
    protected $app;

    public function __construct()
    {
        $this->app = Application::$instance;

        if(class_exists('TunaBundle')) {
            $this->tuna = \TunaBundle::$instance;
        }
    }

    public function addAlias($alias, $instance)
    {
        $this->{$alias} = $instance;
    }
}