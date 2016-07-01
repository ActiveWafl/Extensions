<?php

namespace Wafl\Extensions\Testing\PhpUnit;

class PhpUnit extends \DblEj\Extension\ExtensionBase implements \DblEj\AutomatedTesting\Integration\ITesterExtension
{
	private $_outputLogFile;
	private $_testsFolder;
	private $_echoOutput = false;
	private $_bootstrapFile;

	public function Initialize(\DblEj\Application\IApplication $app)
	{
		\Wafl\Util\Testing::SetUnitTestingEngine($this);
	}

	/**
	 * @return \Wafl\Util\UnitTesting\TestResult
	 */
	public function RunTest()
	{
		$returnResult	 = new \DblEj\UnitTesting\TestResult();
		$outputArray	 = array();
		if ($this->_echoOutput)
		{
			print "PhpUnit\n";
		}
		if ($this->_echoOutput)
		{
			print "Test folder: " . $this->_testsFolder . "\n";
			print "Output File: " . $this->_outputLogFile . "\n";
		}
		if (file_exists($this->_outputLogFile))
		{
			unlink($this->_outputLogFile);
		}
		if ($this->_bootstrapFile)
		{
			if ($this->_echoOutput)
			{
				print "Bootstrap file: " . $this->_bootstrapFile . "\n";
				print "running php " . __DIR__ . "/phpunit.phar --log-junit \"$this->_outputLogFile\" --bootstrap \"$this->_bootstrapFile\" \"$this->_testsFolder\"\n";
			}
			exec("php \"" . __DIR__ . "/phpunit.phar\" --no-globals-backup --log-junit \"$this->_outputLogFile\" --bootstrap \"$this->_bootstrapFile\" \"$this->_testsFolder\"",
		$outputArray);
		}
		else
		{
			if ($this->_echoOutput)
			{
				print "No Bootstrap file\n";
			}
			exec("php \"" . __DIR__ . "/phpunit.phar\" --no-globals-backup --log-junit \"$this->_outputLogFile\" \"$this->_testsFolder\"", $outputArray);
		}

		if (!file_exists($this->_outputLogFile) || filesize($this->_outputLogFile) == 0)
		{
			$errorLine = var_export($outputArray, true);
			foreach ($outputArray as $idx => $outputLine)
			{
				if ($idx > 0 && trim($outputLine) != "")
				{
					$errorLine = $outputLine;
					break;
				}
			}
			throw new \Exception($errorLine);
		}
		$xml = file_get_contents($this->_outputLogFile);
		if (!$xml)
		{
			throw new \Exception("The test results were invalid");
		}
		$rawResults = \DblEj\Text\Parsers\JUnit::Parse($xml);
		foreach ($rawResults as $testSuiteName => $testSuite)
		{
			foreach ($testSuite["allCases"] as $testCase)
			{
				$testCaseAttributes	 = $testCase["attributes"];
				$assertions			 = $testCaseAttributes["assertions"];
				$returnResult->AddTestCaseResult($testSuiteName, $testCaseAttributes["name"], $assertions, $testCase["isFailure"] ? true : false,
									 $testCase["isError"] ? true : false, isset($testCase["message"]) ? $testCase["message"] : "",
													 $testCaseAttributes["time"], $testCaseAttributes["file"], $testCaseAttributes["line"]);
			}
		}


		return $returnResult;
	}

	public function Shutdown()
	{

	}

	public function GetUnderlyingEngine()
	{
		return "PHP Unit";
	}

	protected static function getAvailableSettings()
	{
		return array(
			"TestsFolder",
			"OutputLogFile",
			"EchoOutput",
			"BootstrapFile");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "TestsFolder":
				$this->_testsFolder		 = $settingValue;
				break;
			case "OutputLogFile":
				$this->_outputLogFile	 = $settingValue;
				break;
			case "EchoOutput":
				$this->_echoOutput		 = $settingValue;
				break;
			case "BootstrapFile":
				$this->_bootstrapFile	 = $settingValue;
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