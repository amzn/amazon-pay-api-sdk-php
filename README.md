# Amazon Pay SDK-V2 (PHP)
Amazon Pay API V2 Integration

Please note the Amazon Pay V2 SDK can only be used for V2-specific API calls (e.g., Alexa Delivery Trackers, In-Store API, API V2, etc.)

If you are developing an integration using the original [Amazon Pay API Reference Guide](https://developer.amazon.com/docs/amazon-pay-api/intro.html), then you will need to use the original [Amazon Pay SDK (PHP)](https://github.com/amzn/amazon-pay-sdk-php).

## Requirements

* PHP 5.5 or higher, but highly recommended to use only the latest PHP version, and update often, to ensure current security fixes are applied
* Curl 7.18 or higher
* phpseclib 2.0

## SDK Installation

Use composer to install the latest release of the V2 SDK and its dependencies:

```
    composer require amzn/amazon-pay-sdk-v2-php
```

Verify the instllation with the following test script:

```php
    <?php
        include 'vendor/autoload.php';
        echo "SDK_VERSION=" . AmazonPayV2\Client::SDK_VERSION . "\n";
    ?>
```

## Public and Private Keys

MWS access keys, MWS secret keys, and MWS authorization tokens from previous MWS or Amazon Pay V1 integrations cannot be used with this SDK.

You will need to generate your own public/private key pair to make API calls with this SDK.  This can be done using openssl commands:

```
    openssl genrsa -out private.txt 2048
    openssl rsa -in private.txt -pubout > public.txt
```

The first command above generates a private key and the second line uses the private key to generate a public key.

To associate the key with your account, send an email to amazon-pay-delivery-notifications@amazon.com that includes (1) your *public* key and (2) your Merchant ID.  Do not send your private key to Amazon (or anyone else) under any circumstance!

In your Seller Central account, within 1-2 business days, the account administrator will receive a message that includes the public_key_id you will need to use the SDK.

## Namespace

Namespace for this package is AmazonPayV2 so that there are no conflicts with the original Amazon Pay MWS SDK's that use the AmazonPay namespace.

## Configuration Array

```php
    $amazonpay_config = array(
        'public_key_id' => 'ABC123DEF456XYZ',  // RSA Public Key ID (this is not the Merchant or Seller ID)
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'sandbox'       => true,               // true (Sandbox) or false (Production) boolean
        'region'        => 'us'                // Must be one of: 'us', 'eu', 'jp' 
    );
```

# Convenience Functions (Overview)

Make use of the built-in convenience functions to easily make API calls.  Scroll down further to see example code snippets.

When using the convenience functions, the request payload will be signed using the provided private key, and a HTTPS request is made to the correct regional endpoint.
In the event of request throttling, the HTTPS call will be attempted up to three times using an exponential backoff approach.

## Alexa Delivery Trackers API
Please note that your merchant account must be whitelisted to use the [Delivery Trackers API](https://developer.amazon.com/docs/amazon-pay-onetime/delivery-order-notifications.html).

* **deliveryTrackers**($payload, $headers = null) &#8594; POST to "v1/deliveryTrackers"

## Authorization Tokens API
Please note that your solution provider account must have a pre-existing relationship (valid and active API V1-style MWS authorization token) with the merchant account in order to use this function.

* **getAuthorizationToken**($mwsAuthToken, $merchantId, $headers = null) &#8594; GET to "v1/authorizationTokens/$mwsAuthToken?merchantId=$merchantId"

## API V2
[API V2 Integration Guide](https://amazonpaycheckoutintegrationguide.s3.amazonaws.com/amazon-pay-api-v2/introduction.html)

The $headers field is not optional for create/POST calls below because it requires, at a minimum, the x-amz-pay-idempotency-key header:

```php
    $headers = array('x-amz-pay-idempotency-key' => uniqid());
```

### API V2 CheckoutSession object
* **createCheckoutSession**($payload, $headers) &#8594; POST to "v1/checkoutSessions"
* **getCheckoutSession**($checkoutSessionId, $headers = null) &#8594; GET to "v1/checkoutSessions/$checkoutSessionId"
* **updateCheckoutSession**($checkoutSessionId, $payload, $headers = null) &#8594; PATCH to "v1/checkoutSessions/$checkoutSessionId"

### API V2 ChargePermission object
* **getChargePermission**($chargePermissionId, $headers = null) &#8594; GET to "v1/chargePermissions/$chargePermissionId"
* **updateChargePermission**($chargePermissionId, $payload, $headers = null) &#8594; PATCH to "v1/chargePermissions/$chargePermissionId"
* **closeChargePermission**($chargePermissionId, $payload, $headers = null) &#8594; DELETE to "v1/chargePermissions/$chargePermissionId/close"

### Charge object
* **createCharge**($payload, $headers) &#8594; POST to "v1/charges"
* **getCharge**($chargeId, $headers = null) &#8594; GET to "v1/charges/$chargeId"
* **captureCharge**($chargeId, $payload, $headers) &#8594; POST to "v1/charges/$chargeId/capture"
* **cancelCharge**($chargeId, $payload, $headers = null) &#8594; DELETE to "v1/charges/$chargeId/cancel"

### API V2 Refund object
* **createRefund**($payload, $headers) &#8594; POST to "v1/refunds"
* **getRefund**($refundId, $headers = null) &#8594; GET to "v1/refunds/$refundId"

## In-Store API
Please contact your Amazon Pay Account Manager before using the In-Store API calls in a Production environment to obtain a copy of the In-Store Integration Guide.

* **instoreMerchantScan**($payload, $headers = null) &#8594; POST to "in-store/v1/merchantScan"
* **instoreCharge**($payload, $headers = null) &#8594; POST to "in-store/v1/charge"
* **instoreRefund**($payload, $headers = null) &#8594; POST to "in-store/v1/refund"


# Using Convenience Functions

Four quick steps are needed to make an API call:

Step 1. Construct a Client (using the previously defined Config Array).

```php
    $client = new AmazonPayV2\Client($amazonpay_config);
```

Step 2. Generate the payload.

```php
    $payload = '{"scanData":"UKhrmatMeKdlfY6b","scanReferenceId":"0b8fb271-2ae2-49a5-b35d7","merchantCOE":"US","ledgerCurrency":"USD","chargeTotal":{"currencyCode":"USD","amount":"2.00"},"metadata":{"merchantNote":"Merchant Name","communicationContext":{"merchantStoreName":"Store Name","merchantOrderId":"789123"}}}';
```

Step 3. Execute the call.

```php
     $result = $client->instoreMerchantScan($payload);
```

Step 4. Check the result.

The $result will be an array with the following keys:

* '**status**' - integer HTTP status code (200, 201, etc.)
* '**response**' - the JSON response body
* '**request_id**' - the Request ID from Amazon API gateway
* '**url**' - the URL for the REST call the SDK calls, for troubleshooting purposes
* '**method** - POST, GET, PATCH, or DELETE
* '**headers**' - an array containing the various headers generated by the SDK, for troubleshooting purposes
* '**request**' - the JSON request payload
* '**retries**' - usually 0, but reflects the number of times a request was retried due to throttling or other server-side issue
* '**duration**' - duration in milliseconds of SDK function call

The first two items (status, response) are critical.  The remaining items are useful in troubleshooting situations.

To parse the response in PHP, you can use the PHP json_decode() function:

```php
    $response = json_decode($result['response'], true);
    $id = $response['chargePermissionId'];
```

If you are a Solution Provider and need to make an API call on behalf of a different merchant account, you will need to pass along an extra authentication token parameter into the API call.

```php
    $headers = array('x-amz-pay-authtoken' => 'other_merchant_super_secret_token');
    $result = $client->instoreMerchantScan($payload, $headers);
```

An alternate way to do Step 2 would be to use PHP arrays and programmatically generate the JSON payload:

```php
    $payload = array(
        'scanData' => 'UKhrmatMeKdlfY6b',
        'scanReferenceId' => uniqid(),
        'merchantCOE' => 'US',
        'ledgerCurrency' => 'USD',
        'chargeTotal' => array(
            'currencyCode' => 'USD',
            'amount' => '2.00'
        ),
        'metadata' => array(
            'merchantNote' => 'Merchant Name',
            'communicationContext' => array(
                'merchantStoreName' => 'Store Name',
                'merchantOrderId' => '789123'
            )
        )
    );
    $payload = json_encode($payload);
```
# Convenience Functions Code Samples

## Alexa Delivery Notifications

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => false
    );
    $payload = array(
        'amazonOrderReferenceId' => 'P01-0000000-0000000',
        'externalOrderId' => 'merchantGivenId',
        'deliveryDetails' => array(array(
            'trackingNumber' => '01234567890',
            'carrierCode' => 'FEDEX'
        ))
    );
    try {
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->deliveryTrackers($payload);
        if ($result['status'] === 200) {
            // success
            echo $result['response'] . "\n";
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
    }
    ?>
```

## API V2 - Create Checkout Session (AJAX service example)

```php
    <?php
    session_start();

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );
    $payload = array(
        'webCheckoutDetails' => array(
            'checkoutReviewReturnUrl' => 'https://localhost/store/checkout_review',
            'checkoutResultReturnUrl' => 'https://localhost/store/checkout_result'
        ),
        'storeId' => 'amzn1.application-oa2-client.000000000000000000000000000000000'
    );
    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());
    try {
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->createCheckoutSession($payload, $headers);

        header("Content-type:application/json; charset=utf-8");
        echo $result['response'];
        if ($result['status'] !== 201) {
            http_response_code(500);
        }

    } catch (\Exception $e) {
        echo $se;
        http_response_code(500);
    }
    ?>
```


## API V2 - Create Checkout Session (standalone script example)

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );
    $payload = array(
        'webCheckoutDetails' => array(
            'checkoutReviewReturnUrl' => 'https://localhost/store/checkout_review',
            'checkoutResultReturnUrl' => 'https://localhost/store/checkout_result'
        ),
        'storeId' => 'amzn1.application-oa2-client.000000000000000000000000000000000'
    );
    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());
    try {
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->createCheckoutSession($payload, $headers);
        if ($result['status'] === 201) {
            // created
            $response = json_decode($result['response'], true);
            $checkoutSessionId = $response['checkoutSessionId'];
            echo "checkoutSessionId=$checkoutSessionId\n";
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
    }
    ?>
```

## API V2 - Get Checkout Session

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );

    try {
        $checkoutSessionId = '00000000-0000-0000-0000-000000000000';
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->getCheckoutSession($checkoutSessionId);
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $checkoutSessionState = $response['statusDetails']['state'];
            $chargeId = $response['chargeId'];
            $chargePermissionId = $response['chargePermissionId'];

            // NOTE: Once Checkout Session moves to a "Completed" state, buyer and shipping
            // details must be obtained from the getCharges() function call instead
            $buyerName = $response['buyer']['name'];
            $buyerEmail = $response['buyer']['email'];
            $shipName = $response['shippingAddress']['name'];
            $shipAddrLine1 = $response['shippingAddress']['addressLine1'];
            $shipCity = $response['shippingAddress']['city'];
            $shipState = $response['shippingAddress']['stateOrRegion'];
            $shipZip = $response['shippingAddress']['postalCode'];
            $shipCounty = $response['shippingAddress']['countryCode'];

            echo "checkoutSessionState=$checkoutSessionState\n";
            echo "chargeId=$chargeId; chargePermissionId=$chargePermissionId\n";
            echo "buyer=$buyerName ($buyerEmail)\n";
            echo "shipName=$shipName\n";
            echo "address=$shipAddrLine1; $shipCity $shipState $shipZip ($shipCounty)\n";
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
    }
    ?>
```

## API V2 - Update Checkout Session

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );

    $payload = array(
       'paymentDetails' => array(
            'paymentIntent' => 'Authorize',
            'canHandlePendingAuthorization' => false,
            'chargeAmount' => array(
                'amount' => '1.23',
                'currencyCode' => 'USD'
            ),
        ),
        'merchantMetadata' => array(
            'merchantReferenceId' => '2020-00000001',
            'merchantStoreName' => 'Store Name',
            'noteToBuyer' => 'Thank you for your order!'
        )
    );

    try {
        $checkoutSessionId = '00000000-0000-0000-0000-000000000000';
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->updateCheckoutSession($checkoutSessionId, $payload);
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $amazonPayRedirectUrl = $response['webCheckoutDetails']['amazonPayRedirectUrl'];
            echo "amazonPayRedirectUrl=$amazonPayRedirectUrl\n";
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
    }
    ?>
```

## API V2 - Capture Charge

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );

    $payload = array(
        'captureAmount' => array(
            'amount' => '1.23',
            'currencyCode' => 'USD'
        ),
        'softDescriptor' => 'For CC Statement'
    );

    try {
        $chargeId = 'S01-0000000-0000000-C000000';
        $headers = array('x-amz-pay-Idempotency-Key' => uniqid());
        $client = new AmazonPayV2\Client($amazonpay_config);
        $result = $client->captureCharge($chargeId, $payload, $headers);

        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $state = $response['statusDetail']['state'];
            $reasonCode = $response['statusDetail']['reasonCode'];
            $reasonDescription = $response['statusDetail']['reasonDescription'];
            echo "state=$state; reasonCode=$reasonCode; reasonDescription=$reasonDescription\n";
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
    }
    ?>
```

xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Manual Signing (Advanced Use-Cases Only)

This SDK provides the ability to help you manually sign your API requests if you want to use your own code for sending the HTTPS request over the Internet.

Example call to getPostSignedHeaders function with values:

```php
    /* 	getPostSignedHeaders convenience – Takes values for canonical request sorts and parses it and  
     *	returns a signature for the request being sent 
     * 	@param $http_request_method [String] 
     *	@param $request_uri [String] 
     *	@param $request_parameters [array()]
     *	@param $request_payload [string]
     */
```

Example request method:

```php
    $method = 'POST';

    // API Merchant Scan
    $url = 'https://pay-api.amazon.com/sandbox/in-store/v1/merchantScan';
    
    $payload = array(
        'scanData' => 'UKhrmatMeKdlfY6b',
        'scanReferenceId' => '0b8fb271-2ae2-49a5-b35d4',
        'merchantCOE' => 'US',
        'ledgerCurrency' => 'USD',
        'chargeTotal' => array(
            'currencyCode' => 'USD',
            'amount' => '2.00'
        ),
        'metadata' => array(
            'merchantNote' => 'Ice Cream',
            'customInformation' => 'In-store Ice Cream',
            'communicationContext' => array(
                'merchantStoreName' => 'Store Name',
                'merchantOrderId' => '789123'
            )
        )
    ); 

    // Convert to json string
    $payload = json_encode($payload);
    
    $requestParameters = array();

    $client = new AmazonPayV2\Client($amazonpay_config);

    $postSignedHeaders = $client->getPostSignedHeaders($method, $url, $requestParameters, $payload);
```

Example call to createSignature function with values: 

(This will only be used if you don't use getPostSignedHeaders and want to create your own custom headers.)

```php
  /* 	createSignature convenience – Takes values for canonical request sorts and parses it and  
   *	returns a signature for the request being sent 
   * 	@param $http_request_method [String] 
   *	@param $request_uri [String] 
   *	@param $request_parameters [Array()]
   *	@param $pre_signed_headers [Array()]
   *	@param $request_payload [String]
   *    @param $timeStamp [String]
   */

    // Example request method:

    $method = 'POST';

    // API Merchant Scan
    $url = 'https://pay-api.amazon.com/sandbox/in-store/v1/merchantScan';
    
    $payload = array(
        'scanData' => 'ScanData',
        'scanReferenceId' => '0b8fb271-2ae2-49a5-b35d4',
        'merchantCOE' => 'US',
        'ledgerCurrency' => 'USD',
        'chargeTotal' => array(
            'currencyCode' => 'USD',
            'amount' => '2.00'
        ),
        'metadata' => array(
            'merchantNote' => 'Ice Cream',
            'customInformation' => 'In-store Ice Cream',
            'communicationContext' => array(
                'merchantStoreName' => 'Store Name',
                'merchantOrderId' => '789123'
            )
        )
    ); 

    // Convert to json string
    $payload = json_encode($payload);
    
    $requestParameters = array();

    $client = new AmazonPayV2\Client($amazonpay_config);

    // Create an array that will contain the parameters for the charge API call
    $pre_signed_headers = array();
    $pre_signed_headers['Accept'] = 'application/json';
    $pre_signed_headers['Content-Type'] = 'application/json';
    $pre_signed_headers['X-Amz-Pay-Region'] = 'na';

    $client = new Client($amazonpay_config);
    $signedInput = $client->createSignature($method, $url, $requestParameters, $pre_signed_headers, $payload, '20180326T203730Z');
```
