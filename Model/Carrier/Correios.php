<?php
/**
 * Correios.php
 * 
 * @category Cammino
 * @package  Cammino_Shipping
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-shipping
 */

class Cammino_Shipping_Model_Carrier_Correios extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = "correios";
    
    /**
     * Function responsible for remove some shipping service
     * 
     * @param object $request Magento request object
     * 
     * @return object
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {        
        
        $result = Mage::getModel("shipping/rate_result");
        $error = Mage::getModel("shipping/rate_result_error");

        $originPostcode = Mage::getStoreConfig("shipping/origin/postcode", $this->getStore());
        $originPostcode = str_replace('-', '', trim($originPostcode));
    
        $destPostcode = $request->getDestPostcode();
        $destPostcode = str_replace('-', '', trim($destPostcode));

        $_weight = 0;
        $_packageX = 0;
        $_packageY = 0;
        $_packageZ = 0;

        $_defaultWeight = floatval($this->getConfigData("defaultweight"));
        $_defaultX = floatval($this->getConfigData("defaultx"));
        $_defaultY = floatval($this->getConfigData("defaulty"));
        $_defaultZ = floatval($this->getConfigData("defaultz"));

        // Inicializa variaveis de envio imediato
        $immediateShipment = (bool) $this->getConfigData("immediate_shipment_enable");    // Default false
        $immediateShipmentDays = intval($this->getConfigData("immediate_shipment_days")); // Default: 0
        $calcImmediateShipment = false; // Por default não calcula envio nao imediato

        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                
                if ($item->getParentItem()) continue;
                
                $_product = $item->getProduct();
                $_productId = $_product->getId();
                $_product  = Mage::getModel('catalog/product')->load($_productId);

                $_weightProd = 0;

                if ($_product->getShippingWeight()) {
                    $_weightProd = floatval($_product->getShippingWeight());
                } else if (floatval($item->getWeight()) > 0) {
                    $_weightProd = floatval($item->getWeight());
                } else {
                    $_weightProd = floatval($_defaultWeight);
                }

                $_weight += $_weightProd * $item->getQty();
                $_packageX += (floatval($_product->getShippingX()) > 0 ? floatval($_product->getShippingX()) : $_defaultX) * $item->getQty();
                $_packageY += (floatval($_product->getShippingY()) > 0 ? floatval($_product->getShippingY()) : $_defaultY) * $item->getQty();
                $_packageZ += (floatval($_product->getShippingZ()) > 0 ? floatval($_product->getShippingZ()) : $_defaultZ) * $item->getQty();

                //  Se o modulo de envio imediato estiver habilitado e ainda não tem nenhum produto com envio imediato
                //  verifica se o produto tem envio imediato
                if ($immediateShipment && !$calcImmediateShipment) {
                    $pis = $_product->getData('immediate_shipping');

                    // Se o produto NÃO (0) tem envio imediato, configura variavel para adicionar os dias extras
                    if ($pis == "0" && $pis != null) {
                        $calcImmediateShipment = true;
                    }
                }

            }
        }

        
        if (($_weight > 0) && ($_packageX > 0) && ($_packageY > 0) && ($_packageZ > 0)) {
            $_services = null;
            $_services = $this->getShippingAmount($originPostcode, $destPostcode, $_weight, $_packageX, $_packageY, $_packageZ);
        }

        $packinfo = array (
            'originPostcode' => $originPostcode,
            'destPostcode' => $destPostcode,
            'weight' => $_weight,
            'packageX' => $_packageX,
            'packageY' => $_packageY,
            'packageZ' => $_packageZ
        );

        $_services = $this->getHelper()->applyCustomRules($_services, $packinfo);

        $_shippingTitlePrefix = "";

        if (count($_services) > 0) {

            usort($_services, array('Cammino_Shipping_Model_Carrier_Correios','sortRates'));

            if ( $request->getFreeShipping() === true ) {
                //$_last = count($_services) - 1;
                $_services[0]["price"] = 0;
                //$_services[$_last]["code"] = "00000";
            }

            foreach ($_services as $service) {

                if ($service["price"] == 0) {
                    $_shippingTitlePrefix = "Frete Grátis - ";
                } else {
                    $_shippingTitlePrefix = "";
                }

                $_shippingDaysExtra = floatval($this->getConfigData("shippingdaysextra"));

                if ($_shippingDaysExtra > 0) {
                    $service["days"] += $_shippingDaysExtra;    
                }

                if ($calcImmediateShipment) {
                    $service["days"] += $immediateShipmentDays;
                }

                $this->addRateResult($result, $service["price"], $service["code"], $this->getHelper()->shippingDays($service["days"]), $_shippingTitlePrefix.$this->_shippingTitle($service["code"]));
            }

        } else {
            $this->addError($result, "Desculpe, no momento não estamos atuando com entregas para sua região.");
        }

        return $result;
    }
    
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName

    /**
     * Function responsible for adding shipping result to Magento
     * 
     * @param object $result        Magento result object
     * @param int    $shippingPrice Shipping price value
     * @param int    $shippingCode  Shipping service code
     * @param string $shippingDays  Days to shipping end message
     * @param string $shippingTitle Days to shipping start message
     * 
     * @return null
     */
    private function addRateResult($result, $shippingPrice, $shippingCode, $shippingDays, $shippingTitle)
    {
        // phpcs:enable PEAR.NamingConventions.ValidFunctionName
        $method = Mage::getModel("shipping/rate_result_method");
        $method->setCarrier("correios");
        $method->setCarrierTitle($this->getConfigData("title"));
        $method->setMethod("correios_$shippingCode");
        $method->setMethodTitle("$shippingTitle ($shippingDays) ");
        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        $result->append($method);
    }
    
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName
    
    /**
     * Function responsible for adding error message in shipping result
     * 
     * @param object $result       Magento result object
     * @param string $errorMessage Shipping error message
     * 
     * @return null
     */
    private function addError($result, $errorMessage)
    {
        // phpcs:enable PEAR.NamingConventions.ValidFunctionName
        $error = Mage::getModel("shipping/rate_result_error");        
        $error->setCarrier("correios");
        $error->setCarrierTitle($this->getConfigData("title"));
        $error->setErrorMessage("$errorMessage");
        $result->append($error);
    }
    
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName

    /**
     * Function responsible for format return message according to number of days
     * 
     * @param float $days number of days to shipping
     * 
     * @return string
     */
    private function shippingDays($days)
    {
        // phpcs:enable PEAR.NamingConventions.ValidFunctionName
        
        if (intval($days) == 1) {
            return "um dia útil";
        } else {
            return "$days dias úteis";
        }
    }

    /**
     * Function responsible for returning shipping title based on shipping code
     * 
     * @param int $code shipping code number
     * 
     * @return string
     */
    private function _shippingTitle($code)
    {
        switch ($code) {
        case '00000':
            return "Grátis";
            break;
        case '41106': // sem contrato
        case '41211': // com contrato
        case '41068': // com contrato
        case '04669': // com contrato
            return 'PAC';
            break;
        
        case '40045': // sem contrato
        case '40126': // com contrato
            return 'SEDEX a cobrar';
            break;

        case '81019': // com contrato
        case '81868': // com contrato (grupo 1)
        case '81833': // com contrato (grupo 2)
        case '81850': // com contrato (grupo 3)
            return 'e-SEDEX';
            break;

        case '81027': // com contrato
            return 'e-SEDEX prioritário';
            break;
                
        case '81035': // com contrato
            return 'e-SEDEX express';
            break;

        case '40010': // sem contrato
        case '40096': // com contrato
        case '40436': // com contrato
        case '40444': // com contrato
        case '40568': // com contrato
        case '40606': // com contrato
        case '04162': // com contrato
            return 'SEDEX';
            break;

        case '40215':
            return 'SEDEX 10';
            break;

        case '40169':
            return 'SEDEX 12';
            break;

        case '40290':
            return 'SEDEX Hoje';    
            break;

        default:
            break;
        }
    }

    /**
     * Function responsible for returning correios webservice shipping value
     * 
     * @param string $originPostcode origin zip code
     * @param string $destPostcode   destin zip code
     * @param float  $weight         shipping package weight
     * @param float  $x              shipping package x dimension
     * @param float  $y              shipping package y dimension
     * @param float  $z              shipping package z dimension
     * 
     * @return array
     */
    public function getShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z)
    {

        if(Mage::getStoreConfig('carriers/correios/defaultweighttype') != 'kg')
            //está cadastrado em gramas, divide por 1000
            $weight = $weight / 1000;

        if ($x < 16)
            $x = 16;

        if ($x > 105)
            $x = 105;

        if ($y < 2)
            $y = 2;

        if ($y > 105)
            $y = 105;

        if ($z < 11)
            $z = 11;

        if ($z > 105)
            $z = 105;

        if (($x+$y+$z) > 200) {
            $x = 66;
            $y = 66;
            $z = 66;
        }

        if ($weight == 0)
            $weight = 0.3;

        if ($weight > 30)
            $weight = 30;

        $formatedWeight = number_format($weight, 2, ',', '');
        
        // Configs
        $_services = $this->getConfigData("services");
        $_user = $this->getConfigData("user");
        $_pass = $this->getConfigData("pass");
        
        $url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx";
        $url .= "?nCdEmpresa=" . $_user;
        $url .= "&sDsSenha=" . $_pass;
        $url .= "&nCdServico=" . $_services;
        $url .= "&sCepOrigem=" . $originPostcode;
        $url .= "&sCepDestino=" . $destPostcode;
        $url .= "&nVlPeso=" . $formatedWeight;
        $url .= "&nCdFormato=1";
        $url .= "&nVlComprimento=" . $x;
        $url .= "&nVlAltura=" . $y;
        $url .= "&nVlLargura=" . $z;
        $url .= "&sCdMaoPropria=n";
        $url .= "&nVlValorDeclarado=0";
        $url .= "&sCdAvisoRecebimento=n";
        $url .= "&nVlDiametro=0";
        $url .= "&StrRetorno=xml";
        $url .= "&nIndicaCalculo=3";

        $result = $this->getXml($url);

        return $result;
    }

    /**
     * Function responsible for returning correios webservice shipping value
     * 
     * @param string $url correios webservice shipping price url
     * 
     * @return array|null
     */
    public function getXml($url)
    {
        $content = file_get_contents($url);
        $xml = simplexml_load_string($content);
        $services = null;

        // phpcs:disable Zend.NamingConventions.ValidVariableName

        foreach ($xml->cServico as $cServico) {

            if ((strval($cServico->MsgErro) != "") && (intval($cServico->Erro) != 9) && (intval($cServico->Erro) != 10) && (intval($cServico->Erro) != 11))
                continue;

            $services[] = array (
                "code" => intval($cServico->Codigo),
                "days" => intval($cServico->PrazoEntrega),
                "price" => floatval(str_replace(",", ".", str_replace(".", "", $cServico->Valor)))
            );
        }

        // phpcs:enable Zend.NamingConventions.ValidVariableName

        if (is_array($services)) {
            $services = $this->getHelper()->removeService($services);
            return $services;
        }

        return null;
    }
    
    /**
     * Function responsible for returning difference between prices
     * 
     * @param array $a shipping value infos
     * @param array $b another shipping value infos
     * 
     * @return float
     */
    public static function sortRates($a, $b)
    {
        return $a["price"] - $b["price"];
    }
    
    /**
     * Function responsible for returning correios allowed shipping methods
     * 
     * @return array
     */
    public function getAllowedMethods()
    {
        return array("correios" => $this->getConfigData("name"));
    }

    /**
     * Function responsible for returning tracking information
     * 
     * @param string $tracking correios tracking id
     * 
     * @return object
     */
    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('http://www.linkcorreios.com.br/?id=' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));

        return $track;
    }

    /**
     * Function responsible for returning correios custom helper instance
     * 
     * @return object
     */
    public function getHelper()
    {
        $customHelper = Mage::helper("camminoshipping/custom");
        return $customHelper;
    }
}