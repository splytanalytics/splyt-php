<?php
require_once dirname(__FILE__).'/ssftype.php';
require_once dirname(__FILE__).'/ssferrors.php';
require_once dirname(__FILE__).'/ssfcurl.php';

define('SSFSendTimeToken', '##_-SeNdTiMe-_##');
if(!function_exists('SSFGetSendMicrotime'))
{
    function SSFGetSendMicrotime()
    {
        // We use sprintf to maintain the time as a string as opposed returning a float/double, otherwise we may lose precision
        // This is because once we assign to a double, the number of significant digits is controlled by the precision value set in the php.ini file
        // And we don't want to rely on the user's php settings for precision
        return sprintf("%lf", microtime(true));
    }
}
define('SSFEventTimeToken', '##_-EvEnTtImE-_##');
if(!function_exists('SSFGetEventMicrotime'))
{
    function SSFGetEventMicrotime()
    {
        // We use sprintf to maintain the time as a string as opposed returning a float/double, otherwise we may lose precision
        // This is because once we assign to a double, the number of significant digits is controlled by the precision value set in the php.ini file
        // And we don't want to rely on the user's php settings for precision
        return sprintf("%lf", microtime(true));
    }
}

class SSFClient
{
    private $config;
    private $curl;
    private $batch;

    /**
     * @param string $config array of configuration parameters, which override any statically
     *     defined configuration.  See applyConfig() for a list of supported options.
     */
    function __construct($config=null, $async = false)
    {
        $this->config = self::applyConfig($config);
        $this->curl = new SSFCurl($async);
        $this->batch = array();

        if($this->config['SSF_BATCHMODE'] === true)
        {
            // We don't just use the destructor because on a fatal error, destructors don't get called
            register_shutdown_function(array($this, 'sendBatch'));
        }
    }

    /**
     * Proxy out to an interface method on the destination server.
     *
     * @param string $method A method supported by the SSF application interface
     * @param array $parms Ordered list of parameter for the method
     */
    public function __call($method, $parms)
    {
        // do some quick sanity checks on the config
        $ret = self::validateConfig($this->config);
        if($ret->isError())
        {
            $this->logError("Failed to call $method: " . $ret->getErrorString());
            return $ret;
        }

        // replace instances of the time tokens w/ microtime
        $sendTime = SSFGetSendMicrotime();
        $eventTime = SSFGetEventMicrotime();
        $parms = array_map(function($parm) use($sendTime, $eventTime) {

            if (SSFSendTimeToken === $parm)
            {
                // Replace with the send time
                $parm = $sendTime;
            }
            else if (SSFEventTimeToken === $parm)
            {
                // Replace with the event time
                $parm = $eventTime;
            }

            return $parm;
        }, $parms);

        if($this->config['SSF_BATCHMODE'] === true)
        {
            if(false === $this->config['SSF_BATCHMODE_METHOD'])
            {
                $this->logError("For batchmode, SSF_BATCHMODE_METHOD must be defined");
                return Error::InvalidArgs("For batchmode, SSF_BATCHMODE_METHOD must be defined");
            }

            $callerContext = null;
            $trace = debug_backtrace();
            // $trace[0] - current method, __call.
            // $trace[1] - SSFClient->splyt_interface_method(), which usually does not exist and leads to this magic __call.
            // $trace[2] - The caller of $ssfclient->splyt_interface_method(), which should be an SDK method
            if (count($trace) >= 3)
            {
                $sdkmethod = $trace[2];
                $callerContext = @$sdkmethod['class'] . @$sdkmethod['type'] . @$sdkmethod['function'];
            }

            return $this->addToBatch($method, $parms, $callerContext);
        }

        return $this->makeCall($method, $parms);
    }

    /*
     * Internal method to execute the SSF call.
     *
     * @param string $method A method supported by the SSF application interface
     * @param array $parms Ordered list of parameter for the method
     */
    private function makeCall($method, $parms, $ssfSdkContext = null)
    {
        $url = "{$this->config['SSF_SERVER']}/{$this->config['SSF_APP']}/ws/interface/$method";

        $config = $this->config;
        if (!empty($ssfSdkContext)) 
        { 
            $config['ssf_sdk_context'] = $ssfSdkContext; 
        }

        // add the configuration as query parameters
        $qparms = self::buildQueryParms($config);
        if(!empty($qparms))
        {
            $url .= '?'.$qparms;
        }        

        // send the method parameters as post data
        //$postData = http_build_query($parms);
        $postData = json_encode($parms, JSON_FORCE_OBJECT);

        $ret = $this->curl->post($url, $postData);

        if($ret->isError())
        {
            $this->logError("Call to $method resulted in: " . $ret->getErrorString());
        }
        return $ret;
    }

    /*
     * Internal method to batch the SSF call.
     *
     * @param string $method A method supported by the SSF application interface
     * @param array $parms Ordered list of parameter for the method
     * @param string ssf_sdk_contextname Identifies the context from which the SSF call is being made, for display in loggers or debuggers.
     */
    private function addToBatch($method, $parms, $ssf_sdk_contextname = null)
    {
        $this->batch[] = array(
            'method' => $method, 
            'args' => $parms,
            'ssf_sdk_contextname' => $ssf_sdk_contextname);

        // send some data now, if we've hit our cap
        $maxPackets = (false !== $this->config['SSF_BATCHMODE_MAX']) ? $this->config['SSF_BATCHMODE_MAX'] : 100;
        if(count($this->batch) >= $maxPackets)
        {
            $this->sendBatch();
        }

        return Error::Success();
    }

    /*
     * Internal method to send the batch prior to webservice shutdown.  NOTE: This is
     * only PUBLIC because it is a requirement of register_shutdown_function
     */
    public function sendBatch()
    {
        if(false === $this->config['SSF_BATCHMODE_METHOD'])
        {
            $this->logError("Cannot send batch, SSF_BATCHMODE_METHOD must be defined");
            return;
        }
        if(!empty($this->batch))
        {
            $this->makeCall($this->config['SSF_BATCHMODE_METHOD'], array(SSFGetSendMicrotime(), $this->batch));
        }

        // clear out the batch array
        $this->batch = array();
    }

    /*
     * Attempt to log errors, if the client is configured for it
     *
     * @param string $error The string to log
     */
    private function logError($error)
    {
        if(false !== $this->config['SSF_VERBOSE'])
        {
            error_log($error);
        }
    }

    /*
     * Build out supported query parameters for an SSF call
     *
     * @param array $config The configuration
     */
    private static function buildQueryParms($config)
    {
        $queryParms = array(
            'ssf_ws_version' => 'SSF_WS_VERSION',
            'ssf_cust_id' => 'SSF_CUSTOMER_ID',
            'ssf_output' => 'SSF_OUTPUT',
            'ssf_sdk' => 'SSF_SDK',
            'ssf_sdk_version' => 'SSF_SDK_VERSION',
            'ssf_api_key' => 'SSF_API_KEY'
        );

        // pull the actual value assigned to each of these
        $queryParms = array_map(function($option) use($config) { return $config[$option]; }, $queryParms);

        // and filter out the ones which were not defined
        $queryParms = array_filter($queryParms, function($value) { return $value !== false; });

        return http_build_query($queryParms);
    }

    /*
     * Applies statically defined configuration values to the supplied configuration, giving
     * precedence to the supplied configuration.
     *
     * @param array $config The initial configuration
     * @return array The augmented configuration
     */
    private static function applyConfig($config)
    {
        $knownOptions = array(
            // required configs
            'SSF_SERVER',
            'SSF_APP',

            // query parm configs - HIGHLY recommended
            'SSF_WS_VERSION',
            'SSF_SDK',
            'SSF_SDK_VERSION',
            'SSF_CUSTOMER_ID',
            'SSF_OUTPUT',

            // additional configs
            'SSF_VERBOSE',           // toggle 'verbose' - defaults to false
            'SSF_BATCHMODE',         // toggle batch mode - defaults to false
            'SSF_BATCHMODE_METHOD',  // when batch mode enable, this method sends the batch
            'SSF_BATCHMODE_MAX'      // when batch mode enabled, send if queued packets reaches this amount
        );

        // make sure all config options are set to SOMETHING, and apply global
        // settings for anything which was not directly provided
        foreach($knownOptions as $option)
        {
            if(!array_key_exists($option, $config))
            {
                $value = false;
                if(defined($option))
                {
                    $value = constant($option);
                }
                $config[$option] = $value;
            }
        }

        return $config;
    }

    /*
     * Insure that any REQUIRED configuration values are present/defined/valid.
     *
     * @param array $config The configuration
     */
    private static function validateConfig($config)
    {
        $requiredOptions = array(
            'SSF_SERVER',
            'SSF_APP',
        );

        foreach($requiredOptions as $option)
        {
            if(false === $config[$option])
            {
                return Error::InvalidArgs("Bad config: $option must be defined");
            }
        }

        return Error::Success();
    }
}