<?php
namespace Wafl\Extensions\Forums\Conf\Lang;
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

    public static $ValidationErrorNewThreadNeedsTitle =
    "You must enter a title for the thread";

    public static $ValidationErrorNewThreadNeedsValidForumId =
    "Invalid forum specified";
}
?>
