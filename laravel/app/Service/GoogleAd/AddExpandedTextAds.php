<?php

namespace App\Service\GoogleAd;

use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Util\V6\ResourceNames;
use Google\Ads\GoogleAds\V6\Common\ExpandedTextAdInfo;
use Google\Ads\GoogleAds\V6\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V6\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V6\Resources\Ad;
use Google\Ads\GoogleAds\V6\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V6\Services\AdGroupAdOperation;
use Google\ApiCore\ApiException;

/** This example demonstrates how to add expanded text ads to a given ad group. */
class AddExpandedTextAds
{
    public static function main(
        string $adGroupId,
        string $title1,
        string $title2,
        string $description,
        string $url
    ) {
        $googleAdsClient = app(GetGoogleClient::class)->getClient();
        try {
            self::runExample(
                $googleAdsClient,
                env('GOOGLE_ADS_CUSTOMER_ID'),
                $adGroupId,
                $title1,
                $title2,
                $description,
                $url
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
     * @param int $adGroupId the ad group ID to add expanded text ads to
     */
    // [START AddExpandedTextAds]
    public static function runExample(
        GoogleAdsClient $googleAdsClient,
        int $customerId,
        int $adGroupId,
        string $title1,
        string $title2,
        string $description,
        string $url
    ) {
        $operations = [];
        // Creates the expanded text ad info.
        $expandedTextAdInfo = new ExpandedTextAdInfo([
            'headline_part1' => $title1,
            'headline_part2' => $title2,
            'description' => $description
        ]);

        // Sets the expanded text ad info on an Ad.
        $ad = new Ad([
            'expanded_text_ad' => $expandedTextAdInfo,
            'final_urls' => [$url]
        ]);

        // Creates an ad group ad to hold the above ad.
        $adGroupAd = new AdGroupAd([
            'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
            'status' => AdGroupAdStatus::ENABLED,
            'ad' => $ad
        ]);

        // Creates an ad group ad operation and add it to the operations array.
        $adGroupAdOperation = new AdGroupAdOperation();
        $adGroupAdOperation->setCreate($adGroupAd);
        $operations[] = $adGroupAdOperation;

        // Issues a mutate request to add the ad group ads.
        $adGroupAdServiceClient = $googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds($customerId, $operations);

        foreach ($response->getResults() as $addedAdGroupAd) {
            /** @var AdGroupAd $addedAdGroupAd */
            printf(
                "Expanded text ad was created with resource name: '%s'%s",
                $addedAdGroupAd->getResourceName(),
                PHP_EOL
            );
        }
    }
    // [END AddExpandedTextAds]
}
