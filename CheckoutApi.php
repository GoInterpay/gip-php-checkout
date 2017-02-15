<?php namespace GoInterpay;
// ===========================================================================
// Copyright 2016-2017 GoInterpay
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
// ===========================================================================
//
// Common functions used to access and interact with v2.18 of the GoInterpay
// Checkout API.  All input is verified to match the syntax required in the
// documentation, and all responses are checked for validity.  Each API call
// will return an instance of the 'Result' class, containing the HTTP response
// code, any error code (any message, if any), and the result of the call as
// an associative array.  Please feel free to use any of the methods in
// 'parse' to extract data from the response values.
//
// See docs.gointerpay.net for more information about the requests and the
// required data.
//
// NOTE: Any API method call will throw 'Missing' if a required parameter is
//       not present, and will throw 'InvalidValue' if the parameter is
//       syntactically invalid.  See 'exceptions.php' for more info.
//
// NOTE: All numeric and decimal values must be strings.
// 
// NOTE: All data MUST be UTF-8 encoded.
//
// NOTE: There is no support in this library to fetch a device fingerprint.
//       Those may only be determined by adding the appropriate JavaScript/ES6
//       code in the appropriate checkout page(s).
//
// ===========================================================================

include 'exceptions.php';
include 'parse.php';
include 'Result.php';

// ===========================================================================
// The CheckoutApi class that encapsulates interaction with the GoInterpay
// Checkout API.
//
class CheckoutApi {

  // Enumeration of the environments available.
  const Production = 0;
  const Sandbox = 1;

  // =========================================================================
  // Construction
  // =========================================================================
  // Construct an instance using a GoInterpay assigned merchant ID, shared
  // secret, what environment we're using, and an identifying string for the
  // name and version of the application using this library.
  //
  //  $gip = new GoInterpay\CheckoutApi
  //    ('18da9ea3-f9ac-4e64-8405-d301f079a658',
  //     'FzhEXw6eeJtI0UNnAWdGDhv4ei9cua5qC2J9lt0tVRvaln7V6LBfFnzdfPFKoVC7',
  //     GoInterpay::Production,
  //     'myApplicationName v0.1');
  //
  // NOTE: the $retryDelay parameter specifies the number of milliseconds
  //       between receiving a 503 and re-sending the request.  By default,
  //       wait 50 milliseconds.
  //
  // NOTE: the $maxRetries parameter specifies the maximum number of times to
  //       retry a request that receives a 503 response.  By default, try
  //       twice.
  //
  function __construct($merchantId, $secret, $env, $name,
                       $retryDelay = 50, $maxRetries = 2)
  {
    $this->m_merchantId = parse::is_uuid($merchantId);
    $this->m_secret = parse::is_string($secret);
    if(is_int($env) && $env == self::Production){
      $this->m_url = 'https://checkout.gointerpay.net/';
      // NOTE: in production there is a dedicated fingerprint service as well
      // as the one on checkout.gointerpay.net.
      $this->m_fingerprintUrl = 'https://fingerprint.gointerpay.net/' . self::$st_apiRevision;
    }else if(is_int($env) && $env == self::Sandbox){
      $this->m_url = 'https://checkout-sandbox.gointerpay.net/';
      $this->m_fingerprintUrl = $this->m_url . self::$st_apiRevision . '/fingerprint';
    }else{
      // NOTE: This is used for testing and development.  Feel free to specify
      // an alternate URL to test simulated responses.
      $this->m_url = parse::is_url($env);
      $this->m_fingerprintUrl
        = $this->m_url . self::$st_apiRevision . '/fingerprint';
    }
    $this->m_url .= self::$st_apiRevision . '/';
    $this->m_fingerprintUrl .= '?MerchantId=' . $merchantId;
    $this->m_name = parse::is_string($name);
    $this->m_retryDelay = $retryDelay;
    $this->m_maxRetries = $maxRetries;
    $this->m_verbose = false;
  }

  // =========================================================================
  // Interface
  // =========================================================================

  // -------------------------------------------------------------------------
  // Set the verbose flag
  //
  public function setVerbose($value = true)
  { $this->m_verbose = $value; }

  // -------------------------------------------------------------------------
  // Get the merchant ID
  //
  public function getMerchantId()
  { return $this->m_merchantId; }

  // -------------------------------------------------------------------------
  // Get the API endpoint URL.
  //
  public function getApiUrl()
  { return $this->m_url; }

  // -------------------------------------------------------------------------
  // Get the URL that can be used for device fingerprinting.  The URL returned
  // here returns JavaScript/ES6 code that must be executed in the browser,
  // and returns a device fingerprint value that must be specified in the
  // checkout() call below.  See the API documentation for more details.
  //
  public function getDeviceFingerprintUrl()
  { return $this->m_fingerprintUrl; }

  // -------------------------------------------------------------------------
  // Get the URL that can be used for a call to /localize.  The URL returned
  // here returns JavaScript/ES6 code that must be executed in the browser,
  // and returns the localization information.  See the API documentation for
  // more details.
  //
  public function getLocalizeUrl()
  { return $this->m_url . 'localize?MerchantId=' . $this->m_merchantId; }

  // -------------------------------------------------------------------------
  // Retrieve the localize information.  As this is being executed somewhere
  // other than the consumer's browser, the IP address of the consumer is
  // required.
  //
  //  $x = $gip->localize('1.2.3.4');
  //
  // Alternatively, /localize can be called and the values returned directly
  // to the browser using getLocalizeUrl() above.
  //
  public function localize($consumerIpAddress,
                           $country = null,
                           $includeRate = null)
  {
    return self::prv_get
      ('localize',
       [
         'MerchantId' => $this->m_merchantId,
         'ConsumerIpAddress' => parse::is_ip($consumerIpAddress),
         'IncludeRate' => parse::is_boolean($includeRate, parse::NullOk),
         'Country' => parse::is_country($country, parse::NullOk)
       ]);
  }

  // -------------------------------------------------------------------------
  // Retrieve information about a currency.
  //
  public function getCurrencyInfo($currency)
  {
    return self::prv_get
      ('localize',
       [
         'MerchantId' => $this->m_merchantId,
         'Currency' => parse::is_currency($currency)
       ]);
  }

  // -------------------------------------------------------------------------
  // Get the current rate offers.
  //
  public function getRates()
  {
    return self::prv_get('getRates', [ 'MerchantId' => $this->m_merchantId ]);
  }

  // -------------------------------------------------------------------------
  // Get the payment methods available.
  //
  public function getPaymentMethods($country,
                                    $currency,
                                    $viaAgent = null)
  {
    return self::prv_get
      ('getPaymentMethods',
       [
         'MerchantId' => $this->m_merchantId,
         'Country' => parse::is_country($country),
         'Currency' => parse::is_currency($currency),
         'ViaAgent' => parse::is_boolean($viaAgent, parse::NullOk)
       ]);
  }

  // -------------------------------------------------------------------------
  // Submit a create request.
  //
  // Initial elements:
  //   'DeviceFingerprint' = {string},
  //   'ReferenceId' => {string},
  //   'ConsumerTotal' => {decimal},
  //   'ConsumerCurrency' => {currency code},
  //
  // Items (required) must be specified as: 
  //   'Items' => [
  //     [
  //       'Sku' => {string},
  //       'ConsumerPrice' => '123.45',
  //       'Quantity' => '1.0',
  //       'Description' => {string}, // optional
  //       'ImageUrl' => {url} // optional
  //     ],
  //     ...
  //   ],
  //
  // Consumer must be specified as:
  //   'Consumer' => [
  //     'Name' => {string},
  //     'Company' => {string}, // optional
  //     'Email' => {email address},
  //     'Phone' => {string},
  //     'Address' => {string},
  //     'City' => {string},
  //     'Region' => {string}, // optional
  //     'PostalCode' => {string}, //optional
  //     'Country' => {country code},
  //     'NationalIdentifier' => {string} // optional
  //     'BirthDate' => {date} // optional
  //     'MerchantProfileId' => {string} // optional
  //     'IpAddress' => {ip address} //optional
  //   ],
  //
  // Consignee (required if shipping) must be specified as:
  //   'Consignee' => [
  //     'Name' => {string},
  //     'Company' => {string}, // optional
  //     'Email' => {email address}, // optional
  //     'Phone' => {string}, // optional
  //     'Address' => {string},
  //     'City' => {string},
  //     'Region' => {string}, // optional
  //     'PostalCode' => {string}, //optional
  //     'Country' => {country code}
  //   ],
  //   
  // Shipping (optional) must be specified as:
  //   'Shipping' => [
  //     'ConsumerPrice' => '10.23',
  //     'ConsumerTaxes' => '4.56',
  //     'ConsumerDuty' => '3.90'
  //   ],
  //
  // Ancillary charges and discounts must be specified as:
  //   'Charges' / 'Discounts' => [
  //     [
  //       'Name' => {string},
  //       'ConsumerPrice' => '1.00'
  //     ],
  //     ...
  //   ],
  //
  // Optional financing information must be specified as:
  //   'Financing' => [
  //     'Instalments' => '3',
  //     'ConsumerPrice' => '10.00'
  //   ],
  //
  // Additional settings:   
  //   'Country' => {country code},
  //   'RateOfferId' => {uuid},
  //   'AcceptLiability' => {true|false},
  //   'Store' => {string},
  //   'Notify' => {url},
  //   'Return' => {url},
  //   'Locale' => {true|false}
  //
  public function create($in)
  {
    $shipping = parse::get_shipping($in);
    $args = [
      'MerchantId' => $this->m_merchantId,
      'ReferenceId' => parse::optional_string($in, 'ReferenceId'),
      'DeviceFingerprint' => parse::optional_string($in, 'DeviceFingerprint'),
      'ConsumerTotal' => parse::optional_decimal($in, 'ConsumerTotal'),
      'ConsumerCurrency' => parse::get_currency($in, 'ConsumerCurrency'),
      'Country' => parse::optional_country($in, 'Country'),
      'RateOfferId' => parse::optional_uuid($in, 'RateOfferId'),
      'AcceptLiability' => parse::optional_boolean($in, 'AcceptLiability'),
      'Store' => parse::optional_string($in, 'Store'),
      'Notify' => parse::optional_url($in, 'Notify'),
      'Return' => parse::optional_url($in, 'Return'),
      'Locale' => parse::optional_string($in, 'Locale'),
      'Items' => parse::get_items($in),
      'Shipping' => $shipping,
      'Charges' => parse::get_ancillary($in, 'Charges'),
      'Discounts' => parse::get_ancillary($in, 'Discounts'),
      'Financing' => parse::get_financing($in),
      'Consumer' => parse::get_consumer($in, parse::NullOk),
      'Consignee' => parse::get_consignee($in, is_null($shipping)),
      'Meta' => parse::optional($in, 'Meta')
    ];

    return self::prv_post('create', $args);
  }

  // -------------------------------------------------------------------------
  // Submit a checkout request.
  //
  // This request accepts all of the same parameters as create() (see above),
  // and additionally:
  //   'PaymentMethod' => {string},
  //   'IssuerId' => {string},
  //   'Capture' => {true|false},
  //   'ViaAgent' => {true|false},
  //   'AcceptLiability' => {true|false},
  //   'OpenContract' => {true|false},
  //   'ContractId' => {uuid}
  //
  // $card must be specified as:
  //   [
  //     'Number' => '4111111111111111',
  //     'Name' => 'Joe Shopper',
  //     'Expiry', ['Year' => '2020', 'Month' => '5'],
  //     'VerificationCode' => '013'
  //   ]
  //
  public function checkout($in, $card = null)
  {
    $shipping = parse::get_shipping($in);
    $args = [
      'MerchantId' => $this->m_merchantId,
      'ReferenceId' => parse::optional_string($in, 'ReferenceId'),
      'DeviceFingerprint' => parse::optional_string($in, 'DeviceFingerprint'),
      'PaymentMethod' => parse::get_string($in, 'PaymentMethod'),
      'ConsumerTotal' => parse::optional_decimal($in, 'ConsumerTotal'),
      'ConsumerCurrency' => parse::get_currency($in, 'ConsumerCurrency'),
      'Country' => parse::optional_country($in, 'Country'),
      'RateOfferId' => parse::optional_uuid($in, 'RateOfferId'),
      'Capture' => parse::optional_boolean($in, 'Capture'),
      'ViaAgent' => parse::optional_boolean($in, 'ViaAgent'),
      'AcceptLiability' => parse::optional_boolean($in, 'AcceptLiability'),
      'OpenContract' => parse::optional_boolean($in, 'OpenContract'),
      'ContractId' => parse::optional_uuid($in, 'ContractId'),
      'Store' => parse::optional_string($in, 'Store'),
      'IssuerId' => parse::optional_string($in, 'IssuerId'),
      'Notify' => parse::optional_url($in, 'Notify'),
      'Return' => parse::optional_url($in, 'Return'),
      'Locale' => parse::optional_string($in, 'Locale'),
      'Items' => parse::get_items($in),
      'Shipping' => $shipping,
      'Charges' => parse::get_ancillary($in, 'Charges'),
      'Discounts' => parse::get_ancillary($in, 'Discounts'),
      'Financing' => parse::get_financing($in),
      'Consumer' => parse::get_consumer($in),
      'Consignee' => parse::get_consignee($in, is_null($shipping)),
      'Meta' => parse::optional($in, 'Meta')
    ];

    list ($url, $entity) = self::prv_makePost('checkout', $args);
    if($card !== null){
      $entity .= '&card=' . urlencode(json_encode(parse::get_card($card)));
    }
    return self::prv_doPost($url, $entity);
  }

  // -------------------------------------------------------------------------
  // Submit an openContract request.
  //
  // Initial elements:
  //   'DeviceFingerprint' = {string},
  //   'ReferenceId' => {string},
  //   'ConsumerCurrency' => {currency code},
  //   'PaymentMethod' => {string},
  //   'IssuerId' => {string}
  //
  // Consumer must be specified as:
  //   'Consumer' => [
  //     'Name' => {string},
  //     'Company' => {string}, // optional
  //     'Email' => {email address},
  //     'Phone' => {string},
  //     'Address' => {string},
  //     'City' => {string},
  //     'Region' => {string}, // optional
  //     'PostalCode' => {string}, //optional
  //     'Country' => {country code},
  //     'NationalIdentifier' => {string} // optional
  //     'BirthDate' => {date} // optional
  //     'MerchantProfileId' => {string} // optional
  //     'IpAddress' => {ip address} //optional
  //   ],
  //
  // Additional settings:   
  //   'ViaAgent' => {true|false},
  //   'AcceptLiability' => {true|false},
  //   'Notify' => {url},
  //   'Return' => {url},
  //   'Locale' => {true|false}
  //
  // $card must be specified as:
  //   [
  //     'Number' => '4111111111111111',
  //     'Name' => 'Joe Shopper',
  //     'Expiry', ['Year' => '2020', 'Month' => '5'],
  //     'VerificationCode' => '013'
  //   ]  
  //
  public function openContract($in, $card = null)
  {
    $args = [
      'MerchantId' => $this->m_merchantId,
      'ReferenceId' => parse::optional_string($in, 'ReferenceId'),
      'DeviceFingerprint' => parse::optional_string($in, 'DeviceFingerprint'),
      'PaymentMethod' => parse::get_string($in, 'PaymentMethod'),
      'ConsumerCurrency' => parse::get_currency($in, 'ConsumerCurrency'),
      'ViaAgent' => parse::optional_boolean($in, 'ViaAgent'),
      'AcceptLiability' => parse::optional_boolean($in, 'AcceptLiability'),
      'IssuerId' => parse::optional_string($in, 'IssuerId'),
      'Notify' => parse::optional_url($in, 'Notify'),
      'Return' => parse::optional_url($in, 'Return'),
      'Locale' => parse::optional_string($in, 'Locale'),
      'Consumer' => parse::get_consumer($in),
    ];

    list ($url, $entity) = self::prv_makePost('openContract', $args);
    if($card !== null){
      $entity .= '&card=' . urlencode(json_encode(parse::get_card($card)));
    }
    return self::prv_doPost($url, $entity);
  }

  // -------------------------------------------------------------------------
  // Submit a modify request.
  //
  // For these parameters, see the documentation for create() and checkout()
  // above.  You may specify any of:
  //   - Items
  //   - Consumer
  //   - Consignee
  //   - Shipping
  //   - Charges
  //   - Discounts
  //   - Financing
  //   - ConsumerTotal
  //   - ConsumerCurrency
  //   - RateOfferId
  //   - AcceptLiability
  //   - Locale
  //
  public function modify($orderId, $in)
  {
    $shipping = parse::get_shipping($in);
    $args = [
      'MerchantId' => $this->m_merchantId,
      'OrderId' => parse::is_uuid($orderId),
      'ConsumerTotal' => parse::optional_decimal($in, 'ConsumerTotal'),
      'ConsumerCurrency' => parse::optional_currency($in, 'ConsumerCurrency'),
      'RateOfferId' => parse::optional_uuid($in, 'RateOfferId'),
      'AcceptLiability' => parse::optional_boolean($in, 'AcceptLiability'),
      'Locale' => parse::optional_string($in, 'Locale'),
      'Items' => parse::get_items($in, parse::NullOk),
      'Shipping' => $shipping,
      'Charges' => parse::get_ancillary($in, 'Charges'),
      'Discounts' => parse::get_ancillary($in, 'Discounts'),
      'Financing' => parse::get_financing($in),
      'Consumer' => parse::get_consumer($in, parse::NullOk),
      'Consignee' => parse::get_consignee($in, is_null($shipping)),
      'Meta' => parse::optional($in, 'Meta')
    ];

    return self::prv_post('modify', $args);
  }

  // -------------------------------------------------------------------------
  // Attempt to authorize payment for an order.
  //
  // $in may specify any of the following:
  //   [
  //     'ConsumerIpAddress' => {ip address} // required
  //     'Capture' => {boolean},
  //     'PaymentMethod' = {string},
  //     'IssuerId' = {string},
  //     'DeviceFingerprint' = {string},
  //     'ReturnUrl' = {url},
  //     'ViaAgent' = {boolean},
  //     'OpenContract' = {boolean},
  //     'ContractId' = {uuid}
  //   ]
  //
  // $card must be specified as:
  //   [
  //     'Number' => '4111111111111111',
  //     'Name' => 'Joe Shopper',
  //     'Expiry', ['Year' => '2020', 'Month' => '5'],
  //     'VerificationCode' => '013'
  //   ]
  //
  public function authorize($orderId, $in, $card = null)
  {
    // Unlike a call to /authorize from the browser, using the information
    // from getAuthorizeRequest() below, we require the consumer IP address.
    parse::require_ip($in, 'ConsumerIpAddress');

    // Use our own helper!
    list ($url, $entity) = getAuthorizeRequest($orderId, $in);
    if($card !== null){
      $entity .= '&card=' . urlencode(json_encode(parse::get_card($card)));
    }
    return self::prv_doPost($url, $entity);
  }

  // -------------------------------------------------------------------------
  // Get the URL, request, and signature that would be required to initiate an
  // /authorize call from the browser.  This returns the URL to which the
  // request should be posted, and a signed request to autorize the specified
  // order.  Card details can then be added if necessary.
  //
  // $in may specify any of the following:
  //   [
  //     'ConsumerIpAddress' => {ip address}
  //     'Capture' => {boolean},
  //     'PaymentMethod' = {string},
  //     'IssuerId' = {string},
  //     'DeviceFingerprint' = {string},
  //     'ReturnUrl' = {url},
  //     'ViaAgent' = {boolean},
  //     'OpenContract' = {boolean},
  //     'ContractId' = {uuid}
  //   ]
  //
  public function getAuthorizeRequest($orderId, $in)
  {
    $args = [
      'MerchantId' => $this->m_merchantId,
      'OrderId' => parse::is_uuid($orderId),
      'ConsumerIpAddress' => parse::optional_ip($in, 'ConsumerIpAddress'),
      'PaymentMethod' => parse::optional_string($in, 'PaymentMethod'),
      'IssuerId' => parse::optional_string($in, 'IssuerId'),
      'Capture' => parse::optional_boolean($in, 'Capture'),
      'DeviceFingerprint' => parse::optional_string($in, 'DeviceFingerprint'),
      'Return' => parse::optional_url($in, 'ReturnUrl'),
      'ViaAgent' => parse::optional_boolean($in, 'ViaAgent'),
      'OpenContract' => parse::optional_boolean($in, 'OpenContract'),
      'ContractId' => parse::optional_uuid($in, 'ContractId')
    ];
    return self::prv_makePost('authorize', $args);
  }

  // -------------------------------------------------------------------------
  // Capture a previously authorized order.
  //
  public function capture($orderId)
  {
    return self::prv_post('capture',
                          [
                            'MerchantId' => $this->m_merchantId,
                            'OrderId' => parse::is_uuid($orderId)
                          ]);
  }

  // -------------------------------------------------------------------------
  // Cancel an authorization.
  //
  public function cancel($orderId)
  {
    return self::prv_post('cancel',
                          [
                            'MerchantId' => $this->m_merchantId,
                            'OrderId' => parse::is_uuid($orderId)
                          ]);
  }

  // -------------------------------------------------------------------------
  // Submit a refund.  The amount is specified in the consumer's currency.
  //
  public function refund($orderId, $amount, $reference)
  {
    return self::prv_post('refund',
                          [
                            'MerchantId' => $this->m_merchantId,
                            'OrderId' => parse::is_uuid($orderId),
                            'Amount' => parse::is_decimal($amount),
                            'ReferenceId' => parse::is_string($reference)
                          ]);
  }

  // -------------------------------------------------------------------------
  // Query the state of an order.
  //
  public function query($orderId)
  {
    return self::prv_post('query',
                          [
                            'MerchantId' => $this->m_merchantId,
                            'OrderId' => parse::is_uuid($orderId)
                          ]);
  }

  // -------------------------------------------------------------------------
  // Query for any orders associated with the specified referenceId.
  //
  public function queryByReference($referenceId)
  {
    return self::prv_post('query',
                          [
                            'MerchantId' => $this->m_merchantId,
                            'ReferenceId' => parse::is_string($referenceId)
                          ]);
  }

  // -------------------------------------------------------------------------
  // Call this function when a notification is received.  The callback
  // function must accept the following parameters:
  //
  //  message - an error message if there was a problem handling the response
  //  values  - an array of the information specified in the notification
  //
  // If 'values' is not null, the return value of the callback will be
  // returned as the HTTP status code from this.
  //
  // Returns the HTTP status code that should be sent back.  No entity is ever
  // required to be sent in response to a notification.  If an invalid status
  // code is returned from the callback function, a 'NOTICE' will be raised,
  // and 500 (Internal Server Error) will be returned.
  //
  public function notification($entity, $callback)
  {
    // The entity should be in the form of 'request=<..>&signature=<..>'
    parse_str($entity, $values);
    try {
      $request = parse::get_string($values, 'request');
      $signature = parse::get_string($values, 'signature');
    }catch(Exception $ex){
      call_user_func($callback, 'Malformed notification: ' . $ex, null);
      return 400;
    }

    if($this->prv_sign($request) !== $signature){
      call_user_func($callback, 'Invalid Signature received', null);
      return 400;
    }else{
      $obj = json_decode($request, true);
      if(json_last_error() !== JSON_ERROR_NONE){
        call_user_func($callback,
                       'Invalid JSON received: ' . json_last_error_msg(), null);
        return 400;
      }
      try {
        parse::get_uuid($obj, 'OrderId');
        parse::optional_string($obj, 'ReferenceId');
        parse::get_boolean($obj, 'UnderReview');
        parse::get_string($obj, 'OrderState');
      }catch(Exception $ex){
        call_user_func($callback,
                       'Invalid notification received: ' . $ex, null);
        return 400;
      }
      $code = call_user_func($callback, null, $obj);
      // Make sure the user returned a valid HTTP status.
      if(is_int($code) === false ||
         // .. nothing lower than 200
         $code < 200 ||
         // .. no redirects
         intval($code / 100) === 3 ||
         // .. nothing higher than 599
         $code > 599){
        // NOTE: if we get here, the callback function is broken.
        trigger_error('Invalid HTTP status for notification from callback [' .
                      $callback . '] = ' .
                      (is_null($code) ? '<null>' : print_r($code, true)),
                      E_USER_NOTICE);
        return 500;
      }
      return $code;
    }
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  // -------------------------------------------------------------------------
  // Perform a GET request.  Expected to take UTF-8 data.
  //
  private function prv_get($endpoint, $args)
  {
    $url = $this->m_url . $endpoint . '?' . http_build_query($args);
    $count = 0;
    do {
      // We only want to delay if this isn't the first request.
      if($count > 0) usleep($this->m_retryDelay * 1000);
      list ($result, $code) = $this::http_request($url);

    }while($code === 503 && ++$count < $this->m_maxRetries);

    if($result === false || $code !== 200){
      return new Result($code, null, $result, null, $result);
    }
    $obj = json_decode($result, true);
    if(json_last_error() !== JSON_ERROR_NONE){
      return new Result($code, null, 'Invalid JSON: ' . json_last_error_msg(),
                        null, $result);
    }
    return new Result($code, null, null, $obj, $result);
  }

  // -------------------------------------------------------------------------
  // Perform a POST request.  Expected to take UTF-8 data.
  //
  private function prv_post($endpoint, $args)
  {
    list ($url, $entity) = self::prv_makePost($endpoint, $args);
    return self::prv_doPost($url, $entity);
  }

  // -------------------------------------------------------------------------
  // Helper to make the bits required for a POST request.
  //
  private function prv_makePost($endpoint, $args)
  {
    $url = $this->m_url . $endpoint;
    $data = json_encode(parse::filter($args));
    $entity =
      'request=' . urlencode($data) .
      '&signature=' . urlencode(self::prv_sign($data));
    return [$url, $entity];
  }

  // -------------------------------------------------------------------------
  // Helper to do a post
  private function prv_doPost($url, $entity)
  {
    $ct = 'application/x-www-form-urlencoded';
    $count = 0;
    do {
      // We only want to delay if this isn't the first request.
      if($count > 0) usleep($this->m_retryDelay * 1000);
      list ($result, $code) = $this::http_request($url, $entity, $ct);

    }while($code === 503 && ++$count < $this->m_maxRetries);

    if($result === false || $code !== 200){
      return new Result($code, null, $result, null, $result);
    }

    // The response should be in the form of 'response=<..>&signature=<..>'
    parse_str($result, $values);
    try {
      $response = parse::get_string($values, 'response');
      $signature = parse::get_string($values, 'signature');
    }catch(Exception $ex){
      return new Result($code, null, 'Malformed response entity: ' . $ex,
                        null, $result);
    }

    if($this->prv_sign($response) !== $signature){
      return new Result($code, null, 'Invalid Signature received',
                        null, $result);
    }

    $obj = json_decode($response, true);
    if(json_last_error() !== JSON_ERROR_NONE){
      return new Result($code, null, 'Invalid JSON: ' . json_last_error_msg(),
                        null, $result);
    }

    try {
      // Extract out any error.
      $error = null;
      $message = null;
      if(isset($obj['Error'])){
        $err = $obj['Error'];
        if(is_array($err) === false){
          return new Result($code, null, 'Invalid Error received',
                            null, $result);
        }
        $error = parse::get_string($err, 'Code');
        $message = parse::optional_string($err, 'Message');
        unset($obj['Error']);
      }
    }catch(Exception $ex){
      return new Result($code, null, 'Invalid Error received: ' . $ex,
                        null, $result);
    }

    // return it as a JSON blob; will return null if invalid, which would be
    // odd if it has a valid signature too.
    return new Result($code, $error, $message, $obj, $result);
  }

  // -------------------------------------------------------------------------
  // Use the shared secret to sign some payload.  Expected to take UTF-8 data.
  //
  public function prv_sign($data)
  {
    // We need the raw output of the hash, and then base64 encode that.
    return base64_encode(hash_hmac('sha256', $data, $this->m_secret, true));
  }

  // -------------------------------------------------------------------------
  // Do an HTTP request.
  //
  private function http_request($url, $data = null, $ct = null)
  {
    $curl = curl_init($url);
    // .. make sure we verify the TLS cert
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // .. we want the entire response in one read
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // .. and follow any redirects
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    // .. we want to add the library version to the as the User-Agent
    curl_setopt($curl, CURLOPT_USERAGENT,
                'GoInterpay::sdk::php::CheckoutApi $Revision: 26236 $ - '
                . $this->m_name);

    if($data !== null){
      // .. we're POSTing
      curl_setopt($curl, CURLOPT_POST, true);
      // .. with this data
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

      if($ct !== null){
        // .. and this Content-Type
        curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: ' . $ct]);
      }
    }
    curl_setopt($curl, CURLOPT_VERBOSE, $this->m_verbose);
    $result = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    return [$result, $code];
  }

  // =========================================================================
  // Data
  // =========================================================================

  // The version of the Checkout API we're built against.
  private static $st_apiRevision = 'v2.18';

  // The GoInterpay assigned merchant ID.
  private $m_merchantId;

  // The shared secret for the merchant.
  private $m_secret;

  // The Checkout API endpoint we're using.
  private $m_url;

  // The name of the application using this library.
  private $m_name;

  // The amount of time, in milliseconds, we want to wait to retry a request
  // if we receive 503.
  private $m_retryDelay;

  // The maximum number of times to retry a request.
  private $m_maxRetries;

  // Verbose cURL?
  private $m_verbose;
}

// ==========================================================================
?>
