<?php
 
class Cammino_Shipping_Model_Mysql4_Shippingcache extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('camminoshipping/shippingcache', 'id');
    }
}