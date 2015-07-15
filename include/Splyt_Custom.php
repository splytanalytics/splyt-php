<?php
/**
 * @brief This Splyt plug in contains function for the Generic Splyt API.  Integrators are encouraged to use other plugins
 * whenever possible, but this plug in allows for modeling of things that are not specifically covered elsewhere
 *
 * Methods in this plugin are accessible as follows:
 *     <pre>$splyt->Custom->methodToInvoke();</pre>
 */
class Splyt_Custom
{
    private $ssfClient;
    private $userId;
    private $deviceId;

    /*
     * Constructor should not be called directly.  Instead, utilize the accessor from the Splyt class.
     */
    public function __construct($ssfClient, $userId, $deviceId)
    {
        $this->userId = $userId;
        $this->deviceId = $deviceId;
        $this->ssfClient = $ssfClient;
    }

    /**
     * Updates state information about the user
     *
     * @param array $properties Properties (keys) of a user and their values.  Nested arrays are supported, as needed.
     */
    public function updateUserState($properties)
    {
        $this->ssfClient->datacollector_updateUserState(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $properties);
    }

    /**
     * Updates state information about the device
     *
     * @param array $properties Properties (keys) of a device and their values.  Nested arrays are supported, as needed.
     */
    public function updateDeviceState($properties)
    {
        $this->ssfClient->datacollector_updateDeviceState(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $properties);
    }

    /**
     * Updates collection information.
     *
     * @param String $name The name of the collection to update.
     * @param Number $balance The new balance of the collection.
     * @param Number $balanceModification The modification made to get to the new balance, for example: 4 or -4
     */
    public function updateCollection($name, $balance, $balanceModification, $isCurrency = false)
    {
        $this->ssfClient->datacollector_updateCollection(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $name, $balance, $balanceModification, $isCurrency);
    }

    /**
     * Begin a transaction of a certain category, with a timeout and properties
     *
     * If multiple transactions of this category are going to be active at the same time for a given user, or
     * device, a transaction id is required to be provided
     *
     * @param string $category A category for the transaction.
     * @param int $timeout (optional) The amount of time, in seconds, after which a transaction should time out if a corresponding endTransaction is not sent.
     *     The default is null, which specifies that a default timeout of one hour should be used.
     * @param array $properties (optional) Properties (keys) of the transaction and their values.  Nested arrays are supported, as needed.
     * @param string $transactionId (optional) An ID that uniquely identifies this transaction within the category.
     *     The transaction ID is only required if more than one transaction of a given category will be simultaneously
     *     active for a given user or device
     */
    public function beginTransaction($category, $timeoutMode = null, $timeout = null, $properties = null, $transactionId = null)
    {
        $this->ssfClient->datacollector_beginTransaction(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $category, $timeoutMode, $timeout, $transactionId, $properties);
    }

    /**
     * Update a previously begun transaction of a given category to some progress
     *
     * @param string $category A category for the transaction.
     * @param number $progress A number between 0 and 100 representing the current progress of the transaction.  Should typically increase between successive calls to update.
     * @param array $properties (optional) Properties (keys) of the transaction and their values.  Nested arrays are supported, as needed.
     * @param string $transactionId (optional) An ID that uniquely identifies this transaction within the category.
     *     The transaction ID is only required if more than one transaction of a given category will be simultaneously
     *     active for a given user or device
     */
    public function updateTransaction($category, $progress, $properties = null, $transactionId = null)
    {
        $this->ssfClient->datacollector_updateTransaction(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $category, $progress, $transactionId, $properties);
    }

    /**
     * End a transaction of a given category with some result
     *
     * @param string $category A category for the transaction.
     * @param string $result A string representing the result of the transaction, Splyt::TXN_SUCCESS or Splyt::TXN_ERROR
     * @param array $properties (optional) Properties (keys) of the transaction and their values.  Nested arrays are supported, as needed.
     * @param string $transactionId (optional) An ID that uniquely identifies this transaction within the category.
     *     The transaction ID is only required if more than one transaction of a given category will be simultaneously
     *     active for a given user or device
     */
    public function endTransaction($category, $result = Splyt::TXN_SUCCESS, $properties = null, $transactionId = null)
    {
        $this->ssfClient->datacollector_endTransaction(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $category, $result, $transactionId, $properties);
    }

    /**
     * Writes an event.
     *
     * @param string $name The name of the event.
     * @param string $result A string representing the event result. You can use Splyt::TXN_SUCCESS to indicate a
     *     successful event or Splyt::TXN_ERROR to indicate an error.
     * @param array $properties (optional) Properties (keys) of the transaction and their values.  Nested arrays are
     *     supported, as needed.
     */
    public function writeEvent($name, $result, $properties)
    {
        $this->ssfClient->datacollector_endTransaction(SSFSendTimeToken, SSFEventTimeToken, $this->userId, $this->deviceId, $name, $result, null, $properties);
    }
}
?>