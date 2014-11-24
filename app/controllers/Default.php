<?php

use Caspian\Controller\Application;

class DefaultController extends Application
{
    public function index()
    {

    }

    public function notFound()
    {
        // 404 handler
        echo "404";
    }
}