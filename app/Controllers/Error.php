<?php

use Caspian\Configuration;
use Caspian\Locale;
use Caspian\Controller\Application;
use Caspian\Api\HTTPResponse;

class ErrorController extends Application
{
    public function notFound()
    {
        if(!empty($_SERVER['REQUEST_URI'])) {
            if(strstr($_SERVER['REQUEST_URI'], '/en/')) {
                Locale::switchLocale('en');
            } else {
                Locale::switchLocale(Configuration::get('configuration', 'languages.default'));
            }
        }

        $this->app->helpers->url->setCode(HTTPResponse::NOT_FOUND);
        $this->view->render('notfound');
    }

    public function notSupported()
    {
        $this->view->useLayout('not-supported');
        $this->view->render('not-supported');
    }
}