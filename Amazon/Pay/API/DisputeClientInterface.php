<?php
namespace Amazon\Pay\API;

/* Interface class to showcase the public API methods for Amazon Pay */

interface DisputeClientInterface
{
    // ----------------------------------- Dispute APIs -----------------------------------

    /* Amazon Checkout v2 - Create Dispute
     *
     * The createDispute operation is used to notify Amazon of a newly created chargeback dispute by a buyer on a
     * transaction processed by the PSP (Payment Service Provider), ensuring the dispute is properly accounted for in the Amazon Pay systems.
     * Please note that this API is supported only for PSPs (Payment Service Provider)
     *
     * @param payload - [String in JSON format] or [array]
     * @param headers - [array] - requires x-amz-pay-Idempotency-Key header; optional x-amz-pay-authtoken
     */
    public function createDispute($payload, $headers);

    /* Amazon Checkout v2 - Get Dispute
     *
     * The getDispute operation is used to retrieve details of a chargeback dispute associated with a specific order
     *
     * @param $disputeId [String]
     * @optional headers - [array] - optional x-amz-pay-authtoken
     */
    public function getDispute($disputeId, $headers=null);

    /* Amazon Checkout v2 - Update Dispute
     *
     * The updateDispute operation is used to notify Amazon of the closure status of a chargeback dispute initiated by a buyer for orders, ensuring proper accounting within the Amazon systems.
     *
     * @param disputeId - [String] - Dispute ID provided while calling the API
     * @param payload - [String in JSON format] or [array]
     * @param headers - [array] - optional x-amz-pay-Idempotency-Key header, x-amz-pay-authtoken
     */
    public function updateDispute($disputeId, $payload, $headers = null);

    /* Amazon Checkout v2 - Contest Dispute
    *
    * The contestDispute operation is used by the partner, on behalf of the merchant, to formally contest a dispute
    * managed by Amazon, requiring the submission of necessary evidence files within the specified
    * Dispute Window (11 days for Chargeback, 7 days for A-Z Claims).
    *
    * @param disputeId - [String] - Dispute ID provided while calling the API
    * @param payload - [String in JSON format] or [array]
    * @param headers - [array] - optional x-amz-pay-Idempotency-Key header, x-amz-pay-authtoken
    */
    public function contestDispute($disputeId, $payload, $headers = null);

    // ----------------------------------- File APIs -----------------------------------

    /* Amazon Checkout v2 - Upload File
    *
    * The uploadFile operation is utilised to upload file-based evidence when a merchant contests a dispute,
    * providing the necessary reference ID to the evidence file as part of the Update Dispute API process.
    *
    * @param payload - [String in JSON format] or [array]
    * @param headers - [array] - requires x-amz-pay-Idempotency-Key header; optional x-amz-pay-authtoken
    */
    public function uploadFile($payload, $headers);
}
?>