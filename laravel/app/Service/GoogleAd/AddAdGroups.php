<?php

namespace App\Service\GoogleAd;

use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V6\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V6\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V6\Resources\AdGroup;
use Google\Ads\GoogleAds\V6\Services\AdGroupOperation;
use Google\ApiCore\ApiException;
use Illuminate\Support\Str;

/** This example adds ad groups to a campaign. */
class AddAdGroups
{
    public static function main(string $campaignId)
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(env('GOOGLE_ADS_CLIENT_ID'))
            ->withClientSecret(env('GOOGLE_ADS_CLIENT_SECRET'))
            ->withRefreshToken(env('GOOGLE_ADS_REFRESH_TOKEN'))
            ->build();

        // Construct a Google Ads client configured from a properties file and the OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->withDeveloperToken(env('GOOGLE_ADS_DEVELOPER_TOKEN'))
            ->withOAuth2Credential($oAuth2Credential)
            ->withLoginCustomerId(env('GOOGLE_ADS_MANAGER_ID'))
            ->build();

        try {
            return self::runExample(
                $googleAdsClient,
                env('GOOGLE_ADS_CUSTOMER_ID'),
                (int) $campaignId
            );
        } catch (GoogleAdsException $googleAdsException) {
            printf(
                "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL,
                PHP_EOL
            );
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var GoogleAdsError $error */
                printf(
                    "\t%s: %s%s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage(),
                    PHP_EOL
                );
            }
            exit(1);
        } catch (ApiException $apiException) {
            printf(
                "ApiException was thrown with message '%s'.%s",
                $apiException->getMessage(),
                PHP_EOL
            );
            exit(1);
        }
    }

    /**
     * Runs the example.
     *
     * @param GoogleAdsClient $googleAdsClient the Google Ads API client
     * @param int $customerId the customer ID
     * @param int $campaignId the campaign ID to add ad groups to
     */
    public static function runExample(
        GoogleAdsClient $googleAdsClient,
        int $customerId,
        int $campaignId
    ) {
        $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

        $operations = [];

        // Constructs an ad group and sets an optional CPC value.
        $adGroup1 = new AdGroup([
            'name' => 'Earth to Mars Cruises #' . Str::uuid(),
            'campaign' => $campaignResourceName,
            'status' => AdGroupStatus::ENABLED,
            'type' => AdGroupType::SEARCH_STANDARD,
            'cpc_bid_micros' => 100000000
        ]);

        $adGroupOperation1 = new AdGroupOperation();
        $adGroupOperation1->setCreate($adGroup1);
        $operations[] = $adGroupOperation1;

        // Issues a mutate request to add the ad groups.
        $adGroupServiceClient = $googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            $operations
        );

        printf("Added %d ad groups:%s", $response->getResults()->count(), PHP_EOL);
        $addedAdGroup = $response->getResults()[0];
        /** @var AdGroup $addedAdGroup */
        print $addedAdGroup->getResourceName() . PHP_EOL;
        $idMarker = "customers/$customerId/adGroups/";
        return substr($addedAdGroup->getResourceName(), strlen($idMarker));
    }
}
