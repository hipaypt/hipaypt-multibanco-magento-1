<?php
require 'app/Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}
$order_entity = $_REQUEST['order'];
if (!$order_entity){
    echo 'error: order not found';
    die();
}
// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);
$cf =  Mage::getModel('cf/paymentmethod');  
$tableName = Mage::getSingleton('core/resource')->getTableName('comprafacil');
$tableName2 = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
$db = Mage::getSingleton('core/resource')->getConnection('core_write');
$q = "SELECT * FROM ".$tableName." WHERE `key` = '".$order_entity."'";
$sql = $db->query($q);
$cfdata =  $sql->fetch(PDO::FETCH_ASSOC);
if ($cfdata['status'] == 1){
    echo 'error: status already 1';
    die();
}
if ($cf->checkPay($cfdata['reference']) == false){
    echo 'error: not payed';
    die();
}
$q = 'SELECT * FROM '.$tableName2.' WHERE increment_id = "'.$cfdata['entity_id'].'"';
$sql = $db->query($q);
$storedata =  $sql->fetch(PDO::FETCH_ASSOC);                                            
$order = Mage::getModel("sales/order")->load($storedata['entity_id']);
try {
	if(!$order->canInvoice())
	{
	Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
	}
	 
	$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
	 
	if (!$invoice->getTotalQty()) {
	Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
	}
	 
	$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
	$invoice->register();
	$transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
	 
	$transactionSave->save();
    

     $sql = "UPDATE ".$tableName." SET `status` = 1 WHERE `key` = '".$order_entity."'";
     $db->query($sql);
     return;
	}
	catch (Mage_Core_Exception $e) {
        echo 'error';
	    die(); 
    } 
echo 'OK';
die();