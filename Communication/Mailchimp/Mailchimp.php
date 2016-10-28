<?php

namespace Wafl\Extensions\Communication\Mailchimp;

use DblEj\Communication\Http\Util,
    DblEj\Communication\JsonUtil,
    DblEj\Extension\ExtensionBase;

class Mailchimp extends ExtensionBase
implements \DblEj\Communication\Integration\ISubscriptionEmailerExtension
{
    private static $_apiKey;
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }

    public function SubscribeUserToList($listId, $emailAddress, &$sendResultDetails = null)
    {
        $url  = "https://us2.api.mailchimp.com/3.0/lists/$listId/members";
        $sendInfo =
        [
            "key"=>self::$_apiKey,
            "email_address"=>$emailAddress,
            "status"=>"subscribed"
        ];
        $sendJson = JsonUtil::EncodeJson($sendInfo);
        $response = Util::SendRequest($url, true, $sendJson, false, true, "wafl-mailchimp", self::$_apiKey);
        $response = JsonUtil::DecodeJson($response);
        $sendSucceed = isset($response["status"]) && ($response["status"] == "subscribed")?true:false;
        if (!$sendSucceed)
        {
            if ($response["title"] == "Member Exists")
            {
                $sendResultDetails = "That email address is already subscribed to the newsletter";
            } else {
                $sendResultDetails = isset($response["detail"])?$response["detail"]:print_r($response, true);
            }
        }
        return $sendSucceed;
    }

	protected static function getAvailableSettings()
	{
		return array("ApiKey");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
            case "ApiKey":
                self::$_apiKey = $settingValue;
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
		}
    }
}