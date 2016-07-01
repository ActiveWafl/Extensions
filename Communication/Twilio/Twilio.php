<?php

namespace Wafl\Extensions\Communication\Twilio;

class Twilio extends ExtensionBase implements \DblEj\Communication\Integration\ISmsSenderExtension
{
    public function SendSms($fromNumber, $toNumber, $message, $apiId = null, $authKey = null)
    {
       $url  = "https://api.twilio.com/2010-04-01/Accounts/$apiId/Messages.json";
       $postVars = "To=$toNumber&From=$fromNumber&Body=$message";
       return \DblEj\Communication\Http\Util::SendRequest($url, true, $postVars, false, false, $apiId, $authKey);
    }
}