<?php
/**
 * Data.php
 * 
 * @category Cammino
 * @package  Cammino_Shipping
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-shipping
 */

class Cammino_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Function responsible for remove some shipping service
     * 
     * @param array $services (code, days, price)
     * 
     * @return array
     */
    public function removeService($services)
    {
        return $services;
    }

    /**
     * Function responsible for format return message according to number of days
     * 
     * @param float $days number of days to shipping
     * 
     * @return string
     */
    public function shippingDays($days)
    {
        if (intval($days) == 1) {
            return "um dia útil";
        } else {
            return "$days dias úteis";
        }
    }

    /**
     * Function responsible for apply custom rules to services
     * 
     * @param array $services (code, days, price)
     * @param array $args     package params
     * 
     * @return array
     */
    public function applyCustomRules($services, $args)
    {
        return $services;
    }
}