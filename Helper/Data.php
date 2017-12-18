<?php
class Cammino_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function removeService($services){
        return $services;
    }

    public function shippingDays($days) {
        if(intval($days) == 1) {
            return "um dia útil";
        } else {
            return "$days dias úteis";
        }
    }

    public function applyCustomRules($services, $args) {
        return $services;
    }
}