<?php
namespace Wafl\Extensions\Fonts\LocalFonts;
class LocalFonts
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Presentation\Integration\IFontProviderExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }
	public function Get_StylesheetBaseUrl()
	{
		return "/AppFonts.css";
	}

	public function Get_Title()
	{
		return "Local Fonts";
	}

}