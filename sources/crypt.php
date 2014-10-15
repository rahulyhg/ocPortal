<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__crypt()
{
    define('SALT_MD5PASSWORD',0);
    define('PASSWORD_SALT',1);

    /**
	 * A Compatibility library with PHP 5.5's simplified password hashing API.
	 *
	 * @author Anthony Ferrara <ircmaxell@php.net>
	 * @license http://www.opensource.org/licenses/mit-license.html MIT License
	 * @copyright 2012 The Authors
	 */

    if ((!defined('PASSWORD_DEFAULT')) && (function_exists('crypt'))) {
        define('PASSWORD_BCRYPT',1);
        define('PASSWORD_DEFAULT',PASSWORD_BCRYPT);

        /**
		 * Hash the password using the specified algorithm
		 *
		 * @param  string		The password to hash
		 * @param  integer	The algorithm to use (Defined by PASSWORD_* constants)
		 * @param  array		The options for the algorithm to use
		 * @return ~string	The hashed password (false: error)
		 */
        function password_hash($password,$algo,$options)
        {
            if (!is_integer($algo)) {
                trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given",E_USER_WARNING);
                return false;
            }
            $result_length = 0;
            switch ($algo) {
                case PASSWORD_BCRYPT:
                    // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                    $cost = 10;
                    if (isset($options['cost'])) {
                        $cost = $options['cost'];
                        if ($cost<4 || $cost>31) {
                            trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d",$cost),E_USER_WARNING);
                            return false;
                        }
                    }
                    // The length of salt to generate
                    $raw_salt_len = 16;
                    // The length required in the final serialization
                    $required_salt_len = 22;
                    if (version_compare(PHP_VERSION,'5.3.7') >= 0) {
                        $hash_format = sprintf("$2y$%02d$",$cost);
                    } else {
                        $hash_format = sprintf("$2a$%02d$",$cost);
                    }
                    // The expected length of the final crypt() output
                    $result_length = 60;
                    break;
                default:
                    trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s",$algo),E_USER_WARNING);
                    return false;
            }
            $salt_requires_encoding = false;
            if (isset($options['salt'])) {
                $salt = $options['salt'];
                if (_crypt_strlen($salt)<$required_salt_len) {
                    trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d",_crypt_strlen($salt),$required_salt_len),E_USER_WARNING);
                    return false;
                } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D',$salt)) {
                    $salt_requires_encoding = true;
                }
            } else {
                $buffer = '';
                $buffer_valid = false;
                if ((function_exists('mcrypt_create_iv')) && (!defined('PHALANGER'))) {
                    $buffer = mcrypt_create_iv($raw_salt_len,MCRYPT_DEV_URANDOM);
                    if ($buffer !== false) {
                        $buffer_valid = true;
                    }
                }
                if ((!$buffer_valid) && (function_exists('openssl_random_pseudo_bytes')) && (get_value('disable_openssl') !== '1')) {
                    $buffer = openssl_random_pseudo_bytes($raw_salt_len);
                    if ($buffer !== false) {
                        $buffer_valid = true;
                    }
                }
                if (!$buffer_valid && @is_readable('/dev/urandom')) {
                    $f = fopen('/dev/urandom','r');
                    $read = _crypt_strlen($buffer);
                    while ($read<$raw_salt_len) {
                        $buffer .= fread($f,$raw_salt_len-$read);
                        $read = _crypt_strlen($buffer);
                    }
                    fclose($f);
                    if ($read >= $raw_salt_len) {
                        $buffer_valid = true;
                    }
                }
                if (!$buffer_valid || _crypt_strlen($buffer)<$raw_salt_len) {
                    $bl = _crypt_strlen($buffer);
                    for ($i = 0;$i<$raw_salt_len;$i++) {
                        if ($i<$bl) {
                            $buffer[$i] = $buffer[$i]^chr(mt_rand(0,255));
                        } else {
                            $buffer .= chr(mt_rand(0,255));
                        }
                    }
                }
                $salt = $buffer;
                $salt_requires_encoding = true;
            }
            if ($salt_requires_encoding) {
                // encode string with the Base64 variant used by crypt
                $base64_digits = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
                $bcrypt64_digits = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

                $base64_string = base64_encode($salt);
                $salt = strtr(rtrim($base64_string,'='),$base64_digits,$bcrypt64_digits);
            }
            $salt = _crypt_substr($salt,0,$required_salt_len);

            $hash = $hash_format . $salt;

            $ret = crypt($password,$hash);

            if (!is_string($ret) || _crypt_strlen($ret) == 0/* || _crypt_strlen($ret)!=$result_length  causes problem on mac with old PHP version*/) {
                return false;
            }

            return $ret;
        }

        /**
		 * Verify a password against a hash using a timing attack resistant approach
		 *
		 * @param  string		The password to verify
		 * @param  string		The hash to verify against
		 * @return boolean	If the password matches the hash
		 */
        function password_verify($password,$hash)
        {
            $ret = crypt($password,$hash);
            if (!is_string($ret) || _crypt_strlen($ret) != _crypt_strlen($hash) || _crypt_strlen($ret) == 0/* || _crypt_strlen($ret)<=13  causes problem on mac with old PHP version*/) {
                return false;
            }

            $status = 0;
            for ($i = 0;$i<_crypt_strlen($ret);$i++) {
                $status |= (ord($ret[$i])^ord($hash[$i]));
            }

            return $status === 0;
        }

        /**
		 * Count the number of bytes in a string
		 *
		 * We cannot simply use strlen() for this, because it might be overwritten by the mbstring extension.
		 * In this case, strlen() will count the number of *characters* based on the internal encoding. A
		 * sequence of bytes might be regarded as a single multibyte character.
		 *
		 * @param  string		The input string
		 * @return integer	The number of bytes
		 */
        function _crypt_strlen($binary_string)
        {
            if (function_exists('mb_strlen')) {
                return mb_strlen($binary_string,'8bit');
            }
            return strlen($binary_string);
        }

        /**
		 * Get a substring based on byte limits
		 *
		 * @see _strlen()
		 *
		 * @param string		The input string
		 * @param integer		Start
		 * @param integer		Length
		 * @return string		The substring
		 */
        function _crypt_substr($binary_string,$start,$length)
        {
            if (function_exists('mb_substr')) {
                return mb_substr($binary_string,$start,$length,'8bit');
            }
            return substr($binary_string,$start,$length);
        }
    }
}

/**
 * Do a hashing, with support for our "ratcheting up" algorithm (i.e. lets the admin increase the complexity over the time, as CPU speeds get faster).
 *
 * @param  SHORT_TEXT	The password in plain text
 * @param  SHORT_TEXT	The salt
 * @param  integer		Legacy hashing style to fallback to
 * @return SHORT_TEXT	The salted&hashed password
 */
function ratchet_hash($password,$salt,$legacy_style = 0)
{
    if (function_exists('password_hash')) {
        return password_hash($salt . md5($password),PASSWORD_BCRYPT,array('cost' => intval(get_option('crypt_ratchet'))));
    }

    // Fallback for old versions of PHP
    if ($legacy_style == PASSWORD_SALT) {
        return md5($password . $salt);
    }
    return md5($salt . md5($password));
}

/**
 * Verify a password is correct by comparison of the hashed version.
 *
 * @param  SHORT_TEXT	The password in plain text
 * @param  SHORT_TEXT	The salt
 * @param  SHORT_TEXT	The prior salted&hashed password, which will also include the algorithm/ratcheting level (unless it's old style, in which case we use non-ratcheted md5)
 * @param  integer		Legacy hashing style to fallback to
 * @return boolean		Whether the password if verified
 */
function ratchet_hash_verify($password,$salt,$pass_hash_salted,$legacy_style = 0)
{
    if ((function_exists('password_verify')) && (preg_match('#^\w+$#',$pass_hash_salted) == 0)) {
        return password_verify($salt . md5($password),$pass_hash_salted);
    }

    // Old-style md5'd password
    if ($legacy_style == PASSWORD_SALT) {
        return (md5($password . $salt) == $pass_hash_salted);
    }
    return (md5($salt . md5($password)) == $pass_hash_salted);
}

/**
 * Get a decent randomised salt.
 *
 * @return ID_TEXT		The salt
 */
function produce_salt()
{
    // md5 used in all the below so that we get nice ASCII characters

    if ((function_exists('openssl_random_pseudo_bytes')) && (get_value('disable_openssl') !== '1')) {
        $u = substr(md5(openssl_random_pseudo_bytes(13)),0,13);
    } elseif (function_exists('password_hash')) { // password_hash will include a randomised component
        return substr(md5(password_hash(uniqid('',true),PASSWORD_BCRYPT,array('cost' => intval(get_option('crypt_ratchet'))))),0,13);
    } else {
        $u = substr(md5(uniqid(strval(get_secure_random_number()),true)),0,13);
    }
    return $u;
}

/**
 * Get the site-wide salt. It should be something hard for a hacker to get, so we depend on data gathered both from the database and file-system.
 *
 * @return ID_TEXT		The salt
 */
function get_site_salt()
{
    $site_salt = get_value('site_salt');
    if ($site_salt === NULL) {
        $site_salt = produce_salt();
        set_value('site_salt',$site_salt);
    }
    //global $SITE_INFO; This is unstable on some sites, as the array can be prepopulated on the fly
    //$site_salt.=serialize($SITE_INFO);
    return md5($site_salt);
}

/**
 * Get a randomised password.
 *
 * @return string			The randomised password
 */
function get_rand_password()
{
    return produce_salt();
}

/**
 * Get a secure random number, the best this PHP version can do.
 *
 * @return integer		The randomised number
 */
function get_secure_random_number()
{
    // 2147483647 is from MySQL limit http://dev.mysql.com/doc/refman/5.0/en/integer-types.html ; PHP_INT_MAX is higher on 64bit machines
    if ((function_exists('openssl_random_pseudo_bytes')) && (get_value('disable_openssl') !== '1')) {
        $code = intval(2147483647*(hexdec(bin2hex(openssl_random_pseudo_bytes(4)))/0xffffffff));
        if ($code<0) {
            $code = -$code;
        }
    } elseif (function_exists('password_hash')) { // password_hash will include a randomised component
        $hash = password_hash(uniqid('',true),PASSWORD_BCRYPT,array('cost' => intval(get_option('crypt_ratchet'))));
        return crc32($hash);
    } else {
        $code = mt_rand(0,min(2147483647,mt_getrandmax()));
    }
    return $code;
}

/**
 * Check the given master password is valid.
 *
 * @param  SHORT_TEXT	Given master password
 * @return boolean		Whether it is valid
 */
function check_master_password($password_given)
{
    if (isset($GLOBALS['SITE_INFO']['admin_password'])) { // LEGACY
        $GLOBALS['SITE_INFO']['master_password'] = $GLOBALS['SITE_INFO']['admin_password'];
        unset($GLOBALS['SITE_INFO']['admin_password']);
    }

    global $SITE_INFO;
    if (!array_key_exists('master_password',$SITE_INFO)) {
        exit('No master password defined in _config.php currently so cannot authenticate');
    }
    $actual_password_hashed = $SITE_INFO['master_password'];
    if ((function_exists('password_verify')) && (strpos($actual_password_hashed,'$') !== false)) {
        return password_verify($password_given,$actual_password_hashed);
    }
    $salt = '';
    if ((substr($actual_password_hashed,0,1) == '!') && (strlen($actual_password_hashed) == 33)) {
        $actual_password_hashed = substr($actual_password_hashed,1);
        $salt = 'ocp';
    }
    return (((strlen($password_given) != 32) && ($actual_password_hashed == $password_given)) || ($actual_password_hashed == md5($password_given . $salt)));
}
