<?php
// Licence checks go through the Managed FreeScout hub, never straight to invAIse: a self-hosted
// FreeScout must not hold StackPros' invAIse credentials (msteamsfs card #285, same as
// MFSEssentials card #194). The hub calls invAIse with the MFS Print product-line credentials.
return [
    'hub_url'   => env('MFSPRINT_HUB_URL', 'https://app.managedfreescout.com'),
    // Where admins can buy or renew a licence (shown on the settings page).
    'buy_url'   => env('MFSPRINT_BUY_URL', 'https://managedfreescout.com/mfsprint/'),
    'terms_url' => 'https://managedfreescout.com/terms-and-conditions/',
];
