<?php
namespace Synerise;

use GuzzleHttp\Collection;
use Synerise\Producers\Client;
use Synerise\Producers\Event;
use GuzzleHttp\Pool;
use GuzzleHttp\Ring\Client\MockHandler;
use GuzzleHttp\Subscriber\History;
use Synerise\Consumer\ForkCurlHandler;

class SyneriseTracker extends SyneriseAbstractHttpClient
{

    /**
     * An instance of the Client class (used to create/update client profiles)
     * @var Producers\Client
     */
    public $client;

    /**
     * An instance of the Event class (used for tracking custom event)
     * @var Producers\Event
     */
    public $event;

    /**
     * An instance of the Transaction class (used for tracking purchase event)
     * @var Producers\Transaction
     */
    public $transaction;


    /**
     * Instantiates a new SyneriseTracker instance.
     * @param array $config
     */
    public function __construct($config = array(), $logger = null)
    {
    	if(isset($config['allowFork']) && $config['allowFork'] == true){
			$config['handler'] = new ForkCurlHandler(array());
    	}

        parent::__construct($config, $logger);

        $this->client       = Producers\Client::getInstance();
        $this->event        = Producers\Event::getInstance();
        $this->transaction  = Producers\Transaction::getInstance();

    }

    /**
     * Flush the queue when we destruct the client with retries
     */
    public function __destruct() {
        $this->sendQueue();
    }

    public function sendQueue(){

        $data['json'] = array_merge(
            $this->event->getRequestQueue(),
            $this->transaction->getRequestQueue(),
            $this->client->getRequestQueue()
        );
        
        if(count($data['json']) == 0) {
            return;
        }

        try {
            $response = $this->post(SyneriseAbstractHttpClient::BASE_TCK_URL, $data);
        } catch (\Exception $e) {
            if($this->getLogger()) {
                $this->getLogger()->alert($e->getMessage());
            }
        }

        $this->flushQueue();

        if(isset($response) && $response->getStatusCode() == '200') {
            return true;
        }
        return false;
    }

    public function flushQueue() {
        $this->event->reset();
        $this->transaction->reset();
        $this->client->reset();
    }

    /**
     * @return bool|string
     */
    public function getSnrsParams()
    {
        $snrsP = isset($_COOKIE['_snrs_cl']) && !empty($_COOKIE['_snrs_cl'])?$_COOKIE['_snrs_cl']:false;
        if ($snrsP) {
            return $snrsP;
        }

        return false;
    }


    /**
     * Gets the default configuration options for the client
     *
     * @return array
     */
    public static function getDefaultConfig()
    {
        return [
            'base_url' => self::BASE_TCK_URL,
            'headers' => [
                'Content-Type' => self::DEFAULT_CONTENT_TYPE,
                'Accept' => self::DEFAULT_ACCEPT_HEADER,
                'User-Agent' => self::USER_AGENT,
            ]
        ];
    }

    public function formSubmit($label, $params = array(), $category = 'client.web.browser.contact')
    {
        $this->sendEvent('form.submit', $category, $label, $params);
    }

    public function sendEvent($action, $category, $label, $params = array())
    {
        $uuid = $this->getUuid();
        if(!isset($params['uuid']) && !empty($uuid)){
            $params['uuid'] = $uuid;
        }

        $data['label'] = $label;
        $data['params'] = $params;
        $data['action'] = $action;
        $data['category'] = $category;

        try {
            $response = $this->put('http://tck.synerise.com/tracker/' . $this->apiKey, array(
                'json' => $data,
                'timeout' => 1
            ));
        } catch (\Exception $e) {
            if($this->getLogger()) {
                $this->getLogger()->alert($e->getMessage());
            }
        }
        
        if(isset($response) && $response->getStatusCode() == '200') {
            return true;
        }
        return false;
    }

    public function renderJsScripts($trackingCode, $apiKey)
    {
        return '<script type="text/javascript">'
            .'var _riseA = _riseA || [];'
            .'_riseA.push([ \'setTracker\', \''.$trackingCode.'\' ]);'

            .'(function() {'
                .'var snrs = document.createElement(\'script\');'
                .'snrs.type = \'text/javascript\';'
                .'snrs.async = true;'
                .'snrs.src = (\'https:\' == document.location.protocol ? \'https://\''
                        .': \'http://\')'
                    .'+ \''.self::TC_HOST.'/'.self::TC_SCRIPT.'\';'
                .'var s = document.getElementsByTagName(\'script\')[0];'
                .'s.parentNode.insertBefore(snrs, s);'
            .'})();'
        .'</script>'
        .'<script>'
            .'function onSyneriseLoad() {'
                .'SR.auth.apiKey(\''.$apiKey.'\');'
                .'SR.init();'
            .'}'
            .'(function(s,y,n,e,r,i,se){s[\'SyneriseObjectNamespace\']=r;s[r]=s[r]||[],s[r]._t=1*new Date(),'
                .'s[r]._i=0,s[r]._l=i;var z=y.createElement(n),se=y.getElementsByTagName(n)[0];z.async=1;z.src=e;'
                .'se.parentNode.insertBefore(z,se);z.onload=z.onreadystatechange=function(){var rdy=z.readyState;'
                    .'if(!rdy||/complete|loaded/.test(z.readyState)){s[i]();z.onload = null;z.onreadystatechange=null;}};'
            .'})(window,document,\'script\',\''.self::JS_SDK_URL.'\',\'SR\', \'onSyneriseLoad\');'
        .'</script>';
    }

}