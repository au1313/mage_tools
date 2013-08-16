<?php
ob_implicit_flush();  // Disable output buffering

// Make this a Magento app
require_once '/var/www/html/magento/app/Mage.php';
Mage::app();

date_default_timezone_set('America/Detroit');

$oldorder_incid = $argv[1];
$neworder_incid = $argv[2];

// Don't screw this up.
if ($neworder_incid <= $oldorder_incid) {
    echo "New order id must be higher than the old one. Perhaps you reversed them?".PHP_EOL;
    exit;
}
// Don't screw this up, either
if (!$neworder_incid || !$oldorder_incid) {
    echo "Need two orders to work with here.".PHP_EOL;
    exit;
}

$neworder = Mage::getModel('sales/order')->loadByIncrementId($neworder_incid);
$oldorder = Mage::getModel('sales/order')->loadByIncrementId($oldorder_incid);

echo "Setting new dates for order ".$neworder->getIncrementId()." based on dates from order ".$oldorder->getIncrementId().PHP_EOL;


// Getting and setting the order created_at is simple enough
$neworder->setCreatedAt($oldorder->getCreatedAtDate())->save();



/**********************************************************************
 * Do the credit memo related dates
 */

// Load the credit memos.
$newcreditmemos = $neworder->getCreditmemoscollection();
$oldcreditmemos = $oldorder->getCreditmemoscollection();

// There's only going to be one anyway, so load the created_at for it
$oldcmdate = null;
foreach ($oldcreditmemos as $ocm) {
    $oldcmdate = $ocm->getData('created_at');
}

// Now put it to use
foreach ($newcreditmemos as $ncm) {
    $ncm->setData('created_at',$oldcmdate)->save();

    // Now take care of the credit memo comments
    // Should be safe enough to just use the main cm date
    $ncomments = $ncm->getCommentsCollection();
    foreach($ncomments as $ncomm) {
        $ncomm->setData('created_at',$oldcmdate)->save();
    }
}




/**********************************************************************
 * Do the invoice related dates
 */

// Pretty much the same drill.
$newinvoices = $neworder->getInvoiceCollection(); 
$oldinvoices = $oldorder->getInvoiceCollection(); 
$oldinvdate = null;
foreach ($oldinvoices as $oinv) {
    $oldinvdate = $oinv->getData('created_at');
}
foreach ($newinvoices as $ninv) {
    $ninv->setData('created_at',$oldinvdate )->save();

    // Now take care of the invoice comments
    $newcomments = $ninv->getCommentsCollection();
    foreach($newcomments as $ncomm) {
        $ncomm->setData('created_at', $oldinvdate )->save();
    }
}





/**********************************************************************
 * Do the invoice related dates
 */

// arrrr.
$newships = $neworder->getShipmentsCollection();
$oldships = $oldorder->getShipmentsCollection();
$oldshipdate = $oldinvdate;   // The old order probably won't have a shipment
foreach ($oldships as $oship) {
    $oldshipdate = $oship->getData('created_at');
}
foreach ($newships as $nship) {
    $nship->setData('created_at', $oldshipdate )->save();

    // Now take care of the shipments comments
    $newcomments = $nship->getCommentsCollection();
    foreach($newcomments as $ncomm) {
        $ncomm->setData('created_at',$oldshipdate)->save();
    }
}



/**********************************************************************
 * Do the status history dates.
 */
$newhist = $neworder->getStatusHistoryCollection(true);
$oldhist = $oldorder->getStatusHistoryCollection(true);
$oldhistdates = array();
foreach ($oldhist as $ohist) {
    $oldhistdates[ $ohist->getData('status') ] = $ohist->getData('created_at');
}
foreach ($newhist as $nhist) {
    if (preg_match("/Refunded amount of/i",$nhist->getData('comment'))   )
        $nhist->setData('created_at', $oldhistdates[ 'refund_complete' ] )->save();
    else if ($nhist->getData('status') == 'complete')
        $nhist->setData('created_at', $oldhistdates[ 'paid' ] )->save();
    else 
        $nhist->setData('created_at', $oldhistdates[ $nhist->getData('status') ] )->save();

}

$neworder->save();


echo "done.".PHP_EOL;
