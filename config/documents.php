<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tech Sheet Download Limit
    |--------------------------------------------------------------------------
    | Maximum number of tech sheet downloads allowed per distributor per month.
    | Adjust via TECH_SHEET_MONTHLY_LIMIT environment variable without a deploy.
    */
    'tech_sheet_monthly_limit' => (int) env('TECH_SHEET_MONTHLY_LIMIT', 3),
];
