<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tech Sheet Download Limit
    |--------------------------------------------------------------------------
    | Maximum number of tech sheet downloads allowed per distributor per month.
    | Adjust via TECH_SHEET_MONTHLY_LIMIT environment variable without a deploy.
    */
    'tech_sheet_monthly_limit' => (int) env('TECH_SHEET_MONTHLY_LIMIT', 2),

    /*
    |--------------------------------------------------------------------------
    | Tech Sheet Monthly Timezone
    |--------------------------------------------------------------------------
    | Month boundaries used to reset distributor download quotas for tech sheets.
    */
    'tech_sheet_monthly_timezone' => (string) env('TECH_SHEET_MONTHLY_TIMEZONE', 'America/Bogota'),
];
