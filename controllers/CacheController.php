<?php
class Cammino_Shipping_CacheController extends Mage_Core_Controller_Front_Action {

    public function updateAction() {
        Mage::getModel("camminoshipping/shippingcache")->updateShippingCacheTable();
    }
}