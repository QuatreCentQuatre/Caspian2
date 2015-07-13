<?php

namespace Caspian\Security;

use Caspian\Base;

$GLOBALS['_PUT'] = array();

class Input extends Base
{
    /* Retrieve Type */
    const ALL = 'all';

    /* Input Filters */
    const STRING = 'string';
    const INT    = 'int';
    const ID     = 'id';
    const FLOAT  = 'float';
    const EMAIL  = 'email';
    const SAFE   = 'safe';

    private $post;
    private $get;
    private $put;

    public function __construct()
    {
        parent::__construct();

        $post = $this->post(self::ALL, true);
        $get  = $this->get(self::ALL, true);
        $put  = $this->put(self::ALL, true);

        $this->post = new \stdClass;
        $this->get  = new \stdClass;
        $this->put  = new \stdClass;

        foreach ($post as $key => $value) {
            $this->post->{$key} = $value;
        }

        foreach ($get as $key => $value) {
            $this->get->{$key} = $value;
        }

        foreach ($put as $key => $value) {
            $this->put->{$key} = $value;
        }
    }


    /**
     *
     * Getter
     *
     * @param   string  key
     * @return  mixed   value
     * @access  public
     *
     */
    public function __get($key)
    {
        if (!empty($this->post->{$key})) {
            return $this->post->{$key};
        } elseif (!empty($this->get->{$key})) {
            return $this->get->{$key};
        } elseif (!empty($this->put->{$key})) {
            return $this->put->{$key};
        }

        return null;
    }

    /**
     *
     * get Post element
     *
     * @param    string    target post element
     * @param    bool      clean up the value for security purposes or not
     * @param    string    type of filter
     * @return   mixed     value of target post element
     * @access   public
     * @final
     *
     */
    public final function post($element, $clean=false, $filter=null)
    {
        if ($element == self::ALL) {
            if ($clean) {
                return $this->cleanUp($_POST, $filter);
            } else {
                return $_POST;
            }
        } else {
            if (!empty($_POST[$element])) {
                if ($clean) {
                    return $this->cleanUp($_POST[$element], $filter);
                } else {
                    return $_POST[$element];
                }
            } else {
                return null;
            }
           }
    }

    /**
     *
     * get Get element
     *
     * @param    string    target Get element
     * @param    bool      clean up the value for security purposes or not
     * @param    string    type of filter
     * @return   mixed     value of target Get element
     * @access   public
     * @final
     *
     */
    public final function get($element, $clean=false, $filter=null)
    {
        if ($element == self::ALL) {
            if ($clean) {
                return $this->cleanUp($_GET, $filter);
            } else {
                return $_GET;
            }
        } else {
            if (!empty($_GET[$element])) {
                if ($clean) {
                    return $this->cleanUp($_GET[$element], $filter);
                } else {
                    return $_GET[$element];
                }
            } else {
                return null;
            }
        }
    }

    /**
     *
     * get Put element
     *
     * @param    string    target Put element
     * @param    bool      clean up the value for security purposes or not
     * @param    string    type of filter
     * @return   mixed     value of target Get element
     * @access   public
     * @final
     *
     */
    public final function put($element, $clean=false, $filter=null)
    {
        global $_PUT;
        if (empty($GLOBALS['_PUT'])) {
            parse_str(file_get_contents('php://input'), $_PUT);
        }

        if ($element == self::ALL) {
            if ($clean) {
                return $this->cleanUp($_PUT, $filter);
            } else {
                return $_PUT;
            }
        } else {
            if (!empty($_PUT[$element])) {
                if ($clean) {
                    return $this->cleanUp($_PUT[$element], $filter);
                } else {
                    return $_PUT[$element];
                }
            } else {
                return null;
            }
        }
    }

    /**
     *
     * Clean the give value up
     *
     * @param    string    string to clean up
     * @param    string    filter to use
     * @return   string    cleaned up string
     * @access   private
     * @final
     *
     */
    private final function cleanUp($string, $filter=null)
    {
        if (is_array($string)) {
            foreach ($string as $num => $item) {
                $string[$num] = $this->cleanUp($item, $filter);
            }
            return $string;
        } else {
            if (is_string($string)) {
                $string = urldecode($string);
                $string = strip_tags($string);

                if (!empty($filter)) {
                    switch ($filter) {
                        case self::STRING:
                            $string = filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                            break;

                        case self::INT:
                        case self::ID:
                            $string = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
                            break;

                        case self::FLOAT:
                            $string = filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT);
                            break;

                        case self::EMAIL:
                            $string = filter_var($string, FILTER_SANITIZE_EMAIL);
                            break;

                        case self::SAFE:
                            $string = filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
                            break;
                    }
                }

                $mongoQueryAndProjection = [
                    '$gt',
                    '$gte',
                    '$in',
                    '$lt',
                    '$lte',
                    '$ne',
                    '$nin',
                    '$and',
                    '$nor',
                    '$not',
                    '$or',
                    '$exists',
                    '$type',
                    '$mod',
                    '$regex',
                    '$text',
                    '$where',
                    '$geoIntersects',
                    '$geoWithin',
                    '$nearSphere',
                    '$near',
                    '$all',
                    '$elemMatch',
                    '$size',
                    '$comment',
                    '$elemMatch',
                    '$meta',
                    '$slice'
                ];
                $string = str_replace($mongoQueryAndProjection, '', $string);
                return $string;
            } else {
                return $string;
            }
        }
    }

    /**
     *
     * Generate a CSRF token for a form (with complete html)
     *
     * @return  string  html with token
     * @access  public
     *
     */
    public function csrfToken()
    {
        $this->app->session->set('csrf_token', md5(uniqid(rand(), true)));
        $this->app->session->set('csrf_time', time());

        echo '<input type="hidden" name="__csrf" value="' . $this->app->session->get('csrf_token') . '" />';
    }

    /**
     *
     * Validate the current posted form to match the csrf token and time
     *
     * @param   int     minimum time to respect to detect "bots" or page reload
     * @param   int     maximum time before discarding the form as "timed out"
     * @return  bool    true = OK, false = CSRF hack attempt
     * @access  public
     *
     */
    public function csrfValidation($minimum=3, $maximum=1200)
    {
        $now = time();

        if (($now - $this->app->session->get('csrf_time')) <= $minimum) {
            return false;
        }

        if (($this->app->session->get('csrf_time') + $maximum) <= $now) {
            return false;
        }

        if (($this->app->session->get('csrf_token')) && ($this->app->input->post('__csrf')) ) {
            if ($this->app->session->get('csrf_token') == $this->app->input->post('__csrf')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
