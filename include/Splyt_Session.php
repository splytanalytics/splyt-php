<?php
/**
 * @brief This Splyt plugin contains function to make it defining periods of activity, or sessions
 *
 * Methods in this plugin are accessible as follows:
 *     <pre>$splyt->Session->methodToInvoke();</pre>
 */
class Splyt_Session
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
     * Marks the beginning of a new session of activity for a given user/device.
     * This should be called as early in the flow of the application as possible
     */
    public function begin()
    {
        $this->ssfClient->gamedata_beginSession(SSFEventTimeToken, $this->userId, $this->deviceId);
    }

    /**
     * Progresses a session transaction.  This functionality can be used to either:
     *     * mark a critical point, that is not modeled by some other transaction, that occurs in every session
     *     * force state about a user/device that was not known at the beginning of the session, to be associated with it
     *
     * @param number $progress A number between 0 and 100 representing the current progress of the transaction.  Should typically increase between successive calls to update
     */
    public function updateProgress($progress)
    {
        $this->ssfClient->gamedata_updateSessionProgress(SSFEventTimeToken, $this->userId, $this->deviceId, $progress);
    }

    /**
     * Marks the end of a session of activity for a given user/device.  This
     * call is not explicitly required
     */
    public function end()
    {
        $this->ssfClient->gamedata_endSession(SSFEventTimeToken, $this->userId, $this->deviceId);
    }
}
?>