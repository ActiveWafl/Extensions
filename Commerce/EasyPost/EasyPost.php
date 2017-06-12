<?php

namespace Wafl\Extensions\Commerce\EasyPost;

use DblEj\Extension\ExtensionBase;

class EasyPost
extends ExtensionBase
implements \DblEj\Commerce\Integration\IShipperExtension
{
    private static $_apiKey;

    private static $_apiUrl = "https://api.easypost.com/v2/";
    private static $_maxRequestsPerSecond = 5;
    private static $_throttleRequestBlockSecond = 0;
    private static $_throttleRequestBlockRequestCount = 0;

    private static $_logFile = "/var/log/easypost.log";
    private static $_logHandle;
    private $_debugMode = false;
    public function Initialize(\DblEj\Application\IApplication $app)
    {
        if (DIRECTORY_SEPARATOR == "\\")
        {
            self::$_logFile = "c:\\windows\\temp\\easypost.log";
        }
        if (!self::$_logHandle)
        {
            self::$_logHandle = fopen(self::$_logFile, "a");
        }
        if ($app->GetSettingsSection("Debug")->Get_CurrentValue()->GetOptionValue("DebugMode"))
        {
            $this->_debugMode = true;
        }
    }

    private function _logInbound($url, $msg)
    {
        $msg = str_replace("'", "", $msg);
        $prettyMsg = json_encode(json_decode($msg), JSON_PRETTY_PRINT);
        return $this->_log("INP $url\n".$prettyMsg);
    }
    private function _logOutbound($url, $msg)
    {
        $msg = str_replace("'", "", $msg);
        $prettyMsg = json_encode(json_decode($msg), JSON_PRETTY_PRINT);
        return $this->_log("OUT $url\n".$prettyMsg);
    }
    private function _log($msg)
    {
        $msg = date("Y M d H:i:s")." ".$msg;
        fwrite(self::$_logHandle, $msg."\n\n");
    }
    public function SetCredentials($username = "", $accessToken = null, $password = "", $accessScopes = null)
    {
        return null;
    }

    private static $_history = [];
    private function callApi($uri, $parameters = [], $httpMethod = \DblEj\Communication\Http\Request::HTTP_GET, $postParametersRaw = false)
    {
        if (!self::$_apiKey)
        {
            throw new \Exception("Invalid easyPost API key");
        }
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

        $isPostOrPut = false;
        $isDelete = false;
        if ($httpMethod == \DblEj\Communication\Http\Request::HTTP_POST)
        {
            $isPostOrPut = true;
            $isDelete = false;
        }
        elseif ($httpMethod == \DblEj\Communication\Http\Request::HTTP_PUT)
        {
            $isPostOrPut = \DblEj\Communication\Http\Request::HTTP_PUT;
            $isDelete = false;
        }
        elseif ($httpMethod == \DblEj\Communication\Http\Request::HTTP_DELETE)
        {
            $isPostOrPut = false;
            $isDelete = true;
        } else {
            $isPostOrPut = false;
            $isDelete = false;
        }

        $postArgs = null;
        if ($isPostOrPut)
        {
            $url = self::$_apiUrl.$uri;

            if ($postParametersRaw)
            {
                $postArgs = \DblEj\Communication\JsonUtil::EncodeJson($parameters);
            } else {
                $postArgs = "";
                foreach ($parameters as $parameterName=>$parameterVal)
                {
                    $postArgs.="$parameterName=$parameterVal&";
                }
            }
        } else {
            $url = self::$_apiUrl.$uri.($parameters?"?":"");
            foreach ($parameters as $parameterName=>$parameterVal)
            {
                $url.="$parameterName=$parameterVal&";
            }
            $postArgs = null;
        }

        $this->_logOutbound($url, $postArgs);
        self::$_history[] = ["SEND", $url, $postArgs];
        $json = \DblEj\Communication\Http\Util::SendRequest($url, $isPostOrPut, $postArgs, false, true, self::$_apiKey, "", false, null, null, ["Content-Type: application/json"], $isDelete);

        $this->_logInbound($url, $json);
        self::$_history[] = ["RESP", $json];
        try
        {
            $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        } catch (\Exception $ex) {
            throw new \Exception("There was an error parsing the response from easyPost when calling $uri: $json");
        }
        return $response;
    }

	protected static function getAvailableSettings()
	{
		return array("ApiKey", "ApiUrl", "MaxRequestsPerSecond");
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
			case "MaxRequestsPerSecond":
				return self::$_maxRequestsPerSecond;
				break;
		}
    }

    public function GetShipmentStatus($trackingid, $carrierName = null, $shipDate = null, $disambiguate = false)
    {
        if (!$carrierName)
        {
            throw new \Exception("EasyPost requires the carrier name when checking shipment status");
        }
        $trackingid = preg_replace("/\\s/", "", $trackingid);
        $uri = "trackers";
        $shipInfo = $this->callApi($uri, ["tracker[tracking_code]"=>$trackingid, "tracker[carrier]"=>$carrierName]);
        $events = [];
        $deliveryDate = isset($shipInfo["est_delivery_date"])?strtotime($shipInfo["est_delivery_date"]):null;
        $lastStatusMessage = null;
        $shipInfo = $shipInfo["trackers"][0];
        foreach ($shipInfo["tracking_details"] as $historyEvent)
        {
            $events[] = ["Uid"=>$historyEvent["status"].$historyEvent["datetime"], "EventDate"=>strtotime($historyEvent["datetime"]), "Description"=>$historyEvent["message"], "City"=>$historyEvent["tracking_location"]["city"], "State"=>$historyEvent["tracking_location"]["state"], "Country"=>$historyEvent["tracking_location"]["country"], "Postal"=>$historyEvent["tracking_location"]["zip"], "ShipperCode"=>$historyEvent["status"], "EventCode"=>self::_lookupEventCode($historyEvent["status"], $historyEvent["message"])];
            if (strtoupper($historyEvent["status"]) == "DELIVERED")
            {
                $deliveryDate = strtotime($historyEvent["datetime"]);
            }
            $lastStatusMessage = $historyEvent["status"];
        }
        return ["Status"=>$shipInfo["status"], "StatusDescription"=>$lastStatusMessage, "DeliveryDate"=>$deliveryDate, "Summary"=>$lastStatusMessage, "Events"=>$events];
    }

    function GetCarrierNames()
    {
        return ["USPS"=>"USPS", "DHLGlobalMail"=>"DHL Global Mail", "DHLGlobalmailInternational"=>"DHL Global Mail International", "DHLExpress"=>"DHL Express", "FedEx"=>"FedEx", "UPS"=>"UPS"];
    }
    function GetCarriers()
    {
        if ($this->_debugMode)
        {
            return [];
        }
        $returnArray = [];
        $currentlySupportedCarrierNames = $this->GetCarrierNames();
//        $page = 1;
//        do
//        {
            $carriers = $this->callApi("carrier_accounts");
            foreach ($carriers as $carrier)
            {
                //they dont give us the carrier key, they give us the friendly string.  So we have to look up the key.
                $carrierKey = array_search($carrier["readable"], $currentlySupportedCarrierNames);
                if ($carrierKey)
                {
                    $returnArray[$carrier["id"]] = $carrierKey;
                }
            }
//            $page++;
//        } while ($carriers && isset($carriers["count"]) && $carriers["count"] > 0);
        return $returnArray;
    }
    public function GetServiceNames($carrierName = null)
    {
        switch ($carrierName)
        {
            case "USPS":
                $serviceNames =
                    [
                        "First"=>"First Class",
                        "Priority"=>"Priority",
                        "Express"=>"Priority Mail Express",
                        "ParcelSelect"=>"Parcel Select",
                        "MediaMail"=>"Media Mail",
                        "FirstClassMailInternational"=>"First-Class Mail International",
                        "FirstClassPackageInternationalService"=>"First-Class Package International Service",
                        "PriorityMailInternational"=>"Priority Mail International",
                        "ExpressMailInternational"=>"Priority Mail Express International"
                    ];
                break;
            case "DHLGlobalMail":
                $serviceNames =
                    [
                        "BPMExpeditedDomestic"=>"BPM Expedited",
                        "BPMGroundDomestic"=>"BPM Ground",
                        "FlatsExpeditedDomestic"=>"Flats Expedited",
                        "FlatsGroundDomestic"=>"Flats Ground",
                        "MediaMailGroundDomestic"=>"Media Mail Ground",
                        "ParcelExpeditedMax"=>"Parcels Expedited Max",
                        "ParcelPlusExpeditedDomestic"=>"Parcel Plus Expedited",
                        "ParcelPlusGroundDomestic"=>"Parcel Plus Ground",
                        "ParcelsExpeditedDomestic"=>"Parcels Expedited",
                        "ParcelsGroundDomestic"=>"Parcels Ground",
                        "MarketingParcelExpeditedDomestic"=>"Marketing Parcel Expedited",
                        "MarketingParcelGroundDomestic"=>"Marketing Parcel Ground"
                    ];
                break;
            case "DHLGlobalmailInternational":
                $serviceNames =
                    [
                        "DHLPacketInternationalPriority"=>"Packet Priority",
                        "DHLPacketInternationalStandard"=>"Packet Standard",
                        "DHLPacketPlusInternational"=>"Packet Plus",
                        "DHLPacketIPA"=>"GlobalMail Packet IPA",
                        "DHLPacketISAL"=>"GlobalMail Packet ISAL",
                        "DHLParcelInternationalPriority"=>"Parcel International Priority",
                        "DHLParcelInternationalStandard"=>"Parcel International Standard",
                        "DHLParcelDirectInternationalPriority"=>"Parcel Direct Priority",
                        "DHLParcelDirectInternationalExpedited"=>"Priority Direct Expedited"
                    ];
                break;
            case "DHLExpress":
                $serviceNames =
                    [
                        "BreakBulkEconomy" => "Break Bulk Economy",
                        "BreakBulkExpress" => "Break Bulk Express",
                        "DomesticEconomySelect" => "Domestic Economy Select",
                        "DomesticExpress" => "Domestic Express",
                        "DomesticExpress1030" => "Domestic Express 10:30",
                        "DomesticExpress1200" => "Domestic Express 12:00",
                        "EconomySelect" => "Economy Select",
                        "EconomySelectNonDoc" => "Economy Select NonDoc",
                        "EuroPack" => "Europack",
                        "EuropackNonDoc" => "Europack NonDoc",
                        "Express1030" => "Express 10:30",
                        "Express1030NonDoc" => "Express 10:30 NonDoc",
                        "Express1200" => "Express 12:00",
                        "Express1200NonDoc" => "Express 12:00 NonDoc",
                        "Express900" => "Express 9:00",
                        "Express900NonDoc" => "Express 9:00 NonDoc",
                        "ExpressEasy" => "Express Easy",
                        "ExpressEasyNonDoc" => "Express Easy NonDoc",
                        "ExpressEnvelope" => "Express Envelope",
                        "ExpressWorldwide" => "Express Worldwide",
                        "ExpressWorldwideB2C" => "Express Worldwide (B2C)",
                        "ExpressWorldwideB2CNonDoc" => "Express Worldwide (B2C) NonDoc",
                        "ExpressWorldwideECX" => "Express Worldwide ECX",
                        "ExpressWorldwideNonDoc" => "Express Worldwide NonDoc",
                        "FreightWorldwide" => "Freight Worldwide",
                        "GlobalmailBusiness" => "Business",
                        "JetLine" => "Jet Line",
                        "JumboBox" => "Jumbo Box",
                        "LogisticsServices" => "Logistics Services",
                        "SameDay" => "Same Day",
                        "SecureLine" => "Secure Line",
                        "SprintLine" => "Sprint Line"
                    ];
                break;
            case "UPS":
                $serviceNames =
                    [
                        "UPSStandard"=>"Standard℠",
                        "Ground"=>"Ground",
                        "UPSSaver"=>"Saver®",
                        "Express"=>"Express®",
                        "ExpressPlus"=>"Express Plus®",
                        "Expedited"=>"Expedited®",
                        "NextDayAir"=>"Next Day Air®",
                        "NextDayAirSaver"=>"Next Day Air Saver®",
                        "NextDayAirEarlyAM"=>"Next Day Air Early A.M.®",
                        "2ndDayAir"=>"Second Day Air®",
                        "2ndDayAirAM"=>"Second Day Air A.M.®",
                        "3DaySelect"=>"Three-Day Select®"
                    ];
                break;
            case "FedEx":
                $serviceNames =
                    [
                        "FEDEX_GROUND"=>"Ground",
                        "FEDEX_2_DAY"=>"2 Day",
                        "FEDEX_2_DAY_AM"=>"2 Day A.M.",
                        "FEDEX_EXPRESS_SAVER"=>"Express Saver",
                        "STANDARD_OVERNIGHT"=>"Standard Overnight",
                        "FIRST_OVERNIGHT"=>"First Overnight",
                        "PRIORITY_OVERNIGHT"=>"Priority Overnight",
                        "INTERNATIONAL_ECONOMY"=>"International Economy",
                        "INTERNATIONAL_FIRST"=>"International First",
                        "INTERNATIONAL_PRIORITY"=>"International Priority",
                        "GROUND_HOME_DELIVERY"=>"Ground Home Delivery",
                        "SMART_POST"=>"Smartpost"
                    ];
                break;
            default:
                throw new \Exception("Invalid carrier specified: $carrierName");
                break;
        }

        return $serviceNames;
    }

    public function GetServiceLimits($service, $packageType)
    {
        $limits =
        [
            "MinWeight"=>.1,
            "MaxWeight"=>99999999,
            "MinLength"=>0,
            "MaxLength"=>99999999,
            "MinWidth"=>0,
            "MaxWidth"=>99999999,
            "MinHeight"=>.125,
            "MaxHeight"=>99999999,
            "MinGirth"=>0,
            "MaxGirth"=>99999999,
            "MaxValue"=>99999999
        ];
        switch ($service)
        {
            case "First":
                $limits["MaxWeight"] = 12.9999;
                $limits["MinWidth"] = 3.5;
                $limits["MaxWidth"] = 12;
                $limits["MinLength"] = 5;
                $limits["MaxLength"] = 15;
                break;
            case "FirstClassPackageInternationalService":
                $limits["MaxWeight"] = 1119.9999;
                $limits["MaxWidth"] = 36;
                $limits["MaxLength"] = 36;
                $limits["MaxGirth"] = 107; //if length is only 1 then a girth of 107 max would be allowed
                $limits["MinWidth"] = 1;
                $limits["MinLength"] = 1;
                break;
            case "Priority":
                $limits["MaxWeight"] = 1119.9999;
                $limits["MaxWidth"] = 36; //they have a max of girth + length must be less than 108.  We cant know the exact max but this keeps them close.
                $limits["MaxLength"] = 36;
                $limits["MaxGirth"] = 107; //if length is only 1 then a girth of 107 max would be allowed
                break;
            case "Express":
                $limits["MaxWeight"] = 1119.9999;
                break;
            case "PriorityMailInternational":
                $limits["MaxWeight"] = 1119.9999;
                switch ($packageType)
                {
                    case "USPS_FlatRateEnvelope":
                    case "USPS_FlatRateCardboardEnvelope":
                    case "USPS_FlatRatePaddedEnvelope":
                    case "USPS_FlatRateLegalEnvelope":
                    case "USPS_SmallFlatRateEnvelope":
                    case "USPS_FlatRateWindowEnvelope":
                    case "USPS_FlatRateGiftCardEnvelope":
                    case "USPS_SmallFlatRateBox":
                        $limits["MaxWeight"] = 63.9999;
                        break;
                    case "USPS_MediumFlatRateBox1":
                    case "USPS_MediumFlatRateBox2":
                    case "USPS_LargeFlatRateBox":
                        $limits["MaxWeight"] = 63.9999;
                        break;
                }
            case "ExpressMailInternational":
                $limits["MaxWeight"] = 1120;
                switch ($packageType)
                {
                    case "USPS_FlatRateEnvelope":
                    case "USPS_FlatRatePaddedEnvelope":
                    case "USPS_FlatRateLegalEnvelope":
                        $limits["MaxWeight"] = 63.9999;
                        break;
                }
                break;
            case "FirstClassPackageInternationalService":
                $limits["MaxWeight"] = 63.9999;
                $limits["MaxValue"] = 400;
                break;
            case "MarketingParcelGroundDomestic":
            case "MarketingParcelExpeditedDomestic":
            case "ParcelsGroundDomestic":
            case "ParcelsExpeditedDomestic":
            case "ParcelExpeditedMax":
                $limits["MaxWeight"] = 15.9999;
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                break;
            case "ParcelPlusGroundDomestic":
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                $limits["MinWeight"] = 15.9999;
                $limits["MaxWeight"] = 400;
                break;
            case "ParcelPlusExpeditedDomestic":
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                $limits["MinWeight"] = 15.9999;
                $limits["MaxWeight"] = 240;
                break;
            case "ParcelsExpeditedDomestic":
            case "ParcelsGroundDomestic":
            case "ParcelsExpeditedDomestic":
                $limits["MaxWeight"] = 15.9999;
                $limits["MinWidth"] = 5;
                $limits["MaxWidth"] = 12;
                $limits["MinLength"] = 6;
                $limits["MaxLength"] = 15;
                $limits["MinHeight"] = .009;
                $limits["MaxHeight"] = .75;
                break;
            case "BPMGroundDomestic":
            case "BPMExpeditedDomestic":
                $limits["MinWeight"] = 6;
                $limits["MaxWeight"] = 239.9999;
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                break;
        }

        return $limits;
    }

    public function GetPackageTypes($serviceName = null)
    {
        $carrierKey = null;
        foreach ($this->GetCarrierNames() as $tryCarrierKey=>$tryCarrierName)
        {
            foreach ($this->GetServiceNames($tryCarrierKey) as $carrierServiceName)
            {
                if ($carrierServiceName == $serviceName)
                {
                    $carrierKey = $tryCarrierKey;
                    break 2;
                }
            }
        }
        switch ($carrierKey)
        {
            case "USPS":
                return  [
                            "Card"=>"Card",
                            "Letter"=>"Envelope",
                            "Flat"=>"Large Flat Envelope",
                            "Parcel"=>"Package",
                            "LargeParcel"=>"Large Package",
                            "IrregularParcel"=>"Irregular Package",
                            "FlatRateEnvelope"=>"Flat Rate Envelope",
                            "FlatRateLegalEnvelope"=>"Legal Flat Rate Envelope",
                            "FlatRatePaddedEnvelope"=>"Padded Flat Rate Envelope",
                            "FlatRateGiftCardEnvelope"=>"Gift Card Flat Rate Envelope",
                            "FlatRateWindowEnvelope"=>"Flat Rate Envelope w/ Window",
                            "FlatRateCardboardEnvelope"=>"Flat Rate Cardboard Envelope",
                            "SmallFlatRateEnvelope"=>"Small Flat Rate Envelope",
                            "SmallFlatRateBox"=>"Small Flat Rate Box",
                            "MediumFlatRateBox"=>"Medium Flat Rate Box",
                            "LargeFlatRateBox"=>"Large Flat Rate Box",
                            "RegionalRateBoxA"=>"Regional Rate Box A",
                            "RegionalRateBoxB"=>"Regional Rate Box  B"
                        ];
                break;
            case "FedEx":
                return [
                        "DEFAULT"=>"Package",
                        "FedExEnvelope"=>"FedEx Envelope",
                        "FedExBox"=>"FedEx Box",
                        "FedExPak"=>"FedEx Pak",
                        "FedExTube"=>"FedEx Tube",
                        "FedEx10kgBox"=>"Box 10kg",
                        "FedEx25kgBox"=>"Box 25kg",
                        "FedExSmallBox"=>"Box Small",
                        "FedExMediumBox"=>"Box Medium",
                        "FedExLargeBox"=>"Box Large",
                        "FedExExtraLargeBox"=>"Box Extra Large"
                    ];
                break;
            case "UPS":
                return [
                        "DEFAULT"=>"Package",
                        "UPSLetter"=>"UPS Letter",
                        "UPSExpressBox"=>"Express Box",
                        "UPS10kgBox"=>"Box 10kg",
                        "UPS25kgBox"=>"Box 25kg",
                        "Tube"=>"Tube",
                        "Pak"=>"Pak",
                        "Pallet"=>"Pallet",
                        "SmallExpressBox"=>"Express Box Small",
                        "MediumExpressBox"=>"Express Box Medium",
                        "LargeExpressBox"=>"Express Box Large"
                        ];
                break;
            case "DHLGlobalMail":
                return [
                        "Letter"=>"Letter",
                        "Flat"=>"Flat",
                        "BPM"=>"BPM",
                        "Parcel"=>"Parcel"
                        ];
                break;
            case "DHLGlobalMailInternational":
                return ["DEFAULT"=>"Package"];
                break;
            case "DHLExpress":
                return [
                        "JumboDocument"=>"JumboDocument",
                        "JumboParcel"=>"JumboParcel",
                        "Document"=>"Document",
                        "DHLFlyer"=>"DHLFlyer",
                        "Domestic"=>"Domestic",
                        "ExpressDocument"=>"Express ocument",
                        "DHLExpressEnvelope"=>"DHL Express Envelope",
                        "JumboBox"=>"Jumbo Box",
                        "JumboJuniorDocument"=>"Jumbo Junior Document",
                        "JuniorJumboBox"=>"Junior Jumbo Box",
                        "JumboJuniorParcel"=>"Jumbo Junior Parcel",
                        "OtherDHLPackaging"=>"Other DHL Packaging",
                        "Parcel"=>"Parcel",
                        "YourPackaging"=>"Custom Packaging",
                        ];
                break;
            default:
                return ["DEFAULT"=>"Package"];
                break;
        }
    }

    public function GetPackageTypeDimensions($packageType)
    {
        $dimensions = [0,0,0,0];
        switch ($packageType)
        {
            case "FlatRateEnvelope":
            case "FlatRateCardboardEnvelope":
                $dimensions = [12.5,9.5,.75,0];
                break;
            case "FlatRatePaddedEnvelope":
                $dimensions = [12.5,9.5,.75,1];
                break;
            case "FlatRateLegalEnvelope":
                $dimensions = [15,9,.75,0];
                break;
            case "SmallFlatRateEnvelope":
                $dimensions = [10,6,.75,0];
                break;
            case "FlatRateWindowEnvelope":
                $dimensions = [10,5,.75,0];
                break;
            case "FlatRateGiftCardEnvelope":
                $dimensions = [10,7,.75,0];
                break;
            case "MediumFlatRateBox":
                $dimensions = [11.25, 8.75, 6,0];
                break;
            case "SmallFlatRateBox":
                $dimensions = [8.69, 5.44, 1.75,0];
                break;
            case "LargeFlatRateBox":
                $dimensions = [12.25, 12.25, 6,0];
                break;
            case "FedEx10kgBox":
                $dimensions = [15.81, 12.94, 10.19, 0];
                break;
            case "FedEx25kgBox":
                $dimensions = [54.80, 42.10, 33.50, 0];
                break;
            case "FedExExtraLargeBox":
                $dimensions = [15.75, 14.13, 6, 0];
                break;
            case "FedExLargeBox":
                $dimensions = [17.50, 12.38, 3, 0];
                break;
            case "FedExMediumBox":
                $dimensions = [13.25, 11.50, 2.38, 0];
                break;
            case "FedExSmallBox":
                $dimensions = [11.25, 8.75, 4.38, 0];
                break;
            case "FedExEnvelope":
                $dimensions = [12.50, 9.50, 0.80, 0];
                break;
            case "FedExPak":
                $dimensions = [15.50, 12.00, 0.80, 0];
                break;
            case "FedExTube":
                $dimensions = [38.00, 6.00, 6.00, 0];
                break;
            case "Letter":
                $dimensions = [9.5, 4.5, .25, 0];
                break;
            case "Flat":
                $dimensions = [9.00, 11.00, .5, 0];
                break;
            case "UPS10kgBox":
                $dimensions = [16.1417, 13.189, 10.4331, 0];
                break;
            case "UPS25kgBox":
                $dimensions = [19.0551, 17.0472, 13.7795, 0];
                break;
            case "UPSExpressBox":
                $dimensions = [18.1102, 12.4016, 3.74016, 0];
                break;
            case "LargeExpressBox":
                $dimensions = [18.00, 13.00, 3, 0];
                break;
            case "MediumExpressBox":
                $dimensions = [15.00, 11.00, 3, 0];
                break;
            case "SmallExpressBox":
                $dimensions = [13.00, 11.00, 2, 0];
                break;
            case "UPSLetter":
                $dimensions = [12.50, 9.50, 2, 0];
                break;
            case "Pak":
                $dimensions = [16.00, 12.75, 2, 0];
                break;
            case "Tube":
                $dimensions = [38.189, 7.48031, 6.49606, 0 ];
                break;
            case "Pallet":
                $dimensions = [47.2441, 35.4331, 78.7402, 0];
                break;
            case "Package":
                $dimensions = [12, 12, 12, 0];
                break;
            case "Envelope":
                $dimensions = [10, 4, .25, 0];
                break;
            default:
                break;
        }

        return $dimensions;
    }

    public function GetServiceFlagNames($serviceName = null, $packageType = null, $packageQualifier = null)
    {
        return [];
    }
    public function GetPackageQualifiers($serviceName = null, $packageType = null)
    {
        return [];
    }

    public function GetShippingCost(
        $service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = []
    )
    {
        $rateNode = $this->_getRate($service, $sourceName, $sourceCompany, $sourceAddress, $sourceCity, $sourceStateOrRegion, $sourceCountry, $sourcePostalCode, $sourcePhone, $sourceEmail, $destName, $destAddress, $destCity, $destStateOrRegion, $destCountry, $destPostalCode, $destPhone, $destEmail, $packageType, $packageQualifier, $weight, $packageWidth, $packageHeight, $packageLength, $packageGirth, $valueOfContents, $tracking, $insuranceAmount, $codAmount, $contentsType, $serviceFlags);
        return $rateNode["rate"];
    }

    private function _getRate($service, $sourceName, $sourceCompany, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null,
        $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = [])
    {

        $customerAccountNumber = isset($serviceFlags["carrier_account"])?$serviceFlags["carrier_account"]:null;
        $testCustomsId = isset($serviceFlags["customs_declaration"])?$serviceFlags["customs_declaration"]:null;
        $invoiceId = isset($serviceFlags["invoice"])?$serviceFlags["invoice"]:null;
        if ($packageType == "DEFAULT")
        {
            $packageType = null;
        }
        if (!$weight)
        {
            $weight = 0;
        }
        $weight = round($weight, 4);
        if (!is_array($serviceFlags))
        {
            $serviceFlags = [];
        }
        $postage = 0;
        if ($sourceCountry == "US" && !$sourcePostalCode)
        {
            throw new \Wafl\Exceptions\Exception("easyPost requires sender zip code to get rates for US senders", E_ERROR, null, "Sender zip code is required to calculate shipping cost");
        }
        if ($destCountry == "US" && !$destPostalCode)
        {
            throw new \Wafl\Exceptions\Exception("easyPost requires recipient zip code to get rates for US recipients", E_ERROR, null, "Recipient zip code is required to calculate shipping cost");
        }
        if (!$sourcePhone)
        {
            throw new \Wafl\Exceptions\Exception("easyPost requires sender phone number to get rates", E_ERROR, null, "Sender phone number is required to calculate domestic shipping cost");
        }
        if (!$destPhone)
        {
            //throw new \Wafl\Exceptions\Exception("easyPost requires recipient phone number to get rates", E_ERROR, null, "Destination phone number is required to calculate domestic shipping cost");
        }
        if ($weight > 0)
        {
            $shipmentObject = $this->_createShipment($service, $packageType, $packageLength, $packageWidth, $packageHeight, $weight, $customerAccountNumber, $sourceName, $sourceCompany, $sourceAddress, $sourceCity, $sourceStateOrRegion, $sourcePostalCode, $sourceCountry, $sourcePhone, $sourceEmail, $destName, $destAddress, $destCity, $destStateOrRegion, $destPostalCode, $destCountry, $destPhone, $destEmail, $valueOfContents, null, $invoiceId);

            if ($testCustomsId)
            {
                $shipmentObject["shipment"]["customs_info"] = ["id"=>$testCustomsId];
            }
            $shipmentResponse = $this->callApi("shipments", $shipmentObject, \DblEj\Communication\Http\Request::HTTP_POST, true);

            if ($shipmentResponse && isset($shipmentResponse["rates"]))
            {
                $foundRate = null;
                foreach ($shipmentResponse["rates"] as $rateNode)
                {
                    if ((!$postage || ($rateNode["rate"] < $postage)) && ($rateNode["service"] == $service))
                    {
                        $postage = $rateNode["rate"];
                        $foundRate = $rateNode;
                    }
                }
                if ($foundRate)
                {
                    return $foundRate;
                }
                if ($shipmentResponse && isset($shipmentResponse["messages"]) && $shipmentResponse["messages"])
                {
                    $errorMessages = [];
                    foreach ($shipmentResponse["messages"] as $msg)
                    {
                        $errorMessages[] = $msg["type"].": ".$msg["message"];
                    }
                    throw new \Wafl\Exceptions\Exception("Error getting shipping charges. ".  implode(". ", $errorMessages), E_WARNING, null, "There was an error while trying to retrieve the shipper's rate for this shipment.".  implode(". ", $errorMessages));
                } else {
                    throw new \Wafl\Exceptions\Exception("Rate service level not found in EasyPost response.", E_WARNING, null, "The specified shipping service is not available for this shipment with this packaging (or the cloud service is not available)");
                }
            } else {
                throw new \Wafl\Exceptions\Exception("Unknown error getting shipping charges", E_WARNING, null, "Unknown error getting shipping charges");
            }
        } else {
            throw new \Wafl\Exceptions\Exception("Weight is required to calculate shipping cost", E_WARNING, null, "Weight is required to calculate shipping cost");
        }
    }


    private function _createShipment($service, $packageType, $packageLength, $packageWidth, $packageHeight, $weight, $customerAccountNumber, $sourceName, $sourceCompany, $sourceAddress, $sourceCity, $sourceStateOrRegion, $sourcePostalCode, $sourceCountry, $sourcePhone, $sourceEmail, $destName, $destAddress, $destCity, $destStateOrRegion, $destPostalCode, $destCountry, $destPhone, $destEmail, $valueOfContents, $shipDate = null, $invoiceNumber = null)
    {
        if (!$shipDate)
        {
            $shipDate = time();
        }
        if (strlen($destAddress) > 44)
        {
            $destAddressLine1 = substr($destAddress, 0, 44); //labels were being cutoff when we did fifty so we reduced it to 44
            $destAddressLine2 = substr($destAddress, 44, 50);
        } else {
            $destAddressLine1 = $destAddress;
            $destAddressLine2 = null;
        }
        if (strlen($sourceAddress) > 44)
        {
            $srcAddressLine1 = substr($sourceAddress, 0, 44); //labels were being cutoff when we did fifty so we reduced it to 44
            $srcAddressLine2 = substr($sourceAddress, 44, 50);
        } else {
            $srcAddressLine1 = $sourceAddress;
            $srcAddressLine2 = null;
        }
        if (strlen($sourceName) > 30)
        {
            $sourceName = substr($sourceName, 0, 30);
        }
        if (strlen($sourceCompany) > 30)
        {
            $sourceCompany = substr($sourceCompany, 0, 30);
        }
        if (strlen($destName) > 30)
        {
            $destName = substr($destName, 0, 30);
        }

        $shipmentObject =
            ["shipment"=>
            ["to_address"=>
                [
                "name"=>$destName,
                "street1"=>$destAddressLine1,
                "city"=>$destCity,
                "country"=>$destCountry,
                "phone"=>$destPhone,
                "email"=>$destEmail
                ]
            ,
            "from_address"=>
                [
                "street1"=>$srcAddressLine1,
                "city"=>$sourceCity,
                "zip"=>"$sourcePostalCode",
                "country"=>$sourceCountry,
                "phone"=>$sourcePhone,
                "email"=>$sourceEmail
                ]
            ,
            "parcel"=>
                [
                "length"=>round(floatval($packageLength), 4),
                "width"=>round(floatval($packageWidth), 4),
                "height"=>round(floatval($packageHeight), 4),
                "weight"=>round(floatval($weight), 4)
                ]
            ,
            "options"=>
                [
                    "label_size"=>"4x6",
                    "label_date"=>gmdate("Y-m-d\TH:i:s\Z", $shipDate),
                    "invoice_number"=>$invoiceNumber,
                    "endorsement"=>"RETURN_SERVICE_REQUESTED"
                ]
            ]];
        if ($destAddressLine2)
        {
            $shipmentObject["shipment"]["to_address"]["street2"] = $destAddressLine2;
        }
        if ($srcAddressLine2)
        {
            $shipmentObject["shipment"]["from_address"]["street2"] = $srcAddressLine2;
        }
        if ($sourceName)
        {
            $shipmentObject["shipment"]["from_address"]["name"] = $sourceName;
        }
        if ($sourceCompany)
        {
            $shipmentObject["shipment"]["from_address"]["company"] = $sourceCompany;
            if (!$sourceName)
            {
                $shipmentObject["shipment"]["from_address"]["name"] = $sourceCompany;
            }
        }
        if ($destStateOrRegion)
        {
            $shipmentObject["shipment"]["to_address"]["state"] = $destStateOrRegion;
        }
        if ($destPostalCode)
        {
            $shipmentObject["shipment"]["to_address"]["zip"] = $destPostalCode;
        }
        if ($sourceStateOrRegion)
        {
            $shipmentObject["shipment"]["from_address"]["state"] = $sourceStateOrRegion;
        }
        if ($packageType != "DEFAULT" && $packageType != null)
        {
            $shipmentObject["shipment"]["parcel"]["predefined_package"] = $packageType;
        }
        if ($customerAccountNumber)
        {
            $shipmentObject["shipment"]["carrier_accounts"] = [$customerAccountNumber];
        }
        return $shipmentObject;
    }
    private static function _lookupEventCode($easyPostStatus, $easyPostDescription)
    {
        $code = 999;
        switch ($easyPostStatus)
        {
            case "unknown":
                break;
            case "pre_transit":
                if (stristr($easyPostDescription, "Shipping Label Created"))
                {
                    $code = 5;
                } else {
                    $code = 1;
                }
                break;
            case "in_transit":
                if (stristr($easyPostDescription, "out for delivery"))
                {
                    $code = 100;
                }
                elseif (stristr($easyPostDescription, "has been sorted"))
                {
                    $code = 70;
                }
                elseif (stristr($easyPostDescription, "arrived at the shipping partner"))
                {
                    $code = 60;
                }
                elseif (stristr($easyPostDescription, "accepted at the USPS destination"))
                {
                    $code = 60;
                }
                elseif (stristr($easyPostDescription, "arrived at post office"))
                {
                    $code = 60;
                }
                elseif (stristr($easyPostDescription, "departed the shipping partner"))
                {
                    $code = 65;
                }
                else
                {
                    $code = 999;
                }

                break;
            case "out_for_delivery":
                $code = 100;
                break;
            case "delivered":
                //delivered to address
                $code = 110;
                break;
            case "return_to_sender":
                $code = 160;
                break;
            case "failure":
                $code = 220;
                break;
            default:
                $code = 999;
                break;
        }
        return $code;
    }

    public function CreateCustomsDeclaration($lineItems, $serviceFlags = [])
    {
        $testCustomsResponse = null;
        if ($lineItems)
        {
            $easyPostItemIds = [];
            foreach ($lineItems as $lineItem)
            {
                if ($lineItem[1] > 0)
                {
                    if ($lineItem[4] <= 0)
                    {
                        throw new \Wafl\Exceptions\Exception("User tried to create a easyPost customs declaration but included an item with a weight of 0", E_ERROR, null, "The weight of one of the items is zero");
                    }
                    //0 = id
                    //1 = qty
                    //2 = price
                    //3 = description
                    //4 = weight
                    //5 = origin country
                    //this is temporary pending a good functioniong standard interface for line items
                    $testCustomsItem = ["customs_item"=>["description"=>substr($lineItem[3], 0, 49), "quantity"=>floatval($lineItem[1]), "weight"=>round($lineItem[4]*$lineItem[1], 3), "value"=>round(floatval($lineItem[2])*floatval($lineItem[1]), 3), "currency"=>"USD", "origin_country"=>$lineItem[5], "code"=>$lineItem[0]]];
                    $testCustomItemResponse = $this->callApi("customs_items", $testCustomsItem, \DblEj\Communication\Http\Request::HTTP_POST, true);
                    $easyPostItemIds[] = $testCustomItemResponse["id"];
                }
            }

            $testCustomsDeclaration = [];
            if (isset($serviceFlags["certify_signer"]))
            {
                $testCustomsDeclaration["customs_signer"] = $serviceFlags["certify_signer"];
            }
            if (isset($serviceFlags["certify"]))
            {
                $testCustomsDeclaration["customs_certify"] = $serviceFlags["certify"];
            }
            if (isset($serviceFlags["non_delivery_option"]))
            {
                $testCustomsDeclaration["non_delivery_option"] = strtolower($serviceFlags["non_delivery_option"]);
            }
            if (isset($serviceFlags["contents_type"]))
            {
                $testCustomsDeclaration["contents_type"] = strtolower($serviceFlags["contents_type"]);
            }
            if (isset($serviceFlags["contents_explanation"]))
            {
                $testCustomsDeclaration["contents_explanation"] = strtolower($serviceFlags["contents_explanation"]);
            } else {
                $testCustomsDeclaration["contents_explanation"] = strtolower($serviceFlags["contents_type"]);
            }
            if (isset($serviceFlags["eel_pfc"]))
            {
                if ($serviceFlags["eel_pfc"] == "NOEEI_30_36")
                {
                    $testCustomsDeclaration["eel_pfc"] = "NOEEI 30.36";
                } else {
                    $testCustomsDeclaration["eel_pfc"] = "NOEEI 30.37(a)";
                }
            }
            $testCustomsDeclaration["customs_items"] = [];
            foreach ($easyPostItemIds as $easyPostItemId)
            {
                $testCustomsDeclaration["customs_items"][] = ["id"=>$easyPostItemId];
            }
            $testCustomsResponse = $this->callApi("customs_infos", ["customs_info"=>$testCustomsDeclaration], \DblEj\Communication\Http\Request::HTTP_POST, true);
        }
        if ($lineItems && !isset($testCustomsResponse["id"]))
        {
            $errorMsg = "Error with customs declaration";
            throw new \Wafl\Exceptions\Exception("Could not create customs declaration due to an error. $errorMsg", E_WARNING, null, $errorMsg);
        }
        return $testCustomsResponse?$testCustomsResponse["id"]:null;
    }

    public function CreateManifest($fromName, $fromCompany, $fromAddress1, $fromAddress2, $fromCity, $fromState, $fromPostal, $fromCountry, $fromPhone, $fromEmail, $carrierId, $shipmentDate, $shipmentIds)
    {
        $shipmentObjects = ["shipments"=>[]];
        foreach ($shipmentIds as $shipmentId)
        {
            $shipmentObjects["shipments"][] = ["id" => $shipmentId];
        }
        if (!$shipmentObjects)
        {
            throw new \Exception("There are no matching shipments");
        }
        $scanFormResponse = $this->callApi("scan_forms", $shipmentObjects, \DblEj\Communication\Http\Request::HTTP_POST, true);

        if (!isset($scanFormResponse["form_url"]))
        {
            throw new \Exception("Could not get scan form. ". print_r($scanFormResponse, true).(isset($scanFormResponse["message"])?$scanFormResponse["message"]:""));
        }
        return [$scanFormResponse["id"], [$scanFormResponse["form_url"]]];
    }

    public function CreateShipment($service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null,
        $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = [])
    {
        if ($packageType == "DEFAULT")
        {
            $packageType = "";
        }

        if (!$packageWidth)
        {
            throw new \Wafl\Exceptions\Exception("Package width cannot be 0", E_WARNING, null, "Package width cannot be 0");
        }
        if (!$packageHeight)
        {
            throw new \Wafl\Exceptions\Exception("Package height cannot be 0", E_WARNING, null, "Package height cannot be 0");
        }
        if (!$packageLength)
        {
            throw new \Wafl\Exceptions\Exception("Package length cannot be 0", E_WARNING, null, "Package length cannot be 0");
        }
        if (!$weight)
        {
            throw new \Wafl\Exceptions\Exception("Package weight cannot be 0", E_WARNING, null, "Package weight cannot be 0");
        }

        $rate = $this->_getRate($service, $sourceName, $sourceCompany, $sourceAddress, $sourceCity, $sourceStateOrRegion, $sourceCountry, $sourcePostalCode, $sourcePhone, $sourceEmail, $destName, $destAddress, $destCity, $destStateOrRegion, $destCountry, $destPostalCode, $destPhone, $destEmail, $packageType, $packageQualifier, $weight, $packageWidth, $packageHeight, $packageLength, $packageGirth, $valueOfContents, $tracking, $insuranceAmount, $codAmount, $contentsType, $serviceFlags);
        if ($rate)
        {
            $shipmentId = $rate["shipment_id"];
            $postage = $rate["rate"];
            $transactionObject =    [
                                        "rate"=>["id"=>$rate["id"]]
                                    ];

            $shipmentResponse = $this->callApi("shipments/".$shipmentId."/buy", $transactionObject, \DblEj\Communication\Http\Request::HTTP_POST, true);
            $postage = 0;

            if (isset($shipmentResponse["postage_label"]) && isset($shipmentResponse["tracking_code"]) && $shipmentResponse["tracking_code"])
            {
                if (!isset($shipmentResponse["selected_rate"]) || !$shipmentResponse["selected_rate"])
                {
                    throw new \Wafl\Exceptions\Exception("EasyPost found no rate for shipment", E_ERROR, null, "A shipping rate cannot be found for your location");
                }
                $trackingUrl = $shipmentResponse["tracker"]["public_url"];

                $labelUrl = $shipmentResponse["postage_label"]["label_url"];

//                if ($shipmentResponse["postage_label"]["label_pdf_url"])
//                {
//                    $labelUrl = $shipmentResponse["postage_label"]["label_pdf_url"];
//                } else {
//                    //the label was a PNG
//                    //lets conver to PDF
//                    $labelInfo = $this->GetShipmentLabels($shipmentId);
//                    $labelUrl = $labelInfo["LabelUrl"];
//                }

                $trackingId = $shipmentResponse["tracking_code"];
                $postage = $rate["rate"];

                return ["Postage"=>$postage, "LabelUrl"=>$labelUrl, "TrackingId"=>$trackingId, "LabelFormat"=>"PNG", "LabelLength"=>1800, "Uid"=>$shipmentResponse["id"]];
            } elseif (isset($shipmentResponse["status"])) {
                throw new \Wafl\Exceptions\Exception("Could not create shipment due to an error from the api. Status: ".$shipmentResponse["status"].(isset($shipmentResponse["messages"]) && isset($shipmentResponse["messages"][0])?", Message: ".$shipmentResponse["messages"][0]["text"]:""), E_ERROR, null, "Error creating shipment. ".(isset($shipmentResponse["messages"])&&isset($shipmentResponse["messages"][0])?$shipmentResponse["messages"][0]["text"]:""));
            }
            else
            {
                throw new \Wafl\Exceptions\Exception("Unknown error creating shipment.".print_r($shipmentResponse, true));
            }
        } else {
            throw new \Wafl\Exceptions\Exception("Could not calculate shipping cost.");
        }
    }

    public function GetShipmentLabels($shipmentUid)
    {
        $shipmentResponse = $this->callApi("shipments/$shipmentUid/label", ["file_format"=>"PDF"], \DblEj\Communication\Http\Request::HTTP_GET);
        $postage = 0;

        if ($shipmentResponse && ($shipmentResponse["id"] == $shipmentUid))
        {
            $postage = $shipmentResponse["selected_rate"]["rate"];
            $trackingId = $shipmentResponse["tracker"]["tracking_code"];
            $labelUrl = $shipmentResponse["postage_label"]["label_url"];

            return ["Postage"=>$postage, "LabelUrl"=>$labelUrl, "TrackingId"=>$trackingId, "LabelFormat"=>"PNG", "LabelLength"=>1800, "Uid"=>$shipmentResponse["id"]];
        } else {
            throw new \Exception("Error creating shipment.  Status: ".$shipmentResponse["status"]);
        }
    }
}
?>