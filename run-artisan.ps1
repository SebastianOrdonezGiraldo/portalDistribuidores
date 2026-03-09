param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Args
)

$env:PHPRC = "C:\Users\sebas\Desktop\portalDistribuidores"

# Ensure project .env values are used (ignore inherited system/user vars).
$env:APP_URL = $null
$env:ORDER_NOTIFICATION_EMAIL = $null

& php artisan @Args
