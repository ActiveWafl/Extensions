<?php
namespace Wafl\Extensions\Storage\MySql;

use DblEj\Data\Integration\IDatabaseServerExtension,
    DblEj\Extension\ExtensionBase;

/**
 * MySql extension
 */
class MySql extends ExtensionBase implements IDatabaseServerExtension
{

	private $_connections = [];

    protected static function getAvailableSettings()
    {
        return ["Connections"];
        //return ["ReplicationRole", "AccessScope", "PersistModels", "ModelGroup", "Username", "Password", "CreateScript", "UpdateScript", "RequiredLocation", "Uri", "Port", "Catalog", "CharacterEncoding"];
    }
    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        if ($settingName == "Connections" && is_array($settingValue))
        {
            $connections = $settingValue;
            foreach ($connections as $connectionName => $connectionSettings)
            {
                $dbPort = 3306;
                foreach ($connectionSettings as $connectionSettingName => $connectionSettingValue)
                {
                    switch ($connectionSettingName)
                    {
                        case "PersistModels":
                            $persistModels = $connectionSettingValue;
                            break;
                        case "ModelGroup":
                            $modelGroup = $connectionSettingValue;
                            break;
                        case "Username":
                            $dbUser = $connectionSettingValue;
                            break;
                        case "Password":
                            $dbPassword = $connectionSettingValue;
                            break;
                        case "CreateScript":
                            $createScript = $connectionSettingValue;
                            break;
                        case "UpdateScript":
                            $updateScript = $connectionSettingValue;
                            break;
                        case "RequiredLocation":
                            $requiredLocation = $connectionSettingValue;
                            break;
                        case "Uri":
                            $dbServer = $connectionSettingValue;
                            break;
                        case "Port":
                            $dbPort = $connectionSettingValue;
                            break;
                        case "Catalog":
                            $dbCatalog = $connectionSettingValue;
                            break;
                        case "CharacterEncoding":
                            $characterEncoding = $connectionSettingValue;
                            break;
                        case "ReplicationRole":
                            break;
                        case "AccessScope":
                            break;
                        default:
                            throw new \Wafl\Application\Settings\InvalidSettingException("Invalid MySql connection setting: $connectionSettingName");
                    }
                }

                $connection = new Connection($dbServer, $dbCatalog, $dbUser, $dbPassword, $dbPort);
                $connection->Set_CreateScript($createScript);
                $connection->Set_UpdateScript($updateScript);
                $connection->Set_RequiredStorageLocation($requiredLocation);
                $connection->Set_ModelGroup($modelGroup);
                $connection->Set_CharacterEncoding($characterEncoding);
                $this->AddConnection($connectionName, $connection);
                if ($persistModels)
                {
                    \DblEj\Data\PersistableModel::AddStorageEngine($connection);
                    \DblEj\Data\PersistableModel::SetCurrentUser(\Wafl\Core::$CURRENT_USER);
                }
            }
        }
    }

    public function __toString()
    {
        return "MySql Extension";
    }

    public function AddConnection($dbId, \DblEj\Data\IDatabaseConnection $db)
    {
        $this->_connections[$dbId] = $db;
    }

    public function GetConnection($dbId)
    {
        if ($this->HasConnection($dbId))
        {
            return $this->_connections[$dbId];
        } else {
            throw new \Exception("Invalid database connection id specified: $dbId");
        }
    }

    public function GetConnections()
    {
        return $this->_connections;
    }
    public function HasConnection($dbId)
    {
        return isset($this->_connections[$dbId]);
    }

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        foreach ($this->_connections as $connection)
        {
            $connection->Connect();
        }
    }
    public static function Set_DesignateViewsByStringPrefix($strViewStringPrefix)
    {
        Connection::Set_DesignateViewsByStringPrefix($strViewStringPrefix);
    }
}
?>