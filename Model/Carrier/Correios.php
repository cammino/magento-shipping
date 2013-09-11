<?php
class Cammino_Shipping_Model_Carrier_Correios extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

	protected $_code = "correios";
	private $_errors = array();
	private $_resultsCount = 0;
	private $_currencyRate = 1;
	private $_kinghostAuth = "b14a7b8059d9c055954c92674ce60032";
	private $_area1 = array();
	private $_area2 = array();
	private $_area3 = array();
	
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {		
		$result = Mage::getModel("shipping/rate_result");
		$error = Mage::getModel("shipping/rate_result_error");
		
		$originPostcode = Mage::getStoreConfig("shipping/origin/postcode", $this->getStore());
		$originPostcode = str_replace('-', '', trim($originPostcode));
		$destPostcode = $request->getDestPostcode();
		$destPostcode = str_replace('-', '', trim($destPostcode));
	//	$weightUnits = $this->getConfigData("weight_units");
	//	$weight = $request->getPackageWeight() * $weightUnits;
		$weight = $request->getPackageWeight();

		if (floatval($weight) <= 0) $weight = 100;
		
		$totals = Mage::helper('checkout/cart')->getQuote()->getTotals();
		$subtotal = $totals["subtotal"]["value"];
		
		$services = explode(",", $this->getConfigdata("services"));
	//	$destAddress = $this->getAddressByPostcode($destPostcode);
		$destAddress = null;

		$freepac = intval($this->getConfigdata("freepac")) == 1 ? true : false;
	//	$freeminamount = floatval($this->getConfigdata("freeminamount"));

	//	$this->shippingFreeRules($destAddress, $subtotal, $result);
		
		if (!$this->shippingFreeRules($destAddress, $subtotal, $result)) {		
			for($i = 0; $i < count($services); $i++) {
				$amountObj = $this->getShippingAmount($originPostcode, $destPostcode, $weight, $services[$i]);
			
				$shippingPrice = floatval($amountObj["valor"]);
				$shippingCode = $services[$i];
			//	$shippingDays = $services[$i] == "sedex" ? $this->shippingDays(3) : $this->shippingDays(10);
				$shippingDays = $this->shippingDays($amountObj["prazo"]);
				$shippingTitle = $this->getMethodTitle($services[$i]);

				if (($shippingTitle == "PAC") && ($freepac)) {
					$this->addFreePACShipping($result);
				} else {
					$this->addRateResult($result, $shippingPrice, $shippingCode, $shippingDays, $shippingTitle);
				}
	
			}
		}
		
		if(($this->_resultsCount == 0) && (count($this->_errors) > 0)) {
			$this->addErrors($result);
		}

		return $result;
	}
	
	private function shippingFreeRules($destAddress, $subtotal, $result) {
		// if($subtotal >= 300) {
		// 	$this->addFreeShipping($result);
		// 	return true;
		// } else {
		// 	$this->addFreeShipping($result);
		// }
	}
	
	private function addFreeShipping($result) {
		$method = Mage::getModel("shipping/rate_result_method");

		$method->setCarrier("freeshipping");
		$method->setCarrierTitle("Frete Grátis");
		$method->setMethod("freeshipping_freeshipping");
		$method->setMethodTitle("Frete Grátis");
		$method->setPrice(0);
		$method->setCost(0);

		$result->append($method);
	}

	private function addFreePACShipping($result) {
		$method = Mage::getModel("shipping/rate_result_method");

		$method->setCarrier("correios");
		$method->setCarrierTitle("PAC");
		$method->setMethod("correios_freepac");
		$method->setMethodTitle("PAC<br/><span style=\"font-weight: normal;\">Frete Grátis - Entrega em 10 dias</span>");
		$method->setPrice(0);
		$method->setCost(0);

		$result->append($method);
	}
	
	private function addRateResult($result, $shippingPrice, $shippingCode, $shippingDays, $shippingTitle) {
		if ($shippingPrice == 0) {
			$errorMessage = "Não foi possível calcular o frete para este endereço.";
		}

		if(strlen($errorMessage) <= 0) {
			$method = Mage::getModel("shipping/rate_result_method");
	
			$method->setCarrier("correios");
			$method->setCarrierTitle($this->getConfigData("title"));
			$method->setMethod("correios_$shippingCode");
			$method->setMethodTitle("$shippingTitle<br/><span style=\"font-weight: normal;\">Entrega em $shippingDays</span> ");
			$method->setPrice($shippingPrice);
			$method->setCost($shippingPrice);
	
			$result->append($method);

			$this->_resultsCount += 1;
		} else {
			array_push($this->_errors, $errorMessage);
		}
	}
	
	private function addErrors($result) {
		$error = Mage::getModel ('shipping/rate_result_error');
		$allErrorMessages = "";
		
		foreach($this->_errors as $errorMessage) {
			$allErrorMessages .= "$errorMessage";
			break;
		}
		
		$error->setCarrier('correios');
		$error->setCarrierTitle($this->getConfigData('title'));
		$error->setErrorMessage("$allErrorMessages");

		$result->append($error);
	}
	
	private function getMethodTitle($code) {
		$methods = array();
		
		$methods["pac"] = "PAC";
		$methods["sedex"] = "SEDEX";
		
		return $methods[$code];
	}
	
	private function shippingDays($days) {
		if(intval($days) == 1) {
			return "um dia útil";
		} else {
			return "$days dias úteis";
		}
	}
	
	public function getAddressByPostcode($postcode) {
		$url = "http://webservice.kinghost.net/web_cep.php?formato=javascript&auth=".$this->_kinghostAuth."&cep=".$postcode;
		$json = $this->getJson($url);
		return $json;
	}
	
	public function getShippingAmount($originPostcode, $destPostcode, $weight, $shippingService) {

	// $url = "http://webservice.kinghost.net/web_frete.php?formato=javascript&auth=".$this->_kinghostAuth."&tipo=".$shippingService."&cep_origem=".$originPostcode."&cep_destino=".$destPostcode."&peso=".$weight;
	// $json = $this->getJson($url);		
	// return $json;

		$shippingServiceCode = "";

		if ($shippingService == "sedex") {
			$shippingServiceCode = "40010";
		} else if ($shippingService == "pac") {
			$shippingServiceCode = "41106";
		}

		$formatedWeight = number_format(($weight/1000), 2, ',', '');

		$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx";
		$url .= "?nCdEmpresa=";
		$url .= "&sDsSenha=";
		$url .= "&nCdServico=" . $shippingServiceCode;
		$url .= "&sCepOrigem=" . $originPostcode;
		$url .= "&sCepDestino=" . $destPostcode;
		$url .= "&nVlPeso=" . $formatedWeight;
		$url .= "&nCdFormato=1";
		$url .= "&nVlComprimento=25";
		$url .= "&nVlAltura=15";
		$url .= "&nVlLargura=25";
		$url .= "&sCdMaoPropria=n";
		$url .= "&nVlValorDeclarado=0";
		$url .= "&sCdAvisoRecebimento=n";
		$url .= "&nVlDiametro=0";
		$url .= "&StrRetorno=xml";
		$url .= "&nIndicaCalculo=3";

		var_dump($url);

		$result = $this->getXml($url);

		return $result;
	}

	public function getXml($url) {
		$content = file_get_contents($url);
		$xml = simplexml_load_string($content);

		$result = array(
			'prazo' => intval($xml->cServico->PrazoEntrega),
			'valor' => floatval(str_replace(",", ".", str_replace(".", "", $xml->cServico->Valor)))
		);

		return $result;
	}
	
	public function getJson($url) {
		$content = file_get_contents($url);
		$content = str_replace("var resultado = ", "", $content);
		$content = str_replace("var resultadoCEP = ", "", $content);
		$content = str_replace("'", "\"", $content);
		$content = str_replace("\t", "", $content);
		$content = str_replace(" ", "", $content);
		
		$json = json_decode($content, true);
		
		foreach($json as $key => $value) {
			$json[$key] = utf8_encode(urldecode($value));
		}
		
		return $json;
	}
	
	public function getAllowedMethods() {
		return array("correios" => $this->getConfigData("name"));
	}
	
	public function isTrackingAvailable() {
		return false;
	}

	public function getTrackingInfo($trackingNumber) {
		return false;
    }
}