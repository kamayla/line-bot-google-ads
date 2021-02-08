<?php

namespace App\Service\GoogleAd;

use GetOpt\GetOpt;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\V6\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;

/** This example gets all campaigns. To add campaigns, run AddCampaigns.php. */
class GetCampaigns
{
    public function main()
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
            $this->runExample(
                $googleAdsClient,
                env('GOOGLE_ADS_CUSTOMER_ID')
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
     */
    public function runExample(GoogleAdsClient $googleAdsClient, int $customerId)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
        // Creates a query that retrieves all campaigns.
        $query = 'SELECT campaign.id, campaign.name FROM campaign ORDER BY campaign.id';
        // Issues a search stream request.
        /** @var GoogleAdsServerStreamDecorator $stream */
        $stream =
            $googleAdsServiceClient->searchStream($customerId, $query);

        // Iterates over all rows in all messages and prints the requested field values for
        // the campaign in each row.
        foreach ($stream->iterateAllElements() as $googleAdsRow) {
            /** @var GoogleAdsRow $googleAdsRow */
            printf(
                "Campaign with ID %d and name '%s' was found.%s",
                $googleAdsRow->getCampaign()->getId(),
                $googleAdsRow->getCampaign()->getName(),
                PHP_EOL
            );
        }
    }
}
