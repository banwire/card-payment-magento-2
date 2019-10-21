<?php
/**
 * Banwire
 * used to manage Banwire API calls
 *
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . "curl.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . "ValidationException.php";

use \ValidationException as ValidationException;
use \Exception as Exception;

Class Banwire {
  private $api_endpoint;
  private $auth_endpoint;
  private $auth_headers;
  private $access_token;
  private $client_id;
  private $client_secret;

  function __construct($client_id, $client_secret, $test_mode) {
    $this->curl = new Curl();
    $this->curl->setCacert(__DIR__ . "/cacert.pem");
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;

    if ($test_mode)
      $this->api_endpoint = "https://test.banwire.com/v2/";
    else
      $this->api_endpoint = "https://www.banwire.com/v2/";
    if ($test_mode)
      $this->auth_endpoint = "https://test.banwire.com/oauth2/token/";
    else
      $this->auth_endpoint = "https://www.banwire.com/oauth2/token/";

    $this->getAccessToken();
  }

  public function getAccessToken() {
    $data = array();
    $data['client_id'] = $this->client_id;
    $data['client_secret'] = $this->client_secret;
    $data['scopes'] = "all";
    $data['grant_type'] = "client_credentials";

    $result = $this->curl->post($this->auth_endpoint, $data);
    if ($result) {
      $result = json_decode($result);
      if (isset($result->error)) {
        throw new ValidationException("The Authorization request failed with message '$result->error'", array("Payment Gateway Authorization Failed."), $result);
      } else
        $this->access_token = $result->access_token;
    }

    $this->auth_headers[] = "Authorization:Bearer $this->access_token";

  }

  public function createOrderPayment($data) {
    $endpoint = $this->api_endpoint . "gateway/orders/";
    $result = $this->curl->post($endpoint, $data, array("headers" => $this->auth_headers));
    $result = json_decode($result);
    if (isset($result->order)) {
      return $result;
    } else {
      $errors = array();
      if (isset($result->message))
        throw new ValidationException("Validation Error with message: $result->message", array($result->message), $result);

      foreach ($result as $k => $v) {
        if (is_array($v))
          $errors[] = $v[0];
      }
      if ($errors)
        throw new ValidationException("Validation Error Occurred with following Errors : ", $errors, $result);
    }
  }


  public function getOrderById($id) {
    $endpoint = $this->api_endpoint . "gateway/orders/id:$id/";
    $result = $this->curl->get($endpoint, array("headers" => $this->auth_headers));

    $result = json_decode($result);
    if (isset($result->id) and $result->id)
      return $result;
    else
      throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds " . print_R($result, true));
  }

  public function getPaymentStatus($payment_id, $payments) {
    foreach ($payments as $payment) {
      if ($payment->id == $payment_id) {
        return $payment->status;
      }
    }
  }

}
