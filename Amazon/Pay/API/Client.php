<?php
    // See ClientInterface.php for public function documentation

    namespace Amazon\Pay\API;

    use phpseclib3\Crypt\RSA;

    require_once 'ClientInterface.php';
    require_once 'ReportingClientInterface.php';
    require_once 'HttpCurl.php';
 
    class Client implements ClientInterface, ReportingClientInterface, MerchantOnboardingClientInterface
    {
        const SDK_VERSION = '2.6.4';
        const SDK_LANGUAGE = 'PHP';
        const HASH_ALGORITHM = 'sha256';
        const API_VERSION = 'v2';
        const ACCOUNT_MANAGEMENT = '/merchantAccounts';
        
        private $config = array();

        private $apiServiceUrls = array(
            'eu' => 'pay-api.amazon.eu',
            'na' => 'pay-api.amazon.com',
            'jp' => 'pay-api.amazon.jp');

        private $regionMappings = array(
            'eu' => 'eu',
            'de' => 'eu',
            'uk' => 'eu',
            'us' => 'na',
            'na' => 'na',
            'jp' => 'jp');

        public function __construct($config = null) {
            if (isset($config)) {

                if (!is_array($config)) {
                    throw new \Exception('$config is of the incorrect type ' . gettype($config) . ' and should be of the type array');
                }

                //V2 Algorithm accepts only 'eu', 'na' and 'jp' as region
                $config['region'] =  $this->regionMappings[strtolower($config['region'])];
                $this->config = $config;

                if (!empty($config['sandbox'])) {
                    // setSandbox verifies boolean
                    $this->setSandbox($config['sandbox']);
                } else {
                    $this->setSandbox(false);
                }

            } else {
                throw new \Exception('Expecting config array{region, sandbox, public_key_id, private_key}', 1);
            }
        }

        /* Getter
        *  Gets the value for the key if the key exists in config
        */
        public function __get($name)
        {
            if (array_key_exists(strtolower($name), $this->config)) {
                return $this->config[strtolower($name)];
            } else {
                throw new \Exception('Key ' . $name . ' is either not a part of the configuration array config or the ' . $name . ' does not match the key name in the config array', 1);
            }
        }

        /* Get signature algorithm from config, use AMZN-PAY-RSASSA-PSS by default if not specified */
        private function getAlgorithm() {
            if (!empty($this->config['algorithm'])) {
                if ($this->config['algorithm'] === 'AMZN-PAY-RSASSA-PSS' || $this->config['algorithm'] === 'AMZN-PAY-RSASSA-PSS-V2') {
                    return $this->config['algorithm'];
                } else {
                    throw  new \Exception($this->config['algorithm'] . ' is not a valid algorithm');
                }
            } else {
                return 'AMZN-PAY-RSASSA-PSS';
            }
        }

        /* Create API service URL and the Endpoint path */
        private function createServiceUrl()
        {
            // Comprehensive SDK use requires 'sandbox' and 'region' parameters to be specified in the config array
            // These attributes are not needed for Signature-only SDK usage

            $modePath = strtolower($this->config['sandbox']) ? 'sandbox' : 'live';

            if (!empty($this->config['region'])) {
                $region = strtolower($this->config['region']);
                if (array_key_exists($region, $this->regionMappings)) {

                    if (isset($this->config['override_service_url'])) {
                        $apiEndpointUrl  = preg_replace("(https?://)", "", $this->config['override_service_url']);
                    } else {
                        $apiEndpointUrl  = $this->apiServiceUrls[$this->regionMappings[$region]];
                    }
                    if($this->isEnvSpecificPublicKeyId()){
                        return 'https://' . $apiEndpointUrl . '/';
                    } 
                    return 'https://' . $apiEndpointUrl . '/' . $modePath . '/';
                } else {
                    throw new \Exception($region . ' is not a valid region');
                }
            } else {
                throw new \Exception("config['region'] is a required parameter and is not set");
            }
        }
        
        // Method used to validate whether PublicKeyId starts with prefix LIVE or SANDBOX
        private function isEnvSpecificPublicKeyId() {
            return $this->startsWith($this->config['public_key_id'], 'LIVE') || $this->startsWith($this->config['public_key_id'], 'SANDBOX');
        }

        // Method used to validate whether String starts with Prefix or not
        private function startsWith($string, $prefix)
        {   
            $length = strlen($prefix);
            return (substr(strtoupper($string), 0, $length) === $prefix);
        }

        /* canonicalURI
        *  Strips the http and https off the URL
        *  Strips the Host off the URL
        *  Removes data after ?
        *  Replaces special characters with their URL encoding
        */
        private function getCanonicalURI($unEncodedURI)
        {
            if ($unEncodedURI === '') {
                return '/';
            }
            $urlArray = parse_url($unEncodedURI);
            if (empty($urlArray['path'])) {
                return '/';
            }
            else {
                return $urlArray['path'];
            }
        }

        /* sortCanonicalArray
        *  Sorts given array 
        *  Breaks out values in array that are arrays themselves and 
            returns a new array with that data
        *  Creates new sorted array with all data
        */
        private function sortCanonicalArray($canonicalArray)
        {
            $sortedCanonicalArray = array();
            foreach ($canonicalArray as $key => $val) {
                if (is_array($val)) {
                    foreach ($this->subArrays($val, $key) as $newKey => $subVal) {
                        $sortedCanonicalArray[$newKey] = $subVal;
                    }
                }
                else if ((is_null($val)) || ($val === '')) {}
                else {
                    $sortedCanonicalArray[$key] = $val;
                }
            }
            ksort($sortedCanonicalArray);

            return $sortedCanonicalArray;
        }

        /* subArrays - helper function used to break out arrays in an array */
        private function subArrays($parameters, $category)
        {
            $categoryIndex = 0;
            $newParameters = array();
            $categoryString = "$category.";
            foreach ($parameters as $value) {
                $categoryIndex++;
                $newParameters[$categoryString . $categoryIndex] = $value;
            }

            return $newParameters;
        }

        /* createCanonicalQuery
        *  Returns a string of request parameters
        */
        private function createCanonicalQuery($requestParameters)
        {
            $sortedRequestParameters = $this->sortCanonicalArray($requestParameters);
            return $this->getParametersAsString($sortedRequestParameters);
        }

        /* Convert parameters to Url encoded query string */
        private function getParametersAsString(array $parameters)
        {
            $queryParameters = array();
            foreach ($parameters as $key => $value) {
                $queryParameters[] = $key . '=' . $this->urlEncode($value);
            }
            return implode('&', $queryParameters);
        }

        private function urlEncode($value)
        {
            return str_replace('%7E', '~', rawurlencode($value));
        }
    
        /* HexEncode and hash data */
        private function hexAndHash($data)
        {
            $hash = self::HASH_ALGORITHM;
            return bin2hex(hash($hash, isset($data) ? $data : '', true));
        }
    
        /* Formats date as ISO 8601 timestamp */
        private function getFormattedTimestamp()
        {
            return gmdate("Ymd\THis\\Z", time());
        }

        private function getCanonicalHeaders($headers)
        {
            $sortedCanonicalArray = array();
            foreach ($headers as $key => $val) {
                if ((is_null($val)) || ($val === '')) {}
                else {
                    $sortedCanonicalArray[strtolower($key)] = $val;
                }
            }
            ksort($sortedCanonicalArray);

            return $sortedCanonicalArray;
        }

        /* getCanonicalHeaderNames
        *  Returns a string of the header names
        */
        private function getCanonicalHeadersNames($headers)
        {
            $sortedHeader = $this->getCanonicalHeaders($headers);
            foreach (array_keys($sortedHeader) as $key) {
                $parameters[] = $key;
            }
            ksort($parameters);
            return implode(';', $parameters);
        }

        /* getHost
        *  Returns the host
        */
        private function getHost($unEncodedURI)
        {
            if ($unEncodedURI === '') {
                return '/';
            }

            $urlArray = parse_url($unEncodedURI);
            if (empty($urlArray['host'])) {
                return '/';
            }
            else {
                return $urlArray['host'];
            }
        }

        /* getHeaderString
        *  Returns the Canonical Headers as a string
        */
        public function getHeaderString($headers)
        {
            $queryParameters = array();
            $sortedHeaders = $this->getCanonicalHeaders($headers);
            
            foreach ($sortedHeaders as $key => $value) {
                if (is_array($value)) {
                    $value = $this->collectSubVals($value);
                }
                else {
                    $queryParameters[] = $key . ':' . $value;
                }
            }
            $returnString = implode("\n", $queryParameters);

            return $returnString . "\n";
        }

        /* collectSubVals
        *  Helper function to take an array and return the values as a string
        */
        private function collectSubVals($parameters)
        {
            $categoryIndex = 0;
            $collectedValues = '';
            
            foreach ($parameters as $value) {
                if ($categoryIndex != 0) {
                    $collectedValues .= ' ';
                }
                $collectedValues .= $value;
                $categoryIndex++;
            }

            return $collectedValues;
        }

        /* stringFromArray - helper function used to check if parameters is an array. 
        *  If it is an array it returns all the values as a string
        *  Otherwise it returns parameters
        */
        private function stringFromArray($parameters)
        {
            if (is_array($parameters)) {
                return $this->collectSubVals($parameters);
            }
            else {
                return $parameters;
            }
        }

        /* Create the User Agent Header sent with the POST request */
        /* Protected because of PSP module usage */
        protected function constructUserAgentHeader()
        {
            return 'amazon-pay-api-sdk-php/' . self::SDK_VERSION . ' ('
                . 'PHP/' . phpversion() . '; '
                . php_uname('s') . '/' . php_uname('m') . '/' . php_uname('r') . ')';
        }

        public function getPostSignedHeaders($http_request_method, $request_uri, $request_parameters, $request_payload, $other_presigned_headers = null)
        {
            $preSignedHeaders = array();
            $preSignedHeaders['accept'] = 'application/json';
            $preSignedHeaders['content-type'] = 'application/json';
            $preSignedHeaders['X-Amz-Pay-Region'] = $this->config['region'];

            // Header x-amz-pay-idempotency-key is a special user-supplied header that must be pre-signed if used
            if (isset($other_presigned_headers)) {
                foreach ($other_presigned_headers as $key => $val) {
                    if (strtolower($key) === 'x-amz-pay-idempotency-key') {
                        if (isset($val) && ($val !== '')) {
                            $preSignedHeaders['x-amz-pay-idempotency-key'] = $val;
                        }
                    }
                }
            }

            $timeStamp = $this->getFormattedTimestamp();
            $signature = $this->createSignature($http_request_method, $request_uri, $request_parameters, $preSignedHeaders, $request_payload, $timeStamp);
            $public_key_id = $this->config['public_key_id'];

            $headers = $this->getCanonicalHeaders($preSignedHeaders);
            $headers['X-Amz-Pay-Date'] = $timeStamp;
            $headers['X-Amz-Pay-Host'] = $this->getHost($request_uri);
            $signedHeaders = "SignedHeaders=" . $this->getCanonicalHeadersNames($headers) . ", Signature=" . $signature;

            // Do not add x-amz-pay-idempotency-key header here, as user-supplied headers get added later
            $headerArray = array(
                'accept' => $this->stringFromArray($headers['accept']),
                'content-type' => $this->stringFromArray($headers['content-type']),
                'x-amz-pay-host' => $this->getHost($request_uri),
                'x-amz-pay-date' => $timeStamp,
                'x-amz-pay-region' => $this->config['region'],
                'x-amz-pay-sdk-type' => self::SDK_LANGUAGE,
                'x-amz-pay-sdk-version' => self::SDK_VERSION,
                'x-amz-pay-language-version' => PHP_VERSION,
                'authorization' => $this->getAlgorithm() . " PublicKeyId=" . $public_key_id . ", " . $signedHeaders,
                'user-agent' => $this->constructUserAgentHeader()
            );

            if(isset($this->config['integrator_id'])){
                $headerArray['x-amz-pay-integrator-id'] = $this->config['integrator_id'];
            }

            if(isset($this->config['integrator_version'])){
                $headerArray['x-amz-pay-integrator-version'] = $this->config['integrator_version'];
            }

            if(isset($this->config['platform_version'])){
                $headerArray['x-amz-pay-platform-version'] = $this->config['platform_version'];
            }

            ksort($headerArray);
            foreach ($headerArray as $key => $value) {
                $queryParameters[] = $key . ':' . $value;
            }

            return $queryParameters;
        }

        public function createSignature($http_request_method, $request_uri, $request_parameters, $pre_signed_headers, $request_payload, $timeStamp)
        {
            $rsa = $this->setupRSA();

            $pre_signed_headers['X-Amz-Pay-Date'] = $timeStamp;
            $pre_signed_headers['X-Amz-Pay-Host'] = $this->getHost($request_uri);

            $hashedPayload = $this->hexAndHash($request_payload);
            $canonicalURI = $this->getCanonicalURI($request_uri);
            $canonicalQueryString = $this->createCanonicalQuery($request_parameters);
            $canonicalHeader = $this->getHeaderString($pre_signed_headers);
            $signedHeaders = $this->getCanonicalHeadersNames($pre_signed_headers);
            
            $canonicalRequest = (
                $http_request_method . "\n" .
                $canonicalURI . "\n" .
                $canonicalQueryString . "\n" .
                $canonicalHeader . "\n" .
                $signedHeaders . "\n" .
                $hashedPayload
            );

            $hashedCanonicalRequest = $this->getAlgorithm() . "\n" . $this->hexAndHash($canonicalRequest);

            $signature = $rsa->sign($hashedCanonicalRequest);
            if ($signature === false) {
                throw new \Exception('Unable to sign request, is your RSA private key valid?');
            }

            return base64_encode($signature);
        }


        public function generateButtonSignature($payload) {
            $rsa = $this->setupRSA();

            // if array is passed in, developer will need to ensure same json_encode function is used on website,
            // otherwise the signed payload may not match if a different JSON to string function is used which may
            // generate a string with different whitespace
            if (is_array($payload)) {
                $payload = json_encode($payload);
            }

            // stripcslashes function is used on payload to unescape sequences like http:\/\/ to http://
            // and \"hello\" to "hello"
            $hashedButtonRequest = $this->getAlgorithm() . "\n" . $this->hexAndHash(isset($payload) ? stripcslashes($payload) : '');
            $signature = $rsa->sign($hashedButtonRequest);
            if ($signature === false) {
                throw new \Exception('Unable to sign request, is your RSA private key valid?');
            }

            return base64_encode($signature);
        }


        private function setupRSA() {
            $key_spec = $this->config['private_key'];
            $salt_length = $this->getAlgorithm() === 'AMZN-PAY-RSASSA-PSS' ? 20 : 32;
            if ((strpos($key_spec, 'BEGIN RSA PRIVATE KEY') === false) && (strpos($key_spec, 'BEGIN PRIVATE KEY') === false)) {
                $contents = file_get_contents($key_spec);
                if ($contents === false) {
                    throw new \Exception('Unable to load file: ' . $key_spec);
                }
                $rsa = RSA::loadPrivateKey($contents)->withSaltLength($salt_length);
            } else {
                $rsa = RSA::loadPrivateKey($key_spec)->withSaltLength($salt_length);
            }
            return $rsa;
        }


        public function testPrivateKeyIntegrity() {
            echo "Testing private key: ";

            $rsa = $this->setupRSA();

            for ($i = 0; $i < 100; $i++) {
                echo ($i+1) . " ";
                $rsa->sign("TESTING PRIVATE KEY");
            }
            echo "[Passed]\n";

            return true;
        }

        private function enhanceResponseWithShippingAddressList( &$response ) {
            $responsePayload = json_decode($response['response'], true);

            if(isset($responsePayload['shippingAddressList'])) {
                $responsePayload['shippingAddressList'] = array_map(function ($shippingAddress) {
                    return json_decode($shippingAddress, true);
                }, $responsePayload['shippingAddressList']);
                $response['response'] = json_encode($responsePayload, JSON_UNESCAPED_UNICODE);
            }

            return $response;
        }

        public function apiCall($method, $urlFragment, $payload, $headers = null, $queryParams = null) {
            if (is_array($payload)) {

                // json_encode will fail if non-UTF-8 encodings are present, need to convert them to UTF-8
                array_walk_recursive($payload, function (&$item) {
                    if (is_string($item) && mb_detect_encoding($item, 'UTF-8', true) === false) {
                        $item = utf8_encode($item);
                    }
                });

                $payload = json_encode($payload);
            }

            $url = $this->createServiceUrl() . $urlFragment;
            if (isset($queryParams)) {
                if (!is_array($queryParams)) {
                    throw new \Exception('queryParameters must be a key-value array; e.g. array(\'accountId\' => \'ABCD1234XYZIJK\')');
                }
                $url = $url . '?' . $this->createCanonicalQuery($queryParams);
                $requestParameters = $queryParams;
            } else {
                $requestParameters = array();
            }

            $postSignedHeaders = $this->getPostSignedHeaders($method, $url, $requestParameters, $payload, $headers);
            if (isset($headers)) {
                if (!is_array($headers)) {
                    throw new \Exception('headers must be a key-value array; e.g. array(\'x-amz-pay-authtoken\' => \'abcd1234xyzIJK\')');
                }
                foreach ($headers as $key => $value) {
                    $postSignedHeaders[] = $key . ':' . $value;
                }
            }

            $httpCurlRequest = new HttpCurl(isset($this->config['proxy']) ? $this->config['proxy'] : []);
            return $httpCurlRequest->invokeCurl($method, $url, $payload, $postSignedHeaders);
        }

        /* Setter for sandbox
         * @param value - [boolean]
         * Sets the boolean value for config['sandbox'] variable
         */
        public function setSandbox($value)
        {
            if (is_bool($value)) {
                $this->config['sandbox'] = $value;
            } else {
                throw new \Exception('sandbox value ' . $value . ' is of type ' . gettype($value) . ' and should be a boolean value');
            }
        }

        // ----------------------------------- DELIVERY NOTIFICATIONS API -----------------------------------


        public function deliveryTrackers($payload, $headers = null)
        {
            // Current implementation on deliveryTrackers API does not support the use of auth token
            return $this->apiCall('POST', self::API_VERSION . '/deliveryTrackers', $payload, $headers);
        }


        // ----------------------------------- AUTHORIZATION TOKENS API -----------------------------------

        public function getAuthorizationToken($mwsAuthToken, $merchantId, $headers = null)
        {

            $queryParams =  array('merchantId' => $merchantId);
            return $this->apiCall('GET', self::API_VERSION . '/authorizationTokens/' . $mwsAuthToken, null, $headers, $queryParams);
        }


        // ----------------------------------- IN-STORE API -----------------------------------


        public function instoreMerchantScan($payload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . '/in-store/merchantScan', $payload, $headers);
        }


        public function instoreCharge($payload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . '/in-store/charge', $payload, $headers);
        }


        public function instoreRefund($payload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . '/in-store/refund', $payload, $headers);
        }


        // ----------------------------------- Amazon Checkout v2 API -----------------------------------


        public function getBuyer($buyerToken, $headers = null) {
            return $this->apiCall('GET', self::API_VERSION . '/buyers/' . $buyerToken, null, $headers);
        }

        public function createCheckoutSession($payload, $headers)
        {
            return $this->apiCall('POST', self::API_VERSION . '/checkoutSessions', $payload, $headers);
        }


        public function getCheckoutSession($checkoutSessionId, $headers = null)
        {
            $response = $this->apiCall('GET', self::API_VERSION . '/checkoutSessions/' . $checkoutSessionId, null, $headers);
            return $this->enhanceResponseWithShippingAddressList($response);
        }


        public function updateCheckoutSession($checkoutSessionId, $payload, $headers = null)
        {
            $response = $this->apiCall('PATCH', self::API_VERSION . '/checkoutSessions/' . $checkoutSessionId, $payload, $headers);
            return $this->enhanceResponseWithShippingAddressList($response);
        }


        public function completeCheckoutSession($checkoutSessionId, $payload, $headers = null)
        {
            $response =  $this->apiCall('POST', self::API_VERSION . '/checkoutSessions/' . $checkoutSessionId . '/complete', $payload, $headers);
            return $this->enhanceResponseWithShippingAddressList($response);
        }


        public function getChargePermission($chargePermissionId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/chargePermissions/' . $chargePermissionId, null, $headers);
        }


        public function updateChargePermission($chargePermissionId, $payload, $headers = null)
        {
            return $this->apiCall('PATCH', self::API_VERSION . '/chargePermissions/' . $chargePermissionId, $payload, $headers);
        }


        public function closeChargePermission($chargePermissionId, $payload, $headers = null)
        {
            return $this->apiCall('DELETE', self::API_VERSION . '/chargePermissions/' . $chargePermissionId . '/close', $payload, $headers);
        }


        public function createCharge($payload, $headers)
        {
            return $this->apiCall('POST', self::API_VERSION . '/charges', $payload, $headers);
        }


        public function getCharge($chargeId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/charges/' . $chargeId, null, $headers);
        }


        public function captureCharge($chargeId, $payload, $headers)
        {
            return $this->apiCall('POST', self::API_VERSION . '/charges/' . $chargeId  . '/capture', $payload, $headers);
        }


        public function cancelCharge($chargeId, $payload, $headers = null)
        {
            return $this->apiCall('DELETE', self::API_VERSION . '/charges/' . $chargeId  . '/cancel', $payload, $headers);
        }


        public function createRefund($payload, $headers)
        {
            return $this->apiCall('POST', self::API_VERSION . '/refunds', $payload, $headers);
        }


        public function getRefund($refundId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/refunds/' . $refundId, null, $headers);
        }

        // ----------------------------------- CV2 REPORTING APIS -----------------------------------
 
 
        public function getReports($queryParameters = null, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/reports', null, $headers, $queryParameters);
        }
 
 
        public function getReportById($reportId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/reports/' . $reportId, null, $headers);
        }
 
 
        public function getReportDocument($reportDocumentId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/report-documents/' . $reportDocumentId, null, $headers);
        }
 
 
        public function getReportSchedules($reportTypes = null, $headers = null)
        {
            $queryParameters = array('reportTypes' => $reportTypes);
            return $this->apiCall('GET', self::API_VERSION . '/report-schedules', null, $headers, $queryParameters);
        }
 
 
        public function getReportScheduleById($reportScheduleId, $headers = null)
        {
            return $this->apiCall('GET', self::API_VERSION . '/report-schedules/' . $reportScheduleId, null, $headers);
        }
 
 
        public function createReport($requestPayload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . '/reports' , $requestPayload, $headers);
        }
 
 
        public function createReportSchedule($requestPayload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . '/report-schedules' , $requestPayload, $headers);
        }
 
 
        public function cancelReportSchedule($reportScheduleId, $headers = null) 
        {
            return $this->apiCall('DELETE', self::API_VERSION . '/report-schedules/' . $reportScheduleId, null, $headers);
        }

        /*
        * FinalizeCheckoutSession API which enables Pay to validate payment critical attributes and also update book-keeping attributes present in merchantMetadata 
        */
        public function finalizeCheckoutSession($checkoutSessionId, $payload, $headers = null)
        {
            return $this->apicall('POST', self::API_VERSION . '/checkoutSessions/' . $checkoutSessionId . '/finalize', $payload , $headers);
        }

        // ----------------------------------- Merchant Onboarding & Account Management APIs -----------------------------------

        public function registerAmazonPayAccount($payload, $headers = null)
        {
            return $this->apiCall('POST', self::API_VERSION . self::ACCOUNT_MANAGEMENT, $payload, $headers);
        }

        public function updateAmazonPayAccount($merchantAccountId, $payload, $headers = null)
        {
            return $this->apiCall('PATCH', self::API_VERSION . self::ACCOUNT_MANAGEMENT . '/' . $merchantAccountId, $payload, $headers);
        }

        public function deleteAmazonPayAccount($merchantAccountId, $headers = null)
        {
            return $this->apiCall('DELETE', self::API_VERSION . self::ACCOUNT_MANAGEMENT . '/' . $merchantAccountId, null, $headers);
        }
    }
