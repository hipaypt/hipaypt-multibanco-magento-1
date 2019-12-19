<?php
class Webeffect_Comprafacil_Block_Info extends Mage_Payment_Block_Info
{
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        
        $transport = parent::_prepareSpecificInformation($transport);
        
        $info = $this->getInfo();   
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $order = $info->getOrder();
            $orderid = $order->getData('increment_id');
            $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
            if ($payment_method_code == "cf"){
                $cf = Mage::getModel('cf/paymentmethod');
                $cf_values = $cf->getRef($orderid);
                $transport->setData($cf_values);
            }
        }
        
        return $transport;
    }
}
