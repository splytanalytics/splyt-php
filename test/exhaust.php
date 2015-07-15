<?php 

// The purpose of this script is to drive the interface for interface testing.
// It is light weight and doesn't require special SDKs or libs to be installed, 
// just a php interpreter and a SPLYT data collector to point it at.

define('SSF_SERVER', 'http://localhost');

require_once('../include/Splyt.php');
require_once('../include/Splyt_Custom.php');

// ### INIT ###

$splyt = Splyt::init('rsb-dataexample-test', 'user', 'device');

// ### BEGIN TXN ### 

// Lots of defaults
$splyt->Custom->beginTransaction('begin');

// Timeout combinations
$splyt->Custom->beginTransaction('begin', $timeoutMode = Splyt::TIMEOUT_MODE_TRANSACTION);
$splyt->Custom->beginTransaction('begin', null, 30);
$splyt->Custom->beginTransaction('begin', $timeoutMode = Splyt::TIMEOUT_MODE_TRANSACTION, 30);
$splyt->Custom->beginTransaction('begin', $timeoutMode = Splyt::TIMEOUT_MODE_ANY, 30);

// Full
$splyt->Custom->beginTransaction('begin', $timeoutMode = Splyt::TIMEOUT_MODE_ANY, 30, array('property' => 5), 'mytxnid');

// ### UPDATE TXN ###

$splyt->Custom->updateTransaction('update', 50, array('property' => 6));
$splyt->Custom->updateTransaction('update', 50, null, 'mytxnid');
$splyt->Custom->updateTransaction('update', 50, array('property' => 6), 'mytxnid');

// ### END TXN ### 

$splyt->Custom->endTransaction('end');
$splyt->Custom->endTransaction('end', Splyt::TXN_ERROR);
$splyt->Custom->endTransaction('end', null, array('property' => 7));
$splyt->Custom->endTransaction('end', null, null, 'mytxnid');
$splyt->Custom->endTransaction('end', Splyt::TXN_ERROR, array('property' => 7), 'mytxnid');

// ### UPDATE USER STATE ### 

$splyt->Custom->updateUserState(array("property" => 8));

// ### UPDATE DEVICE STATE ###

$splyt->Custom->updateDeviceState(array("property" => 9));

// ### COLLECTION UPDATE ###

$splyt->Custom->updateCollection("mycollection", 100, -10);
$splyt->Custom->updateCollection("mycollection", 100, -10, false);
$splyt->Custom->updateCollection("mycollection", 100, -10, true);



?>