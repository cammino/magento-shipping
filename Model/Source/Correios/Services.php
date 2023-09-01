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
				array("value"=>"41106", "label"=>"PAC - 41106"),
				array("value"=>"04510", "label"=>"PAC - 04510"),
				array("value"=>"03298", "label"=>"PAC CONTRATO - 03298"),
				array("value"=>"41211", "label"=>"PAC com contrato - 41211"),
				array("value"=>"41068", "label"=>"PAC com contrato - 41068"),
				array("value"=>"04669", "label"=>"PAC com contrato - 04669"),
				array("value"=>"04596", "label"=>"PAC com contrato - 04596"),
				
				// SEDEX
				array("value"=>"40010", "label"=>"SEDEX - 40010"),
				array("value"=>"04014", "label"=>"SEDEX - 04014"),
				array("value"=>"03220", "label"=>"SEDEX CONTRATO - 03220"),
				array("value"=>"40096", "label"=>"SEDEX com contrato - 40096"),
				array("value"=>"40436", "label"=>"SEDEX com contrato - 40436"),
				array("value"=>"40444", "label"=>"SEDEX com contrato - 40444"),
				array("value"=>"40568", "label"=>"SEDEX com contrato - 40568"),
				array("value"=>"40606", "label"=>"SEDEX com contrato - 40606"),
				array("value"=>"04162", "label"=>"SEDEX com contrato - 04162"),
				array("value"=>"04553", "label"=>"SEDEX com contrato - 04553"),
				array("value"=>"03158", "label"=>"SEDEX 10 - 03158"),
				array("value"=>"40215", "label"=>"SEDEX 10 - 40215"),
				array("value"=>"03140", "label"=>"SEDEX 12 - 03140"),
				array("value"=>"40169", "label"=>"SEDEX 12 - 40169"),
				array("value"=>"40290", "label"=>"SEDEX Hoje - 40290"),
				
				// e-SEDEX
				array("value"=>"81019", "label"=>"e-SEDEX - 81019"),
				array("value"=>"81868", "label"=>"e-SEDEX Grupo 1 - 81868"),
				array("value"=>"81833", "label"=>"e-SEDEX Grupo 2 - 81833"),
				array("value"=>"81850", "label"=>"e-SEDEX Grupo 3 - 81850"),

				//Mini-envios
				array("value"=>"04227", "label"=>"MINI ENVIOS - 04227"),
				array("value"=>"04960", "label"=>"MINI ENVIOS - 04960")
	        );    		
    	} else {
    		$services = array(
				array("value"=>"41106", "label"=>"PAC - 41106"),
				array("value"=>"04510", "label"=>"PAC - 04510"),
				array("value"=>"40010", "label"=>"SEDEX - 40010"),
				array("value"=>"04014", "label"=>"SEDEX - 04014"),
				array("value"=>"40215", "label"=>"SEDEX 10 - 40215"),
				array("value"=>"40169", "label"=>"SEDEX 12 - 40169"),
				array("value"=>"40290", "label"=>"SEDEX Hoje - 40290")
	        );
    	}

    	return $services;

        
    }
}