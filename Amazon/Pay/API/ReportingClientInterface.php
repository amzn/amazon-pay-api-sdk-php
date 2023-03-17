<?php
    namespace Amazon\Pay\API;

    /* Interface class to showcase the public API methods for Amazon Pay */

    interface ReportingClientInterface
    {
        // ----------------------------------- Reporting v2 APIs -----------------------------------
 
 
        /* Amazon Checkout v2 Reporting APIs - Get Reports
         *
         * Returns report details for the reports that match the filters that you specify.
         *
         * @optional queryParameters - [multi-dimension array with key and values]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function getReports($queryParameters = null, $headers = null);
 
 
        /* Amazon Checkout v2 Reporting APIs - Get Report By Id
         *
         * Returns report details for the given reportId.
         *
         * @param $reportId [String]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function getReportById($reportId, $headers = null);
 
 
        /* Amazon Checkout v2 Reporting APIs - Get Report Document
         *
         * Returns the pre-signed S3 URL for the report. The report can be downloaded using this URL.
         *
         * @param $reportDocumentId [String]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function getReportDocument($reportDocumentId, $headers = null);
 
 
         /* Amazon Checkout v2 Reporting APIs - Get Report Schedules
         *
         * Returns report schedule details that match the filters criteria specified.
         *
         * @optional reportTypes - [String]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function getReportSchedules($reportTypes = null, $headers = null);
        
 
 
         /* Amazon Checkout v2 Reporting APIs - Get Report Schedules By Id
         *
         * Returns the report schedule details that match the given ID.
         *
         * @param $reportScheduleId [String]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function getReportScheduleById($reportScheduleId, $headers = null);
 
 
         /* Amazon Checkout v2 Reporting APIs - Create Report
         *
         * Submits a request to generate a report based on the reportType and date range specified.
         *
         * @param $requestPayload [String in JSON format] or [array]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function createReport($requestPayload, $headers = null);
 
 
         /* Amazon Checkout v2 Reporting APIs - Create Report Schedule
         *
         * Creates a report schedule for the given reportType. Only one schedule per report type allowed.
         *
         * @param $requestPayload [String in JSON format] or [array]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function createReportSchedule($requestPayload, $headers = null);
 
 
         /* Amazon Checkout v2 Reporting APIs - Cancel Report Schedule
         *
         * Cancels the report schedule with the given reportScheduleId.
         *
         * @param $reportScheduleId [String]
         * @optional headers - [array] - optional x-amz-pay-authtoken
         */
        public function cancelReportSchedule($reportScheduleId, $headers = null);
 
 
    }
