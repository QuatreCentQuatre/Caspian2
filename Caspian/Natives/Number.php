<?php

namespace Caspian\Natives;

class Number
{
    /* Type */
    const INT     = 'int';
    const INTEGER = 'integer';
    const FLOAT   = 'float';

    private $_value;
    private $_type;

    /**
     *
     * __construct
     *
     * Should never be called directly, use int or float factory methods
     *
     */
    public function __construct($value, $type)
    {
        if (is_string($value)) {
            $value = intval($value);
        }
        $this->_value = $value;
        $this->_type  = $type;
    }

    /**
     *
     * int
     *
     * Set the number as an integer with the given value
     *
     * @param   int     Number to set
     * @return  object  Number object
     * @access  public
     * @static
     *
     */
    public static function int($value)
    {
        return new self($value, self::INTEGER);
    }

    /**
     *
     * float
     *
     * Set the number as a float with the given value
     *
     * @param   float   Number to set
     * @return  object  Number object
     * @access  public
     * @static
     *
     */
    public static function float($value)
    {
        return new self($value, self::FLOAT);
    }

    /**
     *
     * isOdd
     *
     * is the number odd?
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isOdd()
    {
        return ($this->_value % 2 == 0) ? false : true;
    }

    /**
     *
     * isEven
     *
     * is the number even?
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isEven()
    {
        return ($this->_value % 2 == 0) ? true : false;
    }

    /**
     *
     * sum
     *
     * sum of a number (native) or Number class and this one
     *
     * @param   mixed   the number to use for the sum
     * @return  object  this modified object
     * @access  public
     *
     */
    public function sum($number)
    {
        if (is_object($number)) {
            if (get_class($number) == get_class(self)) {
                $this->_value += $number->getValue();
            }
        } else {
            $this->_value += $number;
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * sub
     *
     * subtract a number (native) or Number class off this one
     *
     * @param   mixed   the number to use for the substraction
     * @return  object  this modified object
     * @access  public
     *
     */
    public function sub($number)
    {
        if (is_object($number)) {
            if (get_class($number) == get_class(self)) {
                $this->_value -= $number->getValue();
            }
        } else {
            $this->_value -= $number;
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /* Alias of sub */
    public function substract($number) { return $this->sub($number); }

    /**
     *
     * times
     *
     * multiply a number (native) or Number class with this one
     *
     * @param   mixed   the number to use for the multiply
     * @return  object  this modified object
     * @access  public
     *
     */
    public function times($number)
    {
        if (is_object($number)) {
            if (get_class($number) == get_class(self)) {
                $this->_value = $this->_value * $number->getValue();
            }
        } else {
            $this->_value = $this->_value * $number;
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /* Alias of times */
    public function multiply($number) { return $this->times($number); }
    /**
     *
     * divide
     *
     * divide this number by a number (native) or Number class
     *
     * @param   mixed   the number to use for the division
     * @return  object  this modified object
     * @access  public
     *
     */
    public function divide($number)
    {
        if (is_object($number)) {
            if (get_class($number) == get_class(self)) {
                $this->_value = $this->_value / $number->getValue();
            }
        } else {
            $this->_value = $this->_value / $number;
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * mod
     *
     * Modulo this number by a number (native) or Number class
     *
     * @param   mixed   the number to use for the modulo
     * @return  mixed   the value of the modulo
     * @access  public
     *
     */
    public function mod($number)
    {
        if (is_object($number)) {
            if (get_class($number) == get_class(self)) {
                $this->_value ($this->_value % $number->getValue());
            }
        } else {
            $this->_value = ($this->_value % $number);
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * precision
     *
     * round the number to given precision
     *
     * @param   int     the precision value
     * @return  object  this modified object
     * @access  public
     *
     */
    public function precision($precision=2)
    {
        $this->_value = round($this->_value, $precision);
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /* Alias of precision */
    public function round($precision=2) { return $this->precision($precision); }

    /**
     *
     * percentage
     *
     * calculate percentage of this number by the given one (ex: 10 / 100 * 100;
     *
     * @param   int     the precision value
     * @return  object  this modified object
     * @access  public
     *
     */
    public function percentage($total)
    {
        if (is_object($total)) {
            if (get_class($total) == get_class(self)) {
                $this->_value = round($this->_value / $total->getValue() * 100, 2);
            }
        } else {
            $this->_value = round($this->_value / $total * 100, 2);
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * ceil
     *
     * round the number up
     *
     * @return  object  this modified object
     * @access  public
     *
     */
    public function ceil()
    {
        $this->_value = ceil($this->_value);
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * floor
     *
     * round the number down
     *
     * @return  object  this modified object
     * @access  public
     *
     */
    public function floor()
    {
        $this->_value = floor($this->_value);
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * squareRoot
     *
     * the square root of the number
     *
     * @return  object  this modified object
     * @access  public
     *
     */
    public function squareRoot()
    {
        $this->_value = sqrt($this->_value);
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /* Aliases of squareRoot */
    public function sqrRoot() { return $this->squareRoot(); }
    public function sqr() { return $this->squareRoot(); }
    public function sqrt() { return $this->squareRoot(); }

    public function convert($to)
    {
        if ($to == self::INT || $to == self::INTEGER) {
            $this->_type  = self::INTEGER;
            $this->_value = intval($this->_value);
        } elseif ($to == self::FLOAT) {
            $this->_type  = self::FLOAT;
            $this->_value = floatval($this->_value);
        }
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /**
     *
     * isTrue
     *
     * return true if the value of the number is positive and bigger than 0
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isTrue()
    {
        return ($this->_value > 0) ? true : false;
    }

    /**
     *
     * isFalse
     *
     * return false if the value of the number is positive and bigger than 0
     *
     * @return  bool    true/false
     * @access  public
     *
     */
    public function isFalse()
    {
        return ($this->_value > 0) ? false : true;
    }

    /**
     *
     * abs
     *
     * get absolute value of the number
     *
     * @return  object  this modified object
     * @access  public
     *
     */
    public function abs()
    {
        $this->_value = abs($this->_value);
        if (is_int($this->_value)) {
            return self::int($this->_value);
        } else {
            return self::float($this->_value);
        }
    }

    /* Alias of abs */
    public function absolute() { return $this->abs(); }

    /**
     *
     * getValue
     *
     * Get the value of the number (native number value)
     *
     * @return  mixed   int or float number
     * @access  protected
     *
     */
    protected function getValue()
    {
        return $this->_value;
    }

    /**
     *
     * __toString
     *
     * Return the value of the number when it's called directly (ex: echo $number;)
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