<?php

/**
 * @brief This Splyt plugin contains a functions to make it easier to instrument purchasing
 * flows in your application
 *
 * Methods in this plugin are accessible as follows:
 *     <pre>$splyt->Purchase->methodToInvoke();</pre>
 */
class Splyt_Purchase
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
     * Record a purchase and all details about it with 1 call.  This is useful for purchases which do
     * not require a long flow.
     *
     * @param string $result A string representing the result of the transaction, Splyt::TXN_SUCCESS or Splyt::TXN_ERROR
     * @param array $properties An object which can take values for any key, but natively supports the following:
     *     * price array an associative array of currency(string):value(number) pairs
     *     * offerId string an offer id
     *     * itemName string the name of the item being purchased
     *     * pointOfSale string where in the application the purchase occurred
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     *
     */
    public function recordPurchase($result, $properties, $transactionId = null)
    {
        $this->ssfClient->gamedata_recordPurchase(SSFEventTimeToken, $this->userId, $this->deviceId, $properties, $result, $transactionId);
    }

    /**
     * Mark the beginning of a purchase.
     *
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function begin($transactionId = null)
    {
        $this->ssfClient->gamedata_beginPurchase(SSFEventTimeToken, $this->userId, $this->deviceId, $transactionId);
    }

    /**
     * Update the current purchase with the price information
     *
     * @param string $currency The name of the currency being used in the purchase (eg USD)
     * @param number $amount The amount of currency being used in the purchase
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function setPrice($currency, $amount, $transactionId = null)
    {
        $this->ssfClient->gamedata_setPurchasePrice(SSFEventTimeToken, $this->userId, $this->deviceId, $currency, $amount, $transactionId);
    }

    /**
     * Update the current purchase with the offer id
     *
     * @param string $offerId An offer id
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function setOfferId($offerId, $transactionId = null)
    {
        $this->ssfClient->gamedata_setPurchaseOfferId(SSFEventTimeToken, $this->userId, $this->deviceId, $offerId, $transactionId);
    }

    /**
     * Update the current purchase with the item name
     *
     * @param string $itemName Name of the item being purchased
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function setItemName($itemName, $transactionId = null)
    {
        $this->ssfClient->gamedata_setPurchaseItemName(SSFEventTimeToken, $this->userId, $this->deviceId, $itemName, $transactionId);
    }

    /**
     * Update the current purchase with the point of sale
     *
     * @param string $pointOfSale Where in the application the purchase was made
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function setPointOfSale($pointOfSale, $transactionId = null)
    {
        $this->ssfClient->gamedata_setPurchasePointOfSale(SSFEventTimeToken, $this->userId, $this->deviceId, $pointOfSale, $transactionId);
    }

    /**
     * Mark the end of a purchase.
     *
     * @param string $result A string representing the result of the transaction, Splyt::TXN_SUCCESS or Splyt::TXN_ERROR
     * @param string $transactionId (optional) An ID that uniquely identifies this purchase transaction.
     *     The transaction ID is only strictly required if more than one purchase will be simultaneously
     *     active for a given user or device.
     */
    public function end($result, $transactionId = null)
    {
        $this->ssfClient->gamedata_endPurchase(SSFEventTimeToken, $this->userId, $this->deviceId, $result, $transactionId);
    }
}
?>