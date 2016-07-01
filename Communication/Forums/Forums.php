<?php
namespace Wafl\Extensions\Communication\Forums;
use DblEj\Extension\ExtensionSitePage;

class Forums extends \DblEj\Extension\ExtensionBase
implements \DblEj\Communication\Integration\IDiscussionProviderExtension
{
    private static $_sitePages;
    private static $_databaseInstallScripts;
    public static $TablePrefix;
    private static $_autoInstall;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        require_once(__DIR__."/Config/Database.php");
        \DblEj\EventHandling\Events::AddHandler(\DblEj\Util\SystemEvents::AFTER_INITIALIZE, "AfterInitialize_handler", $this);
    }

    public function AfterInitialize_handler()
    {
        if (!\Wafl\Core::$STORAGE_ENGINE->IsReady())
        {
            \Wafl\Core::AppendError("Could not initialize forums because the data engine is not ready");
        }
    }

    public function PrepareSitePage($pagename)
    {
        //need some sort of auto-prepare where it will call a php file
        //by the same name as the template and avoid long switch statements
        switch ($pagename)
        {
            case "Forum Home Page":
                $allCategories = \Wafl\Extensions\Communication\Forums\Lib\Category::LoadAll(\Wafl\Core::$STORAGE_ENGINE);
                \Wafl\Core::AssignHtmlVar("ALL_CATEGORIES", $allCategories);
                \Wafl\Core::AssignHtmlVar("FORUMS_PAGE", "HomePage.tpl");
                break;
            case "Forum":
                $forumid = isset($_REQUEST["ForumId"])?$_REQUEST["ForumId"]:null;
                if ($forumid)
                {
                    \Wafl\Core::AssignHtmlVar("FORUM", new Lib\Forum($forumid));
                }
                \Wafl\Core::AssignHtmlVar("FORUMS_PAGE", "Forum.tpl");
                break;
            case "NewPost":
                $validationErrors = array();
                $action = isset($_POST["Action"])?$_POST["Action"]:null;
                $forumid = isset($_REQUEST["ForumId"])?$_REQUEST["ForumId"]:null;
                $threadid = isset($_REQUEST["ThreadId"])?$_REQUEST["ThreadId"]:null;
                $newThread = null;
                if ($action == "NewPost")
                {
                    $postText = isset($_REQUEST["PostText"])?$_REQUEST["PostText"]:null;
                    if (!$threadid)
                    {
                        $threadTitle = isset($_REQUEST["ThreadTitle"])?$_REQUEST["ThreadTitle"]:null;
                        if (!$threadTitle)
                        {
                            array_push($validationErrors, Forums::GetLocalizedText("ValidationErrorNewThreadNeedsTitle"));
                        }
                        if (!$forumid)
                        {
                            array_push($validationErrors, Forums::GetLocalizedText("ValidationErrorNewThreadNeedsValidForumId"));
                        }
                        if (count($validationErrors) == 0)
                        {
                            $newThread = new Lib\Thread();
                            $newThread->Set_DateCreated(time());
                            $newThread->Set_Title($threadTitle);
                            $newThread->Set_ParentForumId($forumid);
                            $newThread->Set_UserId(\Wafl\Core::$CURRENT_USER);
                            $newThread->Save();
                            $threadid = $newThread->Get_ThreadId();
                        }
                    }
                    if ($threadid)
                    {
                        if ($newThread)
                        {
                            $thread = $newThread;
                        } else {
                            $thread = new Lib\Forum($threadid);
                        }

                        $newPost = new Lib\Post();
                        $newPost->Save();

                        \Wafl\Core::AssignHtmlVar("FORUM", new Lib\Forum($thread->Get_ParentForumId()));
                        \Wafl\Core::AssignHtmlVar("THREAD", $thread);
                        \Wafl\Core::AssignHtmlVar("FORUMS_PAGE", "Thread.tpl");
                    } else {
                        \Wafl\Core::AssignHtmlVar("FORUM", new Lib\Forum($forumid));
                        \Wafl\Core::AssignHtmlVar("VALIDATION_ERRORS", $validationErrors);
                        \Wafl\Core::AssignHtmlVar("FORUMS_PAGE", "NewPost.tpl");
                    }
                } else {
                    if ($forumid)
                    {
                        \Wafl\Core::AssignHtmlVar("FORUM", new Lib\Forum($forumid));
                    }
                    if ($threadid)
                    {
                        $thread = new Lib\Forum($threadid);
                        \Wafl\Core::AssignHtmlVar("FORUM", new Lib\Forum($thread->Get_ParentForumId()));
                        \Wafl\Core::AssignHtmlVar("THREAD", $thread);
                    }
                    \Wafl\Core::AssignHtmlVar("FORUMS_PAGE", "NewPost.tpl");
                }
                break;
        }
    }

    public function Get_RequiresInstallation()
    {
        return self::$_autoInstall?(!\Wafl\Core::$STORAGE_ENGINE->DoesLocationExist(Forums::$TablePrefix."Settings")):false;
    }
	public static function Get_Dependencies() {
		$depends = new \DblEj\Extension\DependencyCollection();
		$depends->AddDependency("Forums", \DblEj\Extension\Dependency::TYPE_EXTENSION, "WissyWig", \DblEj\Extension\Dependency::TYPE_CONTROL);
		return $depends;
	}
    public static function Get_SitePages()
    {
        if (self::$_sitePages == null)
        {
            self::$_sitePages = array();
            self::$_sitePages["Forum Home Page"] = new ExtensionSitePage("Forums", "", "Forums/Presentation/ForumsMain");
            self::$_sitePages["Forum"] = new ExtensionSitePage("Forum", "", "Forums/Presentation/ForumsMain");
            self::$_sitePages["Thread"] = new ExtensionSitePage("Thread", "", "Forums/Presentation/ForumsMain");
            self::$_sitePages["Post"] = new ExtensionSitePage("Post", "", "Forums/Presentation/ForumsMain");
            self::$_sitePages["NewPost"] = new ExtensionSitePage("NewPost", "", "Forums/Presentation/ForumsMain");
        }
        return self::$_sitePages;
    }

    public static function Get_GlobalStylesheets()
    {
        return array(new \DblEj\UI\Stylesheet("", "Forum.css"));
    }
    public static function Get_GlobalScripts()
    {
        return array();
    }
    public static function Get_DatabaseInstallScripts()
    {
        return self::$_databaseInstallScripts;
    }
    public static function Set_DatabaseInstallScripts($installScripts)
    {
        self::$_databaseInstallScripts = $installScripts;
    }

	protected function ConfirmedConfigure($settingName, $settingValue) {

	}

	public function GetRaisedEventTypes() {

	}

	public static function Get_DatabaseInstalledTables() {

	}

	public static function Get_TablePrefix() {

	}
    public static function Get_WebOnly()
    {
        return true;
    }
}
?>