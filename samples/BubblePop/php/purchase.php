<?php
require_once dirname(__FILE__) . '/common.php';

if(!isset($_REQUEST['offerid']))
{
    echo json_encode(array('status' => 'invalidargs'));
    return;
}

// this represents the offer calalog for BubblePop
$knownOffers = array(
    'standard-game' => array(
        'name' => 'new-game',
        'currency' => 'gold',
        'cost' => 100,
        'itemid' => 'game',
        'quantity' => 1
    ),
    'standard-gold' => array(
        'name' => 'gold-25',
        'currency' => 'usd',
        'cost' => .99,
        'itemid' => 'gold',
        'quantity' => 25
    )
);

// Update the cost of playing a game here to match up with what was sent to the
// client in init.php.  Since this occurs after the same instance of Splyt::cacheVariables, you
// are guaranteed to get the same value.
$knownOffers['game_1']['cost'] = $splyt->getVar('newGamePrice', 100);

// make sure the client requested a known offer
$offerid = $_REQUEST['offerid'];
if(!array_key_exists($offerid, $knownOffers))
{
    echo json_encode(array('status' => 'badoffer'));
    return;
}

$pointOfSale = isset($_REQUEST['pointOfSale']) ? $_REQUEST['pointOfSale'] : 'in-game';

// retrieve the player's gold balance from memcache
$memcache = new fakememcache();
$goldKey = $_REQUEST['userid'].'_goldBalance';
$goldBalance = $memcache->get($goldKey);

// isolate the offer
$offer = $knownOffers[$offerid];

// record some simple purchasing telemetry with Splyt.  In many cases, we can
// use Purchase->recordPurchase() for a '1-shot' call, but for this example we call
// individual methods so that we can branch for the completion condition below
$splyt->Purchase->begin();
$splyt->Purchase->setPrice($offer['currency'], $offer['cost']);
$splyt->Purchase->setOfferId($offerid);
$splyt->Purchase->setItemName($offer['name']);
$splyt->Purchase->setPointOfSale($pointOfSale);

// process the purchase
if($offer['currency'] == 'gold')
{
    if($goldBalance < $offer['cost'])
    {
        $splyt->Purchase->end(Splyt::TXN_SUCCESS);
        echo json_encode(array('status' => 'nsf'));
        return;
    }

    $goldBalance -= $offer['cost'];
}
else
{
    // here's where we charge the credit card...  :)
    $ccChargeSuccess = true;
    if(false === $ccChargeSuccess)
    {
        $splyt->Purchase->end(Splyt::TXN_ERROR);
        echo json_encode(array('status' => 'failed'));
        return;
    }

    if($offer['itemid'] == 'gold')
    {
        $goldBalance += $offer['quantity'];
    }
}

// if we made it this far, the purchase was successful, so record that and
// do some bookkeeping before returning to the client
$splyt->Purchase->end(Splyt::TXN_SUCCESS);

// update the gold balance accordingly after the purchase
$memcache->set($goldKey, $goldBalance);

echo json_encode(array(
    'status' => 'ok',
    'goldBalance' => $goldBalance,
));
?>