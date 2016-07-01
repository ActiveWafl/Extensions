<?php

namespace Wafl\Extensions\Visualization\Reports;

use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;

class Reports extends ExtensionBase implements \DblEj\Data\Visualization\Integration\IReportGeneratorExtension
{
	private static $_sitePages;
	private $_layoutTemplate;
    private $_useDefaultRouter = true;

    private $_reports;
    private $_app;
	public function Initialize(\DblEj\Application\IApplication $app)
	{
		if (is_a($app,"\DblEj\Application\IWebApplication"))
		{
            //framework should take care of putting extensions into default site area if it isnt defined
            //left commented out just in case it breaks something
//            if (!$this->Get_SiteAreaId()) //if it wasnt put into a site area then put it into the first one it finds
//            {
//                $allsiteAreas = $app->Get_SiteMap()->GetSiteAreas();
//                if (count($allsiteAreas)>0)
//                {
//                    $siteArea = array_pop($allsiteAreas);
//                    $this->Set_SiteAreaId($siteArea->Get_AreaId());
//                }
//            }
            $this->_reports = [];
            $this->_app = $app;
            if ($this->_useDefaultRouter)
            {
                \Wafl\Util\HttpRouter::AddRouter(new Routers\Report());
            }
		}
	}

    public function AddReport($reportId, IReport $report)
    {
        $this->_reports[$reportId] = $report;
    }

    public function GetReport($reportId)
    {
        if (isset($this->_reports[$reportId]))
        {
            return $this->_reports[$reportId];
        } else {
            throw new \Exception("Invalid report ($reportId) specified in GetReport.  You must add reports via the AddReport() method before you can get/display the report.");
        }
    }
    public function GetAllReports()
    {
        return $this->_reports;
    }
	protected static function getAvailableSettings()
	{
		return array("LayoutTemplate", "UseDefaultRouter");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
        switch ($settingName)
        {
            case "LayoutTemplate":
                $this->_layoutTemplate = $settingValue;
                break;
            case "UseDefaultRouter":
                $this->_useDefaultRouter = $settingValue;
                break;
        }
	}
	public function PrepareSitePage($pageName)
	{
		parent::PrepareSitePage($pageName);
	}
	public function Get_RequiresInstallation()
	{
		 return false;
	}

	public static function Get_DatabaseInstallScripts()
	{
		return [];
	}

	public static function Get_DatabaseInstalledTables()
	{
		return [];
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
            self::$_sitePages["Report"] = new \DblEj\Extension\ExtensionSitePage("Reports.Report", "", "Visualization/Reports/Presentation/Templates/Report.tpl");
            self::$_sitePages["ReportInput"] = new \DblEj\Extension\ExtensionSitePage("Reports.ReportInput", "", "Visualization/Reports/Presentation/Templates/ReportInput.tpl");
            self::$_sitePages["ReportDirectory"] = new \DblEj\Extension\ExtensionSitePage("Reports.ReportDirectory", "", "Visualization/Reports/Presentation/Templates/ReportDirectory.tpl");
        }
        return self::$_sitePages;
	}

    public static function GetSitePage($pageName)
    {
        return isset(self::$_sitePages[$pageName])?self::$_sitePages[$pageName]:null;
    }

	public static function Get_TablePrefix()
	{
		return "";
	}

	public function Get_IsReady()
	{
		return true;
	}

	public function GetSettingDefault($settingName)
	{
        $returnValue = null;
        switch ($settingName)
        {
            case "LayoutTemplate":
                $returnValue = "Master/MainLayout";
                break;
            case "UseDefaultRouter":
                $returnValue = true;
                break;
        }
        return $returnValue;
	}

    protected function getLocalSettingValue($settingName)
    {
        $returnValue = null;
        switch ($settingName)
        {
            case "LayoutTemplate":
                $returnValue = $this->_layoutTemplate;
                break;
            case "UseDefaultRouter":
                $returnValue = $this->_useDefaultRouter;
                break;
        }
        return $returnValue;
    }

	public function GetRaisedEventTypes()
	{
		return array();
	}
}