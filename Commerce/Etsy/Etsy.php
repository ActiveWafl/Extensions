<?php

namespace Wafl\Extensions\Commerce\Etsy;

use DblEj\Extension\ExtensionBase;

class Etsy
extends ExtensionBase
implements \DblEj\Commerce\Integration\ISellerAggregatorExtension
{
    private static $_apiKey;
    private static $_apiSecret;
    private static $_apiUrl;
    private static $_appUrl;
    private static $_maxRequestsPerSecond = 5;
    private static $_throttleRequestBlockSecond = 0;
    private static $_throttleRequestBlockRequestCount = 0;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        if (AM_WEBPAGE)
        {
            self::$_appUrl = $app->Get_Settings()->Get_Web()->Get_WebUrlSecure();
        }
    }

    public function GetOrders($shopId, $accessToken, $accessTokenSecret, $minLastModified = 0, $includeTransactions = true, $includeShippedOrders = false, $limit = 100)
    {

        $args = ["was_paid"=>"true", "min_last_modified"=>$minLastModified, "limit"=>$limit];

        if (!$includeShippedOrders)
        {
            $args["was_shipped"] = "false";
        }
        if ($includeTransactions)
        {
            $args["includes"]="Transactions:100";
        }
        return $this->callApi("shops/$shopId/receipts", $args, $accessToken, $accessTokenSecret);
    }

    public function GetListingImageUrl($listingId)
    {
        return $this->callApi("listings/$listingId/images", [], null, null, false);
    }

    public function GetCountry($countryId)
    {
        $results = $this->callApi("countries/$countryId", [], null, null, false);
        if (isset($results["results"]))
        {
            if (count($results["results"]) > 1)
            {
                throw new \Exception("Too many etsy countries found matching the id: $countryId");
            }
            else if (count($results["results"]) == 1)
            {
                return reset($results["results"]);
            } else {
                throw new \Exception("No etsy country found matching the id: $countryId");
            }
        } else {
            throw new \Exception("Error trying to find etsy country matching the id: $countryId");
        }
    }

    public function Login($token = null, $tokenSecret = null, $verifier = null, $returnUrl = null)
    {
        if (!$returnUrl)
        {
            $returnUrl = self::$_appUrl;
        }
        $oAuth = new \OAuth(self::$_apiKey, self::$_apiSecret);
        $oAuth->disableSSLChecks();
        if ($token)
        {
            $oAuth->setToken($token, $tokenSecret);
            // set the verifier and request Etsy's token credentials url
            $accessToken = $oAuth->getAccessToken(self::$_apiUrl."oauth/access_token", null, $verifier);
            return $accessToken;
        } else {
            $response = $oAuth->getRequestToken(self::$_apiUrl."oauth/request_token?scope=transactions_r&transactions_w", $returnUrl);
            $token = $response["oauth_token"];
            $secret = $response["oauth_token_secret"];

            $app = \Wafl\Core::$RUNNING_APPLICATION;
            $app->StoreSessionData("oauth_token_secret", $secret);
            \DblEj\Communication\Http\Util::HeaderRedirect($response["login_url"]);
            die();
        }
    }

    private static $_oauthObject = null;

    private function callApi($uri, $parameters = [], $accessToken = null, $accessTokenSecret = null, $useOAuth = true)
    {
        $currentTime = microtime(true);
        if (($currentTime - self::$_throttleRequestBlockSecond) < 1)
        {
            self::$_throttleRequestBlockRequestCount++;
        } else {
            self::$_throttleRequestBlockRequestCount = 1;
            self::$_throttleRequestBlockSecond = $currentTime;
        }

        if (self::$_throttleRequestBlockRequestCount >= self::$_maxRequestsPerSecond)
        {
            //going too fast for api, throttle
            sleep(1);
        }

        if ($useOAuth)
        {
            if (!self::$_oauthObject)
            {
                self::$_oauthObject = new \OAuth(self::$_apiKey, self::$_apiSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
                self::$_oauthObject->disableSSLChecks();
                self::$_oauthObject->setToken($accessToken, $accessTokenSecret);
            }
        } else {
            $parameters["api_key"] = self::$_apiKey;
        }
        $url = self::$_apiUrl.$uri."?";
        foreach ($parameters as $parameterName=>$parameterVal)
        {
            $url.="$parameterName=$parameterVal&";
        }
        if ($useOAuth)
        {
            self::$_oauthObject->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            $json = self::$_oauthObject->getLastResponse();
        } else {
            $json = \DblEj\Communication\Http\Util::SendRequest($url);
        }
        $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        return $response;
    }

	protected static function getAvailableSettings()
	{
		return array("ApiKey", "ApiUrl", "ApiSecret", "MaxRequestsPerSecond");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
            case "ApiKey":
                self::$_apiKey = $settingValue;
                break;
            case "ApiUrl":
                self::$_apiUrl = $settingValue;
                break;
            case "ApiSecret":
                self::$_apiSecret = $settingValue;
                break;
            case "MaxRequestsPerSecond":
                self::$_maxRequestsPerSecond = $settingValue;
                break;
		}
	}

    protected function getLocalSettingValue($settingName)
    {
		switch ($settingName)
		{
			case "ApiKey":
				return self::$_apiKey;
				break;
			case "ApiUrl":
				return self::$_apiUrl;
				break;
			case "ApiSecret":
				return self::$_apiSecret;
				break;
			case "MaxRequestsPerSecond":
				return self::$_maxRequestsPerSecond;
				break;
		}
    }

}
?>