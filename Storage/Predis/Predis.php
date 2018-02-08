<?php

namespace Wafl\Extensions\Storage\Predis;

use DblEj\Data\IDataStore;
use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;
use Redis;
use Wafl\Core;

class Predis extends ExtensionBase implements IDataStore
{
	/**
	 * @var Redis
	 */
	private $_predis;
	private $_serverAddress="127.0.0.1";
	private $_serverPort = 6379;
	private $_amReady=false;
    private $_storeId;
    private $_db = 15;
    private $_timeout = 3600;
	public function Initialize(\DblEj\Application\IApplication $app)
	{
		require_once("phar://" . __DIR__ . "/predis_1.1.0.phar");

        //the client is smart it doesnt actually connect here - it will connect on demand
		$this->_predis = new \Predis\Client(array(
							'host'     => $this->_serverAddress,
							'port'     => $this->_serverPort,
							'database' => $this->_db));
	}

	public function PostInit()
	{
		$this->_amReady=true;
	}

	public function SetData($key, $val, $dataExpiration = null)
	{
        if (!$dataExpiration)
        {
            $dataExpiration = $this->_timeout;
        }
		$this->_predis->setex($key, $dataExpiration, serialize($val));
	}

	public function HasData($key)
	{
		return $this->_predis->exists($key);
	}

	public function GetData($key, $defaultValue = null)
	{
		return unserialize($this->_predis->get($key));
	}

	protected static function getAvailableSettings()
	{
		return array(
			"ServerAddress",
			"ServerPort",
            "StoreId",
            "Timeout",
            "DbId");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "ServerAddress":
				$this->_serverAddress = $settingValue;
				break;
			case "ServerPort":
				$this->_serverPort = $settingValue;
				break;
			case "StoreId":
				$this->_storeId = $settingValue;
				break;
			case "DbId":
				$this->_db = $settingValue;
				break;
			case "Timeout":
				$this->_timeout = $settingValue;
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
        $returnVal = null;
		switch ($settingName)
		{
			case "ServerAddress":
				return "127.0.0.1";
				break;
			case "ServerPort":
				return 6379;
				break;
			case "StoreId":
                return "DefaultStore";
				break;
			case "DbId":
                return "15";
				break;
			case "Timeout":
                return 3600;
				break;
		}
        return $returnVal;
	}

	public function GetRaisedEventTypes()
	{
		return array();
	}

	public function DeleteData($key)
	{
		return $this->_predis->del($key);
	}

	public function FlushAllData()
	{
		$this->_predis->flushDB();
	}

    public function Get_StoreId()
    {
        return $this->_storeId;
    }
}