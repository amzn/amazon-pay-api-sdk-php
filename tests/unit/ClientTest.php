<?php
    namespace Amazon\Pay\API;
    
    include 'vendor/autoload.php';
    require_once 'Amazon/Pay/API/Client.php';

    use phpseclib3\Crypt\RSA;
    use PHPUnit\Framework\TestCase;

    class ClientTest extends TestCase
    {
        const INTEGRATOR_ID = 'AXXXXXXXXXXXXX';
        const INTEGRATOR_VERSION = '5.4.3';
        const PLATFORM_VERSION = '2.0.7';
        // Test keys live in a temp path only; the committed fixtures stay placeholders.
        private static $privateKeyPath;
        private static $publicKeyPath;

        // Generates a throwaway RSA keypair into untracked temp files for this test run.
        public static function setUpBeforeClass(): void
        {
            $resource = openssl_pkey_new(array(
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ));
            if ($resource === false) {
                self::fail('Unable to generate test RSA keypair: ' . openssl_error_string());
            }
            if (!openssl_pkey_export($resource, $privateKeyPem)) {
                self::fail('Unable to export generated test private key: ' . openssl_error_string());
            }
            $details = openssl_pkey_get_details($resource);
            if ($details === false || !isset($details['key'])) {
                self::fail('Unable to read generated test public key: ' . openssl_error_string());
            }
            $publicKeyPem = $details['key'];

            self::$privateKeyPath = tempnam(sys_get_temp_dir(), 'ap-sdk-test-key-');
            self::$publicKeyPath = tempnam(sys_get_temp_dir(), 'ap-sdk-test-pub-');
            if (self::$privateKeyPath === false || self::$publicKeyPath === false
                || file_put_contents(self::$privateKeyPath, $privateKeyPem) === false
                || file_put_contents(self::$publicKeyPath, $publicKeyPem) === false) {
                self::fail('Unable to write generated test key fixtures to a temp path');
            }
        }

        // Deletes the generated temp keypair; nothing tracked was ever touched.
        public static function tearDownAfterClass(): void
        {
            if (self::$privateKeyPath !== null) {
                @unlink(self::$privateKeyPath);
            }
            if (self::$publicKeyPath !== null) {
                @unlink(self::$publicKeyPath);
            }
        }

        private $configArray = array(
            //config 
            array(
                'public_key_id' => 'ABC123DEF456XYZ789IJK000',
                'private_key'   => 'tests/unit/unit_test_key_private.txt',
                'sandbox'       => true,
                'region'        => 'na',
                'integrator_id'      => self::INTEGRATOR_ID,
                'integrator_version' => self::INTEGRATOR_VERSION,
                'platform_version'   => self::PLATFORM_VERSION
            ),
            //config with algorithm as a parameter
            array(
                'public_key_id' => 'ABC123DEF456XYZ789IJK000',
                'private_key'   => 'tests/unit/unit_test_key_private.txt',
                'sandbox'       => true,
                'region'        => 'na',
                'algorithm'     => 'AMZN-PAY-RSASSA-PSS-V2',
                'integrator_id'      => self::INTEGRATOR_ID,
                'integrator_version' => self::INTEGRATOR_VERSION,
                'platform_version'   => self::PLATFORM_VERSION

            ),
            //config with proxy parameters
            array(
                'public_key_id' => 'ABC123DEF456XYZ789IJK000',
                'private_key'   => 'tests/unit/unit_test_key_private.txt',
                'sandbox'       => true,
                'region'        => 'na',
                'integrator_id'      => self::INTEGRATOR_ID,
                'integrator_version' => self::INTEGRATOR_VERSION,
                'platform_version'   => self::PLATFORM_VERSION,
                'proxy' => [
                    'host' => 'proxy_host',
                    'port' => 'proxy_port',
                    'username' => 'proxy_username',
                    'password' => 'proxy_password',
                ]
            ) 
        );

        private $requestParameters = array(
            'chargePermissionId'    => 'P03-0772540-6944847',
            'chargeReferenceId'     => 'chargeReferenceId-1',
            'chargeTotal'           => '100.50',
            'currencyCode'          => 'JPY',
            'amount'                => '100.50',
            'softDescriptor'        => 'chargeTest-1',
            'metadata'              => array('shoe sale', 'Information about order'),
            'merchantNote'          => '',
            'customInformation'     => 'Information about order',
            'communicationContext'  => array(),
            'merchantStoreName'     => 'Name of Store',
            'merchantOrderId'       => 'Order 123'
        );

        private $requestHeaders = array(
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Amz-Pay-Host'    => 'pay-api.amazon.jp',
            'User-Agent'        => ''
        );

        private $uri = "http://pay-api.amazon.jp/sandbox/in-store/v999/charge?extradata";
        private $expectedUnitedStatesURL = 'https://pay-api.amazon.com/';
        private $expectedEuropeURL = 'https://pay-api.amazon.eu/';
        private $expectedJapanURL = 'https://pay-api.amazon.jp/';

        // Point every configArray entry at the generated temp keypair; the literal
        // path above only documents shape, it is never read.
        protected function setUp(): void
        {
            foreach ($this->configArray as &$config) {
                $config['private_key'] = self::$privateKeyPath;
            }
        }

        public function testConfigArray()
        {
            $client = new Client($this->configArray[0]);

            $this->assertEquals($this->configArray[0]['public_key_id'], $client->__get('public_key_id'));
            $this->assertEquals($this->configArray[0]['private_key'], $client->__get('private_key'));
            $this->assertEquals($this->configArray[0]['sandbox'], $client->__get('sandbox'));
            $this->assertEquals($this->configArray[0]['region'], $client->__get('region'));
            $this->assertEquals($this->configArray[0]['integrator_id'], $client->__get('integrator_id'));
            $this->assertEquals($this->configArray[0]['integrator_version'], $client->__get('integrator_version'));
            $this->assertEquals($this->configArray[0]['platform_version'], $client->__get('platform_version'));
        }

        public function testConfigArrayWithAlgorithm()
        {
            $client = new Client($this->configArray[1]);

            $this->assertEquals($this->configArray[1]['public_key_id'], $client->__get('public_key_id'));
            $this->assertEquals($this->configArray[1]['private_key'], $client->__get('private_key'));
            $this->assertEquals($this->configArray[1]['sandbox'], $client->__get('sandbox'));
            $this->assertEquals($this->configArray[1]['region'], $client->__get('region'));
            $this->assertEquals($this->configArray[1]['algorithm'], $client->__get('algorithm'));
            $this->assertEquals($this->configArray[1]['integrator_id'], $client->__get('integrator_id'));
            $this->assertEquals($this->configArray[1]['integrator_version'], $client->__get('integrator_version'));
            $this->assertEquals($this->configArray[1]['platform_version'], $client->__get('platform_version'));
        }

        public function testConfigArrayWithProxy() {
            $client = new Client($this->configArray[2]);
            
            $this->assertEquals($this->configArray[2]['public_key_id'], $client->__get('public_key_id'));
            $this->assertEquals($this->configArray[2]['private_key'], $client->__get('private_key'));
            $this->assertEquals($this->configArray[2]['sandbox'], $client->__get('sandbox'));
            $this->assertEquals($this->configArray[2]['region'], $client->__get('region'));
            $this->assertEquals($this->configArray[2]['proxy']['host'], $client->__get('proxy')['host']);
            $this->assertEquals($this->configArray[2]['proxy']['port'], $client->__get('proxy')['port']);
            $this->assertEquals($this->configArray[2]['proxy']['username'], $client->__get('proxy')['username']);
            $this->assertEquals($this->configArray[2]['proxy']['password'], $client->__get('proxy')['password']);
            $this->assertEquals($this->configArray[2]['integrator_id'], $client->__get('integrator_id'));
            $this->assertEquals($this->configArray[2]['integrator_version'], $client->__get('integrator_version'));
            $this->assertEquals($this->configArray[2]['platform_version'], $client->__get('platform_version'));
        }

        public function testGetCanonicalURI()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('getCanonicalURI');

                $uriTrue = "/sandbox/in-store/v999/charge";

                $this->assertEquals($uriTrue, $method->invoke($client, $this->uri));
            }
        }

        public function testSortCanonicalArray()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('sortCanonicalArray');

                $canonicalArrayTrue = array(
                    'amount'                => '100.50',
                    'chargePermissionId'    => 'P03-0772540-6944847',
                    'chargeReferenceId'     => 'chargeReferenceId-1',
                    'chargeTotal'           => '100.50',
                    'currencyCode'          => 'JPY',
                    'customInformation'     => 'Information about order',
                    'merchantOrderId'       => 'Order 123',
                    'merchantStoreName'     => 'Name of Store',
                    'metadata.1'            => 'shoe sale',
                    'metadata.2'            => 'Information about order',
                    'softDescriptor'        => 'chargeTest-1'
                );
            
                $this->assertEquals($canonicalArrayTrue, $method->invoke($client, $this->requestParameters));
            }
        }

        public function testCreateCanonicalQuery()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('createCanonicalQuery');

                $canonicalQueryTrue = ("amount=100.50" .
                    "&chargePermissionId=P03-0772540-6944847" .
                    "&chargeReferenceId=chargeReferenceId-1" .
                    "&chargeTotal=100.50" .
                    "&currencyCode=JPY" .
                    "&customInformation=Information%20about%20order" .
                    "&merchantOrderId=Order%20123" .
                    "&merchantStoreName=Name%20of%20Store" .
                    "&metadata.1=shoe%20sale" .
                    "&metadata.2=Information%20about%20order" .
                    "&softDescriptor=chargeTest-1");
            
                $this->assertEquals($canonicalQueryTrue, $method->invoke($client, $this->requestParameters));
            }
        }

        public function testGetCanonicalHeaders()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('getCanonicalHeaders');

                $canonicalHeadersTrue = array(
                    'accept'            => 'application/json',
                    'content-type'      => 'application/json',
                    'x-amz-pay-host'    => 'pay-api.amazon.jp'
                );

                $this->assertEquals($canonicalHeadersTrue, $method->invoke($client, $this->requestHeaders));
            }
        }

        public function testGetCanonicalHeadersNames()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('getCanonicalHeadersNames');

                $canonicalHeadersNamesTrue = 'accept;content-type;x-amz-pay-host';
            
                $this->assertEquals($canonicalHeadersNamesTrue, $method->invoke($client, $this->requestHeaders));
            }
        }

        public function testGetHost()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $class = new \ReflectionClass($client);
                $method = $class->getMethod('gethost');

                $hostTrue = 'pay-api.amazon.jp';
                $this->assertEquals($hostTrue, $method->invoke($client, $this->uri));

                $emptyHost = '/';
                $this->assertEquals($emptyHost, $method->invoke($client, ''));
            }
        }

        public function testGetHeaderString()
        {
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
            
                $headerStringTrue = (
                    "accept:application/json\n" .
                    "content-type:application/json\n" .
                    "x-amz-pay-host:pay-api.amazon.jp\n"
                );

                $this->assertEquals($headerStringTrue, $client->getHeaderString($this->requestHeaders, $this->uri));
            }
        }

        public function testGetPostSignedHeaders() {
            $method = 'POST';
            $url = 'https://pay-api.amazon.com/sandbox/in-store/v999/merchantScan';
            $requestParameters = array();

            $request = array(
                'chargeId' => 'S03-2622124-8818929-C062250',
                'refundReferenceId' => 'refundRef-1',
                'refundTotal' => array(
                    'currencyCode' => 'JPY',
                    'amount' => 2
                ),
                'softDescriptor' => 'TESTSTORE refund',
            );
            $payload = json_encode($request);
            
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);

                $postSignedHeaders = $client->getPostSignedHeaders($method, $url, $requestParameters, $payload);
                $signature = substr($postSignedHeaders[1], strpos($postSignedHeaders[1], "Signature=") + 10);
                $this-> assertNotNull($signature);
                //TODO: verify signature, see http://phpseclib.sourceforge.net/rsa/2.0/examples.html
            }
        }

        private function verifySignature($plaintext, $signature) {
            $rsa = RSA::loadPrivateKey(file_get_contents(self::$privateKeyPath))->withSaltLength(substr( $plaintext, 0, 22 ) === 'AMZN-PAY-RSASSA-PSS-V2' ? 32 : 20 );
            return $rsa->getPublicKey()->verify($plaintext, base64_decode($signature));
        }

        public function testGenerateButtonSignature() {
            $payload = '{"storeId":"amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx","webCheckoutDetails":{"checkoutReviewReturnUrl":"https://localhost/test/CheckoutReview.php","checkoutResultReturnUrl":"https://localhost/test/CheckoutResult.php"}}';

            
            for ( $i=0; $i<3; $i++ ) {
                $client = new Client($this->configArray[$i]);
                $signature = $client->generateButtonSignature($payload);

                //config with algorithm as parameter, AMZN-PAY-RSASSA-PSS-V2 is used
                if( $i == 1 ){ 
                    $plaintext = "AMZN-PAY-RSASSA-PSS-V2\n8dec52d799607be40f82d5c8e7ecb6c171e6591c41b1111a576b16076c89381c";
                } else {
                    $plaintext = "AMZN-PAY-RSASSA-PSS\n8dec52d799607be40f82d5c8e7ecb6c171e6591c41b1111a576b16076c89381c";
                }
                $this->assertEquals($this->verifySignature($plaintext, $signature), true);

            // confirm "same" sigature is generated if an array is passed in instead of a string
                $payloadArray = array(
                    "storeId" => "amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                    "webCheckoutDetails" => array(
                        "checkoutReviewReturnUrl" => "https://localhost/test/CheckoutReview.php",
                        "checkoutResultReturnUrl" => "https://localhost/test/CheckoutResult.php"
                    ),
                );

                $signature = $client->generateButtonSignature($payloadArray);
                $this->assertEquals($this->verifySignature($plaintext, $signature), true);

                // confirm "same" signature is generated when quotes and slashes are esacped
                $payloadEscaped = '{"storeId\":\"amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\",\"webCheckoutDetails\":{\"checkoutReviewReturnUrl\":\"https:\/\/localhost\/test\/CheckoutReview.php\",\"checkoutResultReturnUrl\":\"https:\/\/localhost\/test\/CheckoutResult.php\"}}';
                $signature = $client->generateButtonSignature($payloadEscaped);
                $this->assertEquals($this->verifySignature($plaintext, $signature), true);
            }
        }

        // Method used to test the Environment Specific Endpoint URL
        public function testCreateServiceURL() {
            // Constants
            $live = 'live/';
            $sandbox = 'sandbox/';

            // Testing Live specific endpoint for region United States
            $this->verifyEnvironmentSpecificEndpoint('us', false, $this->expectedUnitedStatesURL . $live);

            // Testing Sandbox specific endpoint for region United States
            $this->verifyEnvironmentSpecificEndpoint('us', true, $this->expectedUnitedStatesURL . $sandbox);

            // Testing Live specific endpoint for region Europe
            $this->verifyEnvironmentSpecificEndpoint('eu', false, $this->expectedEuropeURL . $live);

            // Testing Sandbox specific endpoint for region Europe
            $this->verifyEnvironmentSpecificEndpoint('eu', true, $this->expectedEuropeURL . $sandbox);

            // Testing Live specific endpoint for region Japan
            $this->verifyEnvironmentSpecificEndpoint('jp', false, $this->expectedJapanURL . $live);

            // Testing Sandbox specific endpoint for region Japan
            $this->verifyEnvironmentSpecificEndpoint('jp', true, $this->expectedJapanURL . $sandbox);
        }

        // Generic method used to verify Environment Specific Endpoint
        private function verifyEnvironmentSpecificEndpoint($region, $sandboxFlag, $expectedURL) {
            // Configuration
            $payConfig = array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'sandbox'       => $sandboxFlag,
                'region'        => $region,
            );
            $reflectionMethod = self::getMethod('createServiceUrl');
            $client = new Client($payConfig);

            // Building URL
            $actualURL = $reflectionMethod->invoke($client);

            // Assertion 
            $this->assertEquals($actualURL, $expectedURL);

            $payConfigWithAlgorithm = array(
                'public_key_id' => $this->configArray[1]['public_key_id'],
                'private_key'   => $this->configArray[1]['private_key'],
                'sandbox'       => $sandboxFlag,
                'region'        => $region,
                'algorithm'     => $this->configArray[1]['algorithm']
            );
            $client = new Client($payConfigWithAlgorithm);

            // Building URL
            $actualURL = $reflectionMethod->invoke($client);

            // Assertion 
            $this->assertEquals($actualURL, $expectedURL);

        }

        // Method used to apply reflection on method which is having abstraction
        private static function getMethod($methodName) {
            $reflectionClass = new \ReflectionClass('Amazon\Pay\API\Client');
            $reflectionMethod = $reflectionClass->getMethod($methodName);
            return $reflectionMethod;
        }

        // Method used to test the Unified Endpoint URL
        public function testCreateServiceURLForUnifiedEndpoint() {
            // Constants
            $livePublicKeyId = 'LIVE-XXXXXXXXXXXXXXXXXXXXXXXX';
            $sandboxPublicKeyId = 'SANDBOX-XXXXXXXXXXXXXXXXXXXXXXXX';

            // Testing Unified endpoint URL by passing Live specific PublicKeyId for UnitedStates
            $this->verifyUnifiedEndpoint('us', $livePublicKeyId, $this->expectedUnitedStatesURL);

            // Testing Unified endpoint URL by passing Sandbox specific PublicKeyId for UnitedStates
            $this->verifyUnifiedEndpoint('us', $sandboxPublicKeyId, $this->expectedUnitedStatesURL);

            // Testing Unified endpoint URL by passing Live specific PublicKeyId for Europe
            $this->verifyUnifiedEndpoint('eu', $livePublicKeyId, $this->expectedEuropeURL);

            // Testing Unified endpoint URL by passing Sandbox specific PublicKeyId for Europe
            $this->verifyUnifiedEndpoint('eu', $sandboxPublicKeyId, $this->expectedEuropeURL);

            // Testing Unified endpoint URL by passing Live specific PublicKeyId for Japan
            $this->verifyUnifiedEndpoint('jp', $livePublicKeyId, $this->expectedJapanURL);

            // Testing Unified endpoint URL by passing Sandbox specific PublicKeyId for Japan
            $this->verifyUnifiedEndpoint('jp', $sandboxPublicKeyId, $this->expectedJapanURL);
        }

        // Generic method used to verify Unified Endpoint
        private function verifyUnifiedEndpoint($region, $publicKeyId, $expectedURL) {
            // Configuration
            $payConfig = array(
                'public_key_id' => $publicKeyId,
                'private_key'   => $this->configArray[0]['private_key'],
                'region'        => $region,
            );
            $reflectionMethod = self::getMethod('createServiceUrl');
            $client = new Client($payConfig);
            
            // Building URL
            $actualURL = $reflectionMethod->invoke($client);

            // Assertion 
            $this->assertEquals($actualURL, $expectedURL);


        }

        // Region is optional for pure signature usage (generateButtonSignature);
        // construction must succeed without it.
        public function testRegionOmittedSucceedsForSignatureOnlyUsage() {
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
            ));

            $signature = $client->generateButtonSignature('{"storeId":"amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"}');
            $this->assertNotNull($signature);
        }

        // getPostSignedHeaders() signs an X-Amz-Pay-Region header, so unlike
        // generateButtonSignature() it does need a region; omitting it must throw
        // the same way createServiceUrl() does, not silently sign an empty header.
        public function testRegionOmittedThrowsOnGetPostSignedHeaders() {
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
            ));

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("config['region'] is a required parameter and is not set");
            $client->getPostSignedHeaders('POST', 'https://pay-api.amazon.com/sandbox/v2/charges', array(), '{}');
        }

        // Region omitted at construction must still be enforced once it is actually
        // needed, i.e. when createServiceUrl() is reached for comprehensive API usage.
        public function testRegionOmittedThrowsOnCreateServiceUrl() {
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
            ));

            $reflectionMethod = self::getMethod('createServiceUrl');

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("config['region'] is a required parameter and is not set");
            $reflectionMethod->invoke($client);
        }

        // An empty-string region is treated the same as an omitted region: allowed at
        // construction, enforced later at point of use.
        public function testRegionEmptyStringTreatedAsOmitted() {
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'region'        => '',
            ));

            $reflectionMethod = self::getMethod('createServiceUrl');

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("config['region'] is a required parameter and is not set");
            $reflectionMethod->invoke($client);
        }

        // A region that IS supplied but unrecognized must fail fast, at construction,
        // not later at the first API call.
        public function testRegionInvalidThrowsAtConstruction() {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Invalid region 'xx'; expected one of:");

            new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'region'        => 'xx',
            ));
        }

        // A non-string region must fail fast at construction with a readable message,
        // and must not trigger a PHP "Array to string conversion" warning.
        public function testRegionNonStringThrowsAtConstruction() {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage("Invalid region 'array'; expected one of:");

            new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'region'        => array('us'),
            ));
        }

        // Region values are matched case-insensitively, same as before 2.8.0.
        public function testRegionMixedCaseAccepted() {
            $reflectionMethod = self::getMethod('createServiceUrl');

            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'sandbox'       => true,
                'region'        => 'Us',
            ));
            $this->assertEquals($this->expectedUnitedStatesURL . 'sandbox/', $reflectionMethod->invoke($client));

            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $this->configArray[0]['private_key'],
                'sandbox'       => true,
                'region'        => 'EU',
            ));
            $this->assertEquals($this->expectedEuropeURL . 'sandbox/', $reflectionMethod->invoke($client));
        }

        // setupRSA() must never include caller-supplied key input in its exception message.
        public function testSetupRSAThrowsFixedMessageWithoutLeakingInput() {
            $secretLookingValue = 'SOME-SECRET-LOOKING-VALUE-NOT-A-REAL-PATH-OR-KEY';
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => $secretLookingValue,
            ));

            try {
                $client->generateButtonSignature('{}');
                $this->fail('Expected an exception for an unreadable private_key value');
            } catch (\Exception $e) {
                $this->assertStringNotContainsString($secretLookingValue, $e->getMessage());
                $this->assertEquals(
                    "Unable to load the private key file. Check that config['private_key'] is a valid file path or PEM-encoded key.",
                    $e->getMessage()
                );
            }
        }

        // A PEM key prefixed with a UTF-8 BOM must still be recognized as key content,
        // not misread as a file path.
        public function testSetupRSAAcceptsBomPrefixedKey() {
            $pem = file_get_contents(self::$privateKeyPath);
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => "\xEF\xBB\xBF" . $pem,
            ));

            $signature = $client->generateButtonSignature('{"storeId":"amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"}');
            $this->assertNotNull($signature);
        }

        // A PEM key with leading whitespace/newlines must still be recognized as key
        // content, not misread as a file path.
        public function testSetupRSAAcceptsWhitespacePrefixedKey() {
            $pem = file_get_contents(self::$privateKeyPath);
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => "\n\n  " . $pem,
            ));

            $signature = $client->generateButtonSignature('{"storeId":"amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"}');
            $this->assertNotNull($signature);
        }

        // Any "-----BEGIN" armor (not just the two previously hardcoded strings) must
        // be routed to the key parser rather than treated as a file path.
        public function testSetupRSARecognizesEncryptedPrivateKeyArmorAsContent() {
            $client = new Client(array(
                'public_key_id' => $this->configArray[0]['public_key_id'],
                'private_key'   => "-----BEGIN ENCRYPTED PRIVATE KEY-----\nnot-a-real-key\n-----END ENCRYPTED PRIVATE KEY-----",
            ));

            try {
                $client->generateButtonSignature('{}');
                $this->fail('Expected an exception for an unparseable encrypted key');
            } catch (\Exception $e) {
                // Must come from the key parser, not from the file-not-found path.
                $this->assertNotEquals(
                    "Unable to load the private key file. Check that config['private_key'] is a valid file path or PEM-encoded key.",
                    $e->getMessage()
                );
            }
        }

    }
