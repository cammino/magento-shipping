<?php
class Cammino_Shipping_Model_Source_Correios_Services
{
    public function toOptionArray()
    {

    	$confUser = Mage::getStoreConfig('carriers/correios/user');
    	$confPass = Mage::getStoreConfig('carriers/correios/pass');
    	$services = null;

    	if ( !empty($confUser) && !empty($confPass) ) {
			$services = array(
				// PAC
				array("value"=>"41106", "label"=>"PAC"),
				array("value"=>"41211", "label"=>"PAC (com contrato) 41211"),
				array("value"=>"41068", "label"=>"PAC (com contrato) 41068"),
				array("value"=>"04669", "label"=>"PAC (com contrato) 04669"),
				
				// SEDEX
				array("value"=>"40010", "label"=>"SEDEX"),
				array("value"=>"40096", "label"=>"SEDEX (com contrato) 40096"),
				array("value"=>"40436", "label"=>"SEDEX (com contrato) 40436"),
				array("value"=>"40444", "label"=>"SEDEX (com contrato) 40444"),
				array("value"=>"40568", "label"=>"SEDEX (com contrato) 40568"),
				array("value"=>"40606", "label"=>"SEDEX (com contrato) 40606"),
				array("value"=>"04162", "label"=>"SEDEX (com contrato) 04162"),
				array("value"=>"40215", "label"=>"SEDEX 10"),
				array("value"=>"40169", "label"=>"SEDEX 12"),
				array("value"=>"40290", "label"=>"SEDEX Hoje"),
				
				// e-SEDEX
				array("value"=>"81019", "label"=>"e-SEDEX"),
				array("value"=>"81868", "label"=>"e-SEDEX (Grupo 1)"),
				array("value"=>"81833", "label"=>"e-SEDEX (Grupo 2)"),
				array("value"=>"81850", "label"=>"e-SEDEX (Grupo 3)")
	        );    		
    	} else {
    		$services = array(
				array("value"=>"41106", "label"=>"PAC"),
				array("value"=>"40010", "label"=>"SEDEX"),
				array("value"=>"40215", "label"=>"SEDEX 10"),
				array("value"=>"40169", "label"=>"SEDEX 12"),
				array("value"=>"40290", "label"=>"SEDEX Hoje")
	        );
    	}

    	return $services;

        
    }
}