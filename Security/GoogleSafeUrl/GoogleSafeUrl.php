<?php
namespace Wafl\Extensions\Security\GoogleSafeUrl;

class GoogleSafeUrl
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Data\Validation\Integration\IUrlCheckerExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }
	static function CheckUrl($apiKey, $url)
	{
		$response = \DblEj\Communication\Http\Util::SendRequest("https://sb-ssl.google.com/safebrowsing/api/lookup?client=Wafl&apikey=$apiKey&appver=1.0&pver=3.0&url=http%3A%2F%2F$url%2F");
		return $response;
	}
}
?>