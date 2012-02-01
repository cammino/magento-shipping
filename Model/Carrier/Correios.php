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
		$destAddress = $this->getAddressByPostcode($destPostcode);
		
		if (!$this->shippingFreeRules($destAddress, $subtotal, $result)) {		
			for($i = 0; $i < count($services); $i++) {
				$amountObj = $this->getShippingAmount($originPostcode, $destPostcode, $weight, $services[$i]);
			
				$shippingPrice = floatval($amountObj["valor"]);
				$shippingCode = $services[$i];
				$shippingDays = $services[$i] == "sedex" ? $this->shippingDays(2) : $this->shippingDays(8);
				$shippingTitle = $this->getMethodTitle($services[$i]);
			
				$this->addRateResult($result, $shippingPrice, $shippingCode, $shippingDays, $shippingTitle);
			}
		}
		
		if(($this->_resultsCount == 0) && (count($this->_errors) > 0)) {
			$this->addErrors($result);
		}

		return $result;
	}
	
	private function shippingFreeRules($destAddress, $subtotal, $result) {
		return false;
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
		$url = "http://webservice.kinghost.net/web_frete.php?formato=javascript&auth=".$this->_kinghostAuth."&tipo=".$shippingService."&cep_origem=".$originPostcode."&cep_destino=".$destPostcode."&peso=".$weight;
		$json = $this->getJson($url);		
		return $json;
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