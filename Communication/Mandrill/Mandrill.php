<?php

namespace Wafl\Extensions\Communication\Mandrill;

use DblEj\Communication\Http\Util,
    DblEj\Communication\JsonUtil,
    DblEj\Extension\ExtensionBase;

class Mandrill extends ExtensionBase implements \DblEj\Communication\Integration\IEmailerExtension
{
    private static $_apiKey;
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }

    /**
     * Send an email
     * @param string $subject Email subject
     * @param string $from Email address of sender or an array such as ["senderemail@address.com", "Acme, Inc."] where the first element is the email address and the second element is the sender's name
     * @param string|array $to Recipient email address or an array of email addresses and names such as [["john@host.com", "John Doe"], ["jane@host.com", "Jane Doe"], ...]
     * @param string $htmlContents HTML email contents
     * @param string $plainContents (Optional) Plain text email contents
     * @param string|array $cc (Optional) CC email address or an array of email addresses and names such as [["john@host.com", "John Doe"], ["jane@host.com", "Jane Doe"], ...]
     * @param string|array $bcc (Optional) BCC email address or an array of email addresses and names such as [["john@host.com", "John Doe"], ["jane@host.com", "Jane Doe"], ...]
     * @param string|array $attachments (Optional) Array of files with the filename and mime type such as [ [ "/absolute/local/file1.txt" => ["file1.txt", "text/plain" ]], [ "/absolute/local/file2.txt" => ["file2.txt", "text/plain" ]], ... ]
     * @param string|array $embeddedImages (Optional) Array of image files with the filename and mime type such as [ [ "/absolute/local/pic1.jpg" => ["JohnsAvatar.jpg", "image/jpeg" ]], [ "/absolute/local/pic2.png" => ["JanesAvatar.png", "image/png" ]], ... ]
     * @param string $replyTo (Optional) Reply to remail address
     * @param boolean $inlineCss (Optional) Whether or not css class should be converted to inline styles
     * @param mixed $sendResultDetails (Optional) When passed, the variable may be filled by implementor-specific details about the transaction
     */
    public function SendEmail($subject, $from, $to, $htmlContents, $plainContents = null, $cc = null, $bcc = null, $attachments = null, $embeddedImages = null, $replyTo = null, $inlineCss = false, $additionalHeaders = null, &$sendResultDetails = null)
    {
        $url  = "https://mandrillapp.com/api/1.0/messages/send.json";
        $sendInfo =
        [
            "key"=>self::$_apiKey,
            "message"=>
            [
                "html"=>$htmlContents,
                "text"=>$plainContents?$plainContents:"",
                "subject"=>$subject,
                "from_email"=>  is_array($from)?$from[0]:$from,
                "from_name"=> is_array($from)?$from[1]:"",
                "to"=>
                [
                ],
                "headers"=>
                [
                    "Reply-To"=>$replyTo?$replyTo:""
                ],
                "inline_css"=>$inlineCss?"true":"false"
            ]

        ];
        if (is_array($additionalHeaders))
        {
            foreach ($additionalHeaders as $additionalHeadersName=>$additionalHeadersVal)
            {
                if (!is_int($additionalHeadersName))
                {
                    $sendInfo["message"]["headers"][$additionalHeadersName] = $additionalHeadersVal;
                }
            }
        }
        if (!is_array($to))
        {
            $to = $to?[$to=>""]:[];
        }
        if (!is_array($cc))
        {
            $cc = $cc?[$cc=>""]:[];
        }
        if (!is_array($bcc))
        {
            $bcc = $bcc?[$bcc=>""]:[];
        }
        foreach ($to as $toEmail=>$toName)
        {
            $sendInfo["message"]["to"][] =
                 [
                     "email"=>$toEmail,
                     "name"=>$toName?$toName:"",
                     "type"=>"to"
                 ];
        }
        foreach ($cc as $toEmail=>$toName)
        {
            $sendInfo["message"]["to"][] =
                 [
                     "email"=>$toEmail,
                     "name"=>$toName?$toName:"",
                     "type"=>"cc"
                 ];
        }
        foreach ($bcc as $toEmail=>$toName)
        {
            $sendInfo["message"]["to"][] =
                 [
                     "email"=>$toEmail,
                     "name"=>$toName?$toName:"",
                     "type"=>"bcc"
                 ];
        }
        if (count($attachments))
        {
            $sendInfo["message"]["attachment"] = [];
            foreach ($attachments as $localFilename=>$fileInfoArray)
            {
                if (file_exists($localFilename))
                {
                    $sendInfo["message"]["attachment"][] =
                    [
                        "type"=>$fileInfoArray[1],
                        "name"=>$fileInfoArray[0],
                        "content"=>  base64_encode(file_get_contents($localFilename))
                    ];
                } else {
                    throw new \Exception("Invalid attachment specified ($localFilename)");
                }
            }
        }
        if (count($embeddedImages))
        {
            $sendInfo["message"]["images"] = [];
            foreach ($embeddedImages as $localFilename=>$fileInfoArray)
            {
                if (file_exists($localFilename))
                {
                    $sendInfo["message"]["images"][] =
                    [
                        "type"=>$fileInfoArray[1],
                        "name"=>$fileInfoArray[0],
                        "content"=>  base64_encode(file_get_contents($localFilename))
                    ];
                } else {
                    throw new \Exception("Invalid attachment specified ($localFilename)");
                }
            }
        }
        $sendJson = JsonUtil::EncodeJson($sendInfo);
        $response = Util::SendRequest($url, true, $sendJson);
        $response = JsonUtil::DecodeJson($response);
        $sendSucceed = isset($response[0])?isset($response[0]["status"])?$response[0]["status"] == "sent":false:false;
        if (!$sendSucceed)
        {
            $sendResultDetails = isset($response[0]["reject_reason"])?$response[0]["reject_reason"]:print_r($response, true);
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