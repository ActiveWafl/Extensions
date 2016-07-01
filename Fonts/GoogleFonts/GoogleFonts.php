<?php
namespace Wafl\Extensions\Fonts\GoogleFonts;
class GoogleFonts
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Presentation\Integration\IFontProviderExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }
	public function Get_StylesheetBaseUrl()
	{
		return "//fonts.googleapis.com/css?family=%f%a";
	}

	public function Get_Title()
	{
		return "Google Fonts";
	}

}