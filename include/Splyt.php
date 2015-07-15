<?php
require_once dirname(__FILE__).'/ssf/ssfclient.php';

// Simple-Server-Framework configuration, so that these don't have to be sent whenever we use SSF
// Allow the user to override this define...
if (!defined('SSF_SERVER'))
{
    define('SSF_SERVER', 'https://data.splyt.com');
}
//define('SSF_SERVER', 'http://localhost');
define('SSF_APP', 'isos-personalization');
define('SSF_WS_VERSION', 4);
define('SSF_SDK', 'php');
define('SSF_SDK_VERSION', .1);
define('SSF_OUTPUT', 'json');
define('SSF_VERBOSE', true);
define('SSF_BATCHMODE_METHOD', 'datacollector_batch');
define('SSF_BATCHMODE_MAX', 100);

/**
 * @brief This is the core implementation class for the Splyt framework.  It provides support for accessing
 * Splyt plugins, tuning variables, and some basic new user telemetry.
 *
 * Additional Splyt functionality is added using plugins, which are found in Splyt_* files included with
 * the distribution, and can be accessed directly from this class, with a usage pattern like:
 *
 * <pre>
 * $splyt = Splyt.init(MyCuStOmErId, userId);
 * $splyt->Session->begin();   // <- references the begin method of the Splyt Session plugin
 * </pre>
 *
 */

class Splyt
{
    /**
     * @var string Constant used to signify a success result from Splyt transactions
     */
    const TXN_SUCCESS='success';
    /**
     * @var string Constant used to signify an unsuccessful result from Splyt transactions
     */
    const TXN_ERROR='error';
    
    const TIMEOUT_MODE_TRANSACTION = "TXN";
    const TIMEOUT_MODE_ANY = "ANY";

    private static $sExt;
    private static $cachePfx = 'Splyt_cache_';

    // create and use static ssfClients to allow for optimal connection caching in the lower levels
    private static $ssfClientBatch;
    private static $ssfClientCVars;

    private $plugins = array();

    private $userId;
    private $deviceId;
    private $customerId;

    /**
     * Initialization function for Splyt.  Must be called at least once per service call, but
     * can be called more than once without issue.  Returns a Splyt instance which can then be
     * used to communicate with the Splyt framework.
     *
     * Either a user id or device id must be specified.  And if the PHP SDK is used in conjunction
     * with a client SDK, it is important for these ids to be provided consistently.
     *
     * @param string $customerId Splyt customerId provided by Row Sham Bow, Inc.
     * @param string $userId (optional) A unique id specific to the user of the app, if such a concept exists
     * @param string $deviceId (optional) A unique id specific to an install/instance of the app, typically representing the client device
     * @return Splyt Used to communicate with the Splyt framework
     */
    public static function init($customerId, $userId = null, $deviceId = null)
    {
        if(!isset(self::$sExt))
        {
            if (file_exists(dirname(__FILE__).'/Splyt_ext.php'))
            {
                include_once dirname(__FILE__).'/Splyt_ext.php';
            }
            if(class_exists('Splyt_ext'))
            {
                $methods = get_class_methods('Splyt_ext');
                if(false !== array_search('clear', $methods) && false !== array_search('store', $methods) && false !== array_search('retrieve', $methods))
                {
                    self::$sExt = new Splyt_ext();
                }
                else
                {
                    error_log("Splyt_ext class missing required 'clear', 'store' and 'retrieve' methods");
                }
            }
        }

        if(!isset(self::$ssfClientBatch))
        {
            self::$ssfClientBatch = new SSFClient(array(
                    'SSF_CUSTOMER_ID' => $customerId,
                    'SSF_BATCHMODE' => true
            ), true);
        }

        return new Splyt($customerId, $userId, $deviceId);
    }

    /*
     * Constructor is private to insure that init() is used to gain access to the framework.
     *
     * @param string $customerId
     * @param string $userId
     * @param string $deviceId
     */
    private function __construct($customerId, $userId, $deviceId)
    {
        $this->deviceId = $deviceId;
        $this->userId = $userId;
        $this->customerId = $customerId;

        /*
        if(!$this->_validate())
        {
            error_log("User id OR Device id must be provided");
        }
        */
    }

    /**
     * Sets that a user is known to be new as of right now. This function only needs to be called when a user is actually detected as new.
     * As such, this should only be called once for the lifetime of a user.
     */
    public function newUser()
    {
        self::$ssfClientBatch->gamedata_setIsNewUser(SSFEventTimeToken, $this->userId, $this->deviceId, true);
    }

    /**
     * Sets that a device is known to be new as of right now. This function only needs to be called when a device is actually detected as new.
     * As such, this should only be called once for the lifetime of a device.
     */
    public function newDevice()
    {
        self::$ssfClientBatch->gamedata_setIsNewDevice(SSFEventTimeToken, $this->userId, $this->deviceId, true);
    }

    /**
     * Retrieves the values for all tuning variables for a given user/device and caches them using the
     * Splyt_ext interface provided by the application.
     */
    public function cacheVariables()
    {
        if(!isset(self::$sExt))
        {
            error_log("cacheVariables() may not be used unless a Splyt_ext class is available.  See documentation for more details");
            return Error::InvalidArgs("cacheVariables() may not be used unless a Splyt_ext class is available.  See documentation for more details");
        }

        isset($this->userId) && self::$sExt->clear(self::$cachePfx.'USER'.$this->userId);
        isset($this->deviceId) && self::$sExt->clear(self::$cachePfx.'DEVICE'.$this->deviceId);

        // if we have any batched telem, be sure to send it before the call to cache variables
        self::$ssfClientBatch->sendBatch();

        // Don't use batching because the response is important
        if (!isset(self::$ssfClientCVars))
        {
            self::$ssfClientCVars = new SSFClient(array(
                    'SSF_CUSTOMER_ID' => $this->customerId,
                    'SSF_BATCHMODE' => false
            ));
        }

        // Determine the proper method for retrieving variables based upon the configuration provided
        $method = null;
        if(!isset($this->userId) && !isset($this->deviceId))
        {
            error_log("No userId or deviceId provided.  Please check your call to Splyt.init()");
            return Error::InvalidArgs("No userId or deviceId provided.  Please check your call to Splyt.init()");
        }
        else if(!isset($this->userId) || !isset($this->deviceId))
        {
            $method = 'tuner_getAllValues';
            if(!isset($this->userId))
            {
                $args = array(SSFSendTimeToken, SSFEventTimeToken, $this->deviceId, 'DEVICE');
            }
            else
            {
                $args = array(SSFSendTimeToken, SSFEventTimeToken, $this->userId, 'USER');
            }
        }
        else
        {
            $method = 'tuner_getAllValuesAllTypes';
            $args = array(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId);
        }

        // make the call - note: this will result in a network transaction
        $ret = call_user_func_array(array(self::$ssfClientCVars, $method), $args);
        if($ret->isError())
        {
            error_log($ret->getErrorString());
            return $ret;
        }

        // parse the response and cache using the Splyt_ext interface
        if(isset($ret['type']))
        {
            if($ret['type'] == 'USER')
            {
                self::$sExt->store(self::$cachePfx.'USER'.$this->userId, $ret['value']);
            }
            if($ret['type'] == 'DEVICE')
            {
                self::$sExt->store(self::$cachePfx.'DEVICE'.$this->deviceId, $ret['value']);
            }
        }
        else
        {
            if(isset($ret['USER']))
            {
                self::$sExt->store(self::$cachePfx.'USER'.$this->userId, $ret['USER']['data']['value']);
            }
            if(isset($ret['DEVICE']))
            {
                self::$sExt->store(self::$cachePfx.'DEVICE'.$this->deviceId, $ret['DEVICE']['data']['value']);
            }
        }

        return Error::Success();
    }

    /**
     * Gets the value of a tuning variable from Splyt.  If Splyt.CacheVariables has not been called, or if
     * there is no definition for the variable available from the server, the provided default value will be returned.
     *
     * @param string $varName The variable to retrieve
     * @param mixed $defaultValue Default value to use if Splyt doesn't have a tuned value
     * @param boolean $prioritizeDevice (optional) Pass true in rare cases where a value may be tuned different for a device than for a user
     */
    public function getVar($varName, $defaultValue, $prioritizeDevice=false)
    {
        if(!isset(self::$sExt))
        {
            error_log('Currently, getVar is only supported when a Splyt_ext class is available.');
            return Error::NotImplemented('Currently, getVar is only supported when a Splyt_ext class is available.');
        }

        $userVars = isset($this->userId) ? self::$sExt->retrieve(self::$cachePfx.'USER'.$this->userId) : null;
        $deviceVars = isset($this->deviceId) ? self::$sExt->retrieve(self::$cachePfx.'DEVICE'.$this->deviceId) : null;

        if($prioritizeDevice && isset($deviceVars) && isset($deviceVars[$varName]))
        {
            $value = $deviceVars[$varName];
        }
        else if(isset($userVars) && isset($userVars[$varName]))
        {
            $value = $userVars[$varName];
        }
        else if(isset($deviceVars) && isset($deviceVars[$varName]))
        {
            $value = $deviceVars[$varName];
        }

        if (!isset($value))
        {
            // Couldn't retrieve the variable, just return the default
            $value = $defaultValue;
        }
        else
        {
            // We have a value so let's attempt to return the variable cast as the type of the default value passed in
            // If we can't cast it, we'll return the default value
            $defaultType = gettype($defaultValue);
            if ('boolean' === $defaultType)
            {
                // Tuning variables are always stored as strings.
                // We must handle booleans specially since type juggling from a string to a boolean doesn't work as expected (e.g., converting from "false" results in true)
                // See http://www.php.net/manual/en/filter.filters.validate.php
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            else
            {
                // Type juggling should work for all other types we care about (e.g., string -> int, string -> double, etc), so let's do the conversion
                if (!settype($value, $defaultType))
                {
                    // Conversion unsuccessful, just return the default
                    $value = $defaultValue;
                }
            }
        }

        return $value;
    }

    /*
     * Utility method for gaining access to a Splyt plugin.  While this method may be called directly,
     * the preferred syntax for retrieving a plugin instance is $splyt->PluginName.
     *
     * In order to utilize a plugin, a plugin implementation file Splyt_<PluginName>.php must be available
     * in the same folder as Splyt.php.  See the BubblePop example for reference.
     *
     * @param string $pluginName The plugin instance to create/retrieve.  Found in Splyt_<pluginName>.php
     */
    public function getPlugin($pluginName)
    {
        // used the cached version if there is one
        if(array_key_exists($pluginName, $this->plugins))
        {
            return $this->plugins[$pluginName];
        }

        // otherwise create one, if possible
        $pluginClass = 'Splyt_'.$pluginName;
        if(file_exists(dirname(__FILE__)."/$pluginClass.php"))
        {
            include_once dirname(__FILE__)."/$pluginClass.php";
            if(class_exists($pluginClass))
            {
                $plugin =  new $pluginClass(self::$ssfClientBatch, $this->userId, $this->deviceId);

                // don't forget to cache it for the next guy!
                $this->plugins[$pluginName] = $plugin;
                return $plugin;
            }
            else
            {
                error_log("$pluginClass not found in $pluginClass.php.  Contact Splyt support.");
            }
        }
        else
        {
            error_log("$pluginClass.php cannot be found.  $pluginName is not an available plugin.");
        }

        return new Splyt_Null($pluginName);
    }

    /*
     * Provides access to the requested Splyt plugin, if available.
     *
     * In order to utilize a plugin, a plugin implementation file Splyt_<PluginName>.php must be available
     * in the same folder as Splyt.php.  See the BubblePop example for reference.
     *
     * @param string $pluginName The plugin instance to create/retrieve.  Found in Splyt_<pluginName>.php
     */
    public function __get($pluginName)
    {
        return $this->getPlugin($pluginName);
    }
}

class Splyt_Null
{
    private $desired;
    function __construct($desired)
    {
        $this->desired = $desired;
    }

    function __call($call, $args)
    {
        error_log("{$this->desired}->$call failed because $this->desired plugin cannot be found.");
    }
}
?>