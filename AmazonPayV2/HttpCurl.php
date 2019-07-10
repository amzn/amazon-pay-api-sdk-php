<?php
namespace AmazonPayV2;

/* Class HttpCurl
 * Handles Curl POST function for all requests
 */

class HttpCurl
{
    const MAX_ERROR_RETRY = 3;

    private $header = false;
    private $accessToken = null;
    private $curlResponseInfo = null;
    private $headerArray = array();

    /* Takes user configuration array as input
     * Takes configuration for API call or IPN config
     */

    private function commonCurlParams($url)
    {
     	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        return $ch;
    }


    /* POST using curl for API V2 calls
     */
    private function httpPost($url, $payload, $postSignedHeaders)
    {
        // Ensure we never send the "Expect: 100-continue" header by adding
        // an 'Expect:' header to the end of the headers
        $postSignedHeaders[] = 'Expect:';

        $ch = $this->commonCurlParams($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $postSignedHeaders);

        $response = $this->execute($ch);
        return $response;
    }

    /* Execute Curl request */
    private function execute($ch)
    {
        $response = '';

        $response = curl_exec($ch);
        if ($response === false) {
            $error_msg = "Unable to post request, underlying exception of " . curl_error($ch);
            curl_close($ch);
            throw new \Exception($error_msg);
        } else {
            $this->curlResponseInfo = curl_getinfo($ch);
        }
        curl_close($ch);
        return $response;
    }

    /* invokePost takes the parameters and invokes the httpPost function to POST the parameters
     * Exponential retries on error 500 and 503
     * The response from the POST is an XML which is converted to Array
     */
    public function invokePost($url, $payload, $postSignedHeaders)
    {
        $response = array();
        $statusCode = 200;

        // Submit the request and read response body
        try {
            $shouldRetry = true;
            $retries = 0;
            do {
                try {
                    $response = $this->httpPost($url, $payload, $postSignedHeaders);
                    $curlResponseInfo = $this->curlResponseInfo;
                    $statusCode = $curlResponseInfo["http_code"];
                    $response = array(
                        'status'   => $statusCode,
                        'url'      => $url,
                        'headers'  => $postSignedHeaders,
                        'request'  => $payload,
                        'response' => $response
                    );

                    $statusCode = $response['status'];
                    if ($statusCode == 200) {
                        $shouldRetry = false;
                    } elseif ($statusCode == 500 || $statusCode == 503) {

                        $shouldRetry = true;
                        if ($shouldRetry) {
                            $this->pauseOnRetry(++$retries, $response);
                        }
                    } else {
                        $shouldRetry = false;
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            } while ($shouldRetry);
        } catch (\Exception $se) {
            throw $se;
        }

        return $response;
    }

    /* Exponential sleep on failed request
     * Up to three retries will occur if first reqest fails
     * after 1.0 second, 2.2 seconds, and finally 7.0 seconds
     * @param retries current retry
     * @throws Exception if maximum number of retries has been reached
     */
    private function pauseOnRetry($retries, $response)
    {
        if ($retries <= self::MAX_ERROR_RETRY) {
            // PHP delays are in microseconds (1 million microsecond = 1 sec)
            // 1st delay is (4^1) * 100000 + 600000 = 0.4 + 0.6 second = 1.0 sec
            // 2nd delay is (4^2) * 100000 + 600000 = 1.6 + 0.6 second = 2.2 sec
            // 3rd delay is (4^3) * 100000 + 600000 = 6.4 + 0.6 second = 7.0 sec
            $delay = (int) (pow(4, $retries) * 100000) + 600000;
            usleep($delay);
        } else {
            throw new \Exception('Error Code: '. $response['status'] . ' - Maximum number of retry attempts - '
              . $retries . " reached\n" . $response['response'] . "\n");
        }
    }

}
