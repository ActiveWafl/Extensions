<?php
namespace Wafl\Extensions\Users\UserAuthentication\SignonHandlers;

use DblEj\Application\IApplication,
    Exception;
class Database implements ISignonHandler
{
    private $_settings = ["UserTable"=>"Users", "UserTableKeyField"=>"UserId", "UserTableKeyIsAuto"=>true, "SessionTable"=>"Sessions", "UsernameColumn"=>"Username", "PasswordColumn"=>"Password", "SessionIdColumn"=>"SessionId", "PasswordHashType"=>"","PasswordSalt"=>"", "PasswordSaltType"=>"Append"];

    /**
     *
     * @var IApplication
     */
    private $_app;
    private $_dataStorage;

    public function Initialize(IApplication $application)
    {
        $this->_app = $application;
        $this->_dataStorage = $this->_app->GetStorageEngine($this->_settings["DataConnection"]);
        if (!$this->_dataStorage)
        {
            throw new Exception("Cannot run the database signon handler without a DataStorage connection.");
        }
        if (!$this->_dataStorage->DoesLocationExist($this->_settings["UserTable"]) || !$this->_dataStorage->DoesLocationExist($this->_settings["SessionTable"]))
        {
            if ($this->_settings["UserTableKeyField"]==$this->_settings["UsernameColumn"])
            {
                $sqlFileName = "Database_RedundantUserId";
            }
            elseif ($this->_settings["UsernameColumn"]==$this->_settings["PasswordColumn"])
            {
                $sqlFileName = "Database_RedundantUsername";
            }
            elseif ($this->_settings["PasswordColumn"]==$this->_settings["UserTableKeyField"])
            {
                $sqlFileName = "Database_RedundantPassword";
            } else {
                $sqlFileName = "Database";
            }
            
            if (is_a($this->_dataStorage,"\\Wafl\\Extensions\\Storage\\SqlServer"))
            {
                $sql = file_get_contents(__DIR__.DIRECTORY_SEPARATOR."Sql".DIRECTORY_SEPARATOR."$sqlFileName.mssql");
            } else {
                $sql = file_get_contents(__DIR__.DIRECTORY_SEPARATOR ."Sql".DIRECTORY_SEPARATOR."$sqlFileName.sql");
            }
            $sql = \str_replace("{\$USERS_TABLE}", $this->_settings["UserTable"], $sql);
            $sql = \str_replace("{\$SESSIONS_TABLE}", $this->_settings["SessionTable"], $sql);            
            $sql = \str_replace("{\$USERID_COLUMN}", $this->_settings["UserTableKeyField"], $sql);
            $sql = \str_replace("{\$USERNAME_COLUMN}", $this->_settings["UsernameColumn"], $sql);
            $sql = \str_replace("{\$PASSWORD_COLUMN}", $this->_settings["PasswordColumn"], $sql);
            $sql = \str_replace("{\$SESSIONID_COLUMN}", $this->_settings["SessionIdColumn"], $sql);
            
            $this->_dataStorage->DirectScriptExecute($sql, true);
            $this->_dataStorage->UpdateStorageLocations();
        }
    }

    public function Get_SignonFields()
    {
        return ["Username","Password"];
    }

    public function SignUp($fieldValues)
    {
        $username = $fieldValues["Username"];
        $password = $fieldValues["Password"];
        $dupUser = $this->_dataStorage->GetData($this->_settings["UserTable"], $this->_settings["UsernameColumn"], $username);
        if (!$dupUser)
        {
            return $this->_dataStorage->StoreData($this->_settings["UserTable"], [$this->_settings["UsernameColumn"],$this->_settings["PasswordColumn"]], [$this->_settings["UsernameColumn"]=>$username, $this->_settings["PasswordColumn"]=>$password], $this->_settings["UserTableKeyField"], $this->_settings["UserTableKeyIsAuto"]);
        } else {
            throw new Exception("The user already exists");
        }
    }

    public function SignOn($fieldValues, $sessionId)
    {
        $returnArray = ["SessionId"=>$sessionId]; //sso providers may return a token or something.  We just give back our session id since that is our persistance key.
        if (isset($fieldValues["Username"]))
        {
            $userRow = $this->_dataStorage->GetData($this->_settings["UserTable"], $this->_settings["UsernameColumn"], $fieldValues["Username"]);
            if (!is_null($userRow) && $userRow[$this->_settings["PasswordColumn"]] == $this->_getHash($fieldValues["Password"]))
            {
                //delete old sessions
                $this->_dataStorage->DeleteData($this->_settings["SessionTable"], $this->_settings["SessionIdColumn"], $sessionId);
                $this->_dataStorage->StoreData($this->_settings["SessionTable"], [$this->_settings["SessionIdColumn"],$this->_settings["UserTableKeyField"]], [$this->_settings["SessionIdColumn"]=>$sessionId, $this->_settings["UserTableKeyField"]=>$userRow[$this->_settings["UserTableKeyField"]]], $this->_settings["SessionIdColumn"]);
            } else {
                $returnArray = null;
            }
        }
        return $returnArray;
    }

    public function SignOff($sessionTokens, $killSessions = true)
    {
        if ($killSessions)
        {
            $phpSessionId = $sessionTokens["SessionId"];
            $this->_dataStorage->DeleteData($this->_settings["SessionTable"], $this->_settings["SessionIdColumn"], $phpSessionId);
        }
    }

    public function IsSignedOn($sessionTokens)
    {
        $phpSessionId = $sessionTokens["SessionId"];
        $sessionRow = $this->_dataStorage->GetData($this->_settings["SessionTable"], $this->_settings["SessionIdColumn"], $phpSessionId);
        return $sessionRow!=null;
    }

    public function Configure($settingName, $settingValue)
    {
        $this->_settings[$settingName] = $settingValue;
    }

    public function Get_RequiredSettings()
    {
        return ["DataConnection"];
    }

    public function Get_OptionalSettings()
    {
        return ["UserTable","SessionTable","UsernameColumn","UserTableKeyField","UserTableKeyIsAuto,","PasswordColumn","SessionIdColumn","PasswordHashType","PasswordSalt","PasswordSaltType"];
    }

    private function _getHash($password)
    {
        switch ($this->_settings["PasswordHashType"])
        {
            case "md5":
                switch ($this->_settings["PasswordSaltType"])
                {
                    case "Append":
                        $password = md5($password.$this->_settings["PasswordSalt"]);
                    case "Prepend":
                        $password = md5($this->_settings["PasswordSalt"].$password);
                    case "HashAppend":
                        $password = md5($password.md5($this->_settings["PasswordSalt"]));
                    case "HashPrepend":
                        $password = md5(md5($this->_settings["PasswordSalt"]).$password);
                }
                break;
            case "sha1":
                switch ($this->_settings["PasswordSaltType"])
                {
                    case "Append":
                        $password = sha1($password.$this->_settings["PasswordSalt"]);
                    case "Prepend":
                        $password = sha1($this->_settings["PasswordSalt"].$password);
                    case "HashAppend":
                        $password = sha1($password.sha1($this->_settings["PasswordSalt"]));
                    case "HashPrepend":
                        $password = sha1(sha1($this->_settings["PasswordSalt"]).$password);
                }
                break;
        }
        return $password;
    }

    public function GetUserIdentifier($sessionTokens)
    {
        $phpSessionId = $sessionTokens["SessionId"];
        $sessionRow = $this->_dataStorage->GetData($this->_settings["SessionTable"], $this->_settings["SessionIdColumn"], $phpSessionId);
        if ($sessionRow)
        {
            $userRow = $this->_dataStorage->GetData($this->_settings["UserTable"], $this->_settings["UserTableKeyField"], $sessionRow[$this->_settings["UserTableKeyField"]]);
        } else {
            $userRow = null;
        }
        return $userRow?$userRow[$this->_settings["UserTableKeyField"]]:null;
    }

}