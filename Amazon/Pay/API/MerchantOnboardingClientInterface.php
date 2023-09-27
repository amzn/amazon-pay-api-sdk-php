<?php
    namespace Amazon\Pay\API;

    /* Interface class to showcase the public API methods for Amazon Pay */
    interface MerchantOnboardingClientInterface
    {
        // ----------------------------------- Merchant Onboarding & Account Management APIs --------------------

        /* Amazon Checkout v2 - Register Amazon Pay Account
         *
         * Creates a non-logginable account for your merchant partners. These would be special accounts through which Merchants would not be able to login to Amazon or access Seller Central.
         *
         * @param payload - [String in JSON format] or [array]
         * @optional headers - [indexed array of string key-value pairs]
         */
        public function registerAmazonPayAccount($payload, $headers = null);

        /* Amazon Checkout v2 - Update Amazon Pay Account
         *
         * Updates a merchant account for the given Merchant Account ID. We would be allowing our partners to update only a certain set of fields which won’t change the legal business entity itself.
         *
         * @param merchantAccountId - [String] - Merchant Account ID
         * @param payload - [String in JSON format] or [array]
         * @optional headers - [indexed array of string key-value pairs]
         */
        public function updateAmazonPayAccount($merchantAccountId, $payload, $headers = null);

        /* Amazon Checkout v2 - Delete Amazon Pay Account
         *
         * Deletes the Merchant account for the given Merchant Account ID. Partners can close the merchant accounts created for their merchant partners.
         *
         * @param merchantAccountId - [String] - Merchant Account ID
         * @optional headers - [indexed array of string key-value pairs]
         */
        public function deleteAmazonPayAccount($merchantAccountId, $headers = null);
    }