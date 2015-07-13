<?php

namespace Caspian\Utils;

use Caspian\Api\HTTPResponse;

class Request
{
    /* Request Type */
    const AJAX     = 'ajax';
    const STANDARD = 'standard';

    /* Request METHOD */
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';

    /* Protocol */
    const HTTP  = 'http';
    const HTTPS = 'https';

    /* Device */
    const TABLET   = 'tablet';
    const PHONE    = 'phone';
    const COMPUTER = 'computer';

    private $status_code;
    private $request_type;
    private $request_method;
    private $protocol;
    private $url;
    private $client;
    private $ip;
    private $mobile;
    private $platform;
    private $ie8;

    public function __construct()
    {
        /* Status code */
        if (function_exists("http_response_code")) {
            $this->status_code = http_response_code();
        } else {
            if (!empty($_SERVER['REDIRECT_STATUS'])) {
                $this->status_code = $_SERVER['REDIRECT_STATUS'];
            } else {
                $this->status_code = HTTPResponse::OK;
            }
        }

        /* Request type */
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->request_type = self::AJAX;
        } else {
            $this->request_type = self::STANDARD;
        }

        /* Request method (post, get, etc.) */
        $this->request_method = $_SERVER['REQUEST_METHOD'];

        /* Protocol (IIS support) */
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $this->protocol = self::HTTPS;
        } else {
            if ($_SERVER['SERVER_PORT'] == '443') {
                $this->protocol = self::HTTPS;
            } else {
                $this->protocol = self::HTTP;
            }
        }

        /* Client */
        $this->client   = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $this->ip       = $_SERVER['REMOTE_ADDR'];
        $this->url      = $_SERVER['REQUEST_URI'];
        $this->mobile   = $this->isMobile();
        $this->platform = $this->getPlatform();
        $this->ie8      = $this->isIE8();
    }

    /**
     * get
     *
     * magic getter for the variables of the request class
     *
     * @param string $variable the property to retreive
     *
     * @return mixed
     */
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
        $device_type = ($detect->isMobile() ? ($detect->isTablet() ? self::TABLET : self::PHONE) : self::COMPUTER);

        if ($device_type == self::TABLET || $device_type == self::PHONE) {
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
        return ($detect->isMobile() ? ($detect->isTablet() ? self::TABLET : self::PHONE) : self::COMPUTER);
    }

    /**
     *
     * isIE
     *
     * Returns if browser is Internet Explorer
     *
     * @return  bool   true/false
     * @access  public
     *
     */
    public function isIE8()
    {
        $detect    = new \Mobile_Detect;
        $useragent = $detect->getUserAgent();

        if(!empty($useragent)) {
            if(preg_match('/(?i)msie [8]/', $detect->getUserAgent())) {
                return true;
            }
        }

        return false;
    }
}
