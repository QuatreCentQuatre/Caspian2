<?php

namespace Caspian\Helpers;

use Caspian\Base;
use Caspian\Api\HTTPResponse;
use Caspian\Configuration;
use Caspian\Routing;

class URL extends Base
{
    /**
     *
     * getRouteURL
     *
     * Get a route url by it's name and substitute dynamic values by what's given
     *
     * @param   string  the name of the route
     * @param   mixed   string/array of elements to substitute with
     * @return  string  the route found
     * @access  public
     *
     */
    public function getRouteURL($name, $arguments=null)
    {
        $routes = Routing::getRoutes();

        if (!empty($arguments) && is_string($arguments)) {
            $arguments = array($arguments);
        }

        foreach ($routes as $route_name => $params) {
            if ($route_name == $name) {
                $url = $params->uri;

                if(is_object($url)) {
                    $language = $this->app->session->get('app_language') ? $this->app->session->get('app_language') : Configuration::get('configuration', 'languages.default');
                    $url      = $url->{$language};

                    if (stristr($url, '(:any)') || stristr($url, '(:param)') || stristr($url, '(:num)') || stristr($url, '(:str)')) {
                        if (!empty($arguments)) {
                            $url = str_replace(array("(:any)", "(:param)", "(:str)", "(:num)"), array("%s", "%s", "%s", "%d"), $url);
                            $url = vsprintf($url, $arguments);
                        }
                    } else {
                        if(preg_match_all(Routing::PATTERN_REGEX, $url, $matches)) {
                            if (!empty($matches[2])) {
                                foreach ($matches[2] as $num => $match) {
                                    $url = str_replace($matches[0][$num], $arguments[$num], $url);
                                }
                            }
                        }
                    }
                }

                return $this->app->site_url . $url . '/';
            }
        }
    }

    /**
     *
     * getAlternateRoute
     *
     * Retrieve alternate route from given language
     *
     * @param   object   $data
     *
     * @return  string   $url
     * @access  public
     *
     */
    public function getAlternateRoute($data)
    {
        $url = '';

        $routes    = $this->app->router->alternate();
        $alternate = $this->app->session->get('app_language') == 'fr' ? 'en' : 'fr';

        if(!empty($data->translation)) {
            if(!empty($data->translation->permalink)) {
                $url = $this->app->site_url . $data->translation->permalink;
            } else if(!empty($data->translation->{$alternate})) {
                $url = $data->translation->{$alternate};
            }
        } else if(!empty($routes) && !empty($routes->{$alternate})) {
            $url =  $routes->{$alternate};
        }

        return substr($url, -1) == '/' ? $url : $url . '/';
    }

    /**
     *
     * setCode
     *
     * Set an http response code
     *
     * @param   int     http code
     * @access  public
     *
     */
    public function setCode($code=HTTPResponse::OK)
    {
        http_response_code($code);
    }

    /**
     *
     * redirect
     *
     * Redirect to url using 301 (optional) redirect
     *
     * @param   string  url to redirect to
     * @param   bool    301 redirect?
     * @access  public
     *
     */
    public function redirect($url, $permanent=false)
    {
        if ($permanent) {
            http_response_code(HTTPResponse::MOVE_PERMANENTLY);
        }

        header('location: ' . $url);
        exit();
    }
}