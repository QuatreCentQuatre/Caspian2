<?php

namespace Caspian\Security;

use Caspian\Base;
use Caspian\Configuration;

class Encryption extends Base
{
    /* Encryption Type */
    const RIJNDAEL  = 'rijndael';
    const AES       = 'aes';
    const BLOWFISH  = 'blowfish';
    const TWOFISH   = 'twofish';
    const TRIPLEDES = 'tripledes';
    const THREEDES  = '3des';

    /**
     *
     * encrypt
     *
     * Encrypt string with static password salt
     *
     * @param   string  string to encrypt
     * @param   string  encryption cipher
     * @return  string  encrypted string
     * @access  public
     * @static
     *
     */
    public static function encrypt($string, $cipher=self::RIJNDAEL)
    {
        $key = Configuration::get('configuration', 'general.crypt_key');

        switch (strtolower($cipher))
        {
            default:
            case self::RIJNDAEL:
                $cipher = new \Crypt_Rijndael();
                $cipher->setBlockLength(256);
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case self::AES:
                $cipher = new \Crypt_AES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case self::BLOWFISH:
                $cipher = new \Crypt_Blowfish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case self::TWOFISH:
                $cipher = new \Crypt_Twofish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case self::TRIPLEDES:
            case self::THREEDES:
                $cipher = new \Crypt_TripleDES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;
        }

        return utf8_encode($cipher->encrypt($string));
    }

    /**
     *
     * decrypt
     *
     * Decrypt string with static password salt
     *
     * @param   string  string to decrypt
     * @param   string  encryption cipher
     * @return  string  clean string
     * @access  public
     * @static
     *
     */
    public static function decrypt($string, $cipher=self::RIJNDAEL)
    {
        $key = Configuration::get('configuration', 'general.crypt_key');

        switch (strtolower($cipher))
        {
            default:
            case self::RIJNDAEL:
                $cipher = new \Crypt_Rijndael();
                $cipher->setBlockLength(256);
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case self::AES:
                $cipher = new \Crypt_AES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case self::BLOWFISH:
                $cipher = new \Crypt_Blowfish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case self::TWOFISH:
                $cipher = new \Crypt_Twofish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case self::TRIPLEDES:
            case self::THREEDES:
                $cipher = new \Crypt_TripleDES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;
        }

        return utf8_encode($cipher->decrypt($string));
    }

    /**
     *
     * encrypt a secure password
     *
     * @param   string  password to hash
     * @return  string  secure encrypted password
     * @access  publoc
     *
     */
    public function password($password)
    {
        $key = Configuration::get('configuration', 'general.hash_salt');

        /* Try safer encryption method, if it fails, hash it with sha256 */
        if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
            $salt = '$2y$11$' . substr(md5($password . $key), 0, 22);
            $hash = crypt($password, $salt);
        } else {
            $int_salt = md5($password . $key);
            $salt     = substr($int_salt, 0, 22);
            $hash     = hash('sha256', $password . $salt);
        }

        return $hash;
    }
}
