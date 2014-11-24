<?php

namespace Caspian\Security;

use Caspian\Base;
use Caspian\Configuration;

class Hash extends Base
{
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
    public static function encrypt($string, $cipher='rijndael')
    {
        $key = Configuration::get('configuration', 'general.crypt_key');

        switch (strtolower($cipher))
        {
            default:
            case "rijndael":
                $cipher = new \Crypt_Rijndael();
                $cipher->setBlockLength(256);
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case "aes":
                $cipher = new \Crypt_AES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case "blowfish":
                $cipher = new \Crypt_Blowfish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case "twofish":
                $cipher = new \Crypt_Twofish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case "tripledes":
            case "3des":
                $cipher = new \Crypt_TripleDES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;
        }

        return $cipher->encrypt($string);
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
    public static function decrypt($string, $cipher='rijndael')
    {
        $key = Configuration::get('configuration', 'general.crypt_key');

        switch (strtolower($cipher))
        {
            default:
            case "rijndael":
                $cipher = new \Crypt_Rijndael();
                $cipher->setBlockLength(256);
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case "aes":
                $cipher = new \Crypt_AES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000, 256 / 8);
                break;

            case "blowfish":
                $cipher = new \Crypt_Blowfish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case "twofish":
                $cipher = new \Crypt_Twofish();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;

            case "tripledes":
            case "3des":
                $cipher = new \Crypt_TripleDES();
                $cipher->setPassword($key, 'pbkdf2', 'sha1', 'phpseclib/salt', 1000);
                break;
        }

        return $cipher->decrypt($string);
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
