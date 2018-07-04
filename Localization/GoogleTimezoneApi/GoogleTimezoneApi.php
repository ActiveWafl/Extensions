<?php
namespace Wafl\Extensions\Localization\GoogleTimezoneApi;

use DblEj\Application\IApplication,
    DblEj\Extension\ExtensionBase;

class GoogleTimezoneApi extends ExtensionBase
{
    private $_apiKey;
    public function Initialize(IApplication $app)
    {
    }

    public function GetLocalTimezone($lat, $long)
    {
        if (!$this->_apiKey)
        {
            throw new \Exception("You must set the Google Maps Timezone Api Key");
        }

        $timesetToDetermineDst = time();
        $url = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$long&timestamp=$timesetToDetermineDst&key=$this->_apiKey";
        $response = \DblEj\Communication\Http\Util::SendRequest($url);
        $parsedResponse = \DblEj\Communication\JsonUtil::DecodeJson($response);
        if (!isset($parsedResponse["timeZoneId"]))
        {
            throw new \Exception("Invalid timezone returned for lat/long $lat, $long");
        }

        return $parsedResponse["timeZoneId"];
    }

    protected static function getAvailableSettings()
    {
        return ["ApiKey"];
    }

    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            case "ApiKey":
                $this->_apiKey = $settingValue;
                break;
        }
    }
}