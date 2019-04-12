<?php
/**
 * Weights.php
 * 
 * @category Cammino
 * @package  Cammino_Shipping
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-shipping
 */
class  Cammino_Shipping_Model_Source_Weights
{
    /**
     * Function responsible for returning weights labels
     * 
     * @return array
     */
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