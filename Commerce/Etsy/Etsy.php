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

    public function GetAllLIstings($activeOnly = true)
    {
        //this was thrown together quickly, not standardized
        $args = ["include_private"=>true];
        $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/active", $args);
        if (isset($apiResult["results"]))
        {
            $results = $apiResult["results"];
        } else {
            $results = [];
        }

        if (!$activeOnly)
        {
            $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/inactive", $args);
            if (isset($apiResult["results"]))
            {
                foreach ($apiResult["results"] as $result)
                {
                    $results[] = $result;
                }
            }

            $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/expired", $args);
            if (isset($apiResult["results"]))
            {
                foreach ($apiResult["results"] as $result)
                {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    public function FindListings($titleSearch, $activeOnly = true)
    {
        //this was thrown together quickly, not standardized
        $args = ["include_private"=>true, "keywords"=>$titleSearch];
        $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/active", $args);
        if (isset($apiResult["results"]))
        {
            $results = $apiResult["results"];
        } else {
            $results = [];
        }

        if (!$activeOnly)
        {
            $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/inactive", $args);
            if (isset($apiResult["results"]))
            {
                foreach ($apiResult["results"] as $result)
                {
                    $results[] = $result;
                }
            }

            $apiResult = $this->callApi("shops/$this->_etsyShopName/listings/expired", $args);
            if (isset($apiResult["results"]))
            {
                foreach ($apiResult["results"] as $result)
                {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    public function CreateListing($title, $description, $price, $quantity, $imageFiles = [], $category = null, $tags = [], $manufacturer = null, $providerArgs = [])
    {
        //upload the images

        //find the category id

        //get etsy specific args

        //create the listing
        $args = [];
        $args["title"] = $title;
        $args["quantity"] = $quantity;
        $args["description"] = $description;
        $args["price"] = $price;
        //$args["materials"] = $title;
        //$args["shipping_template_id"] = $title;
        //$args["image_ids"] = $title;
        //$args["category_id"] = $title;
        $args["tags"] = $tags;
        $args = array_merge($args, $providerArgs);

        //expected to come as provider args:
        //$args["who_made"]
        //$args["is_supply"]
        //$args["when_made"]

        $this->callApi("shops/$this->_etsyShopName/listings/createListing", $args);
    }

    public function GetOrder($orderUid, $serviceArgs = [])
    {
        $includeTransactions = isset($serviceArgs["Include Transaction Data"])?$serviceArgs["Include Transaction Data"]:true;
        $args = [];
        if ($includeTransactions)
        {
            $args["includes"]="Country,Transactions:100,Transactions/MainImage";
        }

        $rawOrder = $this->callApi("receipts/$orderUid", $args);
        if ($rawOrder && isset($rawOrder["count"]) && $rawOrder["count"] > 0)
        {
            return $this->_createOrderFromReceipt($rawOrder["results"][0]);
        } else {
            return null;
        }
    }
    public function GetOrders($onlyOpen = false, $limit = 50, $offset = 0, $serviceArgs = [])
    {
        $args = ["limit"=>$limit, "offset"=>$offset];

        $includeTransactions = isset($serviceArgs["Include Transaction Data"])?$serviceArgs["Include Transaction Data"]:true;

        if ($includeTransactions)
        {
            $args["includes"]="Country,Transactions:100,Transactions/MainImage";
        }
        if (isset($serviceArgs["min_last_modified"]))
        {
            $args["min_last_modified"] = $serviceArgs["min_last_modified"];
        }
        if ($onlyOpen)
        {
            $rawOrders = $this->callApi("shops/$this->_etsyShopName/receipts/open", $args);
        } else {
            $rawOrders = $this->callApi("shops/$this->_etsyShopName/receipts", $args);
        }
        $orders = [];
        foreach ($rawOrders["results"] as $rawOrder)
        {
            if (!$onlyOpen || $rawOrder["was_paid"])
            {
                $orders[] = $this->_createOrderFromReceipt($rawOrder);
            }
        }

        return $orders;
    }

    private function _createOrderFromReceipt($rawOrder)
    {
        $buyerCountry = new \Wafl\CommonObjects\Commerce\Country($rawOrder["Country"]["name"], $rawOrder["Country"]["iso_country_code"]);
        $paymentStatus = $rawOrder["was_paid"]?"paid":"unpaid";
        $paymentMethod = $rawOrder["payment_method"];

        $lineItems = [];
        foreach ($rawOrder["Transactions"] as $lineItem)
        {
            $itemTitle = $lineItem["title"];
            $description = $lineItem["description"];
            $shippingCharge = $lineItem["shipping_cost"];
            $txnId = $lineItem["listing_id"];
            if (isset($lineItem["product_data"]) && isset($lineItem["product_data"]["sku"])&& $lineItem["product_data"]["sku"])
            {
                $itemId = $lineItem["product_data"]["sku"];
            } else {
                $itemId = "etsy-".$lineItem["listing_id"];
            }
            $createDate = $lineItem["creation_tsz"];
            $itemPrice = $lineItem["price"];
            $itemQty = $lineItem["quantity"];

            $itemImageUrl = $lineItem["MainImage"]?$lineItem["MainImage"]["url_75x75"]:null;
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
        return new \Wafl\CommonObjects\Commerce\Order("Etsy.com", $rawOrder["receipt_id"], $rawOrder["creation_tsz"], $rawOrder["buyer_email"], $rawOrder["subtotal"]+$rawOrder["discount_amt"],
                                                    $rawOrder["grandtotal"], $rawOrder["total_shipping_cost"], $rawOrder["total_tax_cost"], $rawOrder["total_price"],
                                                    $shippingFirstName, $shippingLastName, $rawOrder["first_line"], $rawOrder["second_line"], $rawOrder["city"], $rawOrder["state"], $buyerCountry, $rawOrder["zip"],
                                                    $rawOrder["shipping_details"]["shipping_method"], $paymentMethod, $paymentStatus, $lineItems,
                                                    $rawOrder["message_from_buyer"], $rawOrder["discount_amt"], 0, $rawOrder["last_modified_tsz"], $trackingCode, $shipDate);
    }

    public function AddShipment($uid, $trackingId, $shipper)
    {
        $trackingId = preg_replace("/\\s/", "", $trackingId);
        $shipper = str_replace(" ", "-", $shipper);
        $args = [];
        $args["tracking_code"] = $trackingId;
        $args["carrier_name"] = $shipper;
        $this->callApi("shops/$this->_etsyShopName/receipts/$uid/tracking", $args, true, \OAUTH_HTTP_METHOD_POST);
    }

    public function MarkOrderShipped($uid, $trackingId = null, $shipper = null)
    {
        if ($trackingId && $shipper)
        {
            $this->AddShipment($uid, $trackingId, $shipper);
        }
        $args = [];
        $args["was_shipped"] = true;

        $this->callApi("receipts/$uid", $args, true, \OAUTH_HTTP_METHOD_PUT);
    }

    public function VoidOrder($uid, $notes="")
    {
        throw new \Wafl\Exceptions\Exception("Cannot void order.  Etsy API does not support voiding orders", E_WARNING, null, "The Etsy API does not currently support voiding orders.  You will need to cancel the order manually at Etsy.com");
    }

    public function GetSupportedOperations()
    {
        return
        [
            "Refund"=>false,
            "AddItemToOrder"=>false,
            "GetShippingMethods"=>true,
            "GetListingImageUrl"=>true,
            "VoidOrder"=>false,
            "MarkOrderShipped"=>true,
            "AddShipment"=>true,
            "GetOrders"=>true,
            "GetOrder"=>true,
            "CreateListing"=>true,
            "FindListing"=>true,
        ];
    }

    public function IsOperationSupported($operationName)
    {
        $supportedOps = $this->GetSupportedOperations();
        return isset($supportedOps[$operationName])?$supportedOps[$operationName]:false;
    }
    public function Refund($uid, $refundAmount, $notes="")
    {
        throw new \Wafl\Exceptions\Exception("Cannot issue refund.  Etsy API does not support refunds", E_WARNING, null, "The Etsy API does not currently support refunds.  You will need to do this manually at Etsy.com");
        //need to return a refund id of some sort
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

    private function callApi($uri, $parameters = [], $useOAuth = true, $httpMethod = OAUTH_HTTP_METHOD_GET)
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
                self::$_oauthObject->fetch($url, null, $httpMethod);
            }
            catch (\OAuthException $ex)
            {
                if (stristr($ex->lastResponse, "quota") !== false)
                {
                    //we have exceeded 24 hr quota.
                    throw new \Wafl\Exceptions\Exception("Error calling the Etsy API ($url): ".$ex->getMessage().", ".$ex->lastResponse, E_ERROR, null, "Etsy 24 hr quota has been exceeded");
                } else {
                    throw new \Wafl\Exceptions\Exception("Error calling the Etsy API ($url): ".$ex->getMessage(), E_ERROR, null, "Error calling the Etsy API: ".$ex->lastResponse);
                }
            }
            catch (\Exception $ex)
            {
                throw new \Wafl\Exceptions\Exception("Error calling the Etsy API ($url): ".$ex->getMessage(), E_ERROR, null, "There was an unspecified error calling the Etsy API.");
            }
            $json = self::$_oauthObject->getLastResponse();
        } else {
            $json = \DblEj\Communication\Http\Util::SendRequest($url);
        }
        try
        {
            $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        } catch (\Exception $ex) {
            throw new \Exception("There was an error parsing the response from etsy: $json");
        }
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