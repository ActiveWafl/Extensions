<?php

namespace Wafl\Extensions\Commerce\Dhl;

use DblEj\Extension\ExtensionBase;

class Dhl
extends ExtensionBase
implements \DblEj\Commerce\Integration\IShipperExtension
{
    private $_accessToken;
    private $_apiUsername;
    private $_apiPassword;

    private static $_apiUrl;
    private static $_maxRequestsPerSecond = 5;
    private static $_throttleRequestBlockSecond = 0;
    private static $_throttleRequestBlockRequestCount = 0;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }

    public function SetCredentials($username = "", $accessToken = null, $password = "", $accessScopes = null)
    {
        if (!$username && !$accessToken && !$this->_apiUsername)
        {
            throw new \Exception("Cannot login to DHL without a username/password or access token");
        }
        if (!$accessToken)
        {
            if (!$username)
            {
                $username = $this->_apiUsername;
            }
            if (!$password)
            {
                $password = $this->_apiPassword;
            }

            $scopeString = "";
            if ($accessScopes)
            {
                $scopeString = "&scope=".implode(",", $accessScopes);
            }
            $stateString = md5($username.time());
            $response = $this->callApi("auth/access_token?username=$username&password=$password".$scopeString."&state=$stateString");
            $stateMatch = $response["state"];
            if ($stateMatch != $stateString)
            {
                throw new \Exception("Invalid data specified for dhl api");
            }
            $this->_accessToken = $response["data"]["access_token"];
        } else {
            //@todo check if neeed rauth
            $this->_accessToken = $accessToken;
        }
        return $this->_accessToken;
    }

    private function callApi($uri, $parameters = [], $httpMethod = \DblEj\Communication\Http\Request::HTTP_GET)
    {
        if (!$this->_accessToken)
        {
            throw new \Exception("You must login to the DHL API before you can use it");
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

        $parameters["access_token"] = $this->_accessToken;
        $url = self::$_apiUrl.$uri."?";
        foreach ($parameters as $parameterName=>$parameterVal)
        {
            $url.="$parameterName=$parameterVal&";
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
        $json = \DblEj\Communication\Http\Util::SendRequest($url, $isPostOrPut, "", false, true, "", "", false, null, null, [], $isDelete);

        try
        {
            $response = \DblEj\Communication\JsonUtil::DecodeJson($json);
        } catch (\Exception $ex) {
            throw new \Exception("There was an error parsing the response from Dhl: $json");
        }
        return $response;
    }

	protected static function getAvailableSettings()
	{
		return array("ApiUsername", "ApiUrl", "ApiPassword", "MaxRequestsPerSecond");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
            case "ApiUsername":
                $this->_apiUsername = $settingValue;
                break;
            case "ApiPassword":
                $this->_apiPassword = $settingValue;
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
			case "ApiUsername":
				return $this->_apiUsername;
				break;
			case "ApiUrl":
				return self::$_apiUrl;
				break;
			case "ApiPassword":
				return $this->_apiPassword;
				break;
			case "MaxRequestsPerSecond":
				return self::$_maxRequestsPerSecond;
				break;
		}
    }

    public function GetShipmentStatus($trackingid)
    {
        $this->_updateAccessToken();
        $shipStatus = $this->callApi($uri, $parameters);
        return ["x"=>x, "y"=>y];
    }

    public function GetPackageQualifiers($serviceName = null, $packageType = null)
    {
        ;
    }
    public function GetPackageTypes($serviceName = null)
    {
        ;
    }
    public function GetServiceFlagNames($serviceName = null, $packageType = null, $packageQualifier = null)
    {
        ;
    }
    public function GetServiceNames()
    {
        ;
    }
    public function GetShippingCost(
        $service, $sourceName, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = []
    )
    {
        ;
    }

    public function CreateShipment($service, $sourceName, $sourceCompany, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null, $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null, $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null, $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = array())
    {

    }

    public function GetCarrierNames()
    {

    }

    public function GetShipmentLabels($shipmentUid)
    {

    }
}
?>