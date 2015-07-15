<?php
require_once dirname(__FILE__) . '/common.php';

try {
    if(!isset($_REQUEST['event'])) {
        throw new InvalidArgumentException();
    }
    
    $event = $_REQUEST['event'];
    
    switch ($event) {
		case 'gameStarted':
			$splyt->Custom->writeEvent(
				$event,
				Splyt::TXN_SUCCESS,
				null
			);
			break;

		case 'gameFinished':
			if(!isset($_REQUEST['numberOfPops']) || !isset($_REQUEST['winQuality'])) {
				throw new InvalidArgumentException();
			}

			$splyt->Custom->writeEvent(
				$event,
				Splyt::TXN_SUCCESS,
				array(
					'numberOfPops' => $_REQUEST['numberOfPops'],
					'winQuality' => $_REQUEST['winQuality'],
				)
			);
			break;

		default:
			//this wasn't a game event that we recognize
			throw new InvalidArgumentException();
			break;
	}
}
catch (InvalidArgumentException $e) {
	//either the event or its arguments were not what was expected.
	echo json_encode(array(
		'status' => 'invalidargs',
		'message' => $e->getMessage()
	));
	return;
}

?>