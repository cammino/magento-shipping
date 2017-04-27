<?php
class Cammino_Shipping_Helper_Custom extends Mage_Core_Helper_Abstract
{
	/* Override this method in codepool local when you need */
	public function removeService($services){
		return $services;
    }
}