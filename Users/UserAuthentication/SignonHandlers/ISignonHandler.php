<?php
namespace Wafl\Extensions\Users\UserAuthentication\SignonHandlers;
interface ISignonHandler
{
    function Get_SignonFields();
    function SignUp($fieldValues);
    
    /**
     * 
     * @param type $fieldValues
     * @param type $sessionId
     * 
     * @return array array of tokens that can be used to later check if signed in or to sign off
     */
    function SignOn($fieldValues,$sessionId);
    function SignOff($sessionTokens, $killSessions = true);
    function IsSignedOn($sessionTokens);
    function GetUserIdentifier($sessionTokens);
    function Get_OptionalSettings();
    function Get_RequiredSettings();
    function Initialize(\DblEj\Application\IApplication $application);
    function Configure($settingName,$settingValue);
}