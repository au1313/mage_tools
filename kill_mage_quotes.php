<?php 

require_once '/var/www/html/magento/app/Mage.php';
Mage::app('admin');

$victims = array(
'13187',
'14915',
'17531',
'23592',
'4807',
'4808'
);

foreach ($victims as $v) {
  echo "Deleting quote $v... ";
  $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($v);
  $quote->delete();
  echo "done.\n";

}

echo "Done with all quotes.\n";


