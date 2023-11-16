<?php
class Cammino_Shipping_Model_Carrier_Correios extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = "correios";
    
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {        
        
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

            $higherLength = 0;
            $higherHeight = 0;
            $higherWidth = 0;

            foreach ($request->getAllItems() as $item) {
                
                if ($item->getParentItem()) continue;
                
                $_product = $item->getProduct();
                $_productId = $_product->getId();
                $_product  = Mage::getModel('catalog/product')->load($_productId);

                $_weightProd = 0;

                if ($_product->getShippingWeight()) {
                    $_weightProd = floatval($_product->getShippingWeight());
                } else if(floatval($item->getWeight()) > 0) {
                    $_weightProd = floatval($item->getWeight());
                } else {
                    $_weightProd = floatval($_defaultWeight);
                }

                $_weight += $_weightProd * $item->getQty();
                
                if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_length'))) {
                    if ($_product->getShippingX() > $higherLength) {
                        $higherLength = floatval($_product->getShippingX());
                    }
                } else {
                    $_packageX += (floatval($_product->getShippingX()) > 0 ? floatval($_product->getShippingX()) : $_defaultX) * $item->getQty();
                }
                
                if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_height'))) {
                    if ($_product->getShippingX() > $higherHeight) {
                        $higherHeight = floatval($_product->getShippingX());
                    }
                } else {
                    $_packageY += (floatval($_product->getShippingY()) > 0 ? floatval($_product->getShippingY()) : $_defaultY) * $item->getQty();
                }
                
                if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_width'))) {
                    if ($_product->getShippingX() > $higherWidth) {
                        $higherWidth = floatval($_product->getShippingX());
                    }
                } else {
                    $_packageZ += (floatval($_product->getShippingZ()) > 0 ? floatval($_product->getShippingZ()) : $_defaultZ) * $item->getQty();
                }

                //  Se o modulo de envio imediato estiver habilitado e ainda não tem nenhum produto com envio imediato
                //  verifica se o produto tem envio imediato
                if($immediateShipment && !$calcImmediateShipment){
                    $pis = $_product->getData('immediate_shipping');

                    // Se o produto NÃO (0) tem envio imediato, configura variavel para adicionar os dias extras
                    if($pis == "0" && $pis != null){
                        $calcImmediateShipment = true;
                    }
                }

            }

            if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_length'))) {
                $_packageX = ($higherLength > 0) ? $higherLength : $_defaultX;
            }
            if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_height'))) {
                $_packageY = ($higherHeight > 0) ? $higherHeight : $_defaultY;
            }
            if (!empty(Mage::getStoreConfig('carriers/correios/use_higher_width'))) {
                $_packageZ = ($higherWidth > 0) ? $higherWidth : $_defaultZ;
            }
            
        }

	    $_services = null;
        $_services = $this->getShippingAmount($originPostcode, $destPostcode, $_weight, $_packageX, $_packageY, $_packageZ);
            
        $_services = $this->getHelper()->applyCustomRules($_services, array(
            'originPostcode' => $originPostcode,
            'destPostcode' => $destPostcode,
            'weight' => $_weight,
            'packageX' => $_packageX,
            'packageY' => $_packageY,
            'packageZ' => $_packageZ
        ));

        $_shippingTitlePrefix = "";

        if ( count($_services) > 0 ) {

            usort($_services, array('Cammino_Shipping_Model_Carrier_Correios','sortRates'));

            if ( $request->getFreeShipping() === true ) {
                //$_last = count($_services) - 1;
                foreach($_services as $index => $_service) {
                    if (!empty($_service['code'])) {
                        $_services[$index]['price'] = 0;
                        break;
                    }
                }
                //$_services[$_last]["code"] = "00000";
            }

            foreach ($_services as $service) {
		    
                if (empty($service['code'])) {
                    continue;
                }

                if ($service["price"] == 0) {
                    $_shippingTitlePrefix = "Frete Grátis - ";
                } else {
                    $_shippingTitlePrefix = "";
                }

                $_shippingDaysExtra = floatval($this->getConfigData("shippingdaysextra"));

                if ($_shippingDaysExtra > 0) {
                    $service["days"] += $_shippingDaysExtra;    
                }

                if($calcImmediateShipment){
                    $service["days"] += $immediateShipmentDays;
                }

                $this->addRateResult($result, $service["price"], $service["code"], $this->getHelper()->shippingDays($service["days"]), $_shippingTitlePrefix.$this->shippingTitle($service["code"]));
            }

        } else {
            $this->addError($result, "Desculpe, no momento não estamos atuando com entregas para sua região.");
        }

        return $result;
    }
    
    private function addRateResult($result, $shippingPrice, $shippingCode, $shippingDays, $shippingTitle) {
        $method = Mage::getModel("shipping/rate_result_method");
        $method->setCarrier("correios");
        $method->setCarrierTitle($this->getConfigData("title"));
        $method->setMethod("correios_$shippingCode");
        $method->setMethodTitle("$shippingTitle ($shippingDays) ");
        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        $result->append($method);
    }
    
    private function addError($result, $errorMessage) {
        $error = Mage::getModel ("shipping/rate_result_error");        
        $error->setCarrier("correios");
        $error->setCarrierTitle($this->getConfigData("title"));
        $error->setErrorMessage("$errorMessage");
        $result->append($error);
    }
    
    private function shippingDays($days) {
        if(intval($days) == 1) {
            return "um dia útil";
        } else {
            return "$days dias úteis";
        }
    }

    private function shippingTitle($code)
    {
        switch ($code) {

            case '00000':
                return "Grátis";
                break;

            case '41106':
            case '41211':
            case '41068':
            case '04669':
            case '03298':
            case '04510':
                return 'PAC';
                break;
            
            case '40045':
            case '40126':
                return 'SEDEX a cobrar';
                break;

            case '81019':
            case '81868':
            case '81833':
            case '81850':
                return 'e-SEDEX';
                break;

            case '81027':
                return 'e-SEDEX prioritário';
                break;
                    
            case '81035':
                return 'e-SEDEX express';
                break;

            case '40010':
            case '40096':
            case '40436':
            case '40444':
            case '40568':
            case '40606':
            case '04162':
            case '03220':
            case '04014':
                return 'SEDEX';
                break;

            case '03158':
            case '40215':
                return 'SEDEX 10';
                break;    

            case '03140':
            case '40169':
                return 'SEDEX 12';
                break;

            case '40290':
                return 'SEDEX Hoje';    
                break;

            case '04227':
            case '04960':
                return 'MINI ENVIOS';
                break;

            default:
                break;
        }
    }

    public function getToken() {
        $url = 'https://api.correios.com.br/token/v1/autentica/contrato';
        $postcard = $this->getConfigData("postcard");
        $user = $this->getConfigData("user");
        $pass = $this->getConfigData("pass");
        $contract = $this->getConfigData("contract");
        $headers = array('Authorization: Basic ' . base64_encode($user . ':' . $pass));
        $data = array('numero' => $contract);

        if (strval($postcard) != '') {
            $url = 'https://api.correios.com.br/token/v1/autentica/cartaopostagem';
            $data = array('numero' => $postcard);
        }

        $result = $this->requestUrl($url, $data, 'POST', $headers);
        $json = json_decode($result);

        if ($jsonPrice->msg) {
            return null;
        } else {
            return $json->token;
        }
    }

    public function refreshToken() {
        $token = $this->getToken();

        if ($token) {
            Mage::getConfig()->saveConfig('carriers/correios/token', $token, 'default', 0);
            Mage::app()->getConfig()->reinit();

            return $token;
        } else {
            return null;
        }
    }

    public function requestUrl($url, $data = array(), $method = 'GET', $headers = array()) {

        $payload = '';
        $ch = curl_init($url);
        $headers[] = 'Content-Type: application/json';

        if ($method == 'POST') {
            $payload = json_encode($data);
            $headers[] = 'Content-Length: ' . strlen($payload);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } else {
            $payload = http_build_query($data);
            $url .= '?' . $payload;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        Mage::log('REQUEST:', null, 'correios.log');
        Mage::log($url, null, 'correios.log');
        Mage::log($payload, null, 'correios.log');

        $result = curl_exec($ch);
        curl_close($ch);

        Mage::log('RESPONSE:', null, 'correios.log');
        Mage::log($result, null, 'correios.log');

        return $result;
    }
    
    public function getShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z) {
        if ($this->getConfigData("mode") == 'rest') {
            return $this->getRestShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z);
        } else {
            return $this->getLegacyShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z);
        }
    }

    public function getRestShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z) {

        $this->fixDimensions($weight, $x, $y, $z);

        //$token = $this->getToken();
        $token = $this->getConfigData("token");
        $contract = $this->getConfigData("contract");
        $nudr = $this->getConfigData("nudr");

        if (empty(strval($token))) {
            $token = $this->refreshToken();
        }

        if ($token == null) {
            return array();
        }

        $services = $this->getConfigData("services");
        $formatedWeight = number_format($weight, 0, '', '');
        $rates = [];

        foreach(explode(',', $services) as $service) {
            $data = array(
                'cepDestino' => $destPostcode,
                'cepOrigem' => $originPostcode,
                'psObjeto' => $formatedWeight,
                'tpObjeto' => '2',
                'comprimento' => $x,
                'largura' => $z,
                'altura' => $y
            );

            if ((strval($contract) != '') && (strval($nudr) != '')) {
                $data['nuContrato'] = $contract;
                $data['nuDR'] = $nudr;
            }

            $rates[] = $this->getRestRates($service, $data, $token);
        }

        return $rates;
    }

    public function getRestRates($service, $data, $token) {

        $headers = array('Authorization: Bearer  ' . $token);
        
        $urlPrice = 'https://api.correios.com.br/preco/v1/nacional/' . $service;
        $resultPrice = $this->requestUrl($urlPrice, $data, 'GET', $headers);
        $jsonPrice = json_decode($resultPrice);

        $urlDeadline = 'https://api.correios.com.br/prazo/v1/nacional/' . $service;
        $dataDeadline = array(
            'coProduto' => $service,
            'cepOrigem' => $data['cepOrigem'],
            'cepDestino' => $data['cepDestino']
        );
        $resultDeadline = $this->requestUrl($urlDeadline, $dataDeadline, 'GET', $headers);
        $jsonDeadline = json_decode($resultDeadline);

        $services = array();

        if ($jsonPrice->msg) {
            return null;
        } else {
            $services = array (
                "code" => $jsonPrice->coProduto,
                "days" => $jsonDeadline->prazoEntrega,
                "price" => floatval(str_replace(",", ".", str_replace(".", "", $jsonPrice->pcFinal)))
            );

            $services = $this->getHelper()->removeService($services);

            return $services;
        }
    }

    public function getLegacyShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z) {
        
        $this->fixDimensions($weight, $x, $y, $z);

        // Configs
        $_services = $this->getConfigData("services");
        $_user = $this->getConfigData("user");
        $_pass = $this->getConfigData("pass");
        $formatedWeight = number_format($weight, 2, ',', '');
        $rates = [];

        foreach(explode(',', $_services) as $service) {
            $url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx";
            $url .= "?nCdEmpresa=" . $_user;
            $url .= "&sDsSenha=" . $_pass;
            $url .= "&nCdServico=" . $service;
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
            $rates[] = $this->getXmlRates($url)[0];
        }
        return $rates;
    }

    public function getXmlRates($url) {

        Mage::log('REQUEST:', null, 'correios.log');
        Mage::log($url, null, 'correios.log');

        $content = file_get_contents($url);

        Mage::log('RESPONSE:', null, 'correios.log');
        Mage::log($content, null, 'correios.log');

        $xml = simplexml_load_string($content);
        $services = null;

        foreach ($xml->cServico as $cServico) {

            if ((strval($cServico->MsgErro) != "") && (intval($cServico->Erro) != 9) && (intval($cServico->Erro) != 10) && (intval($cServico->Erro) != 11))
                continue;

            $services[] = array (
                "code" => intval($cServico->Codigo),
                "days" => intval($cServico->PrazoEntrega),
                "price" => floatval(str_replace(",", ".", str_replace(".", "", $cServico->Valor)))
            );
        }

        if (is_array($services)) {
            $services = $this->getHelper()->removeService($services);
            return $services;
        }

        return null;
    }

    public function fixDimensions(&$weight, &$x, &$y, &$z) {

        if ($this->getConfigData("mode") == 'rest') {
            if(Mage::getStoreConfig('carriers/correios/defaultweighttype') == 'kg') {
                $weight = $weight * 1000;
            }

            if ($weight == 0) {
                $weight = 300;
            }
                
            if ($weight > 30000) {
                $weight = 30000;
            }

        } else {
            if(Mage::getStoreConfig('carriers/correios/defaultweighttype') != 'kg') {
                $weight = $weight / 1000;
            }

            if ($weight == 0) {
                $weight = 0.3;
            }
                
            if ($weight > 30) {
                $weight = 30;
            }
        }

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
    }
    
    public static function sortRates($a, $b) {
        return $a["price"] - $b["price"];
    }
    
    public function getAllowedMethods() {
        return array("correios" => $this->getConfigData("name"));
    }

    public function isTrackingAvailable() {
        return true;
    }

    public function getTrackingInfo($tracking) {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('http://www.linkcorreios.com.br/?id=' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));

        return $track;
    }

    public function getTracking($trackings)
    {
        $this->_result = Mage::getModel('shipping/tracking_result');
        foreach ((array) $trackings as $code) {
            
            $track = Mage::getModel('shipping/tracking_result_status');
            $track->setUrl('http://www.linkcorreios.com.br/?id=' . $code)
                ->setTracking($code)
                ->setCarrierTitle($this->getConfigData('name'));

            $this->_result->append($track);

        }
        return $this->_result;
    }

    public function getHelper() {
        $customHelper = Mage::helper("camminoshipping/custom");
        return $customHelper;
    }
}
