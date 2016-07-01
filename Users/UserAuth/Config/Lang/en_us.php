<?php
namespace Wafl\Extensions\Users\UserAuth\Conf\Lang;
class en_us implements \DblEj\Extension\ILanguageFile
{
    public final static function GetTitle()
    {
        return "American English";
    }
    public final static function GetIsoCode()
    {
        return "en-us";
    }
    public final static function GetText($textName)
    {
        return self::$$textName;
    }
}
?>
