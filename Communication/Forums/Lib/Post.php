<?php
namespace Wafl\Extensions\Forums\Lib;
class Post extends \DblEj\Data\PersistableModel
{
    public static function Get_StorageLocation()
    {
        return \Wafl\Extensions\Forums\Forums::$TablePrefix."Posts";
    }
    public static function Get_FieldNames()
    {
        return array("PostId", "UserId", "ParentThreadId",
                     "Post", "PostDate", "PostParsed");
    }
    public static function Get_KeyFieldName()
    {
        return "PostId";
    }
    public static function Get_KeyValueIsGeneratedByEngine()
    {
        return true;
    }
}