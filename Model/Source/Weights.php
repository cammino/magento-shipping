<?php
class  Cammino_Shipping_Model_Source_Weights
{
    public function toOptionArray()
    {
        $weights = array(
            // PAC
            array("value"=>"", "label"=>""),
            array("value"=>"kg", "label"=>"kilogramas"),
            array("value"=>"g", "label"=>"gramas"),        
        );

        return $weights;       
    }
}