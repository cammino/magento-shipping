<?php
require_once __DIR__ . '/../../../../../../../../../Mage.php';
Mage::app();
class Teste_Cammino_Shipping_Model_Carrier_Correios extends PHPUnit_Framework_TestCase
{
	private $model;
	private $helper;

	public function __construct() {
		$this->model =  Mage::getModel("camminoshipping/carrier_correios");
		$this->helper = Mage::helper("camminoshipping/data");
	}

	private function turnMethodsAccessible($method) {
		$reflector = new ReflectionClass($this->model);
		$accessibleMethod = $reflector->getMethod($method);
		$accessibleMethod->setAccessible( true );
		return $accessibleMethod;
	}
    public function testShippingDaysReturnsOneDay(){
    	$shippingDays = 1;
    	$value = $this->helper->shippingDays($shippingDays);
        $this->assertEquals("um dia útil", $value, 'Era esperado 1 dia útil de retorno, mas retornou ' . $value);
    }

    public function testShippingDaysReturnsNDays(){
    	$shippingDays = 5;
    	$value = $this->helper->shippingDays($shippingDays);
        $this->assertEquals($shippingDays . " dias úteis", $value, 'Era esperado ' . $shippingDays . ' dias úteis de retorno, mas retornou ' . $value);
    }

    public function testShippingTitleReturnsFree() {
    	$code = '0000';
    	
 		$method = $this->turnMethodsAccessible('shippingTitle');	
 		$value = $method->invokeArgs($this->model, array($code));
    	$this->assertEquals("Grátis", $value, 'Era esperado frete grátis, mas o retorno foi ' . $value);   	
    }

    public function testShippingTitleReturnsPAC() {
    	$code = '41106';
    	$method = $this->turnMethodsAccessible('shippingTitle');
    	$value = $method->invokeArgs($this->model, array($code));
    	$this->assertEquals("PAC", $value, 'Era esperado PAC, mas o retorno foi ' . $value);	
    }

    public function testSortRatesReturnsSum() {
    	$firstValue = $secondValue = array();
    	$firstValue['price'] = 10; 
    	$secondValue['price'] = 1;
    	$method = $this->turnMethodsAccessible('sortRates');
		$value = $method->invokeArgs($this->model, array($firstValue, $secondValue));
    	$this->assertEquals(6, $value, 'O retorno da função esperado é 9, mas o valor retornado é ' . $value);	
    }

	public function testGetXmlIsArray() {
		$url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=13026291&sDsSenha=00662798&nCdServico=04669,04162&sCepOrigem=15025035&sCepDestino=15010020&nVlPeso=0,30&nCdFormato=1&nVlComprimento=16&nVlAltura=2&nVlLargura=11&sCdMaoPropria=n&nVlValorDeclarado=0&sCdAvisoRecebimento=n&nVlDiametro=0&StrRetorno=xml&nIndicaCalculo=3';
		$value = $this->model->getXml($url);
		$this->assertInternalType('array',$value, 'Era esperado que o retorno do método fosse um array, entretanto o valor retornado é ' . $value);
	}
    public function testGetShippingAmountIsArray() {
    	$value = $this->model->getShippingAmount('15025035', '15010020', 0, 0, 0, 0);
    	$this->assertInternalType('array',$value, 'Era esperado que o retorno do método fosse um array, entretanto o valor retornado é ' . $value);
    }

    public function testGetResponseFromCorreios() {
    	$url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=13026291&sDsSenha=00662798&nCdServico=04669,04162&sCepOrigem=15025035&sCepDestino=15010020&nVlPeso=0,30&nCdFormato=1&nVlComprimento=16&nVlAltura=2&nVlLargura=11&sCdMaoPropria=n&nVlValorDeclarado=0&sCdAvisoRecebimento=n&nVlDiametro=0&StrRetorno=xml&nIndicaCalculo=3';
    	$value = file_get_contents($url);
    	$this->assertInternalType('string', $value, 'Era esperado que o retorno do método fosse uma string, entretanto o valor retornado é ' . $value);
    }

    
    public function testGetShippingAmountIsNull() {
    	$value = $this->model->getShippingAmount('9999999', '999999', 0, 0, 0, 0);
    	$this->assertNull($value, 'Era esperado que o retorno do método fosse NULL, entretanto o valor retornado é ' . $value);
    }

	public function testGetTrackingInfo() {
		$value = $this->model->getTrackingInfo(1000);
		$this->assertNotNull($value['tracking'], 'Era esperado que o retorno do método não fosse NULL, entretanto o valor retornado é ' . $value);
	}
	public function testGetAllowedMethods() {
		$value = $this->model->getAllowedMethods();
		$this->assertNotEmpty($value['correios'], 'Era esperado que o retorno do método não fosse vazio, entretanto o valor retornado é ' . $value);
	}
  //   public function testCollectRates() {
  //   	$rateResult = Mage::getModel('shipping/rate_result');
  //   	$method = $this->turnMethodsAccessible('collectRates');
		// $value = $method->invokeArgs($this->model, array($rateResult));
  //   	$filledRateResult = $this->model->collectRates($rateResult);
  //   	var_dump($filledRateResult);die;
  //   }
	
}
