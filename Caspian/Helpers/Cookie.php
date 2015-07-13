<?php

namespace Caspian\Helpers;

use Caspian\Base;

class Cookie extends Base
{
    private static $instance;
    
    private $handler;
    private $expiration  = 1209600;
    private $path        = '/';
    private $domain      = null;
    private $httpOnly    = true;
    private $secure      = false;
    
    public function __construct($expiration=1209600, $path='/', $domain=null, $secure=false, $httpOnly=true)
    {
        if (!empty(self::$instance)) {
            return self::$instance;    
        }
        
        $this->expiration = $expiration;
        $this->path       = $path;
        $this->domain     = $domain;
        $this->secure     = $secure;
        $this->httpOnly   = $httpOnly;
        $this->handler    = array();
        
        self::$instance = $this;
    }

    /**
     * Singleton retrieval function
     *
     * @return Cookie
     * @access public static
     *
     */
    public final static function getInstance()
    {
        return self::$instance;
    }

    /**
     *
     * Magic getter for the cookie helper class
     *
     * @param string $property the name of the property to retrieve
     * @return mixed
     * @access public
     *
     */
    public function __get($property)
    {
        if (isset($this->{$property})) {
            return $this->{$property};
        }

        return null;
    }

    /**
     *
     * Magic setter for the cookie helper class
     *
     * @param string $property the name of the property to set
     * @param string $value the value of the property to set
     * @return mixed
     * @access public
     *
     */
    public function __set($property, $value)
    {
        if (isset($this->{$property})) {
            $this->{$property} = $value;
        }
    }

    /**
     *
     * Set the value for the given cookie index name
     *
     * @param   string  the index name
     * @param   mixed   value
     * @access  public
     *
     */
    public function set($name, $value)
    {
        $exp = time() + $this->expiration;
        setcookie($name, $value, $exp, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }

    /**
     *
     * Get the value for the given index name
     *
     * @param   string  the index name
     * @return  mixed   the value
     * @access  public
     *
     */
    public function get($name)
    {
        if (!empty($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        
        return null;
    }
    
    /**
     *
     * Expire the cookie, like, right now
     *
     * @access  public
     *
     */
    public function expire($name = null)
    {
        if(isset($name)){
            setcookie($name, '', time() - 60, $this->path, $this->domain, $this->secure, $this->httpOnly);
        } else {
            setcookie('caspian', '', time() - 60, $this->path, $this->domain, $this->secure, $this->httpOnly);
        }

    }
}