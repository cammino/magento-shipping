<?php
class Cammino_Shipping_Model_Source_Correios_Services
{
    public function toOptionArray()
    {
        return array(
			array("value"=>"pac", "label"=>"PAC"),
			array("value"=>"sedex", "label"=>"SEDEX")
        );
    }
}