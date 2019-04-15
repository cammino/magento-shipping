<?php
class Cammino_Shipping_Model_Shippingcache extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('camminoshipping/shippingcache');
    }

    public function updateShippingCacheTable() {
        $model = Mage::getModel("camminoshipping/shippingcache");
        $helper = Mage::helper("camminoshipping");
        
        $regions = $helper->getRegionsToCache();
        $weights = $helper->getWeightsToCache();
        $methods = $helper->getActiveShippingMethods();

        $originZipcode = Mage::getStoreConfig("shipping/origin/postcode", Mage::app()->getStore());
        $originZipcode = str_replace('-', '', trim($originZipcode));
        
        foreach($regions as $region) {
            foreach($weights as $weight) {

                $destinationZipcode = $region[4];
                $x = 16;
                $y = 2;
                $z = 11;

                $x = Mage::getModel("camminoshipping/carrier_correios")->getShippingAmount($originZipcode, $destinationZipcode, $weight, $x, $y, $z);

                echo "<pre>"; var_dump($x); die;

                // $data = array(
                //     'uf' => $region[0],
                //     'uf_type' => $region[1],
                //     'service_code' => $method,
                //     'service_name' => 'PAC',
                //     'initial_zipcode' => $region[2],
                //     'final_zipcode' => $region[3],
                //     'origin_zipcode' => $originZipcode,
                //     'destination_zipcode' => $region[4],
                //     'weight' => $weight,
                //     'price' => 22.9
                // );
                // $model->setData($data)->save();
            }
        }
    }
}