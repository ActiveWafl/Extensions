<?php

namespace Wafl\Extensions\Storage\GenericDiskStore;

/**
 * Store and retrieve runtime data on disk using a standardized interface.
 */
class GenericDiskStore
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Data\IPersistantDataStore
{
    private $_filePath;
    private $_data;
    private $_filemtime;
    private $_localDataChanged = false;
    private $_storeId;
    private $_timeout = 3600;

    /**
     * Load data from disk that was previously saved by this data store.
     */
    public function Load()
    {
        if (file_exists($this->_filePath))
        {
            $this->_data = unserialize(file_get_contents($this->_filePath));
            foreach (array_keys($this->_data) as $dataKey)
            {
                $this->_expireOldData($dataKey);
            }
        }
        else
        {
            $this->_data = array();
        }
        $this->_localDataChanged = false;
    }

    private function _reloadFileIfChanged()
    {
        if (file_exists($this->_filePath))
        {
            $filemtime = filemtime($this->_filePath);
            if ($filemtime != $this->_filemtime)
            {
                $this->_filemtime = $filemtime;
                $this->Load();
            }
        }
    }

    /**
     * Persist this store's data to disk.
     */
    public function Persist()
    {
        if ($this->_localDataChanged)
        {
            file_put_contents($this->_filePath, serialize($this->_data));
            $this->_localDataChanged = false;
        }
    }

    /**
     * Delete a specific data point from this data store.
     * @param string $key The unique key of the data point to delete.
     */
    public function DeleteData($key)
    {
        $this->_reloadFileIfChanged();
        if ($this->_data)
        {
            unset($this->_data[$key]);
            $this->_localDataChanged = true;
        }
    }

    /**
     * Delete all of the in-memory as well as on-disk data from this store.
     */
    public function FlushAllData()
    {
        $this->_data = array();
        unlink($this->_filePath);
    }

    /**
     * Retrieve a specific data point from this data store.
     * @param string $key The unique key of the data point to retrieve.
     */
    public function GetData($key)
    {
        $this->_reloadFileIfChanged();
        $this->_expireOldData($key);
        return isset($this->_data[$key]) ? $this->_data[$key][0] : null;
    }

    private function _expireOldData($key)
    {
        if (isset($this->_data[$key]) && ((time() - $this->_data[$key][1]) > $this->_timeout))
        {
            unset($this->_data[$key]);
        }
    }

    /**
     * Check if a specific data point exists in this data store.
     * @param string $key The unique key of the data point to check for.
     */
    public function HasData($key)
    {
        $this->_reloadFileIfChanged();
        $this->_expireOldData($key);
        return isset($this->_data[$key]);
    }

    /**
     * Store a data point in this data store.
     * @param string $key The unique key of the data point to store.
     * @param string $val The data point's value.
     */
    public function SetData($key, $val)
    {
        $this->_data[$key]       = [$val, time()];
        $this->_localDataChanged = true;
        $this->Persist();
    }

    protected static function getAvailableSettings()
    {
        return ["StoreId", "Timeout"];
    }
    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            /**
             * An ID that represents this data store.  This allows an application to have multiple stores.
             * Must be 10 characters or less and contain no special characters.
             */
            case "StoreId":
                $this->_storeId = $settingValue;
                break;
			case "Timeout":
				$this->_timeout = $settingValue;
				break;
        }
    }

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        $appId = $app->Get_Guid();
        if ((strlen($appId) > 10) || preg_match("/[^A-Za-z0-9]/", $appId))
        {
            throw new \Exception("Disk store appId ($appId) must be 10 characters or less and contain no special characters");
        }
        if (strlen($this->_storeId) > 10)
        {
            throw new \Exception("Disk store storeId must be 10 characters or less and contain no special characters");
        } elseif (!$this->_storeId)
        {
            throw new \Exception("Disk store StoreId must be specified");
        }

        try
        {
            $storeFolder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "wafl_genericdiskstore";
            if (!file_exists($storeFolder))
            {
                @mkdir($storeFolder);
                if (!file_exists($storeFolder))
                {
                    throw new \Exception("Cannot create generic disk store folder.");
                }
            }
            $folder          =  $storeFolder . DIRECTORY_SEPARATOR . $appId;
            $this->_filePath = $folder . DIRECTORY_SEPARATOR . $this->_storeId;
            if (!file_exists($folder))
            {
                @mkdir($folder, 0777, true);
                if (!file_exists($folder))
                {
                    throw new \Exception("Cannot create generic disk store sub folder.");
                }
            }
            if (file_exists($this->_filePath))
            {
                $this->_filemtime = filemtime($this->_filePath);
            }
            $this->Load();
        } catch (\Exception $e) {
            throw new \Exception("Error initializing data store folder", $e->getCode(), $e);
        }

    }
    public function Get_StoreId()
    {
        return $this->_storeId;
    }
}