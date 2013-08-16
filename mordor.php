<?php
    require_once '/var/www/html/magento/app/Mage.php';
    Mage::app('admin');

    $longopts = array(
        'rm::','show::','cancel::','history::',
        'cast-into-the-fires::'               // I couldn't resist.
    );
    $opts = getopt("",$longopts);

    if($opts['rm'] || $opts['cast-into-the-fire']) {
        $orders = array();
        array_push($orders, explode(',',$opts['rm']) );
        array_push($orders, explode(',',$opts['cast-into-the-fire']) );

        foreach ($orders as $o) {
            echo "Deleting order $o... ";
            $o = Mage::getModel('sales/order')->loadByIncrementId($o);
            $o->delete();
            echo "done.".PHP_EOL;
        }
    }


    if($opts['cancel']) {
        $orders = array();
        array_push($orders, explode(',',$opts['cancel']) );

        foreach ($orders as $o) {
            $o = Mage::getModel('sales/order')->loadByIncrementId($o);
            if ($o->canCancel()) {
                echo "Canceling order $o... ";
                $o->cancel();
                echo "done.".PHP_EOL;
            }
            else {
                echo "Order $o is not cancelable.".PHP_EOL;
            }
        }
    }

    if($opts['history']) {
        $orders = array();
        array_push($orders, explode(',',$opts['history']) );

        foreach ($orders as $o) {
            $o = Mage::getModel('sales/order')->loadByIncrementId($o);
            echo "Status history for order $o... ".PHP_EOL;
            $shist = $o->getStatusHistoryCollection(true);
            foreach ($shist as $hist) {
                echo $hist->getData('status').": ".$hist->getData('created_at').
                    ($hist->getData('is_customer_notified') == 1)?'(Customer notified)':'';
                echo "\t".$hist->getData('comment').PHP_EOL;
            }
        }
    }


    if($opts['show']) {
        $orders = array();
        $shows = explode(',',$opts['show']);
        foreach ($shows as $s) {
            $orders[] = $s;
        }

        foreach ($orders as $oid) {
            $o = Mage::getModel('sales/order')->loadByIncrementId($oid);
            echo "Information for order $oid: ".PHP_EOL;

            $fields = array(
                'entity_id','increment_id','created_at','updated_at','store_id','customer_id','quote_id',
                'customer_email','customer_firstname','customer_middlename','customer_lastname',
                'customer_prefix','customer_suffix',
                'grand_total','shipping_amount','tax_amount','total_invoiced','total_paid','subtotal','order_currency_code',
                'shipping_method','shipping_description',
            );

            foreach ($fields as $f) {
                echo $f.": ".$o->getData($f).PHP_EOL;
            }
            echo PHP_EOL;
        }
    }



    echo "Done with orders.".PHP_EOL;


?>
