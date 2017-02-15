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

    private $_etsyShopName;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        if (AM_WEBPAGE)
        {
            self::$_appUrl = $app->Get_Settings()->Get_Web()->Get_WebUrlSecure();
        }
    }

    public function GetOrders($minLastModified = 0, $limit = 100, $args = [])
    {
        $args = ["min_last_modified"=>$minLastModified, "limit"=>$limit];

        $includeShippedOrders = isset($args["Shipped Orders"])?$args["Shipped Orders"]:false;
        $includeTransactions = isset($args["Include Transaction Data"])?$args["Include Transaction Data"]:true;
        $wasPaid = isset($args["Paid Orders"])?$args["Paid Orders"]:true;

        if (!$includeShippedOrders)
        {
            $args["was_shipped"] = "false";
        }
        if ($includeTransactions)
        {
            $args["includes"]="Transactions:100";
        }
        $args["was_paid"]=$wasPaid?"true":"false";

        return $this->callApi("shops/$this->_etsyShopName/receipts", $args);
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

    public function Login($requestOrAccessToken = null, $tokenSecret = null, $verifier = null, $returnUrl = null)
    {
        $app = \Wafl\Core::$RUNNING_APPLICATION;
        if (!$returnUrl)
        {
            $returnUrl = self::$_appUrl;
        }
        self::$_oauthObject = new \OAuth(self::$_apiKey, self::$_apiSecret);
        self::$_oauthObject->disableSSLChecks();
        if ($requestOrAccessToken && $verifier)
        {
            $tokenSecret = $app->GetSessionData("oauth_token_secret");

            //have request token with verifier, get an access token
            self::$_oauthObject->setToken($requestOrAccessToken, $tokenSecret);
            $accessToken = self::$_oauthObject->getAccessToken(self::$_apiUrl."oauth/access_token", null, $verifier);
            return $accessToken;
        }
        elseif ($requestOrAccessToken && $tokenSecret)
        {
            //have access token, login complete
            self::$_oauthObject->setToken($requestOrAccessToken, $tokenSecret);

            //erase request token secret from session
            $app->StoreSessionData("oauth_token_secret", null);
            return true;
        } else {
            //no tokens, get a request token and redrect to etsy for autorization
            $response = self::$_oauthObject->getRequestToken(self::$_apiUrl."oauth/request_token?scope=transactions_r&transactions_w", $returnUrl);
            $requestOrAccessToken = $response["oauth_token"];
            $secret = $response["oauth_token_secret"];

            $app->StoreSessionData("oauth_token_secret", $secret);
            \DblEj\Communication\Http\Util::HeaderRedirect($response["login_url"]);
            die();
        }
    }

    private static $_oauthObject = null;

    private function callApi($uri, $parameters = [], $useOAuth = true)
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

        if (!$useOAuth)
        {
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
		return array("ApiKey", "ApiUrl", "ApiSecret", "MaxRequestsPerSecond", "Etsy Shop Name");
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
            case "Etsy Shop Name":
                $this->_etsyShopName = $settingValue;
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
            case "Etsy Shop Name":
                return $this->_etsyShopName;
                break;
		}
    }

}
?>