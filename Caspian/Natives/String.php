<?php

namespace Caspian\Natives;

use Caspian\Helpers\Text;
Use Caspian\Configuration;
use Caspian\Security\Encryption;

class String
{
    private $_value;

    /**
     *
     * __construct
     *
     * Should never be called directly, use init factory method instead
     *
     */
    public function __construct($value)
    {
        $this->_value = $value;
    }

    /**
     *
     * init
     *
     * Initialize a string with the given value
     *
     * @param   string  the value of the string
     * @return  object  string object
     * @access  public
     * @static
     *
     */
    public static function init($value)
    {
        return new String($value);
    }

    /**
     *
     * concat
     *
     * Concat a string with another
     *
     * @param   mixed   string/string object to concat
     * @return  object  string object
     * @access  public
     *
     */
    public function concat($string)
    {
        if (is_string($string)) {
            $string = self::init($string);
        }
        $out = '';
        if (get_class($string) == get_class($this)) {
            $out = $this->_value . $string->getValue();
        }
        return self::init($out);
    }

    /**
     *
     * length
     *
     * Get the length of the string
     *
     * @return  object  number object with the length of the string for value
     * @access  public
     *
     */
    public function length()
    {
        if ($this->mbAvailable()) {
            return Number::int(mb_strlen($this->_value));
        } elseif ($this->iconvAvailable()) {
            return Number::int(iconv_strlen($this->_value));
        } else {
            return Number::int(strlen($this->_value));
        }
    }

    /**
     *
     * substring
     *
     * Substring
     *
     * @param   mixed   int/number for offset
     * @param   mixed   int/number for length
     * @return  object  string object
     * @access  public
     *
     */
    public function substring($offset, $length)
    {
        if (is_object($offset)) {
            $offset = intval($offset);
        }
        if (is_object($length)) {
            $length = intval($length);
        }
        if ($this->mbAvailable()) {
            return self::init(mb_substr($this->_value, $offset, $length));
        } elseif ($this->iconvAvailable()) {
            return self::init(iconv_substr($this->_value, $offset, $length));
        } else {
            return self::init(substr($this->_value, $offset, $length));
        }
    }

    /**
     *
     * lower
     *
     * Lowercase the string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function lower()
    {
        if ($this->mbAvailable()) {
            return self::init(mb_strtolower($this->_value));
        } else {
            return self::init(strtolower($this->_value));
        }
    }

    /**
     *
     * upper
     *
     * Uppercase the string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function upper()
    {
        if ($this->mbAvailable()) {
            return self::init(mb_strtoupper($this->_value));
        } else {
            return self::init(strtoupper($this->_value));
        }
    }

    /**
     *
     * wordwrap
     *
     * Wordwrap the string
     *
     * @param   mixed   int/number of words per line
     * @param   mixed   string/string object for end of line character
     * @return  object  string object
     * @access  public
     *
     */
    public function wordwrap($words_per_line=20, $eol_char='<br/>')
    {
        if (is_object($words_per_line)) {
            $words_per_line = intval($words_per_line);
        }
        $lines = array_chunk(explode(" ", $this->_value), $words_per_line);
        $out   = null;
        foreach ($lines as $chunk) {
            $out .= implode(" ", $chunk) . (string)$eol_char;
        }
        return self::init($out);
    }

    /**
     *
     * truncate
     *
     * Truncate a phrase to a given number of characters.
     *
     * @param   mixed     int/number of characters to limit to
     * @param   mixed     string/string object end character or entity
     * @param   boolean   enable or disable the preservation of words while limiting
     * @return  object    truncated string object
     * @access  public
     *
     */
    public function truncate($length=25, $end_char='...', $preserve_words=true)
    {
        $text = new Text;
        $out  = $text->truncate($this->_value, intval($length), (string)$end_char, $preserve_words);
        return self::init($out);
    }

    /**
     *
     * capitalize
     *
     * Capitalize the string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function capitalize()
    {
        return self::init(ucfirst($this->_value));
    }

    /**
     *
     * encode
     *
     * Base64 encode string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function encode()
    {
        return self::init(base64_encode($this->_value));
    }

    /**
     *
     * decode
     *
     * Base64 decode string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function decode()
    {
        return self::init(base64_decode($this->_value));
    }

    /**
     *
     * urlencode
     *
     * Url encode string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function urlencode()
    {
        return self::init(rawurlencode($this->_value));
    }

    /**
     *
     * urldecode
     *
     * Url decode string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function urldecode()
    {
        return self::init(rawurldecode($this->_value));
    }

    /**
     *
     * encrypt
     *
     * encrypt string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function encrypt()
    {
        $config    = Configuration::get('configuration', 'general.crypt_key');
        $encrypted = Encryption::encrypt($this->_value);
        return self::init($encrypted);
    }

    /**
     *
     * decrypt
     *
     * decrypt encrypted string
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function decrypt()
    {
        $config    = Configuration::get('configuration', 'general.crypt_key');
        $decrypted = Encryption::decrypt($this->_value);
        return self::init($decrypted);
    }

    /**
     *
     * slug
     *
     * create a slug from string (ex: "This Is My String" turns into "this-is-my-string")
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function slug()
    {
        $text = new Text;
        return self::init($text->slug($this->_value));
    }

    /**
     *
     * contains
     *
     * Check if string contains given string
     *
     * @param   mixed   string/string object to check for
     * @return  bool    true/false
     * @access  public
     *
     */
    public function contains($string)
    {
        $string = (string)$string;
        if (stristr($this->_value, $string)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * position
     *
     * Try to find the given string and return the position of where it is
     *
     * @param   mixed   string/string object
     * @return  object  number object
     * @access  public
     *
     */
    public function position($string)
    {
        $string = (string)$string;
        if ($this->mbAvailable()) {
            return Number::int(mb_stripos($this->_value, $string));
        } elseif ($this->iconvAvailable()) {
            return Number::int(iconv_strpos($this->_value, $string));
        } else {
            return Number::int(stripos($this->_value, $string));
        }
    }

    /**
     *
     * equals
     *
     * Check if string is equal to given string
     *
     * @param   mixed   string/string object to check out
     * @return  bool    true/false
     * @access  public
     *
     */
    public function equals($string)
    {
        $string = (string)$string;
        if ($this->_value == $string) {
            return true;
        }
        return false;
    }

    /**
     *
     * isEmpty
     *
     * check if string is empty
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isEmpty()
    {
        if (empty($this->_value)) {
            return true;
        }
        return false;
    }

    /**
     *
     * replace
     *
     * replace strings with others
     *
     * @param   mixed   string/string object/array to replace
     * @param   mixed   string/string object/array to replace with
     * @return  object  string object
     * @access  public
     *
     */
    public function replace($change, $to)
    {
        if (is_array($change)) {
            foreach ($change as $num => $item) {
                $change[$num] = (string)$item;
            }
        } else {
            $change = (string)$change;
        }
        if (is_array($change)) {
            foreach ($to as $num => $item) {
                $to[$num] = (string)$item;
            }
        } else {
            $to = (string)$to;
        }
        return self::init(str_replace($change, $to, $this->_value));
    }

    /**
     *
     * hash
     *
     * return the md5 hash
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function hash()
    {
        return self::init(md5($this->_value));
    }

    /**
     *
     * trim
     *
     * Trim the whitespace off the string
     *
     * @return  object   string object
     * @access  public
     *
     */
    public function trim()
    {
        return self::init(trim($this->_value));
    }

    /**
     *
     * format
     *
     * Format a string with variables (sprintf type thing)
     *
     * @param   array   list of variables to handle
     * @return  object  string object
     * @access  public
     *
     */
    public function format($variables)
    {
        return self::init(vsprintf($this->_value, $variables));
    }

    /**
     *
     * filter
     *
     * Filter the string to make it safe
     *
     * @return  object  string object
     * @access  public
     *
     */
    public function filter()
    {
        return self::init(filter_var($this->_value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));
    }

    /**
     *
     * mbAvailable
     *
     * check if multibyte is available
     *
     * @return  bool    true/false
     * @access  private
     *
     */
    private function mbAvailable()
    {
        if (function_exists('mb_strlen')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * iconvAvailable
     *
     * check if iconv is available
     *
     * @return  bool    true/false
     * @access  private
     *
     */
    private function iconvAvailable()
    {
        if (function_exists('iconv_strlen')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * getValue
     *
     * Get the value of the string
     *
     * @return  string   the value
     * @access  protected
     *
     */
    protected function getValue()
    {
        return $this->_value;
    }

    /**
     *
     * out
     *
     * output the value of the string (like: echo "string"; $string->out();)
     *
     */
    public function out()
    {
        echo $this->_value;
    }

    /**
     *
     * __toString
     *
     * Return the value of the string when it's called directly (ex: echo $string;)
     *
     * @return  string representation of the value
     * @access  public
     *
     */
    public function __toString()
    {
        return (string)$this->_value;
    }
}