<?php
namespace Wafl\Extensions\Testing\Selenium;
/**
 * The base class for phpunit test cases.
 */
class TestCase extends \Wafl\Extensions\Testing\PhpUnit\TestCase implements \DblEj\UnitTesting\ITestCase {
    protected static $webDriver;
    
    public static function onInitialize() {
        $selenium = \Wafl\Util\Extensions::GetExtension("Selenium");
        self::$webDriver = $selenium->GetWebDriver();
        parent::onInitialize();
    }
    public function Get_TestType()
    {
        return TestCase::INTEGRATION_TEST;
    }
    public function GetHumanReadableTestType()
    {
        return "Integration Test";
    }
}