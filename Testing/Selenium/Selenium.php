<?php

namespace Wafl\Extensions\Testing\Selenium;

final class Selenium extends \DblEj\Extension\ExtensionBase implements \DblEj\AutomatedTesting\Integration\ITesterExtension
{
	private $_remoteEngineUri	 = "127.0.0.1";
	private $_browserToEmulate;
	private $_outputLogFile;
	private $_testsFolder;
	private $_echoOutput		 = false;
	private $_webDriver;
	private $_initialized = false;

	final public function Initialize(\DblEj\Application\IApplication $app)
	{
		$this->_initialized = true;
		require_once("phar://" . __DIR__ . "/SeleniumPhp.phar/__init__.php");
		if ($this->_browserToEmulate == null)
		{
			$this->_browserToEmulate = \WebDriverBrowserType::HTMLUNIT;
		}
		\Wafl\Util\Testing::SetIntegrationTestingEngine($this);
	}

	final public function GetWebDriver()
	{
		//todo: dont connect until actually run test
		putenv("DISPLAY=:10");
		if (!$this->_webDriver)
		{
			$this->_webDriver = \RemoteWebDriver::create(
                $this->_remoteEngineUri,
                array(
                    \WebDriverCapabilityType::JAVASCRIPT_ENABLED => false,
                    \WebDriverCapabilityType::BROWSER_NAME		 => $this->_browserToEmulate,
                    \WebDriverCapabilityType::ACCEPT_SSL_CERTS   => true
                )
            );
			//$this->_webDriver = new \FirefoxDriver();
			//$this->_webDriver->setJavascriptEnabled(true);
		}
		return $this->_webDriver;
	}

	final public function RunTest()
	{
		if ($this->_echoOutput)
		{
			print "Selenium\n";
		}
		$unitTester = \Wafl\Util\Testing::GetUnitTestingEngine();
		$unitTester->Configure("TestsFolder", $this->_testsFolder);
		$unitTester->Configure("OutputLogFile", $this->_outputLogFile);
		$unitTester->Configure("EchoOutput", $this->_echoOutput);
		$this->GetWebDriver();
		return $unitTester->RunTest();
	}

	final public function Shutdown()
	{
		if ($this->_webDriver)
		{
			$this->_webDriver->quit();
		}
		$this->_webDriver = null;
	}

	final public function GetUnderlyingEngine()
	{
		return $this->_webDriver;
	}

	protected static function getAvailableSettings()
	{
		return array(
			"TestsFolder",
			"OutputLogFile",
			"EchoOutput",
			"RemoteEngineUri",
			"BrowserToEmulate");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "RemoteEngineUri":
				$this->_remoteEngineUri	 = $settingValue;
				break;
			case "BrowserToEmulate":
                require_once("phar://" . __DIR__ . "/SeleniumPhp.phar/__init__.php");
                if ($settingValue == "firefox")
                {
                    $this->_browserToEmulate = \WebDriverBrowserType::FIREFOX;
                } else {
                    $this->_browserToEmulate = $settingValue;
                }
				break;
			case "TestsFolder":
				$this->_testsFolder		 = $settingValue;
				break;
			case "OutputLogFile":
				$this->_outputLogFile	 = $settingValue;
				break;
			case "EchoOutput":
				$this->_echoOutput		 = $settingValue;
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
		$depends = new \DblEj\Extension\DependencyCollection();
		$depends->AddDependency("Selenim", \DblEj\Extension\Dependency::TYPE_EXTENSION, "PhpUnit",
						  \DblEj\Extension\Dependency::TYPE_EXTENSION);
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
		return array();
	}

	public static function Get_TablePrefix()
	{
		return null;
	}

	public function GetRaisedEventTypes()
	{
		return array();
	}

}