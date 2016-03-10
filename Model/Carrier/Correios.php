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
		
		if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
            	
            	if ($item->getParentItem()) continue;
                
                $_product = $item->getProduct();
                $_productId = $_product->getId();
                $_product  = Mage::getModel('catalog/product')->load($_productId);
                $_weight   = $_product->getShippingWeight() * $item->getQty();
                $_packageX = $_product->getShippingX();
                $_packageY = $_product->getShippingY();
                $_packageZ = $_product->getShippingZ();
                $_productPrice = $item->getPrice();

                if ( $_weight && $_packageX && $_packageY && $_packageZ ) {
	                $_services = null;
	                $_services = $this->getShippingAmount($originPostcode, $destPostcode, $_weight, $_packageX, $_packageY, $_packageZ);
                }
            }
        }

        usort($_services, array('Cammino_Shipping_Model_Carrier_Correios','sortRates'));

        if ( count($_services) > 0 ) {

            if ( $request->getFreeShipping() === true ) {
            	$_last = count($_services) - 1;
            	$_services[$_last]["price"] = 0;
            	$_services[$_last]["code"] = "00000";
            }
               //$this->addRateResult($result, 0, "freeshipping", '', "Frete Grátis");
            // } else {
            	foreach ($_services as $service) {
            		$this->addRateResult($result, $service["price"], $service["code"], $this->shippingDays($service["days"]), $this->shippingTitle($service["code"]));
            	}
            // }
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
			case '41106': // sem contrato
			case '41211': // com contrato
			case '41068': // com contrato
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
				return 'SEDEX';
				break;

			case '40215':
				return 'SEDEX 10';
				break;

			case '40290':
				return 'SEDEX Hoje';	
				break;

			default:
				# code...
				break;
		}
	}
	
	public function getShippingAmount($originPostcode, $destPostcode, $weight, $x, $y, $z) {

				// Divide caso o usuario mande um número inteiro, para converter em gramas.
				if (!is_float($weight)) {
					$weight = $weight / 1000;
				}

				if ($x < 16)
					$x = 16;

				if ($y < 2)
					$y = 2;

				if ($z < 11)
					$z = 11;

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

	public function getXml($url) {
		$content = file_get_contents($url);
        $xml = simplexml_load_string($content);
        $services = null;

        foreach ($xml->cServico as $cServico) {

        	if (strval($cServico->MsgErro) != "")
        		continue;

        	$services[] = array (
            	"code" => intval($cServico->Codigo),
                "days" => intval($cServico->PrazoEntrega),
                "price" => floatval(str_replace(",", ".", str_replace(".", "", $cServico->Valor)))
            );
        }

        if (is_array($services)) {
        	return $services;
        }

        return null;
	}
	
	private static function sortRates($a, $b) {
        return $a["price"] - $b["price"];
    }
	
	public function getAllowedMethods() {
		return array("correios" => $this->getConfigData("name"));
	}
}