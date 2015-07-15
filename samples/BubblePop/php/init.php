<?php
require_once dirname(__FILE__) . '/common.php';

// Mark the start of the session using the Splyt Session plugin.  This should be done as early as
// possible
$splyt->Session->begin();

// Load some data for this user
$memcache = new fakememcache();

// For this app, existence of a gold balance indicates a new user
$goldKey = $_REQUEST['userid'].'_goldBalance';

$goldBalance = $memcache->get($goldKey);

// Our slightly hacky game assumes that a user is new if they don't yet have a gold balance
$isNewUser = (false === $goldBalance);
if($isNewUser)
{
    // Tell Splyt that this is a new user 
    $splyt->newUser();
}

// Record/update some user state that we know about this person
$gender = $_REQUEST['gender'];
$splyt->Custom->updateUserState(array('basic' => array('gender' => $gender)));

// Because we are using Splyt dynamic variables, we need to call cacheVariables() in order to 
//  'prime' the system.  This prevents unnecessary/undesirable bandwidth consumption for getVar calls.
// It is best practice to do this AFTER reporting initial user state, if possible
$splyt->cacheVariables();

if($isNewUser)
{
    // initialize the gold balance using a dynamic variable from Splyt and commit to storage
    $goldBalance = $splyt->getVar('initialGoldAmount', 100);
    $memcache->set($goldKey, $goldBalance);
}

// getVar is also available in the AS3, JS, and other client APIs, but for this example, we 
// will fetch the values from PHP and send them up
$bubbleColor = $splyt->getVar('bubbleColor', 'cyan');
$gameCost = $splyt->getVar('newGamePrice', 100);

$initVars = array(
    'gameCost' => $gameCost,
    'bubbleColor' => $bubbleColor,
    'goldBalance' => $goldBalance
);

echo json_encode($initVars);
?>