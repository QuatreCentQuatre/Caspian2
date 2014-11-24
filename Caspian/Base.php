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
    }
}