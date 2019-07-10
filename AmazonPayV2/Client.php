<?php
    namespace AmazonPayV2;

    use phpseclib\Crypt\RSA;

    require_once 'ClientInterface.php';
    require_once 'HttpCurl.php';
 
    class Client implements ClientInterface
    {
        const SDK_VERSION = '4.1.3';
        const HASH_ALGORITHM = 'sha256';
        const AMAZON_SIGNATURE_ALGORITHM = 'AMZN-PAY-RSASSA-PSS';

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

                    return 'https://' . $apiEndpointUrl . '/' . $modePath . '/';
                } else {
                    throw new \Exception($region . ' is not a valid region');
                }
            } else {
                throw new \Exception("config['region'] is a required parameter and is not set");
            }
        }

        /* canonicalURI
        *  Strips the http and https off the URL
        *  Strips the Host off the URL
        *  Removes data after ?
        *  Replaces special characters with their URL encoding
        */
        private function getCanonicalURI($unEncodedURI)
        {
            if ($unEncodedURI == '') {
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
                    foreach ($this->subArrays($val, "$key") as $newKey => $subVal) {
                        $sortedCanonicalArray["$newKey"] = $subVal;
                    }
                }
                else if ((is_null($val)) || ($val == '')) {}
                else {
                    $sortedCanonicalArray["$key"] = $val;
                }
            }
            ksort($sortedCanonicalArray);

            return $sortedCanonicalArray;
        }

        /* subArrays - helper function used to break out arays in an array */
        private function subArrays($parameters, $catagory)
        {
            $categoryIndex = 0;
            $newParameters = array();
            $categoryString = "$catagory.";
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

        /* Convert paremeters to Url encoded query string */
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
            return bin2hex(hash($hash, $data, true));
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
                if ((is_null($val)) || ($val == '')) {}
                else {
                    $sortedCanonicalArray[strtolower("$key")] = $val;
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
            $headerNames = implode(';', $parameters);

            return $headerNames;
        }

        /* getHost
        *  Returns the host
        */
        private function getHost($unEncodedURI)
        {
            if ($unEncodedURI == '') {
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
        *  If it is array it returns all the values as a string
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
        /* Protected because of PSP module usaged */
        protected function constructUserAgentHeader()
        {
            return 'amazon-pay-sdk-php/' . self::SDK_VERSION . ' ('
                . 'PHP/' . phpversion() . '; '
                . php_uname('s') . '/' . php_uname('m') . '/' . php_uname('r') . ')';
        }

        /* checkForPaymentCriticalDataAPI - Takes the request uri and request payload, checks for
        API names and modifies the payload if needed
        @param $request_uri [String]
        @param $http_request_method [String]
        @param $request_payload [String]
        */
        private function checkForPaymentCriticalDataAPI($request_uri, $http_request_method, $request_payload) {
            $paymentCriticalDataAPIs = array('/live/account-management/v1/accounts', '/sandbox/account-management/v1/accounts');
            $allowedHttpMethods = array('POST', 'PUT', 'PATCH');

            // For APIs handling payment critical data, the payload shouldn't be
            // considered in the signature calculation
            foreach($paymentCriticalDataAPIs as $api) {
                if (strpos($request_uri, $api) !== false && in_array($http_request_method, $allowedHttpMethods)) {
                    return '';
                }
            }
            return $request_payload;
        }

        /* getPostSignedHeaders convenience – Takes values for canonical request, creates a signature and  
        * returns an array of headers to be sent 
        * @param $http_request_method [String] 
        * @param $request_uri [String] 
        * @param $request_parameters [Array()]
        * @param $request_payload [String]
        */
        public function getPostSignedHeaders($http_request_method, $request_uri, $request_parameters, $request_payload)
        {
            $request_payload = $this->checkForPaymentCriticalDataAPI($request_uri, $http_request_method, $request_payload);

            $preSignedHeaders = array();
            $preSignedHeaders['accept'] = 'application/json';
            $preSignedHeaders['content-type'] = 'application/json';
            $preSignedHeaders['X-Amz-Pay-Region'] = $this->config['region'];

            $timeStamp = $this->getFormattedTimestamp();
            $signature = $this->createSignature($http_request_method, $request_uri, $request_parameters, $preSignedHeaders, $request_payload, $timeStamp);
            $public_key_id = $this->config['public_key_id'];

            $headers = $this->getCanonicalHeaders($preSignedHeaders);
            $headers['X-Amz-Pay-Date'] = $timeStamp;
            $headers['X-Amz-Pay-Host'] = $this->getHost($request_uri);
            $signedHeaders = "SignedHeaders=" . $this->getCanonicalHeadersNames($headers) . ", Signature=" . $signature;
            
            $headerArray = array(
                'accept' => $this->stringFromArray($headers['accept']),
                'content-type' => $this->stringFromArray($headers['content-type']),
                'x-amz-pay-host' => $this->getHost($request_uri),
                'x-amz-pay-date' => $timeStamp,
                'x-amz-pay-region' => $this->config['region'],
                'authorization' => self::AMAZON_SIGNATURE_ALGORITHM . " PublicKeyId=" . $public_key_id . ", " . $signedHeaders,
                'user-agent' => $this->constructUserAgentHeader()
            );

            ksort($headerArray);
            foreach ($headerArray as $key => $value) {
                $queryParameters[] = $key . ':' . $value;
            }

            return $queryParameters;
        }

        /* createSignature convenience – Takes values for canonical request sorts and parses it and  
        * returns a signature for the request being sent 
        * @param $http_request_method [String] 
        * @param $request_uri [String] 
        * @param $request_parameters [Array()]
        * @param $pre_signed_headers [Array()]
        * @param $request_payload [String]
        * @param $timeStamp [String]
        */
        public function createSignature($http_request_method, $request_uri, $request_parameters, $pre_signed_headers, $request_payload, $timeStamp)
        {
            $rsa = new RSA();
            $rsa->setHash(self::HASH_ALGORITHM);
            $rsa->setMGFHash(self::HASH_ALGORITHM);
            $rsa->setSaltLength(20);

            $private_key = $this->config['private_key'];
            
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

            $hashedCanonicalRequest = self::AMAZON_SIGNATURE_ALGORITHM . "\n" . $this->hexAndHash($canonicalRequest);

            if (strpos($private_key, 'BEGIN RSA PRIVATE KEY') === false) {
                $rsa->loadKey(file_get_contents($private_key));
            } else {
                $rsa->loadKey($private_key);
            }
            $signature = base64_encode($rsa->sign($hashedCanonicalRequest));
            
            return $signature;
        }


        /* Signs and executes REST API call POST operation for arbitrary Amazon Pay v2 API
         *
         * @param urlFragment - [String] (e.g. 'v1/deliveryTrackers')
         * @param payload - [String in JSON format] or [array]
         * @optional authToken - [String]
         */
        public function apiPost($urlFragment, $payload, $authToken = null) {
            if (is_array($payload)) {
                $payload = json_encode($payload);
            }

            $url = $this->createServiceUrl() . $urlFragment;
            $requestParameters = array();

            $postSignedHeaders = $this->getPostSignedHeaders('POST', $url, $requestParameters, $payload);
            if (isset($authToken)) {
                $postSignedHeaders[] = 'x-amz-pay-authtoken:' . $authToken;
            }

            $httpCurlRequest = new HttpCurl();
            $response = $httpCurlRequest->invokePost($url, $payload, $postSignedHeaders);
            return $response;
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


        /* deliveryTrackers API call - Provides shipment tracking information for Alexa
         *
         * @param payload - [String in JSON format] or [array]
         * @optional authToken - [String]
         */
        public function deliveryTrackers($payload, $authToken = null)
        {
            // Current implementation on deliveryTrackers API does not support the use of auth token
            return $this->apiPost('v1/deliveryTrackers', $payload, $authToken);
        }


        /* In-Store merchantScan API call - Generates Charge Permission ID from Amazon App's Amazon Pay QR Code
         *
         * @param payload - [String in JSON format] or [array]
         * @optional authToken - [String]
         */
        public function instoreMerchantScan($payload, $authToken = null)
        {
            return $this->apiPost('in-store/v1/merchantScan', $payload, $authToken);
        }


        /* In-Store charge API call - Performs Charge on a Charge Permission ID
         *
         * @param payload - [String in JSON format] or [array]
         * @optional authToken - [String]
         */
        public function instoreCharge($payload, $authToken = null)
        {
            return $this->apiPost('in-store/v1/charge', $payload, $authToken);
        }


        /* In-Store refund API call - Peforms Refund on a Charge ID                   
         *
         * @param payload - [String in JSON format] or [array]
         * @optional authToken - [String]
         */
        public function instoreRefund($payload, $authToken = null)
        {
            return $this->apiPost('in-store/v1/refund', $payload, $authToken);
        }

    }
?>
