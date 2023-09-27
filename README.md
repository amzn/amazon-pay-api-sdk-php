# Amazon Pay API SDK (PHP)
Amazon Pay Integration

Please note that the Amazon Pay API SDK can only be used for API calls to the pay-api.amazon.com|eu|jp endpoint.

If you need to make an Amazon Pay API call that uses the mws.amazonservices.com|jp or mws-eu.amazonservices.com endpoint, then you will need to use the original [Amazon Pay SDK (PHP)](https://github.com/amzn/amazon-pay-sdk-php).

## Requirements

* PHP 5.5 or higher, but highly recommended to use only the latest PHP version, and update often, to ensure current security fixes are applied
* Curl 7.18 or higher
* phpseclib 2.0

## SDK Installation

Use composer to install the latest release of the SDK and its dependencies:

```
    composer require amzn/amazon-pay-api-sdk-php
```

Verify the installation with the following test script:

```php
    <?php
        include 'vendor/autoload.php';
        echo "SDK_VERSION=" . Amazon\Pay\API\Client::SDK_VERSION . "\n";
    ?>
```

## Public and Private Keys

MWS access keys, MWS secret keys, and MWS authorization tokens from previous MWS integrations cannot be used with this SDK.

You will need to generate your own public/private key pair to make API calls with this SDK.

In Windows 10 this can be done with ssh-keygen commands:

```
ssh-keygen -t rsa -b 2048 -f private.pem
ssh-keygen -f private.pem -e -m PKCS8 > public.pub
```

In Linux or macOS this can be done using openssl commands:

```
openssl genrsa -out private.pem 2048
openssl rsa -in private.pem -pubout > public.pub
```

The first command above generates a private key and the second line uses the private key to generate a public key.

To associate the key with your account, follow the instructions here to
[Get your Public Key ID](http://amazonpaycheckoutintegrationguide.s3.amazonaws.com/amazon-pay-checkout/get-set-up-for-integration.html#4-get-your-public-key-id).

## Namespace

Namespace for this package is Amazon\Pay\API so that there are no conflicts with the original Amazon Pay MWS SDK's that use the AmazonPay namespace.

## Configuration Array

```php
    $amazonpay_config = array(
        'public_key_id' => 'ABC123DEF456XYZ',  // RSA Public Key ID (this is not the Merchant or Seller ID)
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'sandbox'       => true,               // true (Sandbox) or false (Production) boolean
        'region'        => 'us',                // Must be one of: 'us', 'eu', 'jp'
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2'  //Amazon Signing Algorithm, Optional: uses AMZN-PAY-RSASSA-PSS if not specified
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
```
If you have created environment specific keys (i.e Public Key Starts with LIVE or SANDBOX) in Seller Central, then use those PublicKeyId & PrivateKey. In this case, there is no need to pass the Sandbox parameter to the ApiConfiguration.

```php
    $amazonpay_config = array(
	    'public_key_id' => 'MY_PUBLIC_KEY_ID',  // LIVE-XXXXX or SANDBOX-XXXXX
	    'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
	    'region'        => 'us', // Must be one of: 'us', 'eu', 'jp'
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2'  //Amazon Signing Algorithm, Optional: uses AMZN-PAY-RSASSA-PSS if not specified
	);
```

If you have want to enable proxy support, you can set it in the $amazonpay_config in the following way:
```php
    $amazonpay_config = array(
        'public_key_id' => 'ABC123DEF456XYZ',  // RSA Public Key ID (this is not the Merchant or Seller ID)
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'sandbox'       => true,               // true (Sandbox) or false (Production) boolean
        'region'        => 'us',               // Must be one of: 'us', 'eu', 'jp'
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',  //Amazon Signing Algorithm, Optional: uses AMZN-PAY-RSASSA-PSS if not specified
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4',            // (optional) Solution Provider Platform Version in Semantic Versioning Format
        'proxy' => [
            'host' => 'proxy_host',
            'port' => 'proxy_port',
            'username' => 'proxy_username',
            'password' => 'proxy_password',
        ]
    );
```
# Versioning

The pay-api.amazon.com|eu|jp endpoint uses versioning to allow future updates.  The major version of this SDK will stay aligned with the API version of the endpoint.

If you have downloaded version 1.x.y of this SDK, $version in below examples would be "v1".  2.x.y would be "v2", etc. 

# Convenience Functions (Overview)

Make use of the built-in convenience functions to easily make API calls.  Scroll down further to see example code snippets.

When using the convenience functions, the request payload will be signed using the provided private key, and a HTTPS request is made to the correct regional endpoint.
In the event of request throttling, the HTTPS call will be attempted up to three times using an exponential backoff approach.

## Alexa Delivery Trackers API
Use this API to provide shipment tracking information to Amazon Pay so that Amazon Pay can notify buyers on Alexa when shipments are out for delivery and when they are delivered. Please refer to the [Delivery Trackers API documentation](https://developer.amazon.com/docs/amazon-pay-onetime/delivery-order-notifications.html) for additional information.

* **deliveryTrackers**($payload, $headers = null) &#8594; POST to "$version/deliveryTrackers"

## Authorization Tokens API
Please note that your solution provider account must have a pre-existing relationship (valid and active MWS authorization token) with the merchant account in order to use this function.

* **getAuthorizationToken**($mwsAuthToken, $merchantId, $headers = null) &#8594; GET to "$version/authorizationTokens/$mwsAuthToken?merchantId=$merchantId"

## Amazon Checkout v2 API
[API Integration Guide](https://amazonpaycheckoutintegrationguide.s3.amazonaws.com/amazon-pay-api-v2/introduction.html)

The $headers field is not optional for create/POST calls below because it requires, at a minimum, the x-amz-pay-idempotency-key header:

```php
    $headers = array('x-amz-pay-idempotency-key' => uniqid());
```

### Amazon Checkout v2 Buyer object
* **getBuyer**($buyerToken, $headers = null) &#8594; GET to "$version/buyers/$buyerToken"

### Amazon Checkout v2 CheckoutSession object
* **createCheckoutSession**($payload, $headers) &#8594; POST to "$version/checkoutSessions"
* **getCheckoutSession**($checkoutSessionId, $headers = null) &#8594; GET to "$version/checkoutSessions/$checkoutSessionId"
* **updateCheckoutSession**($checkoutSessionId, $payload, $headers = null) &#8594; PATCH to "$version/checkoutSessions/$checkoutSessionId"
* **completeCheckoutSession**($checkoutSessionId, $payload, $headers = null) &#8594; POST to "$version/checkoutSessions/$checkoutSessionId/complete"

### Amazon Checkout v2 ChargePermission object
* **getChargePermission**($chargePermissionId, $headers = null) &#8594; GET to "$version/chargePermissions/$chargePermissionId"
* **updateChargePermission**($chargePermissionId, $payload, $headers = null) &#8594; PATCH to "$version/chargePermissions/$chargePermissionId"
* **closeChargePermission**($chargePermissionId, $payload, $headers = null) &#8594; DELETE to "$version/chargePermissions/$chargePermissionId/close"

### Amazon Checkout v2 Charge object
* **createCharge**($payload, $headers) &#8594; POST to "$version/charges"
* **getCharge**($chargeId, $headers = null) &#8594; GET to "$version/charges/$chargeId"
* **captureCharge**($chargeId, $payload, $headers) &#8594; POST to "$version/charges/$chargeId/capture"
* **cancelCharge**($chargeId, $payload, $headers = null) &#8594; DELETE to "$version/charges/$chargeId/cancel"

### Amazon Checkout v2 Refund object
* **createRefund**($payload, $headers) &#8594; POST to "$version/refunds"
* **getRefund**($refundId, $headers = null) &#8594; GET to "$version/refunds/$refundId"

## In-Store API
Please contact your Amazon Pay Account Manager before using the In-Store API calls in a Production environment to obtain a copy of the In-Store Integration Guide.

* **instoreMerchantScan**($payload, $headers = null) &#8594; POST to "$version/in-store/merchantScan"
* **instoreCharge**($payload, $headers = null) &#8594; POST to "$version/in-store/charge"
* **instoreRefund**($payload, $headers = null) &#8594; POST to "$version/in-store/refund"

### Amazon Checkout v2 SPC
* **finalizeCheckoutSession**($checkoutSessionId, $payload, $headers = null) &#8594; POST to "$version/checkoutSessions/$checkoutSessionId/finalize"

### Amazon Checkout v2 Merchant Onboarding & Account Management object
* **registerAmazonPayAccount**($payload, $headers = null) &#8594; POST to "$version/merchantAccounts"
* **updateAmazonPayAccount**($merchantAccountId, $payload, $headers = null) &#8594; PATCH to "$version/merchantAccounts/$merchantAccountId"
* **deleteAmazonPayAccount**($merchantAccountId, $headers = null) &#8594; DELETE to "$version/merchantAccounts/$merchantAccountId"

# Using Convenience Functions

Four quick steps are needed to make an API call:

Step 1. Construct a Client (using the previously defined Config Array).

```php
    $client = new Amazon\Pay\API\Client($amazonpay_config);
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
        'sandbox'       => false,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
    );
    $payload = array(
        'amazonOrderReferenceId' => 'P01-0000000-0000000',
        'deliveryDetails' => array(array(
            'trackingNumber' => '01234567890',
            'carrierCode' => 'FEDEX'
        ))
    );
    try {
        $client = new Amazon\Pay\API\Client($amazonpay_config);
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

## Amazon Checkout v2 - Create Checkout Session (AJAX service example)

```php
    <?php
    session_start();

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
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
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->createCheckoutSession($payload, $headers);

        header("Content-type:application/json; charset=utf-8");
        echo $result['response'];
        if ($result['status'] !== 201) {
            http_response_code(500);
        }

    } catch (\Exception $e) {
        // handle the exception
        echo $e . "\n";
        http_response_code(500);
    }
    ?>
```


## Amazon Checkout v2 - Create Checkout Session (standalone script example)

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
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
        $client = new Amazon\Pay\API\Client($amazonpay_config);
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

## Amazon Checkout v2 - Get Checkout Session

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );

    try {
        $checkoutSessionId = '00000000-0000-0000-0000-000000000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
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

## Amazon Checkout v2 - Update Checkout Session

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
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
        $client = new Amazon\Pay\API\Client($amazonpay_config);
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

## Amazon Checkout v2 - Complete Checkout Session API

```php
<?php
    include 'vendor/autoload.php';
    
    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
    
    $payload = array(
        'chargeAmount' => array(
            'amount' => '14.00',
            'currencyCode' => 'USD'
        )
    );
    
    try {
        $checkoutSessionId = '00000000-0000-0000-0000-000000000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->completeCheckoutSession($checkoutSessionId, $payload);
        
        if ($result['status'] === 202) {
            // Charge Permission is in AuthorizationInitiated state
            $response = json_decode($result['response'], true);
            $checkoutSessionState = $response['statusDetails']['state'];
            $chargeId = $response['chargeId'];
            $chargePermissionId = $response['chargePermissionId'];
        } 
        else if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $checkoutSessionState = $response['statusDetails']['state'];
            $chargePermissionId = $response['chargePermissionId'];
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'] . "\n";
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Get Charge Permission API

```php
<?php
    include 'vendor/autoload.php';
    
    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );

    try {
        $chargePermissionId = 'S01-0000000-0000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getChargePermission($chargePermissionId);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $chargePermissionState = $response['statusDetails']['state'];

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Update Charge Permission API

```php
<?php
    include 'vendor/autoload.php';
    
    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );

    $payload = array(
        'merchantMetadata' => array(
            'merchantReferenceId' => '32-41-323141-32',
            'merchantStoreName' => 'AmazonTestStoreFront',
            'noteToBuyer' => 'Some Note to buyer',
            'customInformation' => ''   
        )
    );

    try {
        $chargePermissionId = 'S01-0000000-0000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->updateChargePermission($chargePermissionId, $payload);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Close Charge Permission API

```php
<?php
    include 'vendor/autoload.php';
    
    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );

    $payload = array(
        'closureReason' => 'No more charges required',
        'cancelPendingCharges' => false
    );

    try {
        $chargePermissionId = 'S01-0000000-0000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->closeChargePermission($chargePermissionId, $payload);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $chargePermissionState = $response['statusDetails']['state'];
            
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Create Charge API

```php
<?php

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
        
    $payload = array(
        'chargePermissionId' => 'S01-0000000-0000000',
        'chargeAmount' => array(
            'amount' => '14.00',
            'currencyCode' => 'USD'
        ),
        'captureNow' => true,
        'softDescriptor' => 'Descriptor',
        'canHandlePendingAuthorization' => false 
    );

    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());

    try {
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->createCharge($payload, $headers);
        
        if ($result['status'] === 201) {
            $response = json_decode($result['response'], true);
            $chargeState = $response['statusDetails']['state'];
            $chargeId = $response['chargeId'];

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Get Charge API

```php
<?php

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
        
    try {
        $chargeId = 'S01-0000000-0000000-C000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getCharge($chargeId);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $chargeState = $response['statusDetails']['state'];

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Capture Charge API

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',  // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
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
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->captureCharge($chargeId, $payload, $headers);

        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $state = $response['statusDetails']['state'];
            $reasonCode = $response['statusDetails']['reasonCode'];
            $reasonDescription = $response['statusDetails']['reasonDescription'];
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

## Amazon Checkout v2 - Cancel Charge API

```php
<?php 

    include 'vendor/autoload.php';
    
    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );  

    $payload = array(
        'cancellationReason' => 'REASON DESCRIPTION'
    );

    try {
        $chargeId = 'S01-0000000-0000000-C000000';
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->cancelCharge($chargeId, $payload);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $chargeState = $response['statusDetails']['state'];
            
        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Create Refund API

```php
<?php

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
        
    $payload = array(
        'chargeId' => 'S01-0000000-0000000-C000000',
        'refundAmount' => array(
            'amount' => '14.00',
            'currencyCode' => 'USD'
        ),
        'softDescriptor' => 'Descriptor'
    );

    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());

    try {
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->createRefund($payload, $headers);
        
        if ($result['status'] === 201) {
            $response = json_decode($result['response'], true);
            $refundState = $response['statusDetails']['state'];
            $refundId = $response['refundId'];

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Get Refund API

```php
<?php

    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE',
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
        
    try {
        $refundId = 'S01-0000000-0000000-R000000'
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getRefund($refundId);
        
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);
            $chargeState = $response['statusDetails']['state'];

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

## Amazon Checkout v2 - Get Buyer API

```php
<?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'YOUR_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem', // Path to RSA Private Key (or a string representation)
        'region'        => 'YOUR_REGION_CODE', 
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
        'integrator_id'      => 'AXXXXXXXXXXXXX',   // (optional) Solution Provider Platform Id in Amz UID Format
        'integrator_version' => '1.2.3',            // (optional) Solution Provider Plugin Version in Semantic Versioning Format
        'platform_version'   => '0.0.4'            // (optional) Solution Provider Platform Version in Semantic Versioning Format
    );
            
    try {
        $buyerToken = 'BUYER_TOKEN';
        
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getBuyer($buyerToken);
            
        if ($result['status'] === 200) {
            $response = json_decode($result['response'], true);

        } else {
            // check the error
            echo 'status=' . $result['status'] . '; response=' . $result['response'];
        }
    } catch (Exception $e) {
        // handle the exception
        echo $e;
    }
?>
```

# Generate Button Signature (helper function)

The signatures generated by this helper function are only valid for the Checkout v2 front-end buttons.  Unlike API signing, no timestamps are involved, so the result of this function can be considered a static signature that can safely be placed in your website JS source files and used repeatedly (as long as your payload does not change).

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true,
        'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
    );

    $client = new Amazon\Pay\API\Client($amazonpay_config);
    $payload = '{"storeId":"amzn1.application-oa2-client.xxxxx","webCheckoutDetails":{"checkoutReviewReturnUrl":"https://localhost/test/CheckoutReview.php","checkoutResultReturnUrl":"https://localhost/test/CheckoutResult.php"}}';
    $signature = $client->generateButtonSignature($payload);
    echo $signature . "\n";
    ?>
```

# Manual Signing (Advanced Use-Cases Only)

This SDK provides the ability to help you manually sign your API requests if you want to use your own code for sending the HTTPS request over the Internet.

Example call to getPostSignedHeaders function with values:

```php
    /*  getPostSignedHeaders convenience – Takes values for canonical request sorts and parses it and
     *  returns a signature for the request being sent
     *  @param $http_request_method [String]
     *  @param $request_uri [String]
     *  @param $request_parameters [array()]
     *  @param $request_payload [string]
     */
```

Example request method:

```php
    $method = 'POST';

    // API Merchant Scan
    $url = 'https://pay-api.amazon.com/sandbox/' . $versiom . '/in-store/merchantScan';
    
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

    $client = new Amazon\Pay\API\Client($amazonpay_config);

    $postSignedHeaders = $client->getPostSignedHeaders($method, $url, $requestParameters, $payload);
```

Example call to createSignature function with values: 

(This will only be used if you don't use getPostSignedHeaders and want to create your own custom headers.)

```php
  /*    createSignature convenience – Takes values for canonical request sorts and parses it and
   *    returns a signature for the request being sent
   *    @param $http_request_method [String]
   *    @param $request_uri [String]
   *    @param $request_parameters [Array()]
   *    @param $pre_signed_headers [Array()]
   *    @param $request_payload [String]
   *    @param $timeStamp [String]
   */

    // Example request method:

    $method = 'POST';

    // API Merchant Scan
    $url = 'https://pay-api.amazon.com/sandbox/in-store/' . $version . '/merchantScan';
    
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

    $client = new Amazon\Pay\API\Client($amazonpay_config);

    // Create an array that will contain the parameters for the charge API call
    $pre_signed_headers = array();
    $pre_signed_headers['Accept'] = 'application/json';
    $pre_signed_headers['Content-Type'] = 'application/json';
    $pre_signed_headers['X-Amz-Pay-Region'] = 'na';

    $client = new Client($amazonpay_config);
    $signedInput = $client->createSignature($method, $url, $requestParameters, $pre_signed_headers, $payload, '20180326T203730Z');
```

#  Reporting APIs code samples

## Amazon Checkout v2 Reporting APIs - GetReport API

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => false
    );

    $requestPayload = array(
        'reportTypes' => '_GET_FLAT_FILE_OFFAMAZONPAYMENTS_ORDER_REFERENCE_DATA_',
        'processingStatuses' => 'COMPLETED',
        'pageSize' => '10'
    );

    try {
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getReports($requestPayload);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - GetReportById API

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
        $reportId = "1234567890";
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getReportById($reportId);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - GetReportDocument API

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
        $reportDocumentId = "amzn1.tortuga.0.000000000-0000-0000-0000-000000000000.00000000000000";
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getReportDocument($reportDocumentId);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - GetReportSchedules API

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
        $reportTypes = "_GET_FLAT_FILE_OFFAMAZONPAYMENTS_ORDER_REFERENCE_DATA_,_GET_FLAT_FILE_OFFAMAZONPAYMENTS_BILLING_AGREEMENT_DATA_";
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getReportSchedules($reportTypes);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - GetReportScheduleById API

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
        $reportScheduleId = "1234567890";
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->getReportScheduleById($reportScheduleId);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - CreateReport API

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );

    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());

    try {
        $requestPayload = array(
            'reportType' => '_GET_FLAT_FILE_OFFAMAZONPAYMENTS_AUTHORIZATION_DATA_',
            'startTime' => '20221114T074550Z',
            'endTime' => '20221114T074550Z'
        );
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->createReport($requestPayload);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - CreateReportSchedule API

```php
    <?php
    include 'vendor/autoload.php';

    $amazonpay_config = array(
        'public_key_id' => 'MY_PUBLIC_KEY_ID',
        'private_key'   => 'keys/private.pem',
        'region'        => 'US',
        'sandbox'       => true
    );

    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());

    try {
        $requestPayload = array(
            'reportType' => '_GET_FLAT_FILE_OFFAMAZONPAYMENTS_ORDER_REFERENCE_DATA_',
            'scheduleFrequency' => 'P1D',
            'nextReportCreationTime' => '20221114T074550Z',
            'deleteExistingSchedule' => false
        );
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->createReportSchedule($requestPayload);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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


## Amazon Checkout v2 Reporting APIs - CancelReportSchedule API

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
        $reportScheduleId = "1234567890";
        $client = new Amazon\Pay\API\Client($amazonpay_config);
        $result = $client->cancelReportSchedule($reportScheduleId);

        if ($result['status'] === 200) {
            // success
            $response = $result['response'];
            echo $response;
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
## Amazon Checkout v2 SPC - finalizeCheckoutSession API

```php
<?php
include 'vendor/autoload.php';
require_once 'Amazon/Pay/API/Client.php';
$amazonpay_config = array(
    'public_key_id' => 'MY_PUBLIC_KEY_ID',
    'private_key'   => 'keys/private.pem',
    'region'        => 'US',
    'sandbox'       => true,
    'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
);
try{
    $payload =  array(
        "shippingAddress" => array(
            "name" => "Susie Smith",
            "addressLine1" => "10 Ditka Ave",
            "addressLine2" => "Suite 2500",
            "city" => "Chicago",
            "county" => null,
            "district" => null,
            "stateOrRegion" => "IL",
            "postalCode" => "60602",
            "countryCode" => "US",
            "phoneNumber" => "800-000-0000"
        ),
        "billingAddress" => null,
        "chargeAmount" => array(
            "amount" => "10",
            "currencyCode" => "USD"
        ),
        "totalOrderAmount" => array(
            "amount" => "10",
            "currencyCode" => "USD"
        ),
        "paymentIntent" => "Confirm",
        "canHandlePendingAuthorization" => "false"
    );
    $headers = array('x-amz-pay-Idempotency-Key' => uniqid());
    $client = new Amazon\Pay\API\Client($amazonpay_config);
    $checkoutSessionId = "your-checkout-session-id";
    $result = $client->finalizeCheckoutSession($checkoutSessionId,$payload, $headers);
if ($result['status'] === 200) {
    // success
    $response = $result['response'];
    echo $response;
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