<?php

namespace App\Service\GoogleAd;

use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V6\Common\ManualCpc;
use Google\Ads\GoogleAds\V6\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V6\Enums\BudgetDeliveryMethodEnum\BudgetDeliveryMethod;
use Google\Ads\GoogleAds\V6\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V6\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V6\Resources\Campaign;
use Google\Ads\GoogleAds\V6\Resources\Campaign\NetworkSettings;
use Google\Ads\GoogleAds\V6\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V6\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V6\Services\CampaignOperation;
use Google\ApiCore\ApiException;
use Illuminate\Support\Str;

/** This example adds new campaigns to an account. */
class AddCampaigns
{
    public function main(string $campaignTitle)
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
            return $this->runExample(
                $googleAdsClient,
                env('GOOGLE_ADS_CUSTOMER_ID'),
                $campaignTitle
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
    public function runExample(GoogleAdsClient $googleAdsClient, int $customerId, string $campaignTitle)
    {
        // Creates a single shared budget to be used by the campaigns added below.
        $budgetResourceName = self::addCampaignBudget($googleAdsClient, $customerId);

        // Configures the campaign network options.
        $networkSettings = new NetworkSettings([
            'target_google_search' => true,
            'target_search_network' => true,
            'target_content_network' => false,
            'target_partner_search_network' => false
        ]);

        $campaignOperations = [];
        $campaign = new Campaign([
            'name' => $campaignTitle,
            'advertising_channel_type' => AdvertisingChannelType::SEARCH,
            // Recommendation: Set the campaign to PAUSED when creating it to prevent
            // the ads from immediately serving. Set to ENABLED once you've added
            // targeting and the ads are ready to serve.
            'status' => CampaignStatus::ENABLED,
            // Sets the bidding strategy and budget.
            'manual_cpc' => new ManualCpc(),
            'campaign_budget' => $budgetResourceName,
            // Adds the network settings configured above.
            'network_settings' => $networkSettings,
            // Optional: Sets the start and end dates.
            'start_date' => date('Ymd', strtotime('+1 day')),
            'end_date' => date('Ymd', strtotime('+1 month'))
        ]);
        // [END AddCampaigns]

        // Creates a campaign operation.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setCreate($campaign);
        $campaignOperations[] = $campaignOperation;

        // Issues a mutate request to add campaigns.
        $campaignServiceClient = $googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, $campaignOperations);

        printf("Added %d campaigns:%s", $response->getResults()->count(), PHP_EOL);

        $addedCampaign = $response->getResults()[0];
        $idMarker = "customers/$customerId/campaigns/";
        return substr($addedCampaign->getResourceName(), strlen($idMarker));
    }

    /**
     * Creates a new campaign budget in the specified client account.
     *
     * @param GoogleAdsClient $googleAdsClient the Google Ads API client
     * @param int $customerId the customer ID
     * @return string the resource name of the newly created budget
     */
    // [START AddCampaigns_1]
    private static function addCampaignBudget(GoogleAdsClient $googleAdsClient, int $customerId)
    {
        // Creates a campaign budget.
        $budget = new CampaignBudget([
            'name' => 'Interplanetary Cruise Budget #' . Str::uuid(),
            'delivery_method' => BudgetDeliveryMethod::STANDARD,
            'amount_micros' => 500000000
        ]);

        // Creates a campaign budget operation.
        $campaignBudgetOperation = new CampaignBudgetOperation();
        $campaignBudgetOperation->setCreate($budget);

        // Issues a mutate request.
        $campaignBudgetServiceClient = $googleAdsClient->getCampaignBudgetServiceClient();
        $response = $campaignBudgetServiceClient->mutateCampaignBudgets(
            $customerId,
            [$campaignBudgetOperation]
        );

        /** @var CampaignBudget $addedBudget */
        $addedBudget = $response->getResults()[0];
        printf("Added budget named '%s'%s", $addedBudget->getResourceName(), PHP_EOL);

        return $addedBudget->getResourceName();
    }
    // [END AddCampaigns_1]
}
