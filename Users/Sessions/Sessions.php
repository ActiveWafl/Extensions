<?php
namespace Wafl\Extensions\Users\Sessions;

class Sessions extends \DblEj\Extension\ExtensionBase implements \DblEj\Authentication\Integration\ISessionManagerExtension
{
    private $_sessionClass;
    private $_app;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
        $this->_app = $app;
    }

    protected static function getAvailableSettings()
    {
        return ["SessionClassName"];
    }

    public function OpenSession()
    {
        $className = $this->_sessionClass;
        return $className::Open($this->_app);
    }

    public function GetSettingDefault($settingName)
    {
        if ($settingName == "SessionClassName")
        {
            return "\\Wafl\\Users\\FunctionalModel\\Session";
        }
    }
    protected function ConfirmedConfigure($settingName, $settingValue)
    {
       if ($settingName == "SessionClassName")
        {
            $this->_sessionClass = $settingValue;
        }
        parent::ConfirmedConfigure($settingName, $settingValue);
    }
}