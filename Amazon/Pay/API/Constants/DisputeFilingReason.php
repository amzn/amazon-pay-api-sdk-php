<?php

namespace Amazon\Pay\API\Constants;

class DisputeFilingReason {
    public const PRODUCT_NOT_RECEIVED = "ProductNotReceived";
    public const PRODUCT_UNACCEPTABLE = "ProductUnacceptable";
    public const PRODUCT_NO_LONGER_NEEDED = "ProductNoLongerNeeded";
    public const CREDIT_NOT_PROCESSED = "CreditNotProcessed";
    public const OVERCHARGED = "Overcharged";
    public const DUPLICATE_CHARGE = "DuplicateCharge";
    public const SUBSCRIPTION_CANCELLED = "SubscriptionCancelled";
    public const UNRECOGNIZED = "Unrecognized";
    public const FRAUDULENT = "Fraudulent";
    public const OTHER = "Other";
}