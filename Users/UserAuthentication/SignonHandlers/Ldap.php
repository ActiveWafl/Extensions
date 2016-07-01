<?php
namespace Wafl\Extensions\Users\UserAuthentication\SignonHandlers;
use DblEj\Application\IApplication;
class Ldap implements ISignonHandler
{
    private $_settings = ["UidAttribute"=>"uid","UsernameAttribute"=>"cn","UserObjectClasses"=>["person","uidObject"],"TcpPort"=>389];
    private $_ldapConnection;
    public function Configure($settingName, $settingValue)
    {
        if (array_search($settingName, $this->Get_OptionalSettings()) !== false || array_search($settingName, $this->Get_RequiredSettings()) !== false)
        {
            $this->_settings[$settingName] = $settingValue;
        }
    }

    public function GetUserIdentifier($sessionTokens)
    {
        return $sessionTokens[$this->_settings["UidAttribute"]];
    }

    public function Get_OptionalSettings()
    {
        return ["UidAttribute","UsernameAttribute","UserObjectClasses","TcpPort"];        
    }

    public function Get_RequiredSettings()
    {
        return ["ServerUri","AdminUserDistinguishedName","AdminUserPassword","ParentDistinguishedName"];        
    }

    public function Get_SignonFields()
    {
        return ["Username","Password","RelativeDistinguishedName"];
    }

    public function Initialize(IApplication $application)
    {
        $this->_ldapConnection = ldap_connect($this->_settings["ServerUri"], $this->_settings["TcpPort"]);
        if ($this->_ldapConnection)
        {
            ldap_set_option($this->_ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        } else {
            throw new \Exception("Could not connect to specified ldap server");
        }
    }

    public function SignOn($fieldValues, $sessionId)
    {
        $returnTokens=null;
        //logon to ldap as admin, find users dn, then try to login as user
        $userdn = null;
        $userid = null;
        if (ldap_bind($this->_ldapConnection, $this->_settings["AdminUserDistinguishedName"], $this->_settings["AdminUserPassword"]))
        {
            if (isset($fieldValues["RelativeDistinguishedName"]))
            {
                $rdn=$fieldValues["RelativeDistinguishedName"];
            } else {
                $rdn="";
            }
            $query = "(&(".$this->_settings["UsernameAttribute"]."=" . $fieldValues["Username"] . "$rdn)(objectClass=".reset($this->_settings["UserObjectClasses"])."))";
            $searchResult = ldap_search($this->_ldapConnection, $this->_settings["ParentDistinguishedName"], $query, array("dn",$this->_settings["UidAttribute"]));
            if ($searchResult !== false) {
                $searchResultEntries = ldap_get_entries($this->_ldapConnection, $searchResult);
                if ($searchResultEntries["count"]>0)
                {
                    $userdn = $searchResultEntries[0]["dn"];
                    if ($searchResultEntries[0][$this->_settings["UidAttribute"]]["count"]>0)
                    {
                        $userid = $searchResultEntries[0][$this->_settings["UidAttribute"]][0];
                    }
                }
            }
        }
        if ($userdn)
        {
            if (ldap_bind($this->_ldapConnection, $userdn, $fieldValues["Password"]))
            {
                $returnTokens = ["DistinguishedName"=>$userdn,$this->_settings["UsernameAttribute"]=>$fieldValues["Username"],$this->_settings["UidAttribute"]=>$userid,"IsLoggedIn"=>true];
                ldap_unbind($this->_ldapConnection);
            }
        }
        return $returnTokens;
    }

    public function SignUp($fieldValues)
    {
       if (isset($fieldValues["RelativeDistinguishedName"]))
       {
           $rdn=$fieldValues["RelativeDistinguishedName"];
       } else {
           $rdn="";
       }
       $newEntryDn = $fieldValues["UidAttribute"]."=".$fieldValues["Username"].",".($rdn?$rdn.",":"").$this->_settings["ParentDistinguishedName"];
       
       $newEntry = [$fieldValues["UidAttribute"]=>$fieldValues["Username"],"userPassword"=>$fieldValues["Password"]];
       $newEntry["objectClass"] = $this->_settings["UserObjectClasses"];
       ldap_add($this->_ldapConnection,$newEntryDn,$newEntry);
    }
    
    public function SignOff($sessionTokens)
    {
        //we dont have to do anything since it was never persistant.  Its up to the php session or whoever is calling me.
    }
    
    public function IsSignedOn($sessionTokens)
    {
        //not super secure, weak to session hijacking exploits?
        return ($sessionTokens["IsLoggedIn"]);
    }
}