<?php
class Webeffect_Comprafacil_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cf';
 
    protected $_isGateway               = true; //true
    
    protected $_canAuthorize            = true; //true
    
    protected $_canCapture              = true; //true
    
    protected $_canCapturePartial       = false; //false
    
    protected $_canRefund               = false; //false
    
    protected $_canVoid                 = true; //true
    
    protected $_canUseInternal          = true; //true
    
    protected $_canUseCheckout          = true; //true
    
    protected $_canUseForMultishipping  = true; //true
    
    protected $_canSaveCc = false; //false
    
    private $_key = 'K4UW8$oM4yv';
    
    protected $_order;
    
    protected $_config;
    
    protected $_payment;
        
    protected $_redirectUrl;
    
    private $_username = ""; 
      
    private $_password = ""; 
    
    private $_debugMode = "0";
    
    private $_config_entidade = "10241";
     
    private $_reference = "";

    private $_entity = "";

    private $_value = "";
    
    private $_orderID = "";

    protected $_cferror = "";
    
    protected $_infoBlockType = 'cf/info';
    
    public function getOrderPlaceRedirectUrl(Mage_Sales_Model_Order $order)
    {
        
    }
  
    protected function _getOrder(){   
        return $this->_order;
    }
  
    public function authorize(Varien_Object $payment, $amount){
        $systemConfig = Mage::getStoreConfig('payment/cf');  
        $this->_username = $systemConfig['cf_username'];
        $this->_password = $systemConfig['cf_password'];
        $this->_debugMode = $systemConfig['cf_debug'];
        $this->_config_entidade = $systemConfig['cf_entidade'];
        Mage::log('_username:'.$this->_username, null, 'comprafacil.log');
        Mage::log('_password:'.$this->_password, null, 'comprafacil.log');
        Mage::log('_debugMode:'.$this->_debugMode, null, 'comprafacil.log');
        Mage::log('_config_entidade:'.$this->_config_entidade, null, 'comprafacil.log');
        
        if (empty($this->_order)){
            $this->_order = $payment->getOrder();    
        }
        
        if (empty($this->_payment)){
            $this->_payment = $payment;    
        }
        
        $order = $this->_getOrder();
        Mage::log('$order:'.$order, null, 'comprafacil.log');
        $billingAddress = $order->getBillingAddress();    
        $email = $order->getCustomerEmail();
        $info = $billingAddress->getStreetFull();
        $this->_orderID = $order->getIncrementId();
        if (!class_exists('soapclient')){ 
            Mage::log('soap: não', null, 'comprafacil.log');
            Webeffect_Comprafacil_Model_PaymentMethod::CallWithoutSoap($amount, $email, $info); 
        }else{
            Mage::log('soap: sim', null, 'comprafacil.log');
            Webeffect_Comprafacil_Model_PaymentMethod::CallWithSoap($amount, $email, $info);     
        }
        Webeffect_Comprafacil_Model_PaymentMethod::saveToSuportTable();
    }
 
    function CallWithSoap($valor, $email, $info){ 
        try {
            $origem = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'cf_callback.php?order='.md5($this->_orderID.$this->_key);
            if($this->_debugMode == 1){
                if($this->_config_entidade=="10241"){
                    $wsURL = "https://hm.comprafacil.pt/SIBSClickTESTE/webservice/ClicksmsV4.asmx?WSDL";    
                }else{
                    $wsURL = "https://hm.comprafacil.pt/SIBSClick2TESTE/webservice/ClicksmsV4.asmx?WSDL";    
                }
            }else if($this->_config_entidade=="10241"){
                $wsURL = "https://hm.comprafacil.pt/SIBSClick/webservice/ClicksmsV4.asmx?WSDL";
            }else if($this->_config_entidade=="11249"){
                $wsURL = "https://hm.comprafacil.pt/SIBSClick2/webservice/ClicksmsV4.asmx?WSDL";
            }
            $parameters = array(
                "origem" => $origem,
                "IDCliente" => $this->_username,
                "password" => $this->_password,
                "valor" => $valor,
                "informacao" => $info,
                "nome" => "",
                "morada" => "",
                "codPostal" => "",
                "localidade" => "l",
                "NIF" => "",
                "RefExterna" => "",
                "telefoneContacto" => "",
                "email" => $email,
                "IDUserBackoffice" => -1
                );
            
            $client = new SoapClient($wsURL);
            $res = $client->SaveCompraToBDValor2 ($parameters); 
        
            if ($res->SaveCompraToBDValor2Result){
                $this->_entity = $res->entidade;
                $this->_value = number_format($res->valorOut, 2);
                $this->_reference = $res->referencia;
                $this->_cferror = "";
                return true;
            }else{
                $this->_cferror = $res->error;
                return false;
            }
        }
        catch (Exception $e){
            $this->_cferror = $e->getMessage();
            return false;
        }
  } 
  
  function CallWithoutSoap($valor, $email, $info){
    $origem = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK).'cf_callback.php?order='.md5($this->_orderID.$this->_key);
    $jsPath =  Mage::getBaseDir();
    require_once $jsPath.'\app\code\local\Webeffect\Comprafacil\nusoap\lib\nusoap.php';
    
    $IDUserBackoffice="-1";

    $action='http://hm.comprafacil.pt/SIBSClick/webservice/SaveCompraToBDValor2';
    if($this->_debugMode == 1){
        if($this->_config_entidade=="10241"){
            $serverpath = "https://hm.comprafacil.pt/SIBSClickTESTE/webservice/ClicksmsV4.asmx?WSDL";    
        }else{
            $serverpath = "https://hm.comprafacil.pt/SIBSClick2TESTE/webservice/ClicksmsV4.asmx?WSDL";    
        }
    }else if($this->_config_entidade=="10241"){
        $serverpath = "https://hm.comprafacil.pt/SIBSClick/webservice/ClicksmsV4.asmx?WSDL";
    }else if($this->_config_entidade=="11249"){
        $serverpath = "https://hm.comprafacil.pt/SIBSClick2/webservice/ClicksmsV4.asmx?WSDL";
    }

    $client = new soapclient($serverpath);

    $msg=$client->serializeEnvelope('<SaveCompraToBDValor2 xmlns="http://hm.comprafacil.pt/SIBSClick/webservice/"><origem>'.$origem.'</origem><IDCliente>'.$this->_username.'</IDCliente><password>'.$this->_password.'</password><valor>'.$valor.'</valor><informacao>'.$info.'</informacao><nome></nome><morada></morada><codPostal></codPostal><localidade></localidade><NIF></NIF><RefExterna></RefExterna><telefoneContacto></telefoneContacto><email>'.$email.'</email><IDUserBackoffice>'.$IDUserBackoffice.'</IDUserBackoffice></SaveCompraToBDValor2>','',array(),'document', 'literal');

    $response = $client->send($msg,$action);

    if ($client->fault) {
        echo '<p>Fault</p><pre>';
        print_r($response);
        echo '</pre>';
    }
    
    $result=$response['SaveCompraToBDValor2Result'];
    $res = false;
    
    if($result == "true"){
        $this->_reference=$response['referencia'];
        $this->_entity=$response['entidade'];
        $this->_value=$response['valorOut'];
        $this->_cferror=$response['error'];   
        $res = true;
    }
    else{
        $this->_cferror=$response['error'];
    }
    return $res;
    }  
    
	function getRef($orderID){
       $systemConfig = Mage::getStoreConfig('payment/cf');  
       if ($systemConfig['label_entity'] != '' && $systemConfig['label_entity'] != NULL){ $label_entity = $systemConfig['label_entity']; } else{ $label_entity = 'Entity'; };
       if ($systemConfig['label_reference'] != '' && $systemConfig['label_reference'] != NULL){ $label_reference = $systemConfig['label_reference']; }else{ $label_reference = 'Reference'; };
       if ($systemConfig['label_value'] != '' && $systemConfig['label_value'] != NULL){ $label_value = $systemConfig['label_value']; }else{ $label_value =  'Value'; };
       $tableName = Mage::getSingleton('core/resource')->getTableName('comprafacil');
       $db = Mage::getSingleton('core/resource')->getConnection('core_write');
       $sql = $db->query('SELECT * FROM '.$tableName.' WHERE entity_id = '.$orderID);
       $data =  $sql->fetch(PDO::FETCH_ASSOC);
       $cf[$label_entity] = $label_entity." - ".$data['entity'];
       $cf[$label_reference] = $label_reference." - ".$data['reference'];
       $cf[$label_value] = $label_value." - ".$data['value']."€";
	   $array["Dados de Pagamento"] = $cf;
       return $array;
   }
   
   function getRefhtml($orderID){
       $systemConfig = Mage::getStoreConfig('payment/cf');  
       if ($systemConfig['label_entity'] != '' && $systemConfig['label_entity'] != NULL){ $label_entity = $systemConfig['label_entity']; } else{ $label_entity = 'Entity'; };
       if ($systemConfig['label_reference'] != '' && $systemConfig['label_reference'] != NULL){ $label_reference = $systemConfig['label_reference']; }else{ $label_reference = 'Reference'; };
       if ($systemConfig['label_value'] != '' && $systemConfig['label_value'] != NULL){ $label_value = $systemConfig['label_value']; }else{ $label_value =  'Value'; };
       $tableName = Mage::getSingleton('core/resource')->getTableName('comprafacil');
       $db = Mage::getSingleton('core/resource')->getConnection('core_write');
       $sql = $db->query('SELECT * FROM '.$tableName.' WHERE entity_id = '.$orderID);
       $data =  $sql->fetch(PDO::FETCH_ASSOC);
	   $html = "Dados de Pagamento:<br/>".$label_entity." - ".$data['entity']."<br/>".$label_reference." - ".$data['reference']."<br/>".$label_value." - ".$data['value']."€";
       return $html;
   }
   
   function saveToSuportTable(){
     $tableName = Mage::getSingleton('core/resource')->getTableName('comprafacil');
     $db = Mage::getSingleton('core/resource')->getConnection('core_write');
     $sql = "INSERT INTO ".$tableName." (`entity_id`, `reference`, `entity`, `value`, `status`, `key`) VALUES ('".$this->_orderID."', '".$this->_reference."', '".$this->_entity."', '".$this->_value."', '0', '".md5($this->_orderID.$this->_key)."')";     
     $db->query($sql);
     return;
   }
   
   function checkPay($ref){
       $systemConfig = Mage::getStoreConfig('payment/cf');  
       $this->_username = $systemConfig['cf_username'];
       $this->_password = $systemConfig['cf_password'];
       $this->_debugMode = $systemConfig['cf_debug'];
       $this->_config_entidade = $systemConfig['cf_entidade'];
       if (!class_exists('soapclient')){
        
            require_once $jsPath.'\app\code\local\Webeffect\Comprafacil\nusoap\lib\nusoap.php';

            $action='http://hm.comprafacil.pt/SIBSClick/webservice/getInfoCompra';
            if($this->_debugMode == 1){
                if($this->_config_entidade=="10241"){
                    $serverpath = "https://hm.comprafacil.pt/SIBSClickTESTE/webservice/ClicksmsV4.asmx?WSDL";    
                }else{
                    $serverpath = "https://hm.comprafacil.pt/SIBSClick2TESTE/webservice/ClicksmsV4.asmx?WSDL";    
                }
            }else if($this->_config_entidade=="10241"){
                $serverpath = "https://hm.comprafacil.pt/SIBSClick/webservice/ClicksmsV4.asmx?WSDL";
            }else if($this->_config_entidade=="11249"){
                $serverpath = "https://hm.comprafacil.pt/SIBSClick2/webservice/ClicksmsV4.asmx?WSDL";
            }

            $client = new soapclient($serverpath);

            $msg=$client->serializeEnvelope('<getInfoCompra xmlns="http://hm.comprafacil.pt/SIBSClick/webservice/"><IDCliente>'.$this->_username.'</IDCliente><password>'.$this->_password.'</password><referencia>'.$ref.'</referencia></getInfoCompra>','',array(),'document', 'literal');

            $response = $client->send($msg,$action);

            if ($client->fault) {
                echo '<p>Fault</p><pre>';
                print_r($response);
                echo '</pre>';
            }
            
            $result=$response['getInfoCompraResult'];
            
            if($result == "true"){
                if($response['pago'] == true){
                    return true;
                }else{
                    return false;
                }
            }
            else{
               return false;
            }
       }else{
          
           try 
            {
                if($this->_debugMode == 1){
                    if($this->_config_entidade=="10241"){
                        $wsURL = "https://hm.comprafacil.pt/SIBSClickTESTE/webservice/ClicksmsV4.asmx?WSDL";    
                    }else{
                        $wsURL = "https://hm.comprafacil.pt/SIBSClick2TESTE/webservice/ClicksmsV4.asmx?WSDL";    
                    }
                }else if($this->_config_entidade=="10241"){
                    $wsURL = "https://hm.comprafacil.pt/SIBSClick/webservice/ClicksmsV4.asmx?WSDL";
                }else if($this->_config_entidade=="11249"){
                    $wsURL = "https://hm.comprafacil.pt/SIBSClick2/webservice/ClicksmsV4.asmx?WSDL";
                }
                
                $parameters = array(
                 
                    "IDCliente" => $this->_username,
                    "password" => $this->_password,
                    "referencia" => $ref
                    );
                
                $client = new SoapClient($wsURL);
                $res = $client->getInfoCompra ($parameters); 
            
                if ($res->getInfoCompraResult)
                {
                    if($res->pago == true){
                        return true;
                    }else{
                        return false;
                    }
                }
                else
                {
                    return false;
                }
            }
            catch (Exception $e){
                return false;
            }
           
       }  
   }
}
?>