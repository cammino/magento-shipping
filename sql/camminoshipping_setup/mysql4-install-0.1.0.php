<?php
/**
* mysql4-install-0.1.0.php
*
* @category Cammino
* @package  Cammino_Shipping
* @author   Cammino Digital <suporte@cammino.com.br>
* @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
* @link     https://github.com/cammino/magento-banners
*/

$installer = $this;

$installer->startSetup();

$installer->run(
    "-- DROP TABLE IF EXISTS {$this->getTable('shipping_cache')};
    CREATE TABLE {$this->getTable('shipping_cache')} (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `uf` char(2) NOT NULL DEFAULT '',
        `uf_type` varchar(12) NOT NULL DEFAULT '',
        `service_code` varchar(12) NOT NULL DEFAULT '',
        `service_name` varchar(20) NOT NULL DEFAULT '',
        `initial_zipcode` int(8) NOT NULL,
        `final_zipcode` int(8) NOT NULL,
        `choosen_zipcode` int(8) NOT NULL,
        `origin_zipcode` int(8) NOT NULL,
        `destination_zipcode` int(8) NOT NULL,
        `weight` float NOT NULL,
        `price` decimal(12,4) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->endSetup(); 