<?php

namespace Wafl\Extensions\Communication\BlogAdmin;

use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;

class BlogAdmin extends ExtensionBase
{
	private static $_tablePrefix;
	private static $_sitePages;
	private static $_storageGroup;
    private static $_autoInstall;


    private static $_userClass;
    private static $_layoutTemplate = "Master/MainLayout.tpl";
    private static $_tagsTable;
    private static $_tagModel;
    private static $_tagKey;
    private static $_tagField;
    private static $_title;
    private static $_description;
    private static $_baseUrl;

	public function Initialize(\DblEj\Application\IApplication $app)
	{
        Models\FunctionalModel\BlogCategory::Set_Extension($this);
        Models\FunctionalModel\BlogPost::Set_Extension($this);
        Models\FunctionalModel\BlogPostTag::Set_Extension($this);
	}

    public static function TranslateUrl(\DblEj\Communication\Http\Request $request)
    {
        $extensionPath = "/Extensions/Communication/BlogAdmin/";
        $extensionPathLength = strlen($extensionPath);
        if (strlen($request->Get_RequestUrl()) >= strlen($extensionPath))
        {
            if (substr($request->Get_RequestUrl(), 0, strlen($extensionPath)) == $extensionPath)
            {
                $url = substr($request->Get_RequestUrl(), $extensionPathLength);
                if ($url == "")
                {
                    $request->Set_RequestUrl($extensionPath."Home");
                }
            }
        }
        return $request;
    }
	protected static function getAvailableSettings()
	{
		return array("TablePrefix", "AutoInstall", "StorageGroup", "TagsTable", "TagModel", "TagKey", "TagField", "Title", "LayoutTemplate", "Description", "UserClass", "BaseUrl");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
            case "UserClass":
                self::$_userClass = $settingValue;
                break;
			case "TablePrefix":
				self::$_tablePrefix = $settingValue;
				break;
            case "StorageGroup":
                self::$_storageGroup = $settingValue;
                break;
            case "TagsTable":
                self::$_tagsTable = $settingValue;
                break;
            case "TagModel":
                self::$_tagModel = $settingValue;
                break;
            case "TagKey":
                self::$_tagKey = $settingValue;
                break;
            case "TagField":
                self::$_tagField = $settingValue;
                break;
            case "Title":
                self::$_title = $settingValue;
                break;
            case "LayoutTemplate":
                self::$_layoutTemplate = $settingValue;
                break;
            case "Description":
                self::$_description = $settingValue;
                break;
            case "BaseUrl":
                self::$_baseUrl = $settingValue;
                break;
			case "AutoInstall":
                self::$_autoInstall = $settingValue;
				break;
		}
	}
    protected function getLocalSettingValue($settingName)
    {
		switch ($settingName)
		{
			case "TablePrefix":
				return self::$_tablePrefix;
				break;
            case "StorageGroup":
                return self::$_storageGroup;
                break;
            case "TagsTable":
                return self::$_tagsTable;
                break;
            case "UserClass":
                return self::$_userClass;
                break;
            case "TagModel":
                return self::$_tagModel;
                break;
            case "TagKey":
                return self::$_tagKey;
                break;
            case "TagField":
                return self::$_tagField;
                break;
            case "Title":
                return self::$_title;
                break;
            case "LayoutTemplate":
                return self::$_layoutTemplate;
                break;
            case "Description":
                return self::$_description;
                break;
            case "BaseUrl":
                return self::$_baseUrl;
                break;
			case "AutoInstall":
                return self::$_autoInstall;
				break;
		}
    }

    public static function Get_UserClass()
    {
        return self::$_userClass;
    }
    public static function Get_TagModel()
    {
        return self::$_tagModel;
    }
	public function PrepareSitePage($pageName)
	{
		parent::PrepareSitePage($pageName);
		
		
	}
	public function Get_RequiresInstallation()
	{
		 return self::$_autoInstall?(!\Wafl\Core::$STORAGE_ENGINE->DoesLocationExist(self::$_tablePrefix."BlogPosts")):false;
	}

	public static function Get_DatabaseInstallScripts()
	{
		return array(realpath(__DIR__.DIRECTORY_SEPARATOR."Config/CreateTables.sql"));
	}

	public static function Get_DatabaseInstalledTables()
	{
		return array(
			self::$_tablePrefix."BlogCategories",
			self::$_tablePrefix."BlogPosts",
			self::$_tablePrefix."BlogPostTags"
		);
	}

	public static function Get_Dependencies()
	{
		$depends = new DependencyCollection();
		return $depends;
	}

	public static function Get_GlobalScripts()
	{
		return array();
	}

	public static function Get_GlobalStylesheets()
	{
		return array(
		);
	}

	public static function Get_SitePages()
	{
        if (self::$_sitePages == null)
        {
            self::$_sitePages = array();
            self::$_sitePages["Home"] = new \DblEj\Extension\ExtensionSitePage("Blog.Admin.LandingPage", "", "Communication/BlogAdmin/Presentation/Templates/Home.tpl");
            self::$_sitePages["BlogPostEdit"] = new \DblEj\Extension\ExtensionSitePage("Blog.Admin.EditBlogPage", "", "Communication/BlogAdmin/Presentation/Templates/BlogPostEdit.tpl");
        }
        return self::$_sitePages;
	}

	public static function Get_TablePrefix()
	{
		return self::$_tablePrefix;
	}
    public static function Get_AutoInstall()
    {
        return self::$_autoInstall;
    }
    
	public function Get_IsReady()
	{
		return true;
	}

	public function GetSettingDefault($settingName)
	{
        switch ($settingName)
        {
            case "TablePrefix":
                return "";
            case "StorageGroup":
                return "Default";
            case "UserClass":
                return "\\Wafl\\Users\\User";
            case "TagsTable":
                return "Tags";
            case "TagModel":
                return "Tag";
            case "TagKey":
                return "TagId";
            case "TagField":
                return "Tag";
            case "Title":
                return "My Blog Admin";
            case "Description":
                return "Just another ActiveWAFL blog";
            case "LayoutTemplate":
                return "Master/MainLayout.tpl";
            case "BaseUrl":
                return "/Extensions/Communication/BlogAdmin/";
        }
	}

	public function GetRaisedEventTypes()
	{
		return array();
	}
    public static function Get_WebOnly()
    {
        return true;
    }
}