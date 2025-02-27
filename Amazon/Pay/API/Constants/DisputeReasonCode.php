<?php

namespace Amazon\Pay\API\Constants;

class DisputeReasonCode {
    public const MERCHANT_RESPONSE_REQUIRED = "MerchantResponseRequired";
    public const MERCHANT_ADDITIONAL_EVIDENCES_REQUIRED = "MerchantAdditionalEvidencesRequired";
    public const BUYER_ADDITIONAL_EVIDENCES_REQUIRED = "BuyerAdditionalEvidencesRequired";
    public const MERCHANT_ACCEPTED_DISPUTE = "MerchantAcceptedDispute";
    public const MERCHANT_RESPONSE_DEADLINE_EXPIRED = "MerchantResponseDeadlineExpired";
    public const BUYER_CANCELLED = "BuyerCancelled";
    public const INVESTIGATOR_RESOLVED = "InvestigatorResolved";
    public const AUTO_RESOLVED = "AutoResolved";
    public const CHARGEBACK_FILED = "ChargebackFiled";
}