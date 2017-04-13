<?php
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
// Example usage of the GoInterpay CheckoutApi php SDK.
//
// Try running this as:
//
//   $ php demo.php
//
// ===========================================================================
include 'CheckoutApi.php';

// ---------------------------------------------------------------------------
// Here, we create a new instance of the GoInterpay CheckoutApi using the demo
// account credentials.
$gip = new GoInterpay\CheckoutApi
    ('2a144a21-066a-42fe-a553-736a777e39e2',
     'dkgBq45hWlQLtFnrTJukpA3iyUP7YGRlrNFPOVWWdFb2vEesctRiSn9wxtit7yxx',
     GoInterpay\CheckoutApi::Sandbox,
     'example/demo.php');

// ---------------------------------------------------------------------------
// Get the locale information for the specified IP address.
//
$localize = $gip->localize('1.2.3.4');
print_r($localize);

// ---------------------------------------------------------------------------
// Get the badge information for the specified currency
//
$badge = $gip->badge('CAD');
print_r($badge);

// ---------------------------------------------------------------------------
// Get the rate offers available for the merchant.
//
$rates = $gip->getRates();
print_r($rates);

// ---------------------------------------------------------------------------
// Get the payment methods available to the specified country and currency.
//
$paymentMethods = $gip->getPaymentMethods('CA', 'CAD');
print_r($paymentMethods);

// ---------------------------------------------------------------------------
// The credentials published in this demo file are shared.  Make a random
// reference that we can use here that is unlikely to show demo transactions
// from others.
$myReference = 'gip-php-checkout:demo.php:' . md5(rand());

// ---------------------------------------------------------------------------
// NOTE: the device fingerprint value will have to be determined before this
//       call.  See the API documentation for details.  For the purposes of
//       demonstration, we use one that is known to exist.
//
$deviceFingerprint = '1b3957e8-1c8f-4af5-8517-94bc8cda8595';
$checkout = $gip->checkout(
  [
    'DeviceFingerprint' => $deviceFingerprint,
    'Reference' => $myReference,
    'PaymentMethod' => 'VISA',
    'ConsumerTotal' => '100.00',
    'ConsumerCurrency' => 'CAD',
    'Items' => [
      [
        'Sku' => 'thing_1',
        'ConsumerPrice' => '50',
        'Quantity' => '1'
      ],
      [
        'Sku' => 'thing_2',
        'ConsumerPrice' => '2',
        'Quantity' => '25'
      ]
    ],
    'Consumer' => [
      'Name' => 'Joe Shopper',
      'Email' => 'joe.shopper@example.com',
      'Phone' => '+12345678901',
      'Address' => '123 Any Street',
      'City' => 'Somewhere',
      'Region' => 'AB',
      'PostalCode' => 'T2T2T2',
      'Country' => 'CA',
      'IpAddress' => '1.2.3.4'
    ]
  ],
  [
    'Number' => '4111111111111111',
    'Name' => 'Joe Shopper',
    'VerificationCode' => '737',
    'Expiry' => [ 'Year' => '2018', 'Month' => '12']
  ]
);
print_r($checkout);
$orderId = GoInterpay\parse::get_uuid($checkout->result, 'OrderId');

// ---------------------------------------------------------------------------
// NOTE: we don't show an example here for modify() or authorize().
// ---------------------------------------------------------------------------
// Next, attempt capture the authorized order.
//
// NOTE: typically this would be done after some time since if the two
// operations were to be done this close it would make more sense to include
// 'Capture' => true in the 'checkout()' call above.
//
$capture = $gip->capture($orderId);
print_r($capture);

// ---------------------------------------------------------------------------
// Try to issue a refund
//
$refund = $gip->refund($orderId, '50', 'refund1');
print_r($refund);

// ---------------------------------------------------------------------------
// Get the current state of that order.
//
$query = $gip->query($orderId);
print_r($query);

// ---------------------------------------------------------------------------
// Try looking up what orders have been placed using our reference.
//
$byReference = $gip->queryByReference($myReference);
print_r($byReference);

// ---------------------------------------------------------------------------
// Now try opening a contract
//
$openContract = $gip->openContract(
  [
    'DeviceFingerprint' => $deviceFingerprint,
    'Reference' => $myReference + ':openContract',
    'PaymentMethod' => 'VISA',
    'ConsumerCurrency' => 'CAD',
    'Consumer' => [
      'Name' => 'Joe Shopper',
      'Email' => 'joe.shopper@example.com',
      'Phone' => '+12345678901',
      'Address' => '123 Any Street',
      'City' => 'Somewhere',
      'Region' => 'AB',
      'PostalCode' => 'T2T2T2',
      'Country' => 'CA',
      'IpAddress' => '1.2.3.4'
    ]
  ],
  [
    'Number' => '4111111111111111',
    'Name' => 'Joe Shopper',
    'VerificationCode' => '737',
    'Expiry' => [ 'Year' => '2018', 'Month' => '12']
  ]
);
print_r($openContract);
$contractId = GoInterpay\parse::get_uuid($openContract->result, 'ContractId');


// ---------------------------------------------------------------------------
// Now make a checkout request against that contract.
//
$checkout = $gip->checkout(
  [
    'DeviceFingerprint' => $deviceFingerprint,
    'Reference' => $myReference + ':contract',
    'ContractId' => $contractId,
    'ConsumerTotal' => '50.00',
    'PaymentMethod' => 'VISA',
    'ConsumerCurrency' => 'CAD',
    'Items' => [
      [
        'Sku' => 'thing_1',
        'ConsumerPrice' => '50',
        'Quantity' => '1'
      ]
    ],
    'Consumer' => [
      'Name' => 'Joe Shopper',
      'Email' => 'joe.shopper@example.com',
      'Phone' => '+12345678901',
      'Address' => '123 Any Street',
      'City' => 'Somewhere',
      'Region' => 'AB',
      'PostalCode' => 'T2T2T2',
      'Country' => 'CA',
      'IpAddress' => '1.2.3.4'
    ]
  ]
);
print_r($checkout);

// ---------------------------------------------------------------------------
// Finally, here is a sample of how notifications can be handled.  When an
// HTTP notification request is received, the 'notification()' method must be
// called to parse the information.  When finished, the 'callback' function
// (specified and implemented by the caller) will be called.  If $message is
// not null, an error occurred and the text of $message gives some information
// about that error.  If $message is null, the notification was well-formed
// and should be handled by the callback function, and the return value from
// this function will be used as the HTTP status returned by notification().
//
function myNotificationCallback($message, $values)
{
  if($message){
    print 'Error parsing notification: ' . $message . PHP_EOL;
    // NOTE: return status ignored.
  }else{
    print 'Received notification: ';
    print_r($values);
    print PHP_EOL;
  }
  // Return 200 to indicate that the notification was handled.  Return a
  // different status to indicate that the notification should be re-sent.
  return 200;
}

// When the HTTP notification is received, we call the 'notification()' method
// as follows.
$notificationEntity = 'request=%7b%22OrderId%22%3a%2210bf26ac-f6b0-42e3-a0a2-0bd3383ac2f7%22%2c%22UnderReview%22%3afalse%2c%22OrderState%22%3a%22PaymentAuthorized%22%2c%22ReferenceId%22%3a%22gip-php-checkout%3ademo.php%3a357443e228d2d52e03cb3a9c56d6bc8a%22%2c%22Refunds%22%3a%5b%5d%7d&signature=7o%2b%2bTvRvft0u72cTu8G77yRKgCqM%2bdsmbsKiTXfoJeE%3d';
$httpStatus = $gip->notification($notificationEntity, 'myNotificationCallback');
// At this point, $httpStatus can be used to respond to the originating HTTP
// notification request.  No response entity is ever required for
// notifications.

// ===========================================================================
?>
