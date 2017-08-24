<?php

namespace Wafl\Extensions\Templating\Smarty3;

use DblEj\Application\IApplication,
    DblEj\Extension\ExtensionBase,
    DblEj\Extension\DependencyCollection,
    DblEj\Text\Minifiers\Html,
    DblEj\Presentation\ITemplate,
    DblEj\Presentation\RenderOptions,
    DblEj\UI\Stylesheet,
    Exception,
    Wafl\Core,
    Wafl\Util\Template;

class Smarty3 extends ExtensionBase implements \DblEj\Presentation\Integration\ITemplateRendererExtension
{
	/**
	 * @var \Smarty
	 */
	private $_smarty;
	private $_errorReporting = E_ALL;
	private $_templateFolders;
	private $_compileFolder;
	private $_cacheFolder;
	private $_configFolder;
	private $_pluginFolders;
	private $_enableCaching	 = true;
	private $_forceCompile	 = false;
	private $_amReady		 = false;

	public function Initialize(IApplication $app)
	{
		require_once("phar://" . __DIR__ . "/Smarty3.1.30.phar/Smarty.class.php");
		$this->_smarty = new \Smarty();
		$this->AddPluginFolder(__DIR__ . "/Plugins/");
		foreach ($this->_pluginFolders as $pluginFolder)
		{
			$this->AddPluginFolder($pluginFolder);
		}

		$this->_smarty->setTemplateDir($this->_templateFolders);
		$this->_smarty->setConfigDir($this->_configFolder);
		$this->_smarty->setCacheDir($this->_cacheFolder);
		$this->_smarty->setCompileDir($this->_compileFolder);
		$this->_smarty->setCaching($this->_enableCaching?\Smarty::CACHING_LIFETIME_CURRENT:\Smarty::CACHING_OFF);
		$this->_smarty->force_compile	= $this->_forceCompile;
		$this->_smarty->error_reporting = $this->_errorReporting;
        $this->_smarty->inheritance_merge_compiled_includes = false;
        $this->_smarty->autoload_filters = array('pre' => array('attribute_values', 'repeater','display_condition','repeater_docs','display_condition_docs'));
	}

    public function DeleteCache($includeCompiled=false)
    {
        if (file_exists($this->_cacheFolder))
        {
            $files = \DblEj\Util\Folder::GetFiles($this->_cacheFolder, false, "*.php");
            foreach ($files as $file)
            {
                unlink($file);
            }
            if ($includeCompiled)
            {
                $files = \DblEj\Util\Folder::GetFiles($this->_compileFolder, false, "*.php");
                foreach ($files as $file)
                {
                    unlink($file);
                }
            }
        }
    }
	public function GetUnderlyingEngine()
	{
		return $this->_smarty;
	}

	public function PostInit()
	{
		$this->_smarty->error_reporting	 = $this->_errorReporting;
		//\Smarty::muteExpectedErrors();
		$this->_amReady					 = true;
	}

	public function RenderString($string, RenderOptions $renderOptions)
	{
		$smartyData	 = $this->_smarty->createData($this->_smarty);
		$tokens		 = $renderOptions->GetTokens();

		foreach ($tokens as $tokenVar=>$tokenVal)
		{
			$smartyData->assign($tokenVar, $tokenVal);
		}
		if (count($tokens)==1) { $tokens = reset($tokens); }
		$smartyData->assign("MODEL", $tokens);
		$template = $this->_smarty->createTemplate("string:" . $string, $renderOptions->Get_Key1(), $renderOptions->Get_Key2(), $smartyData);
		$renderedTemplate	 = $template->fetch();

		if (isset($renderOptions) && $renderOptions->Get_MinifyWhenPossible())
		{
			$renderedTemplate = Html::Minify($renderedTemplate);
		}
		return $renderedTemplate;


	}

	public function Render(ITemplate $template, RenderOptions $renderOptions)
	{
		$this->_smarty->setCaching($renderOptions->Get_UseServerSideCache()?\Smarty::CACHING_LIFETIME_SAVED:\Smarty::CACHING_OFF);
        $this->_smarty->setCacheLifetime($renderOptions->Get_UseServerSideCache()?$renderOptions->Get_ServerSideCacheTimeout():0);

		$smartyData	 = $this->_smarty->createData($this->_smarty);
		$tokens		 = $renderOptions->GetTokens();

		foreach ($tokens as $tokenVar=>$tokenVal)
		{
			$smartyData->assign($tokenVar, $tokenVal);
		}
		if (\DblEj\Util\Strings::EndsWith($template->Get_Filename(), ".tpl"))
		{
			$templateFilename = $template->Get_Filename();
		} else {
			$templateFilename = $template->Get_Filename().".tpl";
		}

		$template			= $this->_smarty->createTemplate($templateFilename, $renderOptions->Get_Key1(), $renderOptions->Get_Key2(), $smartyData);
        try
        {
            $renderedTemplate	= $template->fetch(null, $renderOptions->Get_Key1());
        }
        catch (\Exception $ex)
        {
            if (stristr($ex->getFile(), ".tpl") > -1)
            {
                $templateContents = file_get_contents($ex->getFile());
                $templateContents = str_replace("\r\n", "\n", $templateContents);
                $templateContentsArray = explode("\n", $templateContents);
                $badLine = $templateContentsArray[$ex->getLine()-1];
                if (stristr($badLine, "tpl_vars['") > -1)
                {
                    $badLine = str_replace("tpl_vars['", "{\$", $badLine);
                    $badLine = str_replace("']->value", "}", $badLine);
                    $badLine = str_replace("\$_smarty_tpl->", "", $badLine);
                    $badLine = str_replace("<?php echo", "", $badLine);
                    $badLine = str_replace("?>", "", $badLine);

                    throw new \Exception($ex->getMessage()."<br>Possibly caused by an invalid template variable in: <code>". htmlentities($badLine) . "</code>");
                } else {
                    throw new \Exception($ex->getMessage()."<br>in: <code>". htmlentities($badLine) . "</code>");
                }
            } else {
                throw($ex);
            }
        }

        if (isset($renderOptions) && $renderOptions->Get_MinifyWhenPossible())
		{
			$renderedTemplate = Html::Minify($renderedTemplate);
		}
		return $renderedTemplate;
	}

	public function AddTemplateFolder($folder, $priority = null)
	{

		if ($priority == null) { $priority = 100; };
		while ($this->_smarty->getTemplateDir($priority) != null)
		{
			$priority++;
		}
		$resolvedPath = realpath($folder);
		if ($resolvedPath)
		{
			$this->_smarty->addTemplateDir($folder, $priority);
		} else {
			throw new Exception("Invalid template directory: $folder");
		}
	}

	public function AddPluginFolder($folder)
	{
		$this->_smarty->addPluginsDir($folder);
	}

	protected static function getAvailableSettings()
	{
		return array(
			"TemplateFolders",
			"CompileFolder",
			"CacheFolder",
			"ConfigFolder",
			"PluginFolders",
			"EnableCaching",
			"ErrorReporting",
			"ForceCompile");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "TemplateFolders":
				$this->_templateFolders	 = $settingValue;
				break;
			case "CompileFolder":
				$this->_compileFolder	 = $settingValue;
				break;
			case "CacheFolder":
				$this->_cacheFolder		 = $settingValue;
				break;
			case "ConfigFolder":
				$this->_configFolder	 = $settingValue;
				break;
			case "PluginFolders":
				$this->_pluginFolders	 = $settingValue;
				break;
			case "EnableCaching":
				$this->_enableCaching	 = $settingValue;
				break;
			case "ErrorReporting":
				$this->_errorReporting	 = $settingValue;
				break;
			case "ForceCompile":
				$this->_forceCompile	 = $settingValue;
				break;
		}
	}

	public function Get_RequiresInstallation()
	{
		return false;
	}

	public function PrepareSitePage($pageName)
	{

	}

	public static function Get_DatabaseInstallScripts()
	{
		return array();
	}

	public static function Get_DatabaseInstalledTables()
	{
		return array();
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
			new Stylesheet("SyntaxHighlighting", "block.highlightcode.css", false, false),
			new Stylesheet("ConsoleOuput", "block.console.css", false, false)
		);
	}

	public static function Get_SitePages()
	{
		return array();
	}

	public static function Get_TablePrefix()
	{
		return null;
	}

	public function Get_IsReady()
	{
		return $this->_amReady;
	}

	public function GetSettingDefault($settingName)
	{
		switch ($settingName)
		{
			case "PluginFolders":
			case "TemplateFolders":
				return array();
				break;
			case "EnableCaching":
				return true;
				break;
			default:
				return null;
		}
	}

	public function GetRaisedEventTypes()
	{
		return array();
	}

	public function DoesTemplateExist($template)
	{
		if (!\DblEj\Util\Strings::EndsWith($template, ".tpl"))
		{
			$template.=".tpl";
		}
		return ($this->_smarty->templateExists($template));
	}

	public function DoesCacheExist($template, $cacheId)
	{
		return ($this->_smarty->isCached($template, $cacheId));
	}
	public function GetTemplateFolders()
	{
		$dir = $this->_smarty->getTemplateDir();
		if (!is_array($dir)) {$dir = array($dir);}
		return $dir;
	}

	public function SetGlobalData($variableName,$value)
	{
		$this->_smarty->assign($variableName, $value);
        return $this;
	}
	public function GetGlobalData($variableName)
	{
		return $this->_smarty->getTemplateVars($variableName);
	}
	public function SetStaticData($alias,$fullyQualifiedStaticName)
	{
		$this->_smarty->registerClass($alias, $fullyQualifiedStaticName);
	}

	public function SetGlobalDataReference($dataName, &$value)
	{
		$this->_smarty->assign($dataName,$value);
	}

	public function CreateScopedData()
	{
		return $this->_smarty->createData();
	}

	public function AssignScopedData($scopedData)
	{

	}

    public static function Get_WebOnly()
    {
        return false;
    }
}