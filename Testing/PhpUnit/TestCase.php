<?php
namespace Wafl\Extensions\Testing\PhpUnit;
/**
 * The base class for phpunit test cases.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase implements \DblEj\UnitTesting\ITestCase {
    public function Get_TestType()
    {
        return TestCase::UNIT_TEST;
    }
    public function GetHumanReadableTestType()
    {
        return "Unit Test";
    }
    public static function onInitialize(){}
    public static function onCleanup(){}

    final public static function setUpBeforeClass()
    {
      try
      {
        $classname = get_called_class();
        $classname::onInitialize();
        \DblEj\Util\SystemEvents::RaiseSystemEvent(new \DblEj\EventHandling\EventInfo(\DblEj\Util\SystemEvents::BEFORE_EXECUTE_TEST_CASE));
        \DblEj\Util\SystemEvents::RaiseSystemEvent(new \DblEj\EventHandling\EventInfo(\DblEj\Util\SystemEvents::BEFORE_EXECUTE_UNITTEST_CASE));
      } catch (\Exception $ex) {
        throw new \Exception("Error setting up test: ".$ex->getMessage(), $ex->getCode(), $ex);
      }
    }

    final public static function tearDownAfterClass ()
    {
        $classname = get_called_class();
        $classname::onCleanup();
        \DblEj\Util\SystemEvents::RaiseSystemEvent(new \DblEj\EventHandling\EventInfo(\DblEj\Util\SystemEvents::AFTER_EXECUTE_TEST_CASE));
        \DblEj\Util\SystemEvents::RaiseSystemEvent(new \DblEj\EventHandling\EventInfo(\DblEj\Util\SystemEvents::AFTER_EXECUTE_UNITTEST_CASE));
    }
}