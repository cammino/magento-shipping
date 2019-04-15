<?php
 
class Cammino_Shipping_Model_Mysql4_Shippingcache_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('camminoshipping/shippingcache');
    }
}