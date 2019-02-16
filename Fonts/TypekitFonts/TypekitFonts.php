<?php
namespace Wafl\Extensions\Fonts\TypekitFonts;
class TypekitFonts
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Presentation\Integration\IFontProviderExtension
{
    private $_cssFilename;
    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }

    protected function ConfirmedConfigure($settingName, $settingValue) {
        parent::ConfirmedConfigure($settingName, $settingValue);
        if ($settingName == "CssFilename")
        {
            $this->_cssFilename = $settingValue;
        }
    }
    protected static function getAvailableSettings() {
        $settings = parent::getAvailableSettings();
        
        $settings[] = "CssFilename";
        
        return $settings;
    }
    
    public function Get_StylesheetBaseUrl()
    {
            return "//use.typekit.net/".$this->_cssFilename;
    }

    public function Get_Title()
    {
            return "Typekit Fonts";
    }

}