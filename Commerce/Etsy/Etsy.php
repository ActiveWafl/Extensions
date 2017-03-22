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

    public function GetOrders($minCreateDate = 0, $minLastModified = 0, $limit = 100, $offset = 0, $serviceArgs = [])
    {
        $args = ["min_created"=>$minCreateDate, "min_last_modified"=>$minLastModified, "limit"=>$limit, "offset"=>$offset];

        $includeShippedOrders = isset($serviceArgs["Shipped Orders"])?$serviceArgs["Shipped Orders"]:false;
        $includeTransactions = isset($serviceArgs["Include Transaction Data"])?$serviceArgs["Include Transaction Data"]:true;
        $wasPaid = isset($serviceArgs["Paid Orders"])?$serviceArgs["Paid Orders"]:true;

        if (!$includeShippedOrders)
        {
            $args["was_shipped"] = "false";
        }
        if ($includeTransactions)
        {
            $args["includes"]="Transactions:100";
        }
        $args["was_paid"]=$wasPaid?"true":"false";

        $rawOrders = $this->callApi("shops/$this->_etsyShopName/receipts", $args);
        $orders = [];
        foreach ($rawOrders["results"] as $rawOrder)
        {
            $buyerCountry = $this->GetCountry($rawOrder["country_id"]);
            $paymentStatus = $rawOrder["was_paid"]?"paid":"unpaid";
            $paymentMethod = $rawOrder["payment_method"];

            $lineItems = [];
            foreach ($rawOrder["Transactions"] as $lineItem)
            {
                $itemTitle = $lineItem["title"];
                $description = $lineItem["description"];
                $shippingCharge = $lineItem["shipping_cost"];
                $txnId = $lineItem["listing_id"];
                if (isset($lineItem["product_data"]))
                {
                    $itemId = $lineItem["product_data"]["sku"];
                } else {
                    $itemId = "etsy-".$lineItem["listing_id"];
                }
                $createDate = $lineItem["creation_tsz"];
                $itemPrice = $lineItem["price"];
                $itemQty = $lineItem["quantity"];

                $listingImages = $this->GetListingImageUrl($lineItem["listing_id"]);
                if (isset($listingImages["results"]))
                {
                    $listingImages = $listingImages["results"];
                    if (count($listingImages) > 0)
                    {
                        $listingImage = reset($listingImages);
                    }
                }
                $itemImageUrl = $listingImage?$listingImage["url_75x75"]:null;
                $lineItems[] = new \Wafl\CommonObjects\Commerce\LineItem($txnId, $itemId, $itemQty, $itemPrice, $createDate, $itemTitle, $description, $itemImageUrl, $shippingCharge);
            }

            if (isset($rawOrder["shipping_tracking_code"]))
            {
                $trackingCode = $rawOrder["shipping_tracking_code"];
                $shipDate = $rawOrder["shipping_notification_date"];
            }
            elseif (isset($rawOrder["shipping_details"]["tracking_code"]))
            {
                $trackingCode = $rawOrder["shipping_details"]["tracking_code"];
                $shipDate = isset($rawOrder["shipping_details"]["notification_date"])?$rawOrder["shipping_details"]["notification_date"]:null;
            }
            elseif (isset($rawOrder["shipments"]) && count($rawOrder["shipments"]))
            {
                $rawShipment = $rawOrder["shipments"][0];
                $trackingCode = $rawShipment["tracking_code"];
                $shipDate = $rawShipment["notification_date"];

            } else {
                $trackingCode = null;
                $shipDate = null;
            }
            $fullName = $rawOrder["name"];
            if (stristr($fullName, " "))
            {
                $shippingFirstName = trim(substr($fullName, 0, stripos($fullName, " ")));
                $shippingLastName = trim(substr($fullName, stripos($fullName, " ")));
            } else {
                $shippingFirstName = $fullName;
                $shippingLastName = "";
            }
            $orders[] = new \Wafl\CommonObjects\Commerce\Order("Etsy.com", $rawOrder["receipt_id"], $rawOrder["creation_tsz"], $rawOrder["buyer_email"], $rawOrder["subtotal"],
                                                        $rawOrder["grandtotal"], $rawOrder["total_shipping_cost"], $rawOrder["total_tax_cost"], $rawOrder["total_price"],
                                                        $shippingFirstName, $shippingLastName, $rawOrder["first_line"], $rawOrder["second_line"], $rawOrder["city"], $rawOrder["state"], $buyerCountry, $rawOrder["zip"],
                                                        $rawOrder["shipping_details"]["shipping_method"], $paymentMethod, $paymentStatus, $lineItems,
                                                        $rawOrder["message_from_buyer"], $rawOrder["discount_amt"], 0, $rawOrder["last_modified_tsz"], $trackingCode, $shipDate);
        }

        return $orders;
    }
    public function MarkOrderShipped($uid, $trackingId, $shipper)
    {
        $args = [];
        $args["tracking_code"] = $trackingId;
        $args["carrier_name"] = $shipper;
        $this->callApi("/shops/$this->_etsyShopName/receipts/$uid/tracking", $args);
    }

    public function VoidOrder($uid, $notes="")
    {
        throw new \Wafl\Exceptions\Exception("Cannot void order.  Etsy API does not support voiding orders", E_WARNING, null, "The Etsy API does not currently support voiding orders.  You will need to cancel the order manually at Etsy.com");
    }
    public function Refund($uid, $refundAmount, $notes="")
    {
        throw new \Wafl\Exceptions\Exception("Cannot issue refund.  Etsy API does not support refunds", E_WARNING, null, "The Etsy API does not currently support refunds.  You will need to do this manually at Etsy.com");
    }
    public function AddItemToOrder($uid, $productCode, $addQty, $priceEach)
    {
        throw new \Wafl\Exceptions\Exception("Cannot add item.  Etsy API does not support adding items to an order", E_WARNING, null, "The Etsy API does not currently support adding items to existing order.  You will need to handle this manually at Etsy.com");
    }
    public function GetShippingMethods()
    {
        $methods = [];
        $templates = $this->callApi("users/$this->_etsyShopName/shipping/templates");
        $upgrades = [];
        $entries = [];
        if (isset($templates["results"]))
        {
            foreach ($templates["results"] as $template)
            {
                $templateId = $template["shipping_template_id"];
                $title = $template["title"];
                $upgrades = $this->callApi("shipping/templates/$templateId/upgrades");
                $entries = $this->callApi("shipping/templates/$templateId/entries");
                if (isset($entries["results"]))
                {
                    foreach ($entries["results"] as $entry)
                    {
                        $countries = $this->callApi("countries/".$entry["destination_country_id"]);
                        $countries = $countries["results"];
                        if (count($countries) > 1)
                        {
                            $countryName = "International";
                        }
                        elseif (count($countries) == 1)
                        {
                            $country = $countries[0];
                            $countryName = $country["name"];
                        }
                        else
                        {
                            $countryName = "";
                        }
                        $profileId = $entry["shipping_template_id"]."-".$entry["shipping_template_entry_id"];
                        $price = $entry["primary_cost"];
                        $methods[] = ["Title"=>trim("$title $countryName"), "Price"=>$price, "Uid"=>$profileId];
                    }
                }
                if (isset($upgrades["results"]))
                {
                    foreach ($upgrades["results"] as $template)
                    {
                        $profileId = $template["shipping_profile_id"]."-".$template["value_id"];
                        $title = $template["value"];
                        $price = $template["price"];
                        $methods[] = ["Title"=>$title, "Price"=>$price, "Uid"=>$profileId];
                    }
                }
            }
        }
        return $methods;
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
                $countryInfo = reset($results["results"]);
                return new \Wafl\CommonObjects\Commerce\Country($countryInfo["name"], $countryInfo["iso_country_code"]);
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
            $response = self::$_oauthObject->getRequestToken(self::$_apiUrl."oauth/request_token?scope=transactions_r%20transactions_w%20listings_r%20listings_w", $returnUrl);
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
            try
            {
                self::$_oauthObject->fetch($url, null, OAUTH_HTTP_METHOD_GET);
            } catch (\Exception $ex) {
                print_r($ex);
            }
            $json = self::$_oauthObject->getLastResponse();
        } else {
            $json = \DblEj\Communication\Http\Util::SendRequest($url);
        }
        $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        return $response;
    }

	protected static function getAvailableSettings()
	{
		return array("ApiKey", "ApiUrl", "ApiSecret", "MaxRequestsPerSecond", "Etsy Shop Name", "Shipping Rate Mappings");
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