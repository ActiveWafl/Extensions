<?php

namespace Wafl\Extensions\Communication\Blog;

use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;

class Blog extends ExtensionBase
implements \DblEj\Communication\Integration\IPostSharerExtension
{
	private static $_tablePrefix;
	private static $_sitePages;
	private static $_storageGroup;
    private static $_autoInstall;


    private static $_userClass = "\\Wafl\\Users\\User";
    private static $_tagsTable = "Tags";
    private static $_tagModel = "Tag";
    private static $_tagKey = "TagId";
    private static $_tagField = "Tag";
    private static $_title = "My Blog";
    private static $_description = "Just another ActiveWAFL blog";
    private static $_baseUrl = "/Extensions/Communication/Blog/";
    private static $_layoutTemplate = "Master/MainLayout.tpl";
    private static $_sidePostsCategories = null;
    private static $_sidePostsCount = 20;
    private static $_tokenReplacements = [];

	public function Initialize(\DblEj\Application\IApplication $app)
	{
        Models\FunctionalModel\BlogCategory::Set_Extension($this);
        Models\FunctionalModel\BlogPost::Set_Extension($this);
        Models\FunctionalModel\BlogPostTag::Set_Extension($this);
	}

    public static function AddTokenReplacement($token, $replacement)
    {
        self::$_tokenReplacements[$token] = $replacement;
    }

    public static function GetTokenReplacements()
    {
        return self::$_tokenReplacements;
    }

    public static function TranslateUrl(\DblEj\Communication\Http\Request $request)
    {
        $extensionPath = "/Extensions/Communication/Blog/";
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
                elseif (substr($url, 0, 7) == "Search?" || $url == "Search")
                {
                    $request->Set_RequestUrl($extensionPath."Search");
                }
                elseif (substr($url, 0, 4) == "Tag?" || $url == "Tag")
                {
                    $request->Set_RequestUrl($extensionPath."Tag");
                }
                elseif (substr($url, 0, 9) == "Category?" || $url == "Category")
                {
                    $request->Set_RequestUrl($extensionPath."Category");
                }
                else
                {
                    $blogPostTitle = $url;
                    $blogPostTitle = preg_replace("/[^A-Za-z0-9]/", "-", $blogPostTitle); //sanitize to protect against injection
                    $blogPost = Models\FunctionalModel\BlogPost::FilterFirst("UrlTitle = '$blogPostTitle'");
                    if (!$blogPost)
                    {
                        throw new \Exception("Invalid blog post");
                    }
                    $request->Set_RequestUrl($extensionPath."BlogPost");
                    $request->SetInput("BlogPostId", $blogPost->Get_BlogPostId());
                }
            }
        }
        return $request;
    }
	protected static function getAvailableSettings()
	{
		return array("TablePrefix", "AutoInstall", "StorageGroup", "TagsTable", "TagModel", "TagKey", "TagField", "Title", "Description", "UserClass", "BaseUrl", "LayoutTemplate", "SidePostsCategories", "SidePostsCount");
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
            case "Description":
                self::$_description = $settingValue;
                break;
            case "BaseUrl":
                self::$_baseUrl = $settingValue;
                break;
            case "LayoutTemplate":
                self::$_layoutTemplate = $settingValue;
                break;
            case "SidePostsCategories":
                self::$_sidePostsCategories = $settingValue;
                break;
            case "SidePostsCount":
                self::$_sidePostsCount = $settingValue;
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
            case "Description":
                return self::$_description;
                break;
            case "BaseUrl":
                return self::$_baseUrl;
                break;
            case "LayoutTemplate":
                return self::$_layoutTemplate;
                break;
            case "SidePostsCategories":
                return self::$_sidePostsCategories;
                break;
            case "SidePostsCount":
                return self::$_sidePostsCount;
                break;
			case "AutoInstall":
                return self::$_autoInstall;
				break;
		}
    }
    public static function Get_AutoInstall()
    {
        return self::$_autoInstall;
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
        $depends->AddDependency("Communication\\Blog", \DblEj\Extension\Dependency::TYPE_EXTENSION, "FixedScroller", \DblEj\Extension\Dependency::TYPE_CONTROL);
		return $depends;
	}

	public static function Get_GlobalScripts()
	{
		return array();
	}

	public static function Get_GlobalStylesheets()
	{
		return array();
	}

	public static function Get_SitePages()
	{
        if (self::$_sitePages == null)
        {
            self::$_sitePages = array();
            self::$_sitePages["Home"] = new \DblEj\Extension\ExtensionSitePage("Blog.LandingPage", "", "Communication/Blog/Presentation/Templates/Home.tpl");
            self::$_sitePages["Category"] = new \DblEj\Extension\ExtensionSitePage("Blog.Category", "", "Communication/Blog/Presentation/Templates/Category.tpl");
            self::$_sitePages["BlogPost"] = new \DblEj\Extension\ExtensionSitePage("Blog.Post", "", "Communication/Blog/Presentation/Templates/BlogPost.tpl");
            self::$_sitePages["Search"] = new \DblEj\Extension\ExtensionSitePage("Blog.Search", "", "Communication/Blog/Presentation/Templates/Search.tpl");
            self::$_sitePages["Tag"] = new \DblEj\Extension\ExtensionSitePage("Blog.Tags", "", "Communication/Blog/Presentation/Templates/Tag.tpl");
        }
        return self::$_sitePages;
	}

	public static function Get_TablePrefix()
	{
		return self::$_tablePrefix;
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
                return "My Blog";
            case "Description":
                return "Just another ActiveWAFL blog";
            case "BaseUrl":
                return "/Extensions/Communication/Blog/";
            case "LayoutTemplate":
                return "Master/MainLayout.tpl";
            case "SidePostsCategories":
                return "";
            case "SidePostsCount":
                return 20;
			case "AutoInstall":
                return true;
				break;
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