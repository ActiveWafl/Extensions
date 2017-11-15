<?php

namespace Wafl\Extensions\Commerce\Shippo;

use DblEj\Extension\ExtensionBase;

class Shippo
extends ExtensionBase
implements \DblEj\Commerce\Integration\IShipperExtension
{
    private static $_apiKey;

    private static $_apiUrl = "https://api.goshippo.com/";
    private static $_maxRequestsPerSecond = 5;
    private static $_throttleRequestBlockSecond = 0;
    private static $_throttleRequestBlockRequestCount = 0;

    private static $_logFile = "/var/log/shippo.log";
    private static $_logHandle;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        if (DIRECTORY_SEPARATOR == "\\")
        {
            self::$_logFile = "c:\\windows\\temp\\shippo.log";
        }
        if (!self::$_logHandle)
        {
            self::$_logHandle = fopen(self::$_logFile, "a");
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
            throw new \Exception("Invalid shippo API key");
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
        $json = \DblEj\Communication\Http\Util::SendRequest($url, $isPostOrPut, $postArgs, false, true, "", "", false, null, null, ["Content-Type: application/json", "Authorization: ShippoToken ".self::$_apiKey], $isDelete);

        $this->_logInbound($url, $json);
        self::$_history[] = ["RESP", $json];
        try
        {
            $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        } catch (\Exception $ex) {
            throw new \Exception("There was an error parsing the response from shippo when calling $uri: $json");
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
            throw new \Exception("Shippo requires the carrier name when checking shipment status");
        }
        $trackingid = preg_replace("/\\s/", "", $trackingid);
        $uri = "tracks/$carrierName/$trackingid";
        $shipInfo = $this->callApi($uri);
        $events = [];
        $deliveryDate = isset($shipInfo["eta"])?strtotime($shipInfo["eta"]):null;

        $preTransitTransmitRecorded = false;

        foreach ($shipInfo["tracking_history"] as $historyEvent)
        {
            $isTransitTransmit = stristr($historyEvent["status_details"], "Pre-Transit: Shipment information has been transmitted") !== false;
            if (!$isTransitTransmit)
            {
                $events[] = ["Uid"=>$historyEvent["object_id"], "EventDate"=>strtotime($historyEvent["status_date"]), "Description"=>$historyEvent["status_details"], "City"=>isset($historyEvent["location"])?$historyEvent["location"]["city"]:null, "State"=>isset($historyEvent["location"])?$historyEvent["location"]["state"]:null, "Country"=>isset($historyEvent["location"])?$historyEvent["location"]["country"]:null, "Postal"=>isset($historyEvent["location"])?$historyEvent["location"]["zip"]:null, "ShipperCode"=>$historyEvent["status"], "EventCode"=>self::_lookupEventCode($historyEvent["status"], $historyEvent["status_details"])];
            }
            elseif (!$preTransitTransmitRecorded)
            {
                $events[] = ["Uid"=>$historyEvent["object_id"], "EventDate"=>strtotime($historyEvent["status_date"]), "Description"=>$historyEvent["status_details"], "City"=>isset($historyEvent["location"])?$historyEvent["location"]["city"]:null, "State"=>isset($historyEvent["location"])?$historyEvent["location"]["state"]:null, "Country"=>isset($historyEvent["location"])?$historyEvent["location"]["country"]:null, "Postal"=>isset($historyEvent["location"])?$historyEvent["location"]["zip"]:null, "ShipperCode"=>$historyEvent["status"], "EventCode"=>self::_lookupEventCode($historyEvent["status"], $historyEvent["status_details"])];
                $preTransitTransmitRecorded = true;
            }

            if ($historyEvent["status"] == "DELIVERED")
            {
                $deliveryDate = strtotime($historyEvent["status_date"]);
            }
        }
        return ["Status"=>$shipInfo["tracking_status"]["status"], "StatusDescription"=>$shipInfo["tracking_status"]["status_details"], "DeliveryDate"=>$deliveryDate, "Summary"=>$shipInfo["tracking_status"]["status_details"], "Events"=>$events, "ShipmentUid"=>$shipInfo["transaction"]];
    }

    function GetCarrierNames()
    {
        return ["usps"=>"USPS", "dhl_ecommerce"=>"DHL eCommerce", "dhl_express"=>"DHL Express", "fedex"=>"FedEx", "ups"=>"UPS"];
    }
    function GetCarriers()
    {
        $returnArray = [];
        $page = 1;
        $currentlySupportedCarrierNames = $this->GetCarrierNames();
        do
        {
            $carriers = $this->callApi("carrier_accounts", ["page"=>$page]);
            if ($carriers && isset($carriers["count"]))
            {
                foreach ($carriers["results"] as $carrier)
                {
                    if ($carrier["active"] == 1 && isset($currentlySupportedCarrierNames[$carrier["carrier"]]))
                    {
                        $returnArray[$carrier["object_id"]] = $carrier["carrier"];
                    }
                }
            }
            $page++;
        } while ($carriers && isset($carriers["count"]) && $carriers["count"] > 0);
        return $returnArray;
    }
    public function GetServiceNames($carrierName = null)
    {
        switch ($carrierName)
        {
            case "usps":
                $serviceNames =
                    [
                        "usps_first"=>"First Class Letter",
                        "usps_first_class_package"=>"First Class Package",
                        "usps_priority"=>"Priority",
                        "usps_priority_express"=>"Priority Mail Express",
                        "usps_parcel_select"=>"Parcel Select",
                        "usps_media_mail"=>"Media Mail",
                        "usps_priority_mail_express_international"=>"Priority Mail Express International",
                        "usps_priority_mail_international"=>"Priority Mail International",
                        "usps_first_class_package_international_service"=>"First-Class Package International Service",
                    ];
                break;
            case "dhl_ecommerce":
                $serviceNames =
                    [
                        "dhl_ecommerce_marketing_parcel_expedited"=>"Marketing Parcel Expedited",
                        "dhl_ecommerce_globalmail_business_ips"=>"GlobalMail Business IPS",
                        "dhl_ecommerce_parcel_international_direct"=>"Parcel International Direct",
                        "dhl_ecommerce_parcels_expedited_max"=>"Parcels Expedited Max",
                        "dhl_ecommerce_bpm_ground"=>"BPM Ground",
                        "dhl_ecommerce_priority_expedited"=>"Priority Expedited",
                        "dhl_ecommerce_globalmail_packet_ipa"=>"GlobalMail Packet IPA",
                        "dhl_ecommerce_globalmail_packet_isal"=>"GlobalMail Packet ISAL",
                        "dhl_ecommerce_easy_return_plus"=>"Easy Return Plus",
                        "dhl_ecommerce_marketing_parcel_ground"=>"Marketing Parcel Ground",
                        "dhl_ecommerce_first_class_parcel_expedited"=>"First Class Parcel Expedited",
                        "dhl_ecommerce_globalmail_business_priority"=>"GlobalMail Business Priority",
                        "dhl_ecommerce_parcels_expedited"=>"Parcels Expedited",
                        "dhl_ecommerce_globalmail_business_isal"=>"GlobalMail Business ISAL",
                        "dhl_ecommerce_parcel_plus_expedited_max"=>"Parcel Plus Expedited Max",
                        "dhl_ecommerce_globalmail_packet_plus"=>"GlobalMail Packet Plus",
                        "dhl_ecommerce_parcels_ground"=>"Parcels Ground",
                        "dhl_ecommerce_expedited"=>"Expedited",
                        "dhl_ecommerce_parcel_plus_ground"=>"Parcel Plus Ground",
                        "dhl_ecommerce_parcel_international_standard"=>"Parcel International Standard",
                        "dhl_ecommerce_bpm_expedited"=>"BPM Expedited",
                        "dhl_ecommerce_parcel_international_expedited"=>"Parcel International Expedited",
                        "dhl_ecommerce_globalmail_packet_priority"=>"GlobalMail Packet Priority",
                        "dhl_ecommerce_easy_return_light"=>"EZ Return Light",
                        "dhl_ecommerce_parcel_plus_expedited"=>"Parcel Plus Expedited",
                        "dhl_ecommerce_globalmail_business_standard"=>"GlobalMail Business Standard",
                        "dhl_ecommerce_ground"=>"Ground",
                        "dhl_ecommerce_globalmail_packet_standard"=>"GlobalMail Packet Standard"
                    ];
                break;
            case "dhl_express":
                $serviceNames =
                    [
                        "dhl_express_domestic_express_doc" => "Domestic Express Doc",
                        "dhl_express_economy_select_doc" => "Economy Select Doc",
                        "dhl_express_worldwide_nondoc" => "Express Worldwide Nondoc",
                        "dhl_express_worldwide_doc" => "Express Worldwide Doc",
                        "dhl_express_worldwide" => "Worldwide",
                        "dhl_express_worldwide_eu_doc" => "Express Worldwide EU Doc",
                        "dhl_express_break_bulk_express_doc" => "Break Bulk Express Doc",
                        "dhl_express_express_9_00_nondoc" => "Express 9:00 NonDoc",
                        "dhl_express_economy_select_nondoc" => "Economy Select NonDoc",
                        "dhl_express_break_bulk_economy_doc" => "Break Bulk Economy Doc",
                        "dhl_express_express_9_00_doc" => "Express 9:00 Doc",
                        "dhl_express_express_10_30_doc" => "Express 10:30 Doc",
                        "dhl_express_express_10_30_nondoc" => "Express 10:30 NonDoc",
                        "dhl_express_express_12_00_doc" => "Express 12:00 Doc",
                        "dhl_express_europack_nondoc" => "Europack NonDoc",
                        "dhl_express_express_envelope_doc" => "Express Envelope Doc",
                        "dhl_express_express_12_00_nondoc" => "Express 12:00 NonDoc",
                        "dhl_express_worldwide_b2c_doc" => "Express Worldwide (B2C) Doc",
                        "dhl_express_worldwide_b2c_nondoc" => "Express Worldwide (B2C) NonDoc",
                        "dhl_express_medical_express" => "Medical Express",
                        "dhl_express_express_easy_nondoc" => "Express Easy NonDoc"
                    ];
                break;
            case "ups":
                $serviceNames =
                    [
                        "ups_standard"=>"Standard℠",
                        "ups_ground"=>"Ground",
                        "ups_saver"=>"Saver®",
                        "ups_3_day_select"=>"Three-Day Select®",
                        "ups_second_day_air"=>"Second Day Air®",
                        "ups_second_day_air_am"=>"Second Day Air A.M.®",
                        "ups_next_day_air"=>"Next Day Air®",
                        "ups_next_day_air_saver"=>"Next Day Air Saver®",
                        "ups_next_day_air_early_am"=>"Next Day Air Early A.M.®",
                        "ups_mail_innovations_domestic"=>"Mail Innovations (domestic)",
                        "ups_surepost"=>"Surepost",
                        "ups_surepost_lightweight"=>"Surepost Lightweight",
                        "ups_express"=>"Express®",
                        "ups_express_plus"=>"Express Plus®",
                        "ups_expedited"=>"Expedited®",
                    ];
                break;
            case "fedex":
                $serviceNames =
                    [
                        "fedex_ground"=>"Ground",
                        "fedex_home_delivery"=>"Home Delivery",
                        "fedex_smart_post"=>"Smartpost",
                        "fedex_2_day"=>"2 Day",
                        "fedex_2_day_am"=>"2 Day A.M.",
                        "fedex_express_saver"=>"Express Saver",
                        "fedex_standard_overnight"=>"Standard Overnight",
                        "fedex_priority_overnight"=>"Priority Overnight",
                        "fedex_first_overnight"=>"First Overnight",
                        "fedex_international_economy"=>"International Economy",
                        "fedex_international_priority"=>"International Priority",
                        "fedex_international_first"=>"International First",
                        "fedex_europe_first_international_priority"=>"Europe First International Priority"
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
            case "usps_first":
                $limits["MaxWeight"] = 12.9999;
                $limits["MinWidth"] = 3.5;
                $limits["MaxWidth"] = 12;
                $limits["MinLength"] = 5;
                $limits["MaxLength"] = 15;
                break;
            case "usps_first_class_package":
                $limits["MaxWeight"] = 1119.9999;
                $limits["MaxWidth"] = 36;
                $limits["MaxLength"] = 36;
                $limits["MaxGirth"] = 107; //if length is only 1 then a girth of 107 max would be allowed
                $limits["MinWidth"] = 1;
                $limits["MinLength"] = 1;
                break;
            case "usps_priority":
                $limits["MaxWeight"] = 1119.9999;
                $limits["MaxWidth"] = 36; //they have a max of girth + length must be less than 108.  We cant know the exact max but this keeps them close.
                $limits["MaxLength"] = 36;
                $limits["MaxGirth"] = 107; //if length is only 1 then a girth of 107 max would be allowed
                break;
            case "usps_priority_express":
                $limits["MaxWeight"] = 1119.9999;
                break;
            case "usps_priority_mail_international":
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
            case "usps_priority_mail_express_international":
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
            case "usps_first_class_package_international_service":
                $limits["MaxWeight"] = 63.9999;
                $limits["MaxValue"] = 400;
                break;
            case "dhl_ecommerce_marketing_parcel_ground":
            case "dhl_ecommerce_marketing_parcel_expedited":
            case "dhl_ecommerce_parcels_ground":
            case "dhl_ecommerce_parcels_expedited":
            case "dhl_ecommerce_parcels_expedited_max":
                $limits["MaxWeight"] = 15.9999;
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                break;
            case "dhl_ecommerce_parcel_plus_ground":
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                $limits["MinWeight"] = 15.9999;
                $limits["MaxWeight"] = 400;
                break;
            case "dhl_ecommerce_parcel_plus_expedited":
            case "dhl_ecommerce_parcel_plus_expedited_max":
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                $limits["MinWeight"] = 15.9999;
                $limits["MaxWeight"] = 240;
                break;
            case "dhl_ecommerce_first_class_parcel_expedited":
            case "dhl_ecommerce_ground":
            case "dhl_ecommerce_expedited":
                $limits["MaxWeight"] = 15.9999;
                $limits["MinWidth"] = 5;
                $limits["MaxWidth"] = 12;
                $limits["MinLength"] = 6;
                $limits["MaxLength"] = 15;
                $limits["MinHeight"] = .009;
                $limits["MaxHeight"] = .75;
                break;
            case "dhl_ecommerce_bpm_ground":
            case "dhl_ecommerce_bpm_expedited":
                $limits["MinWeight"] = 6;
                $limits["MaxWeight"] = 239.9999;
                $limits["MaxLength"] = 27;
                $limits["MaxWidth"] = 17;
                $limits["MaxHeight"] = 17;
                break;
        }

        return $limits;
    }

    public function GetPackageTypeDimensions($packageType)
    {
        $dimensions = [0,0,0,0];
        switch ($packageType)
        {
            case "USPS_FlatRateEnvelope":
            case "USPS_FlatRateCardboardEnvelope":
                $dimensions = [12.5,9.5,.75,0];
                break;
            case "USPS_FlatRatePaddedEnvelope":
                $dimensions = [12.5,9.5,.75,1];
                break;
            case "USPS_FlatRateLegalEnvelope":
                $dimensions = [15,9,.75,0];
                break;
            case "USPS_SmallFlatRateEnvelope":
                $dimensions = [10,6,.75,0];
                break;
            case "USPS_FlatRateWindowEnvelope":
                $dimensions = [10,5,.75,0];
                break;
            case "USPS_FlatRateGiftCardEnvelope":
                $dimensions = [10,7,.75,0];
                break;
            case "USPS_MediumFlatRateBox1":
                $dimensions = [11.25, 8.75, 6,0];
                break;
            case "USPS_SmallFlatRateBox":
                $dimensions = [8.69, 5.44, 1.75,0];
                break;
            case "USPS_MediumFlatRateBox2":
                $dimensions = [14, 12, 3.5,0];
                break;
            case "USPS_LargeFlatRateBox":
                $dimensions = [12.25, 12.25, 6,0];
                break;
            case "FedEx_Box_10kg":
                $dimensions = [15.81, 12.94, 10.19, 0];
                break;
            case "FedEx_Box_25kg":
                $dimensions = [54.80, 42.10, 33.50, 0];
                break;
            case "FedEx_Box_Extra_Large_1":
                $dimensions = [11.88, 11.00, 10.75, 0];
                break;
            case "FedEx_Box_Extra_Large_2":
                $dimensions = [15.75, 14.13, 6, 0];
                break;
            case "FedEx_Box_Large_1":
                $dimensions = [17.50, 12.38, 3, 0];
                break;
            case "FedEx_Box_Large_2":
                $dimensions = [11.25, 8.75, 7.75, 0];
                break;
            case "FedEx_Box_Medium_1":
                $dimensions = [13.25, 11.50, 2.38, 0];
                break;
            case "FedEx_Box_Medium_2":
                $dimensions = [11.25, 8.75, 4.38, 0];
                break;
            case "FedEx_Box_Small_1":
                $dimensions = [12.38, 10.88, 1.50, 0];
                break;
            case "FedEx_Box_Small_2":
                $dimensions = [11.25, 8.75, 4.38, 0];
                break;
            case "FedEx_Envelope":
                $dimensions = [12.50, 9.50, 0.80, 0];
                break;
            case "FedEx_Padded_Pak":
                $dimensions = [11.75, 14.75, 2, 0];
                break;
            case "FedEx_Pak_1":
                $dimensions = [15.50, 12.00, 0.80, 0];
                break;
            case "FedEx_Pak_2":
                $dimensions = [12.75, 10.25, 0.80, 0];
                break;
            case "FedEx_Tube":
                $dimensions = [38.00, 6.00, 6.00, 0];
                break;
            case "FedEx_XL_Pak":
                $dimensions = [17.50, 20.75, 2, 0];
                break;
            case "DHLeC_Easy_Return":
                $dimensions = [10.00, 10.00, 10.00, 0];
                break;
            case "DHLeC_Irregular":
                $dimensions = [10.00, 10.00, 10.00, 0];
                break;
            case "DHLeC_SM_Flats":
                $dimensions = [27.00, 17.00, 17.00, 0];
                break;
            case "UPS_Box_10kg":
                $dimensions = [16.1417, 13.189, 10.4331, 0];
                break;
            case "UPS_Box_25kg":
                $dimensions = [19.0551, 17.0472, 13.7795, 0];
                break;
            case "UPS_Express_Box":
                $dimensions = [18.1102, 12.4016, 3.74016, 0];
                break;
            case "UPS_Express_Box_Large":
                $dimensions = [18.00, 13.00, 3, 0];
                break;
            case "UPS_Express_Box_Medium":
                $dimensions = [15.00, 11.00, 3, 0];
                break;
            case "UPS_Express_Box_Small":
                $dimensions = [13.00, 11.00, 2, 0];
                break;
            case "UPS_Express_Envelope":
                $dimensions = [12.50, 9.50, 2, 0];
                break;
            case "UPS_Express_Hard_Pak":
                $dimensions = [14.75, 11.50, 2, 0];
                break;
            case "UPS_Express_Legal_Envelope":
                $dimensions = [15.00, 9.50, 2, 0];
                break;
            case "UPS_Express_Pak":
                $dimensions = [16.00, 12.75, 2, 0];
                break;
            case "UPS_Express_Tube":
                $dimensions = [38.189, 7.48031, 6.49606, 0 ];
                break;
            case "UPS_Laboratory_Pak":
                $dimensions = [17.25, 12.75, 2, 0];
                break;
            case "UPS_Pad_Pak":
                $dimensions = [14.75, 11, 2, 0];
                break;
            case "UPS_Pallet":
                $dimensions = [47.2441, 35.4331, 78.7402, 0];
                break;
            case "Package":
                $dimensions = [12, 12, 12, 0];
                break;
            case "Envelope":
                $dimensions = [10, 4, .5, 0];
                break;
            default:
                break;
        }

        return $dimensions;
    }

    public function GetPackageTypes($serviceName = null)
    {
        switch ($serviceName)
        {
            case "usps_first":
                return  [
                            "Envelope"=>"Envelope"
                        ];
                break;
            case "usps_media_mail":
            case "usps_first_class_package":
            case "usps_parcel_select":
                return  [
                            "Package"=>"Package", "Envelope"=>"Envelope"
                        ];
                break;
            case "usps_priority_mail_express_international":
            case "usps_priority_express":
                return  [
                            "USPS_FlatRateEnvelope"=>"Flat Rate Envelope", "USPS_FlatRatePaddedEnvelope"=>"Padded Flat Rate Envelope",
                            "USPS_FlatRateLegalEnvelope"=>"Legal Flat Rate Envelope", "DEFAULT"=>"Package", "Envelope"=>"Envelope"
                        ];
                        break;
            case "usps_priority":
            case "usps_priority_mail_international":
                return  [
                            "USPS_FlatRateEnvelope"=>"Flat Rate Envelope", "USPS_FlatRateCardboardEnvelope"=>"Flat Rate Cardboard Envelope", "USPS_FlatRatePaddedEnvelope"=>"Padded Flat Rate Envelope",
                            "USPS_FlatRateLegalEnvelope"=>"Legal Flat Rate Envelope", "USPS_SmallFlatRateEnvelope"=>"Small Flat Rate Envelope",
                            "USPS_FlatRateWindowEnvelope"=>"Flat Rate Envelope w/ Window", "USPS_FlatRateGiftCardEnvelope"=>"Gift Card Flat Rate Envelope",
                            "USPS_MediumFlatRateBox1"=>"Flat Rate Box", "USPS_SmallFlatRateBox"=>"Small Flat Rate Box", "USPS_MediumFlatRateBox2"=>"Medium Flat Rate Box",
                            "USPS_LargeFlatRateBox"=>"Large Flat Rate Box", "DEFAULT"=>"Package", "USPS_IrregularParcel"=>"Irregular Package", "Envelope"=>"Envelope"
                        ];
                break;
            case "usps_first_class_package_international_service":
                return  [
                            "USPS_FlatRateEnvelope"=>"Flat Rate Envelope", "USPS_FlatRateCardboardEnvelope"=>"Flat Rate Cardboard Envelope", "USPS_FlatRatePaddedEnvelope"=>"Padded Flat Rate Envelope",
                            "USPS_FlatRateLegalEnvelope"=>"Legal Flat Rate Envelope", "USPS_SmallFlatRateEnvelope"=>"Small Flat Rate Envelope",
                            "USPS_FlatRateWindowEnvelope"=>"Flat Rate Envelope w/ Window", "USPS_FlatRateGiftCardEnvelope"=>"Gift Card Flat Rate Envelope",
                            "USPS_MediumFlatRateBox1"=>"Flat Rate Box", "USPS_SmallFlatRateBox"=>"Small Flat Rate Box", "USPS_MediumFlatRateBox2"=>"Medium Flat Rate Box",
                            "USPS_LargeFlatRateBox"=>"Large Flat Rate Box", "DEFAULT"=>"Package", "USPS_IrregularParcel"=>"Irregular Package", "Envelope"=>"Envelope"
                        ];
                break;
            default:
                if (strtolower(substr($serviceName, 0, 5)) == "fedex")
                {
                    return ["FedEx_Box_10kg"=>"Box 10kg", "FedEx_Box_25kg"=>"Box 25kg", "FedEx_Box_Extra_Large_1"=>"Box Extra Large (1)",
                           "FedEx_Box_Extra_Large_2"=>"Box Extra Large (2)",
                           "FedEx_Box_Large_1"=>"Box Large (1)",
                           "FedEx_Box_Large_2"=>"Box Large (2)",
                           "FedEx_Box_Medium_1"=>"Box Medium (1)",
                           "FedEx_Box_Medium_2"=>"Box Medium (2)",
                           "FedEx_Box_Small_1"=>"Box Small (1)",
                           "FedEx_Box_Small_2"=>"Box Small (2)",
                           "FedEx_Envelope"=>"Envelope",
                           "FedEx_Padded_Pak"=>"Padded Pak",
                           "FedEx_Pak_1"=>"Pak (1)",
                           "FedEx_Pak_2"=>"Pak (2)",
                           "FedEx_Tube"=>"Tube",
                           "FedEx_XL_Pak"=>"XL Pak",
                           "DEFAULT"=>"Package"];
                }
                elseif (strtolower(substr($serviceName, 0, 3)) == "ups")
                {
                    return ["UPS_Box_10kg"=>"Box 10kg", "UPS_Box_25kg"=>"Box 25kg", "UPS_Express_Box"=>"Express Box",
                        "UPS_Express_Box_Large"=>"Express Box Large",
                        "UPS_Express_Box_Medium"=>"Express Box Medium",
                        "UPS_Express_Box_Small"=>"Express Box Small",
                        "UPS_Express_Envelope"=>"Express Envelope",
                        "UPS_Express_Hard_Pak"=>"Express Hard Pak",
                        "UPS_Express_Legal_Envelope"=>"Express Legal Envelope",
                        "UPS_Express_Pak"=>"Express Pak",
                        "UPS_Express_Tube"=>"Express Tube",
                        "UPS_Laboratory_Pak"=>"Laboratory Pak",
                        "UPS_MI_BPM"=>"BPM (Mail Innovations - Domestic & International)",
                        "UPS_MI_BPM_Flat"=>"BPM Flat (Mail Innovations - Domestic & International)",
                        "UPS_MI_BPM_Parcel"=>"BPM Parcel (Mail Innovations - Domestic & International)",
                        "UPS_MI_First_Class"=>"First Class (Mail Innovations - Domestic only)",
                        "UPS_MI_Flat"=>"Flat (Mail Innovations - Domestic only)",
                        "UPS_MI_Irregular"=>"Irregular (Mail Innovations - Domestic only)",
                        "UPS_MI_Machinable"=>"Machinable (Mail Innovations - Domestic only)",
                        "UPS_MI_MEDIA_MAIL"=>"Media Mail (Mail Innovations - Domestic only)",
                        "UPS_MI_Parcel"=>"Parcel (Mail Innovations - Domestic only)",
                        "UPS_MI_Parcel_Post"=>"Parcel Post (Mail Innovations - Domestic only)",
                        "UPS_MI_Priority"=>"Priority (Mail Innovations - Domestic only)",
                        "UPS_MI_Standard_Flat"=>"Standard Flat (Mail Innovations - Domestic only)",
                        "UPS_Pad_Pak"=>"Pad Pak",
                        "UPS_Pallet"=>"Pallet",
                        "DEFAULT"=>"Package"];
                }
                elseif ($serviceName == "dhl_ecommerce_expedited")
                {
                    return ["DHLeC_SM_Flats"=>"Flats"];
                }
                elseif (strtolower(substr($serviceName, 0, 6)) == "dhl_ec")
                {
                    return ["DHLeC_Irregular"=>"Irregular Shipment", "DEFAULT"=>"Package"];
                } else {
                    return ["DEFAULT"=>"Package"];
                }
        }
    }
    public function GetServiceOptions($serviceName = null, $packageType = null, $packageQualifier = null)
    {
        $options =
        [
            "signature_confirmation"=>["Delivery Confirmation", "Unset", ["Unset"=>"Signature not required", "STANDARD"=>"Signature required", "ADULT"=>"Adult signature required", "CERTIFIED"=>"Certified (USPS Only)"]],
        ];

        $advancedOptions =
        [
            "alcohol"=>["Contains Alcolhol", "no" , ["licensee"=>"Yes, for licensee", "consumer"=>"Yes, for consumer", "no"=>"No"]],
            "incoterm"=>["Incoterm", "DDU" ,["DDP"=>"DDP", "DDU"=>"DDU"]],
            "saturday_delivery"=>["Saturday Delivery", "no", ["yes"=>"Yes", "no"=>"No"]]
        ];

        return ["Options"=>$options, "AdvancedOptions"=>$advancedOptions];
    }

    public function GetPackageQualifiers($serviceName = null, $packageType = null)
    {
        return [];
    }

    public function GetShippingCost(
        $service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceAddress2 = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destAddress2 = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = []
    )
    {
        if ($packageType == "Package" || $packageType == "Envelope")
        {
            $packageType = "";
        }
        $rateNode = $this->_getRate($service, $sourceName, $sourceCompany, $sourceAddress, $sourceAddress2, $sourceCity, $sourceStateOrRegion, $sourceCountry, $sourcePostalCode, $sourcePhone, $sourceEmail, $destName, $destAddress, $destAddress2, $destCity, $destStateOrRegion, $destCountry, $destPostalCode, $destPhone, $destEmail, $packageType, $packageQualifier, $weight, $packageWidth, $packageHeight, $packageLength, $packageGirth, $valueOfContents, $tracking, $insuranceAmount, $codAmount, $contentsType, $serviceFlags);
        return $rateNode["amount"];
    }

    private function _getRate($service, $sourceName, $sourceCompany, $sourceAddress = null, $sourceAddress2 = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destAddress2 = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null,
        $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = [])
    {
        $customerAccountNumber = isset($serviceFlags["carrier_account"])?$serviceFlags["carrier_account"]:null;
        $testCustomsId = isset($serviceFlags["customs_declaration"])?$serviceFlags["customs_declaration"]:null;
        //we expose more services than Shippo actually has,
        //this is because we wanted to create different restrictions for different mail services that shippo has lumped into on
        //for example, first class vs first class package.
        //so, here, we translate them back to shippo's more general service name
        switch ($service)
        {
            case "usps_first_class_package":
                $service = "usps_first";
                break;
        }
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
            throw new \Wafl\Exceptions\Exception("shippo requires sender zip code to get rates for US senders", E_ERROR, null, "Sender zip code is required to calculate shipping cost");
        }
        if ($destCountry == "US" && !$destPostalCode)
        {
            throw new \Wafl\Exceptions\Exception("shippo requires recipient zip code to get rates for US recipients", E_ERROR, null, "Recipient zip code is required to calculate shipping cost");
        }
        if (!$sourcePhone)
        {
            throw new \Wafl\Exceptions\Exception("shippo requires sender phone number to get rates", E_ERROR, null, "Sender phone number is required to calculate domestic shipping cost");
        }
        if (!$destPhone)
        {
            //throw new \Wafl\Exceptions\Exception("shippo requires recipient phone number to get rates", E_ERROR, null, "Destination phone number is required to calculate domestic shipping cost");
        }
        if ($weight > 0)
        {
            $shipmentObject = $this->_createShipment($service, $packageType, $packageLength, $packageWidth, $packageHeight, $weight, $customerAccountNumber, $sourceName, $sourceCompany, $sourceAddress, $sourceAddress2, $sourceCity, $sourceStateOrRegion, $sourcePostalCode, $sourceCountry, $sourcePhone, $sourceEmail, $destName, $destAddress, $destAddress2, $destCity, $destStateOrRegion, $destPostalCode, $destCountry, $destPhone, $destEmail, $valueOfContents, $serviceFlags);
            $shipmentObject["address_to"]["phone"] = $destPhone;
            if ($testCustomsId)
            {
                $shipmentObject["customs_declaration"] = $testCustomsId;
            }
            $shipmentResponse = $this->callApi("shipments", $shipmentObject, \DblEj\Communication\Http\Request::HTTP_POST, true);

            if ($shipmentResponse && isset($shipmentResponse["rates"]))
            {
                $foundRate = null;
                foreach ($shipmentResponse["rates"] as $rateNode)
                {
                    if ((!$postage || ($rateNode["amount"] < $postage)) && ($rateNode["servicelevel"]["token"] == $service))
                    {
                        $postage = $rateNode["amount"];
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
                        $errorMessages[] = $msg["source"].": ".$msg["text"];
                    }
                    throw new \Wafl\Exceptions\Exception("Error getting shipping charges. ".  implode(". ", $errorMessages), E_WARNING, null, "There was an error while trying to retrieve the shipper's rate for this shipment.".  implode(". ", $errorMessages));
                } else {
                    throw new \Wafl\Exceptions\Exception("Rate service level not found in Shippo response.", E_WARNING, null, "The specified shipping service is not available for this shipment with this packaging (or the cloud service is not available)");
                }
            } elseif ($shipmentResponse && isset($shipmentResponse["status"])) {
                throw new \Wafl\Exceptions\Exception("Error getting rates.  Status: ".$shipmentResponse["status"].(isset($shipmentResponse["messages"])?", Message: ".$shipmentResponse["messages"][0]["text"]:""), E_WARNING, null, (isset($shipmentResponse["messages"])?", Message: ".$shipmentResponse["messages"][0]["text"]:"Unknown Error"));
            }
            elseif ($shipmentResponse && isset($shipmentResponse["address_to"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["address_to"] as $addressFieldIssue)
                {
                    foreach ($addressFieldIssue as $fieldName=>$issue)
                    {
                        foreach ($issue as $issueline)
                        {
                            $errorMsg .= $issueline;
                        }
                    }
                }
                throw new \Wafl\Exceptions\Exception("Error getting rates. $errorMsg", E_WARNING, null, "Error getting rates. $errorMsg");
            }
            elseif ($shipmentResponse && isset($shipmentResponse["parcels"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["parcels"] as $parcelFieldIssue)
                {
                    foreach ($parcelFieldIssue as $fieldName=>$issue)
                    {
                        foreach ($issue as $issueline)
                        {
                            if (substr($issueline, 0, 14) == "ParcelTemplate")
                            {
                                $errorMsg .= "The package type is not supported with this service";
                            } else {
                                $errorMsg .= $issueline;
                            }
                        }
                    }
                }
                throw new \Wafl\Exceptions\Exception("Error getting rates. $errorMsg", E_WARNING, null, "Error getting rates. $errorMsg");
            }
            elseif ($shipmentResponse && isset($shipmentResponse["__all__"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["__all__"] as $genericIssue)
                {
                        $errorMsg .= $genericIssue;
                }
                throw new \Wafl\Exceptions\Exception("Error getting rates. $errorMsg", E_WARNING, null, "Error getting rates. $errorMsg");
            }
            elseif ($shipmentResponse && isset($shipmentResponse["messages"]))
            {
                $errorMessages = [];
                foreach ($shipmentResponse["messages"] as $msg)
                {
                    $errorMessages[] = $msg["source"].": ".$msg["text"];
                }
                throw new \Wafl\Exceptions\Exception("Shipping error. ".  implode(". ", $errorMessages), E_WARNING, null, "Shipping error. ".  implode(". ", $errorMessages));
            } else {
                throw new \Wafl\Exceptions\Exception("Unknown error getting shipping charges", E_WARNING, null, "Unknown error getting shipping charges");
            }
        } else {
            throw new \Wafl\Exceptions\Exception("Weight is required to calculate shipping cost", E_WARNING, null, "Weight is required to calculate shipping cost");
        }
    }

    private function _createAddressObject($name, $company, $street1, $street2, $city, $stateOrRegion, $country, $postalCode, $phone, $email)
    {
        $addy =
            [
                "name"=>$name,
                "street1"=>$street1,
                "city"=>$city,
                "country"=>$country,
                "phone"=>$phone,
                "email"=>$email
            ];
        if ($street2)
        {
            $addy["street2"] = $street2;
        }
        if ($company)
        {
            $addy["company"] = $company;
            if (!$name)
            {
                $addy["name"] = $company;
            }
        }
        if ($stateOrRegion)
        {
            $addy["state"] = $stateOrRegion;
        }
        if ($postalCode)
        {
            $addy["zip"] = $postalCode;
        }

        return $addy;
    }
    private function _createShipment($service, $packageType, $packageLength, $packageWidth, $packageHeight, $weight, $customerAccountNumber, $sourceName, $sourceCompany, $sourceAddress, $sourceAddress2, $sourceCity, $sourceStateOrRegion, $sourcePostalCode, $sourceCountry, $sourcePhone, $sourceEmail, $destName, $destAddress, $destAddress2, $destCity, $destStateOrRegion, $destPostalCode, $destCountry, $destPhone, $destEmail, $valueOfContents, $serviceFlags)
    {
        if (strlen($destAddress) > 44)
        {
            $destAddress = substr($destAddress, 0, 44); //labels were being cutoff when we did fifty so we reduced it to 44
            $destAddress2 = substr($destAddress, 44, 50);
        }
        if (strlen($sourceAddress) > 44)
        {
            $sourceAddress = substr($sourceAddress, 0, 44); //labels were being cutoff when we did fifty so we reduced it to 44
            $sourceAddress2 = substr($sourceAddress, 44, 50);
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
        [
            "address_to"=>$this->_createAddressObject($destName, null, $destAddress, $destAddress2, $destCity, $destStateOrRegion, $destCountry, $destPostalCode, $destPhone, $destEmail),
            "address_from"=>$this->_createAddressObject($sourceName, $sourceCompany, $sourceAddress, $sourceAddress2, $sourceCity, $sourceStateOrRegion, $sourceCountry, $sourcePostalCode, $sourcePhone, $sourceEmail),
            "parcels"=>
            [
                [
                    "length"=>round(floatval($packageLength), 4),
                    "width"=>round(floatval($packageWidth), 4),
                    "height"=>round(floatval($packageHeight), 4),
                    "distance_unit"=>"in",
                    "weight"=>round(floatval($weight), 4),
                    "mass_unit"=>"oz"
                ]
            ],
            "async"=>false
        ];

        if ($packageType != "DEFAULT" && $packageType != null)
        {
            $shipmentObject["parcels"][0]["template"] = $packageType;
        }
        if ($customerAccountNumber)
        {
            $shipmentObject["carrier_accounts"] = [$customerAccountNumber];
        }

        $serviceOptions = $this->GetServiceOptions($service, $packageType, null);
        foreach ($serviceFlags as $serviceFlagKey=>$serviceFlagValue)
        {
            if ($serviceFlagValue == "yes")
            {
                $useValue = "1";
            }
            elseif ($serviceFlagValue == "no")
            {
                $useValue = "0";
            }
            else
            {
                $useValue = $serviceFlagValue;
            }
            if ($useValue != "Unset")
            {
                if (isset($serviceOptions["Options"][$serviceFlagKey]) || isset($serviceOptions["AdvancedOptions"][$serviceFlagKey]))
                {
                    if (!isset($shipmentObject["extra"]))
                    {
                        $shipmentObject["extra"] = [];
                    }
                    $shipmentObject["extra"][$serviceFlagKey] = $useValue;
                }
            }
        }
        return $shipmentObject;
    }

    public function IsEventTimeLocal($carrierName)
    {
        return false; //i dont actually know, just setting to some default
    }

    private static function _lookupEventCode($shippoStatus, $shippoDescription)
    {
        $code = 999;
        switch ($shippoStatus)
        {
            case "UNKNOWN":
                if (stristr($shippoDescription, "Pre-Transit: Shipment information has been transmitted"))
                {
                    $code = 5;
                }
                break;
            case "TRANSIT":
                if (stristr($shippoDescription, "shipment picked up"))
                {
                    $code = 50;
                }
                elseif (stristr($shippoDescription, "arrived at sort facility"))
                {
                    $code = 60;
                }
                elseif (stristr($shippoDescription, "arrived at delivery facility"))
                {
                    $code = 60;
                }
                elseif (stristr($shippoDescription, "departed facility"))
                {
                    $code = 65;
                }
                elseif (stristr($shippoDescription, "processed at"))
                {
                    $code = 80;
                }
                elseif (stristr($shippoDescription, "clearance processing"))
                {
                    $code = 80;
                }
                elseif (stristr($shippoDescription, "customs status"))
                {
                    $code = 85;
                }
                elseif (stristr($shippoDescription, "with delivery courier"))
                {
                    $code = 100;
                }
                elseif (stristr($shippoDescription, "delivery attempted"))
                {
                    $code = 130;
                }
                elseif (stristr($shippoDescription, "attempted delivery"))
                {
                    $code = 130;
                }
                elseif (stristr($shippoDescription, "missed delivery"))
                {
                    $code = 130;
                }
                else
                {
                    $code = 999;
                }

                break;
            case "DELIVERED":
                //delivered to address
                $code = 110;
                break;
            case "RETURNED":
                $code = 160;
                break;
            case "FAILURE":
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
            $shippoItemIds = [];
            foreach ($lineItems as $lineItem)
            {
                if ($lineItem[1] > 0)
                {
                    if ($lineItem[4] <= 0)
                    {
                        throw new \Wafl\Exceptions\Exception("User tried to create a shippo customs declaration but included an item with a weight of 0", E_ERROR, null, "The weight of one of the items is zero");
                    }
                    //0 = id
                    //1 = qty
                    //2 = price
                    //3 = description
                    //4 = weight
                    //5 = origin country
                    //this is temporary pending a good functioniong standard interface for line items
                    $testCustomsItem = ["description"=>substr($lineItem[3], 0, 49), "quantity"=>  floatval($lineItem[1]), "net_weight"=>round($lineItem[4]*$lineItem[1], 3), "mass_unit"=>"oz", "value_amount"=>round($lineItem[2]*$lineItem[1], 2), "value_currency"=>"USD", "origin_country"=>$lineItem[5], "sku_code"=>$lineItem[0]];
                    $testCustomItemResponse = $this->callApi("customs/items", $testCustomsItem, \DblEj\Communication\Http\Request::HTTP_POST, true);
                    if (isset($testCustomItemResponse["object_id"]))
                    {
                        $shippoItemIds[] = $testCustomItemResponse["object_id"];
                    } else {
                        $errorLines = [];
                        foreach ($testCustomItemResponse as $errorField=>$errorItems)
                        {
                            foreach ($errorItems as $errorItem)
                            {
                                $errorLines[] = "$errorField: $errorItem";
                            }
                        }
                        if ($errorLines)
                        {
                            $errorMsg = implode(", ", $errorLines);
                        } else {
                            $errorMsg = "Unspecified error";
                        }
                        throw new \Wafl\Exceptions\Exception("Could not add an item to the customs declaration ($errorMsg).", E_WARNING, null, $errorMsg);
                    }
                }
            }

            $testCustomsDeclaration = $serviceFlags;
            $testCustomsDeclaration["items"] = $shippoItemIds;
            if ($shippoItemIds)
            {
                $testCustomsResponse = $this->callApi("customs/declarations", $testCustomsDeclaration, \DblEj\Communication\Http\Request::HTTP_POST, true);
                if (!isset($testCustomsResponse["object_id"]))
                {
                    $errorMsg = "";
                    if (isset($testCustomsResponse["__all__"]))
                    {
                        foreach ($testCustomsResponse["__all__"] as $errorNote)
                        {
                            if (stristr($errorNote, "certify") !== false && stristr($errorNote, "declaration") !== false)
                            {
                                $errorMsg = "You must type your name to certify the shipment's contents and value";
                                break;
                            } else {
                                $errorMsg .= $errorNote;
                            }
                        }
                    }
                    if (!$errorMsg)
                    {
                        $errorMsg = "Unspecified error";
                    }
                    throw new \Wafl\Exceptions\Exception("Could not create customs declaration due to: $errorMsg", E_WARNING, null, $errorMsg);
                }
            }
        }
        return $testCustomsResponse?$testCustomsResponse["object_id"]:null;
    }

    public function CreateManifest($fromName, $fromCompany, $fromAddress1, $fromAddress2, $fromCity, $fromState, $fromPostal, $fromCountry, $fromPhone, $fromEmail, $carrierId, $shipmentDate, $shipmentIds)
    {
        $addressObject = $this->_createAddressObject($fromName, $fromCompany, $fromAddress1, $fromAddress2, $fromCity, $fromState, $fromCountry, $fromPostal, $fromPhone, $fromEmail);
        $addressObject = $this->callApi("addresses", $addressObject, \DblEj\Communication\Http\Request::HTTP_POST, true);

        $formattedDate = strftime("%Y-%m-%dT%H:%M:%SZ", $shipmentDate);
        $scanFormResponse = $this->callApi("manifests", ["async"=>false, "address_from"=>$addressObject["object_id"], "carrier_account"=>$carrierId, "shipment_date"=>$formattedDate], \DblEj\Communication\Http\Request::HTTP_POST, true);

        if (!isset($scanFormResponse["documents"]) || !$scanFormResponse["documents"])
        {
            throw new \Exception("Could not get manifest. ".print_r($scanFormResponse, true).(isset($scanFormResponse["status"])?$scanFormResponse["status"]:""));
        }

        return [$scanFormResponse["object_id"], $scanFormResponse["documents"]];
    }

    public function CreateShipment($service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceAddress2 = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destAddress2 = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null,
        $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = [])
    {
        if ($packageType == "Package" || $packageType == "Envelope")
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

        $rate = $this->_getRate($service, $sourceName, $sourceCompany, $sourceAddress, $sourceAddress2, $sourceCity, $sourceStateOrRegion, $sourceCountry, $sourcePostalCode, $sourcePhone, $sourceEmail, $destName, $destAddress, $destAddress2, $destCity, $destStateOrRegion, $destCountry, $destPostalCode, $destPhone, $destEmail, $packageType, $packageQualifier, $weight, $packageWidth, $packageHeight, $packageLength, $packageGirth, $valueOfContents, $tracking, $insuranceAmount, $codAmount, $contentsType, $serviceFlags);
        if ($rate)
        {

            $postage = $rate["amount"];
            $customerAccountNumber = isset($serviceFlags["carrier_account"])?$serviceFlags["carrier_account"]:null;
            $transactionObject =    [
                                        "rate"=>$rate["object_id"],
                                        "label_file_type"=>"PDF_4x6",
                                        "async"=>false
                                    ];

            if ($customerAccountNumber)
            {
                $transactionObject["carrier_account"] = $customerAccountNumber;
            }
            $shipmentResponse = $this->callApi("transactions", $transactionObject, \DblEj\Communication\Http\Request::HTTP_POST, true);
            $postage = 0;

            if (isset($shipmentResponse["status"]) && $shipmentResponse && $shipmentResponse["status"] == "SUCCESS" and $shipmentResponse["object_state"] == "VALID")
            {
                if (!isset($shipmentResponse["rate"]) || !$shipmentResponse["rate"])
                {
                    throw new \Wafl\Exceptions\Exception("Shippo found no rate for shipment", E_ERROR, null, "A shipping rate cannot be found for your location");
                }
                $trackingUrl = $shipmentResponse["tracking_url_provider"];
                $labelUrl = $shipmentResponse["label_url"];

                $trackingId = $shipmentResponse["tracking_number"];
                $postage = $rate["amount"];

                return ["Postage"=>$postage, "LabelUrl"=>$labelUrl, "TrackingId"=>$trackingId, "LabelFormat"=>"PDF", "LabelLength"=>576, "Uid"=>$shipmentResponse["object_id"]];
            } elseif (isset($shipmentResponse["status"])) {
                throw new \Wafl\Exceptions\Exception("Could not create shipment due to an error from the api. Status: ".$shipmentResponse["status"].(isset($shipmentResponse["messages"]) && isset($shipmentResponse["messages"][0])?", Message: ".$shipmentResponse["messages"][0]["text"]:""), E_ERROR, null, "Error creating shipment. ".(isset($shipmentResponse["messages"])&&isset($shipmentResponse["messages"][0])?$shipmentResponse["messages"][0]["text"]:""));
            }
            elseif (isset($shipmentResponse["address_to"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["address_to"] as $addressFieldIssue)
                {
                    foreach ($addressFieldIssue as $fieldName=>$issue)
                    {
                        $errorMsg .= $issue;
                    }
                }
                throw new \Wafl\Exceptions\Exception("Could not create shipment due to an address error from the api: $errorMsg", E_ERROR, null, "Error creating shipment. $errorMsg");
            }
            elseif (isset($shipmentResponse["shipment"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["shipment"] as $shipmentIssue)
                {
                    $errorMsg .= $shipmentIssue;
                }
                throw new \Wafl\Exceptions\Exception("Could not create shipment due to an error from the api: $errorMsg", E_ERROR, null, "Error creating shipment. $errorMsg");
            }
            elseif (isset($shipmentResponse["rate"]))
            {
                $errorMsg = "";
                foreach ($shipmentResponse["rate"] as $rateIssue)
                {
                    $errorMsg .= $rateIssue;
                }
                throw new \Wafl\Exceptions\Exception("Could not create shipment due to an error from the api: $errorMsg", E_ERROR, null, "Error creating shipment. $errorMsg");
            }
            elseif ($shipmentResponse && isset($shipmentResponse["messages"]))
            {
                $errorMessages = [];
                foreach ($shipmentResponse["messages"] as $msg)
                {
                    $errorMessages[] = $msg["source"].": ".$msg["text"];
                }
                throw new \Exception("Error getting shipping charges from Shippo. ".  implode(". ", $errorMessages));
            }
            else
            {
                throw new \Wafl\Exceptions\Exception("Unknown error creating shipment.");
            }
        } else {
            throw new \Wafl\Exceptions\Exception("Could not calculate shipping cost.");
        }
    }

    public function GetShipmentLabels($shipmentUid)
    {
        $shipmentResponse = $this->callApi("transactions/$shipmentUid", null, \DblEj\Communication\Http\Request::HTTP_GET);
        $postage = 0;

        if ($shipmentResponse && $shipmentResponse["status"] == "SUCCESS" and $shipmentResponse["object_state"] == "VALID")
        {
            $rateUid = $shipmentResponse["rate"];
            $rateResponse = $this->callApi("rates/$rateUid", [], \DblEj\Communication\Http\Request::HTTP_GET);
            $labelUrl = $shipmentResponse["label_url"];
            if (!$rateResponse)
            {
                throw new \Exception("Could not find a rate for this shipment");
            }

            $postage = $rateResponse["amount"];
            $trackingId = $rateResponse["tracking_number"];

            return ["Postage"=>$postage, "LabelUrl"=>$labelUrl, "TrackingId"=>$trackingId, "LabelFormat"=>"PDF", "LabelLength"=>576, "Uid"=>$shipmentResponse["object_id"]];
        } else {
            throw new \Exception("Error creating shipment.  Status: ".$shipmentResponse["status"]);
        }

    }
}
?>