<?php
namespace AmazonPayV2;

/* Interface class to showcase the public API methods for Amazon Pay */

interface ClientInterface
{

    // ----------- Comprehensive SDK API calls -----------

    /* Setter for sandbox
     * @param value - [boolean]
     * Sets the boolean value for config['sandbox'] variable
     */
    public function setSandbox($value);


    /* deliveryTrackers API call - Provides shipment tracking information for Alexa
     *
     * @param payload - [String in JSON format] or [array]
     * @optional authToken - [String]
     */
    public function deliveryTrackers($payload, $authToken = null);

    /* In-Store merchantScan API call - Generates Charge Permission ID from Amazon App's Amazon Pay QR Code
     *
     * @param payload - [String in JSON format] or [array]
     * @optional authToken - [String]
     */
    public function instoreMerchantScan($payload, $authToken = null);


    /* In-Store charge API call - Performs Charge on a Charge Permission ID
     *
     * @param payload - [String in JSON format] or [array]
     * @optional authToken - [String]
     */
    public function instoreCharge($payload, $authToken = null);


    /* In-Store refund API call - Peforms Refund on a Charge ID
     *
     * @param payload - [String in JSON format] or [array]
     * @optional authToken - [String]
     */
    public function instoreRefund($payload, $authToken = null);


    /* Getter for config attribute
     * Gets the value for the key if the key exists in config
     */
    public function __get($name);

    // ----------- Signature SDK API calls -----------

    /* Signs and executes REST API call POST operation for arbitrary Amazon Pay v2 API
     *
     * @param urlFragment - [String] (e.g. 'v1/deliveryTrackers')
     * @param payload - [String in JSON format] or [array]
     * @optional authToken - [String]
     */
    public function apiPost($urlFragment, $payload, $authToken = null);


    /* getPostSignedHeaders convenience – Takes values for canonical request, creates a signature and  
     * returns an array of headers to be sent 
     * @param $http_request_method [String] 
     * @param $request_uri [String] 
     * @param $request_parameters [Array()]
     * @param $request_payload [String]
     */
    public function getPostSignedHeaders($http_request_method, $request_uri, $request_parameters, $request_payload);


    /* createSignature convenience – Takes values for canonical request sorts and parses it and  
     * returns a signature for the request being sent 
     * @param $http_request_method [String] 
     * @param $request_uri [String] 
     * @param $request_parameters [Array()]
     * @param $pre_signed_headers [Array()]
     * @param $request_payload [String]
     * @param $timeStamp [String]
     */
    public function createSignature($http_request_method, $request_uri, $request_parameters, $pre_signed_headers, $request_payload, $timeStamp);

}

?>
