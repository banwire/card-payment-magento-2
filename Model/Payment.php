<?php

namespace Banwire\Card\Model;

class Payment extends \Magento\Payment\Model\Method\Cc {
  const CODE = 'banwirecard';
  protected $_code = self::CODE;
  protected $_canAuthorize = true;
  protected $_canCapture = true;
  protected $_isGateway = true;
  protected $_countryFactory;
  protected $cart = null;

  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Payment\Model\Method\Logger $logger,
    \Magento\Framework\Module\ModuleListInterface $moduleList,
    \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Checkout\Model\Cart $cart,
    array $data = []
  ) {
    parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data);
    $this->cart = $cart;

    $this->_countryFactory = $countryFactory;
  }

  public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    try {
      //check if payment has not been authorized then authorize it      if(is_null($payment->getParentTransactionId()))
      {
        $this->authorize($payment, $amount);
      }
      //build array of all necessary details to pass to your Payment Gateway..
      $request = ['CardCVV2' => $payment->getCcCid(), ‘CardNumber’ => $payment->getCcNumber(), ‘CardExpiryDate’ => $this->getCardExpiryDate($payment), ‘Amount’ => $amount, ‘Currency’ => $this->cart->getQuote()->getBaseCurrencyCode()];
      //make API request to credit card processor.
      $response = $this->captureRequest($request);
      //Handle Response accordingly. //transaction is completed.
      $payment->setTransactionId($response['tid'])->setIsTransactionClosed(0);
    } catch (\Exception $e) {
      $this->debug($payment->getData(), $e->getMessage());
    }
    return $this;
  }

  public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    try {
      $dataOrder = $this->cart->getQuote()->getData();
      $dataAddress = $this->cart->getQuote()->getBillingAddress()->getData();
      $configBanwire = $this->_scopeConfig->getValue("payment/banwirecard");
      $dataPayment = $payment->getData();

      //build array of all necessary details to pass to your Payment Gateway..
      $allData = [
        "amount" => $amount,
        "dataAddress" => $dataAddress,
        "dataOrder" => $dataOrder,
        "configBanwire" => $configBanwire,
        "dataPayment" => $dataPayment
      ];

      //check if payment has been authorized
      $response = $this->authRequest($allData);
    } catch (\Exception $e) {
      $this->debug($payment->getData(), $e->getMessage());
    }
    if (isset($response['tid'])) { // Successful auth request.
      // Set the transaction id on the payment so the capture request knows auth has happened.
      $payment->setTransactionId($response['tid']);
      $payment->setParentTransactionId($response['tid']);
    }
    //processing is not done yet.
    $payment->setIsTransactionClosed(0);
    return $this;
  }

  /*This function is defined to set the Payment Action Type that is - - Authorize -
  Authorize and Capture Whatever has been set under Configuration of this Payment Method in Admin Panel,
  that will be fetched and set for this Payment Method by passing that into getConfigPaymentAction() function. */
  public function getConfigPaymentAction() {
    return $this->getConfigData('payment_action');
  }

  public function authRequest($allData) {
    $url = $allData["configBanwire"]["debug"]
      ? "https://test.banwire.com/api.pago_pro/"
      : "https://banwire.com/api.pago_pro/";

    $fullName = $allData["dataOrder"]["customer_firstname"] . " "
      . $allData["dataOrder"]["customer_middlename"] . " "
      . $allData["dataOrder"]["customer_lastname"];

    switch ($allData["dataPayment"]["cc_type"]) {
      case "MC":
        $ccType = "mastercard";
        break;
      case "VI":
        $ccType = "visa";
        break;
      case "AE":
        $ccType = "amex";
        break;
    }

    $ccMonth = ($allData["dataPayment"]["cc_exp_month"] < 10)
      ? "0" . $allData["dataPayment"]["cc_exp_month"]
      : $allData["dataPayment"]["cc_exp_month"];
    $ccYear = ($allData["dataPayment"]["cc_exp_year"] > 1000)
      ? $allData["dataPayment"]["cc_exp_year"] - 2000
      : $allData["dataPayment"]["cc_exp_year"];

    $address = $allData["dataAddress"]["street"] . " "
      . $allData["dataAddress"]["city"] . " "
      . $allData["dataAddress"]["country_id"];

    $arrayRequest = [
      "user" => $allData["configBanwire"]["banwire_user"],
      "reference" => $allData["dataOrder"]["entity_id"],
      "ammount" => $allData["amount"],
      "concept" => "Magento " . $allData["dataOrder"]["entity_id"],
      "card_num" => $allData["dataPayment"]["cc_number"],
      "card_name" => $fullName,
      "card_type" => $ccType,
      "card_exp" => "$ccMonth/$ccYear",
      "card_ccv2" => $allData["dataPayment"]["cc_cid"],
      "address" => $address,
      "post_code" => $allData["dataAddress"]["postcode"],
      "mail" => $allData["dataAddress"]["email"],
      "response_format" => "JSON"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $result = curl_exec($ch);

    $response = json_decode($result, true);

    //Process Request and receive the response from Payment Gateway---

    $response["tid"] = isset($response["code_auth"]) ? $response["code_auth"] : null;

    //Here, check response and process accordingly---
    if (!isset($response["code_auth"]) || $response["response"] != "ok") {
      throw new \Magento\Framework\Exception\LocalizedException(__($response["message"]));
    }
    return $response;
  }

  /**
   * Test method to handle an API call for capture request.
   *
   * @param $request
   * @return array
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  public function captureRequest($request) {
    //Process Request and receive the response from Payment Gateway---
    $response = ['tid' => rand(100000, 99999999)];
    //Here, check response and process accordingly---
    if (!$response) {
      throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
    }
    return $response;
  }
}
