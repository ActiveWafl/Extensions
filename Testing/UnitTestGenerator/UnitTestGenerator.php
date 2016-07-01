<?php

namespace Wafl\Extensions\Testing\UnitTestGenerator;

class UnitTestGenerator extends \DblEj\Extension\ExtensionBase implements \DblEj\AutomatedTesting\Integration\ITestGeneratorExtension
{
	private $_appName;
	private $_sourceFolder;
	private $_destinationFolder;
	private $_ignorePaths;
	private $_externalFiles;

	const EVENT_GENERATE_UNIT_TEST_BEGIN		 = "EVENT_GENERATE_UNIT_TEST_BEGIN";
	const EVENT_GENERATE_UNIT_TEST_FILE		 = "EVENT_GENERATE_UNIT_TEST_FILE";
	const EVENT_SAVING_TEST_FILE				 = "EVENT_SAVING_TEST_FILE";
	const EVENT_FOUND_CLASS					 = "EVENT_FOUND_CLASS";
	const EVENT_CLASS_NOT_FOUND				 = "EVENT_CLASS_NOT_FOUND";
	const EVENT_ERROR							 = "EVENT_ERROR";
	const EVENT_GENERATE_UNIT_TEST_COMPLETED	 = "EVENT_GENERATE_UNIT_TEST_COMPLETED";

	public function Initialize(\DblEj\Application\IApplication $app)
	{

	}

	public function PrepareSitePage($pageName)
	{

	}

	public static function Get_DatabaseInstallScripts()
	{

	}

	public static function Get_DatabaseInstalledTables()
	{

	}

	public static function Get_Dependencies()
	{
		$depends = new \DblEj\Extension\DependencyCollection(
		array(
			new \DblEj\Extension\Dependency($this, \DblEj\Extension\Dependency::TYPE_EXTENSION, "PhpUnit",
								   \DblEj\Extension\Dependency::TYPE_EXTENSION)
		)
		);
		return $depends;
	}

	public static function Get_GlobalScripts()
	{

	}

	public static function Get_GlobalStylesheets()
	{

	}

	public function Get_ExteralFiles()
	{
		$ignorePaths	 = explode(",", $this->_ignorePaths);
		$ignorePaths[]	 = $this->_destinationFolder;
		$externalFiles	 = array();
		for ($argIdx = 5; $argIdx < $argc; $argIdx++)
		{
			$externalFiles[] = $argv[$argIdx];
		}
		return $this->_externalFiles;
	}

	public static function Get_SitePages()
	{

	}

	public static function Get_TablePrefix()
	{

	}

	protected static function getAvailableSettings()
	{
		return array(
			"AppName",
			"SourceFolder",
			"DestinationFolder",
			"IgnorePaths",
			"SettingFolders");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "AppName":
				$this->_appName				 = $settingValue;
				break;
			case "SourceFolder":
				$this->_sourceFolder		 = $settingValue;
				break;
			case "DestinationFolder":
				$this->_destinationFolder	 = $settingValue;
				break;
			case "IgnorePaths":
				$this->_ignorePaths			 = $settingValue;
				break;
			case "SettingFolders":
				$this->_externalFiles		 = $settingValue;
		}
	}

	public function GenerateTests()
	{
		if (!file_exists($this->_sourceFolder))
		{
			throw new \Exception("Invalid source folder: $this->_sourceFolder");
		}
		if (!file_exists($this->_destinationFolder))
		{
			if (!mkdir($this->_destinationFolder, 0755, true))
			{
				throw new \Exception("Invalid destination folder: $this->_destinationFolder");
			}
		}
		$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_GENERATE_UNIT_TEST_BEGIN));

		$files		 = \DblEj\Util\Folder::GetFiles($this->_sourceFolder, true, "*.php", true, $this->_ignorePaths);
		$filesAdded	 = 0;
		foreach ($files as $file)
		{
			if (substr($file, 0, strlen($this->_sourceFolder)) == $this->_sourceFolder)
			{
				$relativeFile = substr($file, strlen($this->_sourceFolder));
			}
			else
			{
				$relativeFile = $file;
			}
			$relativeFile		 = str_replace($this->_sourceFolder, "", $file);
			$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_GENERATE_UNIT_TEST_FILE, $relativeFile, $this));
			$namespace			 = "";
			$classname			 = "";
			$outputFile			 = "";
			$fhandle			 = fopen($file, "r");
			$phpFileHeaderFound	 = false;
			$lineIdx			 = 0;
			while ($line				 = fgets($fhandle))
			{
				if (strtolower(trim($line)) == "<?php")
				{
					$phpFileHeaderFound = true;
				}
				if ($phpFileHeaderFound)
				{
					if (strtolower(substr(trim($line), 0, 9)) == "namespace")
					{
						$namespace	 = substr(trim($line), 9);
						$eol		 = stripos($namespace, ";");
						$namespace	 = trim(substr($namespace, 0, $eol));
					}
					elseif (strtolower(substr(trim($line), 0, 5)) == "class")
					{
						$classname	 = substr(trim($line), 5);
						$classname	 = $this->stripConstructLine($classname);
						break;
					}
					elseif (strtolower(substr(trim($line), 0, 13)) == "abstract class")
					{
						$classname	 = substr(trim($line), 13);
						$classname	 = $this->stripConstructLine($classname);
						break;
					}
					elseif (strtolower(substr(trim($line), 0, 11)) == "final class")
					{
						$classname	 = substr(trim($line), 11);
						$classname	 = $this->stripConstructLine($classname);
						break;
					}
				}
				$lineIdx++;
			}
			fclose($fhandle);
			if ($classname)
			{
				if ($namespace)
				{
					$fqClass		 = $namespace . "\\" . $classname;
					$outputFolder	 = $this->_destinationFolder . $namespace . DIRECTORY_SEPARATOR;
				}
				else
				{
					$fqClass		 = $classname;
					$outputFolder	 = $this->_destinationFolder;
				}

				if (!file_exists($outputFolder))
				{
					mkdir($outputFolder, 0755, true);
				}
				$outputFile = $outputFolder . $classname . "Test.php";
				$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_FOUND_CLASS, $fqClass, $this,
														 "Found class $fqClass, saving to $outputFile"));

				require_once("UnitTestGenerator".DIRECTORY_SEPARATOR."PhpUnitSkelgen" . DIRECTORY_SEPARATOR . "autoload.php");
				$skelGen = new \SebastianBergmann\PHPUnit\SkeletonGenerator\TestGenerator($fqClass, $file);
				$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_SAVING_TEST_FILE, null, null, "Saving file $outputFile..."));
				$skelGen->write($outputFile);
				$filesAdded++;
			}
			else
			{
				$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_ERROR, null, $this,
														 "This file does not contain a class: $relativeFile"));
			}
		}

		$this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_GENERATE_UNIT_TEST_COMPLETED, null, $this,
													   "Finished.\n\nCreated unit tests for $filesAdded files"));
	}

	private function stripConstructLine($line)
	{
		$returnLine	 = $line;
		$curly		 = stripos($line, "{");
		if ($curly)
		{
			$returnLine = substr($line, 0, $curly);
		}

		$extends = stripos($line, " extends ");
		if ($extends)
		{
			$returnLine = substr($line, 0, $extends);
		}

		$implements = stripos($line, " implements ");
		if ($implements)
		{
			$returnLine = substr($line, 0, $implements);
		}

		return trim($returnLine);
	}

	public function Get_RequiresInstallation()
	{
		return false;
	}

	public function GetRaisedEventTypes()
	{
		return new \DblEj\EventHandling\EventTypeCollection
		(
		array(
			self::EVENT_CLASS_NOT_FOUND,
			self::EVENT_ERROR,
			self::EVENT_FOUND_CLASS,
			self::EVENT_GENERATE_UNIT_TEST_BEGIN,
			self::EVENT_GENERATE_UNIT_TEST_COMPLETED,
			self::EVENT_GENERATE_UNIT_TEST_FILE,
			self::EVENT_SAVING_TEST_FILE)
		);
	}

}