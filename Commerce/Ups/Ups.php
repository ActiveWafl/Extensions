<?php

namespace Wafl\Extensions\Commerce\Ups;

class Ups implements \DblEj\Commerce\Integration\IShipperExtension {

    private $_userId;
    private $_password;
    private $_apiKey;

    function SetCredentials($id, $keyOrSignature, $authCodeOrPassword)
    {
        $this->_userId = $id;
        $this->_password = $authCodeOrPassword;
        $this->_apiKey = $keyOrSignature;
    }

    /**
     * @param string $trackingid
     * @return ShipmentStatus
     */
    function GetShipmentStatus($trackingNumber, $carrier = null, $shipDate = null, $disambiguate = false)
    {
         $wsdl = __DIR__."/Ups/Track.wsdl";
         $operation = "ProcessTrack";
         if ($debugMode)
         {
               $endpointurl = 'https://wwwcie.ups.com/webservices/Track';
         } else {
               $endpointurl = 'https://onlinetools.ups.com/webservices/Track';
         }

         try {

               $mode = array
                    (
                    'soap_version' => 'SOAP_1_1', // use soap 1.1 client
                    'trace' => 1
               );

               // initialize soap client
               $client = new \SoapClient($wsdl, $mode);

               //set endpoint url
               $client->__setLocation($endpointurl);


               //create soap header
               $usernameToken['Username'] = $this->_userId;
               $usernameToken['Password'] = $this->_password;
               $serviceAccessLicense['AccessLicenseNumber'] = $this->_apiKey;
               $upss['UsernameToken'] = $usernameToken;
               $upss['ServiceAccessToken'] = $serviceAccessLicense;
               $header = new \SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', $upss);
               $client->__setSoapHeaders($header);

               //get response
               $req=array();
               $args=array();
               $req['RequestOption'] = '1';
               $req['TransactionReference'] = array("CustomerContext"=>"My Send Token");
               $args['Request'] = $req;
               $args['InquiryNumber'] = $trackingNumber;
               $args['TrackingOption'] = '02';
               $resp = $client->__soapCall($operation, array($args));

               //get status
               $statusString = $resp->Response->ResponseStatus->Description;
               $responseInfo = $client->__getLastResponse();


               $xmlObject = new \SimpleXMLElement($responseInfo);

               $xmlObject=$xmlObject->children("soapenv", true)->
                                     Body->children("trk", true)->
                                     TrackResponse->children("trk",true)->
                                     Shipment;
               $xmlObject = (array)$xmlObject;
               $package = (array)$xmlObject["Package"];
               $activities = (array)$package["Activity"];
               $deliveryDetail = (array)$xmlObject["DeliveryDetail"];


               $message = (array)$package["Message"];
               $historyItems=array();
               $activityLocation="";
               $currentLocation="";
               $deliveryDate=false;
               foreach ($activities as $activity)
               {
                    $time = strtotime($activity->Date." ".$activity->Time);
                    $address = (array)$activity->ActivityLocation->Address;
                    if ($address["City"])
                    {
                         $activityLocation = $address["City"].", ".$address["StateProvinceCode"].", ".$address["CountryCode"];
                    } elseif ($address["CountryCode"]) {
                         $activityLocation = $address["CountryCode"];
                    } else {
                         $activityLocation ="N/A";
                    }
                    $historyItems[$time] = $activityLocation . ", ". $activity->Status->Description;
                    if (stristr($activity->Status->Description,"delivered"))
                    {
                         $deliveryDate=$time;
                    }
                    if (!$currentLocation)
                    {
                         $currentLocation = $activityLocation;
                    }
               }
               $returnStatus = new ShipmentStatus();
               $returnStatus->Set_StatusHistory($historyItems);
               $returnStatus->Set_CurrentLocation($currentLocation);
               $returnStatus->Set_CurrentStatus($message["Description"]);
               $returnStatus->Set_SentDate(strtotime($xmlObject["PickupDate"]));
               if (count($deliveryDetail)>0)
               {
                    if ($deliveryDetail["Type"]->Code == "03")
                    {
                         $returnStatus->Set_DeliveredDate(strtotime($deliveryDetail["Date"]));
                    } else {
                         $returnStatus->Set_ExpectedDeliveryDate(strtotime($deliveryDetail["Date"]));
                    }
               } else {
                    $returnStatus->Set_DeliveredDate($deliveryDate);
               }
               return $returnStatus;
         } catch (\Exception $ex) {
               throw new \Exception("There was an error while communicating with the UPS SOAP API: ".$ex->getMessage());
         }
    }

    public function GetShippingCost(
        $service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = []
    )
    {
        return 0;
    }

    function GetServiceNames($carrierName = NULL)
    {
        return [];
    }
    function GetPackageTypes($serviceName = null)
    {
        return [];
    }
    function GetPackageQualifiers($serviceName = null, $packageType = null)
    {
        return [];
    }

    public function GetServiceOptions($serviceName = null, $packageType = null, $packageQualifier = null)
    {
        return ["Options"=>[], "AdvancedOptions"=>[]];
    }

    public function Configure($settingName, $settingValue)
    {

    }

    public function GetSettingDefault($settingName)
    {
        return null;
    }

    public function Get_RequiresInstallation()
    {
        return false;
    }

    public function Get_SiteAreaId()
    {

    }

    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }

    public function PrepareSitePage($pageName)
    {

    }

    public function Set_SiteAreaId($siteAreaId)
    {

    }

    public function Shutdown()
    {

    }

    public static function GetLocalizedText($textName)
    {

    }

    public static function Get_AvailableSettings()
    {
        return [];
    }

    public static function Get_DatabaseInstallScripts()
    {
        return [];
    }

    public static function Get_DatabaseInstalledTables()
    {
        return [];
    }

    public static function Get_Dependencies()
    {
        return [];
    }

    public static function Get_GlobalScripts()
    {
        return [];
    }

    public static function Get_GlobalStylesheets()
    {
        return [];
    }

    public static function Get_SitePages()
    {
        return [];
    }

    public static function Get_TablePrefix()
    {
        return "";
    }

    public static function Get_WebOnly()
    {
        return false;
    }

    public static function InstallData(\DblEj\Data\Integration\IDatabaseServer $storageEngine)
    {

    }

    public static function Set_LanguageFileClassname($langFileClassname)
    {

    }

    public static function TranslateUrl(\DblEj\Communication\Http\Request $request)
    {

    }


    public function CreateShipment($service, $sourceName, $sourceCompany = null, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $sourcePhone = null, $sourceEmail = null, $destName = null, $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null, $destPhone = null, $destEmail = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = [])
    {
    }


    public function GetShipmentLabels($shipmentUid)
    {

    }

    public function GetCarrierNames()
    {
        return ["ups"];
    }
   public function GetCarriers()
    {
        return ["ups"=>"UPS"];
    }
}
?>