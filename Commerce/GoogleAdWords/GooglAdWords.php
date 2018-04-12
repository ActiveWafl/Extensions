<?php

namespace Wafl\Extensions\Commerce\GoogleAdWords;

use DblEj\Extension\ExtensionBase;

class GoogleAdWords
extends ExtensionBase
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
}
?>