<?php

namespace Wafl\Extensions\Search\Solr;

use DblEj\Extension\DependencyCollection;

class Solr extends \DblEj\Data\Integration\IndexerExtensionBase
{
	private $_serverAddress="localhost:8983";
	private $_amReady=false;
	private $_indexNames=array();
    private $_indexServlets=array();

	public function Initialize(\DblEj\Application\IApplication $app)
	{
		if (!class_exists("\SolrClient"))
		{
			throw new \DblEj\System\MissingPhpExtensionException("SolrClient");
		}
        require_once (__DIR__.DIRECTORY_SEPARATOR."Index.php");
		foreach ($this->_indexNames as $indexName)
		{
			$indexObject = new Index($indexName, $this->_indexServlets[$indexName]);
			$this->AddIndex($indexName, $indexObject);
			$indexObject->Connect($this->_serverAddress);
		}
	}

	public function PostInit()
	{
		$this->_amReady=true;
	}

	protected static function getAvailableSettings()
	{
		return array(
			"ServerAddress",
			"Indexes");
	}
	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "ServerAddress":
				$this->_serverAddress = $settingValue;
				break;
			case "Indexes":
                foreach ($settingValue as $settingKey => $settingValue)
                {
                    if (is_array($settingValue))
                    {
                        $indexSettings = $settingValue;
                        $indexName = $settingKey;

                        $this->_indexNames[] = $indexName;
                        foreach ($indexSettings as $indexSettingName=>$indexSettingValue)
                        {
                            switch (strtolower($indexSettingName))
                            {
                                case "servlet":
                                    $this->_indexServlets[$indexName] = $indexSettingValue;
                                    break;
                            }
                        }
                    } else {
                        $indexName = $settingValue;
                        $this->_indexNames[] = $indexName;
                    }
                    if (!isset($this->_indexServlets[$indexName]))
                    {
                        $this->_indexServlets[$indexName] = "select";
                    }
                }
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
		return null;
	}

	public function GetRaisedEventTypes()
	{
		return array();
	}

	public function AddIndex($indexName, \DblEj\Data\IIndex $index)
	{
		$this->_indexes[$indexName]=$index;
	}

	public function HasIndex($indexName)
	{
		return isset($this->_indexes[$indexName]);
	}
	public function GetIndex($indexName)
	{
		if (!isset($this->_indexes[$indexName]))
		{
			throw new \Exception("Invalid Solr index: $indexName");
		}
		return $this->_indexes[$indexName];
	}
	public function GetTitle()
	{
		return "Apache Solr";
	}

}