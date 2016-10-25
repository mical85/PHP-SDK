<?php
namespace Synerise;

use GuzzleHttp\Client;
use Synerise\Adapter\Guzzle5 as Guzzle5Adapter;
use Synerise\Adapter\Guzzle6 as Guzzle6Adapter;

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
    const BASE_API_URL = 'http://api.synerise.com';

    /** @var string */
    const BASE_TCK_URL = 'http://tck.synerise.com/sdk-proxy';

    private static $_instances = array();

    /**
     * Returns a singleton instance of SyneriseAbstractHttpClient
     * @param array $config
     * @return SyneriseAbstractHttpClient
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
        $snrsP = isset($_COOKIE['_snrs_p'])?$_COOKIE['_snrs_p']:false;
        if ($snrsP) {
            $snrsP = explode('&', $snrsP);
            foreach ($snrsP as $snrs_part) {
                if (strpos($snrs_part, 'uuid:') !== false) {
                    return str_replace('uuid:', null, $snrs_part);
                }
            }
        }

        return false;
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
    protected function mergeConfig(array $config = []) {

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

}