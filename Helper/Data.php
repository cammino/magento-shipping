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


    /**
     * Function responsible for return some regions list to generate correios cache
     * 
     * @return array
     */
    public function getRegionsToCache() {
        return array (
            //     0      1         2          3            4
            //     uf   uf_type   initial     final     destination
            array('SP','capital','01000000','09999999','01310932'),
            array('SP','general','01000000','19999999','15060035'),
            array('RJ','capital','20000000','26600999','22793380'),
            array('RJ','general','20000000','28999999','28930000'),
            array('ES','capital','29000000','29099999','29023047'),
            array('ES','general','29000000','29999999','29295000'),
            array('MG','capital','30000000','34999999','30672772'),
            array('MG','general','30000000','39999999','39790000'),
            array('BA','capital','40000000','44470999','41340545'),
            array('BA','general','40000000','48999999','48000001'),
            array('SE','capital','49000000','49099999','49070073'),
            array('SE','general','49000000','49999999','49790000'),
            array('PE','capital','50000000','54999999','50920825'),
            array('PE','general','50000000','56999999','56800000'),
            array('AL','capital','57000000','57099999','57073021'),
            array('AL','general','57000000','57999999','57630000'),
            array('PB','capital','58000000','58099999','58011345'),
            array('PB','general','58000000','58999999','58390000'),
            array('RN','capital','59000000','59099999','59123029'),
            array('RN','general','59000000','59999999','59965000'),
            array('CE','capital','60000000','61900999','60544760'),
            array('CE','general','60000000','63999999','62580000'),
            array('PI','capital','64000000','64099999','64090451'),
            array('PI','general','64000000','64999999','64480000'),
            array('MA','capital','65000000','65099999','65059421'),
            array('MA','general','65000000','65999999','65335000'),
            array('PA','capital','66000000','67999999','66815142'),
            array('PA','general','66000000','68899999','68690000'),
            array('AP','capital','68900000','68914999','68908465'),
            array('AP','general','68900000','68999999','68976000'),
            array('AM','capital','69000000','69099999','69036662'),
            array('AM','general','69000000','69899999','69445000'),
            array('RR','capital','69300000','69339999','69316275'),
            array('RR','general','69300000','69389999','69378000'),
            array('AC','capital','69900000','69920999','69918018'),
            array('AC','general','69900000','69999999','69980000'),
            array('DF','capital','70000000','70999999','70802060'),
            array('DF','general','70000000','73699999','73031501'),
            array('GO','capital','72800000','74894999','74786610'),
            array('GO','general','72800000','76799999','76155000'),
            array('TO','capital','77000000','77270999','77060194'),
            array('TO','general','77000000','77995999','77908000'),
            array('MT','capital','78000000','78109999','78089712'),
            array('MT','general','78000000','78899999','78770000'),
            array('RO','capital','76800000','76834999','76829550'),
            array('RO','general','76800000','76999999','76880000'),
            array('MS','capital','79000000','79129999','79072560'),
            array('MS','general','79000000','79999999','79680000'),
            array('PR','capital','80000000','83800999','81830190'),
            array('PR','general','80000000','87999999','87528000'),
            array('SC','capital','88000000','88469999','88058115'),
            array('SC','general','88000000','89999999','89778000'),
            array('RS','capital','90000000','94900999','91740011'),
            array('RS','general','90000000','99999999','98905000'),
        );
    }

    /**
     * Function responsible for return some regions list to generate correios cache
     * 
     * @return array
     */
    public function getWeightsToCache() {
        return array (0.3,0.6,1,2,3,4,5,6,8,10,12,14,15,18,20,22,26,30);
    }

    public function getActiveShippingMethods() {
        return Mage::getStoreConfig('carriers/correios/services');
    }
}