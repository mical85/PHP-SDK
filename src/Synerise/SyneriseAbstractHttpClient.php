<?php
namespace Synerise;

use GuzzleHttp\Client;
use Synerise\Adapter\Guzzle5 as Guzzle5Adapter;
use Synerise\Adapter\Guzzle6 as Guzzle6Adapter;
use Detection\MobileDetect as Mobile_Detect;

abstract class SyneriseAbstractHttpClient extends Client
{

    /** @var array The required config variables for this type of client */
    public static $required = [
        'apiKey',
        'headers',
    ];

    /** @var string */
    const DEFAULT_CONTENT_TYPE = 'application/json';

    /** @var string */
    const DEFAULT_ACCEPT_HEADER = 'application/json';

    /** @var string */
    const USER_AGENT = 'synerise-php-sdk';

    /** @var string */
    const DEFAULT_API_VERSION = '3.0';

    /** @var string */
    const BASE_API_URL = 'https://api.synerise.com';

    /** @var string */
    const BASE_TCK_URL = 'https://tck.synerise.com/sdk-proxy';

    /** @var string */
    const TC_HOST = 'tc.synerise.com';

    /** @var string */
    const TC_SCRIPT = 'snrs-2.0.js';

    /** @var string */    
    const JS_SDK_URL = 'https://app.synerise.com/js/sdk/synerise-javascript-sdk-latest.min.js';

    const SOURCE_DESKTOP_WEB        = 'WEB_DESKTOP';
    const SOURCE_MOBILE_APP         = 'MOBILE';
    const SOURCE_MOBILE_WEB         = 'MOBILEWEB';
    const SOURCE_POS                = 'POS';
    const SOURCE_UNDEFINED          = 'UNDEFINED';

    /**
     * Client context for standard clent session.
     * Allows cookie use.
     *
     * @var string
     */
    const APP_CONTEXT_CLIENT        = 'client';

    /**
     * System context for cli, cron, admin, etc
     * 
     * @var string
     */
    const APP_CONTEXT_SYSTEM        = 'system';

    private static $_instances = array();

    private $_context = self::APP_CONTEXT_CLIENT;

    protected $_apiKey = null;

    /**
     * Returns a singleton instance of Synerise Client
     * @param array $config
     * @return self
     */
    public static function getInstance($config = array(), $logger = null)
    {
        $class = get_called_class();

        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class($config, $logger);
        }
        return self::$_instances[$class];
    }

    /**
     * Instantiates a new instance.
     * @param array $config
     */
    public function __construct($config = array(), $logger = null)
    {
        $this->_logger = $logger;

        if(isset($config['apiKey'])) {
            $this->_apiKey = $config['apiKey'];
        }

        if(isset($config['context']) && $config['context'] == self::APP_CONTEXT_SYSTEM) {
            $this->_context = self::APP_CONTEXT_SYSTEM;
        } else {
            $this->_context = self::APP_CONTEXT_CLIENT;
            $this->getUuid();
        }

        switch (substr(self::VERSION,0,1)):
            case '6':
                $config = Guzzle6Adapter::prepareConfig(self::mergeConfig($config), $logger);
                parent::__construct($config);
                break;
            case '5':
                $config = Guzzle5Adapter::prepareConfig(self::mergeConfig($config), $logger);
                parent::__construct($config);
                $this->setDefaultOption('headers', $config['headers']);
                break;
            default:
                throw new \Exception('Unsupported Guzzle version. Please use Guzzle 6.x or 5.x.');
        endswitch;
    }

    /**
     * Overrides the error handling in Guzzle so that when errors are encountered we throw
     * Synersie errors, not Guzzle ones.
     *
     */
    private function setErrorHandler()
    {
            //@TODO ErrorHendler
    }

    /**
     * Gets the default configuration options for the client
     *
     * @return array
     */
    public static function getDefaultConfig()
    {
        return [
            'base_url' => self::BASE_API_URL,
            'headers' => [
                'Content-Type' => self::DEFAULT_CONTENT_TYPE,
                'Accept' => self::DEFAULT_ACCEPT_HEADER,
                'User-Agent' => self::USER_AGENT.'/'.self::DEFAULT_API_VERSION,
                'Api-Version' => self::DEFAULT_API_VERSION,
            ]
        ];
    }

    /**
     * @return bool|string
     */
    protected function getUuid()
    {
        if(empty($this->uuid) && $this->_context == self::APP_CONTEXT_CLIENT) {

            $this->uuid = isset($_COOKIE['_snrs_uuid']) ? $_COOKIE['_snrs_uuid'] : false;

            if(empty($this->uuid)) {
                $snrsP = isset($_COOKIE['_snrs_p'])?$_COOKIE['_snrs_p']:false;
                if ($snrsP) {
                    $snrsP = explode('&', $snrsP);
                    foreach ($snrsP as $snrs_part) {
                        if (strpos($snrs_part, 'uuid:') !== false) {
                            $this->uuid = str_replace('uuid:', null, $snrs_part);
                        }
                    }
                }
            }

            if(empty($this->uuid)) {
                if (headers_sent()) {
                    if($this->getLogger()) {
                        $this->getLogger()->alert('Headers already sent. Cookie cannot be set');
                    }
                } else {
                    $this->uuid = $this->generateUuidV4();
                    setcookie("_snrs_uuid", $this->uuid, 2147483647);
                    setcookie("_snrs_p", 'uuid:'.$this->uuid, 2147483647);
                }
            }
            
        }

        return $this->uuid;
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Merge config with defaults
     *
     * @param array $config   Configuration values to apply.
     *
     * @return array
     * @throws \InvalidArgumentException if a parameter is missing
     */
    protected function mergeConfig(array $config = array()) {

        $defaults = static::getDefaultConfig();
        $required = static::$required;

        $data = $config + $defaults;

        if ($missing = array_diff($required, array_keys($data))) {
            throw new \InvalidArgumentException(
                'Config is missing the following keys: ' .
                implode(', ', $missing));
        }

        if(isset($config['apiKey'])) {
            $data['headers']['Api-Key'] = $config['apiKey'];
        }

        if(isset($config['apiVersion'])) {
            $data['headers']['Api-Version'] = $config['apiVersion'];
            $data['headers']['User-Agent'] = self::USER_AGENT.'/'.$config['apiVersion'];
        }

        return ($data);
    }

    public static function generateUuidV4(){
      return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
      );
    }

    public function isMobile()
    {
        $detect = new Mobile_Detect;
        return $detect->isMobile();
    }

    public function getCategoryBySource($source, $action)
    {
        if($source == self::SOURCE_POS) {
            return "client.retail.pos.core";
        } elseif($source == self::SOURCE_MOBILE_APP) {
            return "client.mobile.application.screen";
        } else {
            // category trigger
            $cTrigger = 'client';

            // category env
            $cEnv = 'web';

            if($source == self::SOURCE_MOBILE_WEB) {
                $cEnv = 'mobile';
            }

            // category source
            $cSource = 'browser';

            // category medium
            $cMedium = 'page';

            if($action == 'form.submit') {
                $cMedium = 'contact';
            }

            return "$cTrigger.$cEnv.$cSource.$cMedium";
        }
    }

    public function getSource()
    {
        return $this->isMobile() ? self::SOURCE_MOBILE_WEB : self::SOURCE_DESKTOP_WEB;
    }

}