<?php

namespace Caspian\Utils;

use Caspian\Configuration;

class Request
{
    private $status_code;
    private $request_type;
    private $request_method;
    private $protocol;
    private $url;
    private $client;
    private $ip;
    private $mobile;
    private $platform;

    public function __construct()
    {
        /* Status code */
        if (function_exists("http_response_code")) {
            $this->status_code = http_response_code();
        } else {
            if (!empty($_SERVER['REDIRECT_STATUS'])) {
                $this->status_code = $_SERVER['REDIRECT_STATUS'];
            } else {
                $this->status_code = 200;
            }
        }

        /* Request type */
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->request_type = 'ajax';
        } else {
            $this->request_type = 'standard';
        }

        /* Request method (post, get, etc.) */
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        /* Protocol (IIS support) */
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $this->protocol = 'https';
        } else {
            if ($_SERVER['SERVER_PORT'] == '443') {
                $this->protocol = 'https';
            } else {
                $this->protocol = 'http';
            }
        }

        /* Client */
        $this->client   = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $this->ip       = $_SERVER['REMOTE_ADDR'];
        $this->url      = $_SERVER['REQUEST_URI'];
        $this->mobile   = $this->isMobile();
        $this->platform = $this->getPlatform();
    }

    public function __get($variable)
    {
        return $this->{$variable};
    }


    /**
     *
     * ajax
     *
     * Return whether the request is an ajax request or not
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function ajax()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * isMobile
     *
     * Check if user is on a mobile device
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isMobile()
    {
        $detect      = new \Mobile_Detect;
        $device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

        if ($device_type == 'tablet' || $device_type == 'phone') {
            return true;
        }

        return false;
    }

    /**
     *
     * getPlatform
     *
     * Get the user's platform
     *
     * @return  string  tablet, phone or computer
     * @access  public
     *
     */
    public function getPlatform()
    {
        $detect = new \Mobile_Detect;
        return ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
    }
}
