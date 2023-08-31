<?php
class Cammino_Shipping_Model_Source_Correios_Mode
{
    public function toOptionArray()
    {
    	return array(
            array('label' => 'API XML (legacy)', 'value' => 'legacy'),
            array('label' => 'API REST', 'value' => 'rest'),
        );   
    }
}