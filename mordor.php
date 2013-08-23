<?php
    require_once '/var/www/html/magento/app/Mage.php';
    Mage::app('admin');

    $longopts = array(
        'rm::','show::','cancel::','history::','fields::',
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
            echo "Status history for order {$o->getIncrementId()}... ".PHP_EOL;
            $shist = $o->getStatusHistoryCollection(true);
            foreach ($shist as $hist) {
                printf("%-20s %s",$hist->getStatusLabel().':',time_utc_to_local($hist->getData('created_at')));
                echo ($hist->getData('is_customer_notified') == 1)?' (Customer notified)':'';
                echo "\t".$hist['comment'].PHP_EOL;
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

            if ($opts['fields'])
                $fields = explode(',',$opts['fields']);

            $pad = get_max_width($fields)+2;
            foreach ($fields as $f) {
                printf("%-'.{$pad}s %s\n",$f,$o->getData($f));
            }
            echo "Status History:".PHP_EOL;
            $shist = $o->getStatusHistoryCollection(true);
            foreach ($shist as $hist) {
                printf("\t%-20s %s",$hist->getStatusLabel().':',time_utc_to_local($hist->getData('created_at')));
                echo ($hist->getData('is_customer_notified') == 1)?' (Customer notified)':'';
                echo "\t\t".$hist['comment'].PHP_EOL;
            }
            echo PHP_EOL;
        }
    }

    function get_max_width($fields) {
        $len = 0;
        foreach ($fields as $f) {
            if ($len < strlen($f))
                $len = strlen($f);
        }
        return $len;
    }


    function time_utc_to_local($date) {
        $dt = new DateTime($date, new DateTimeZone("UTC"));
        $dt->setTimezone(new DateTimeZone("America/Detroit"));
        return( $dt->format("F j, Y g:i a") );
    }

?>
