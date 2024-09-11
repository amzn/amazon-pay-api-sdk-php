<?php

namespace Amazon\Pay\API;

/* Interface class to showcase the public API methods for Amazon Pay */

interface AccountManagementClientInterface
{
    // ----------------------------------- Account Management APIs-----------------------------------

    /* Amazon Checkout v2 - Create Merchant Account
         *
         * Provide merchant info through this API to create loginable account for your merchant partners. Client should expect either a success message or a detailed error message based on data validation and fulfillment.
         *
         * @param payload - [String in JSON format] or [array]
         * @param headers - [indexed array of string key-value pairs]
         */
    public function createMerchantAccount($payload, $headers);

    /* Amazon Checkout v2 - Update Amazon Pay Merchant Account
         *
         * Updates a merchant account and store for the given Amazon merchantAccountId. Partners are only able to update fields which do not change the legal business entity itself.
         *
         * @param merchantAccountId - [String] - Merchant Account ID
         * @param payload - [String in JSON format] or [array]
         * @param headers - [array] - requires x-amz-pay-authToken header
         */
    public function updateMerchantAccount($merchantAccountId, $payload, $headers);

    /* Amazon Checkout v2 - Claim Amazon Pay Merchant Account
         *
         * Initiates the merchant account claim process. Clients should expect a redirection response or a detailed error message based on data validation and fulfillment. 
         *
         * @param merchantAccountId - [String] - Merchant Account ID
         * @param payload - [String in JSON format] or [array]
         * @optional headers - [indexed array of string key-value pairs]
         */
    public function claimMerchantAccount($merchantAccountId, $payload, $headers = null);
}
