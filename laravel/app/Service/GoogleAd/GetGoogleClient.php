<?php

namespace App\Service\GoogleAd;

use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;

/** This example adds ad groups to a campaign. */
class GetGoogleClient
{
    public static function getClient()
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(env('GOOGLE_ADS_CLIENT_ID'))
            ->withClientSecret(env('GOOGLE_ADS_CLIENT_SECRET'))
            ->withRefreshToken(env('GOOGLE_ADS_REFRESH_TOKEN'))
            ->build();

        // Construct a Google Ads client configured from a properties file and the OAuth2 credentials above.
        return (new GoogleAdsClientBuilder())
            ->withDeveloperToken(env('GOOGLE_ADS_DEVELOPER_TOKEN'))
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId(env('GOOGLE_ADS_MANAGER_ID'))
            ->build();
    }
}
