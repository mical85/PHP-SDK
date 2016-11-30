<?php
namespace Synerise\Helper;

class Cookie
{
    const SNRS_P    = '_snrs_p';
    const SNRS_UUID = '_snrs_uuid';

    protected $_data;

    protected $_uuid;

    private static $_instance;

    /**
     * Determine whether current session allows cookie use
     *
     * @return bool
     */
    public static function isAllowedUse() {
        return (!headers_sent() && php_sapi_name() !== "cli") ? true : false;
    }

    /**
     * Return cookie if set
     *
     * @param string $name
     * @return string
     */
    public function getCookieString($name) {
        if(!isset($this->data[$name])) {
           $this->data[$name] = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        }
        return $this->data[$name];
    }

    /**
     * Return cookie as array
     *
     * @param string $name
     * @return array|null
     */
    public function getCookie($name) {
        return $this->_breakCookie($this->getCookieString($name));
    }

    public function getEmailHash() {
        $p = $this->getCookie(self::SNRS_P);
        return isset($p['emailHash']) ? $p['emailHash'] : null;
    }

    public function setEmailHash($hash) {
        $_snrs_p = $this->getCookie(self::SNRS_P);
        if(!is_array($_snrs_p)) {
            $_snrs_p = array();

        }
        $_snrs_p['emailHash'] = $hash;
        $this->setCookie(self::SNRS_P, $_snrs_p);
        return true;
    }

    public function getUuid()
    {
        if(empty($this->_uuid)) {
            $this->_uuid = isset($_COOKIE['_snrs_uuid']) ? $_COOKIE['_snrs_uuid'] : false;

            if(empty($this->_uuid)) {
                $snrsP = isset($_COOKIE['_snrs_p'])?$_COOKIE['_snrs_p']:false;
                if ($snrsP) {
                    $snrsP = explode('&', $snrsP);
                    foreach ($snrsP as $snrs_part) {
                        if (strpos($snrs_part, 'uuid:') !== false) {
                            $this->_uuid = str_replace('uuid:', null, $snrs_part);
                        }
                    }
                }
            }
        }
        return $this->_uuid;
    }

    public function setCookie($name, $value) {
        $string = is_array($value) ? static::_buildCookie($value) : $value;
        $this->data[$name] = $string;
        return setcookie($name, (string) $string, 2147483647);
    }

    public function setUuid($uuid) {
        $this->uuid = $uuid;
        $_snrs_p = $this->getCookie(self::SNRS_P);
        if(!is_array($_snrs_p)) {
            $_snrs_p = array();
            
        }
        $_snrs_p['uuid'] = $uuid;
        $this->setCookie(self::SNRS_P, $_snrs_p);
        $this->setCookie(self::SNRS_UUID, $uuid);
        return true;
    }

    protected function _buildCookie($array) {
        if (is_array($array)) {
            $out = '';
            foreach ($array as $index => $data) {
                $out.= ($data!="") ? $index.":".$data."&" : "";
            }
        }
        
        return rtrim($out,"&");
    }

    protected function _breakCookie($cookieString) {
        $array = explode("&",$cookieString);

        if(count($array) < 2) {
            return $cookieString;
        }

        foreach ($array as $i=>$stuff) {
            $stuff = explode(":",$stuff);
            $array[$stuff[0]] = $stuff[1];
            unset($array[$i]);
        }

        return $array;
    }

    /**
     * Returns a singleton instance
     * @return self
     */
    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$_instance)) {
            self::$_instance = new $class();
        }
        return self::$_instance;
    }

}