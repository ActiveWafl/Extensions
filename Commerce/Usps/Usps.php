<?php

namespace Wafl\Extensions\Commerce\Usps;

class Usps
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Commerce\Integration\IShipperExtension, \DblEj\Commerce\Integration\IAddressVerifierExtension
{

    private $_userId;
    private $_password;
    private $_apiKey;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }

    protected static function getAvailableSettings()
    {
        return ["UserId", "Password", "ApiKey"];
    }

    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            case "UserId":
                $this->_userId = $settingValue;
                break;
            case "Password":
                $this->_password = $settingValue;
                break;
            case "ApiKey":
                $this->_apiKey = $settingValue;
                break;
        }
    }
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
    function GetShipmentStatus($trackingNumber, $mailDate = null, $disambiguate = false)
    {
        $localIP = getHostByName(getHostName());
        $apiEndpoint = "http://production.shippingapis.com/ShippingAPI.dll?API=TrackV2";


        if ($disambiguate && $mailDate)
        {
            $mailDateString = strftime("%m/%d/%Y", $mailDate);
            $xmlString = "<TrackFieldRequest USERID=\"$this->_userId\" >
                            <Revision>1</Revision>
                            <ClientIp>$localIP</ClientIp>
                            <SourceId>Plazko.com</SourceId>
                            <TrackID ID=\"$trackingNumber\"><MailingDate>$mailDateString</MailingDate></TrackID>
                          </TrackFieldRequest>";
        } else {
            $xmlString = "<TrackFieldRequest USERID=\"$this->_userId\" >
                            <Revision>1</Revision>
                            <ClientIp>$localIP</ClientIp>
                            <SourceId>Plazko.com</SourceId>
                            <TrackID ID=\"$trackingNumber\"></TrackID>
                          </TrackFieldRequest>";
        }

        $xmlString = str_replace("\r\n", "\n", $xmlString);
        $xmlString = str_replace("\n\r", "\n", $xmlString);
        $xmlString = preg_replace("/\n\s+/s", "", $xmlString);
        $xmlString = str_replace(" ","%20",$xmlString);
        $url = "$apiEndpoint&XML=".$xmlString;
        $result = \DblEj\Communication\Http\Util::SendRequest($url);
        if ($result)
        {
            $resultXml = simplexml_load_string($result);
            $events = [];
            if (isset($resultXml->TrackInfo->Error))
            {
                if ($resultXml->TrackInfo->Error->Description[0] == "Duplicate" && $mailDate && !$disambiguate)
                {
                    return $this->GetShipmentStatus($trackingNumber, $mailDate, true);
                } else {
                    return ["Status"=>"CantTrack", "StatusDescription"=>$resultXml->TrackInfo->Error->Description[0], "DeliveryDate"=>"N/A", "Summary"=>"The shipment cannot be tracked.","Events"=>[]];
                }
            }
            elseif (isset($resultXml->Error))
            {
                if ($resultXml->Error->Description[0] == "Duplicate"  && $mailDate && !$disambiguate)
                {
                    return $this->GetShipmentStatus($trackingNumber, $mailDate, true);
                } else {
                    return ["Status"=>"CantTrack", "StatusDescription"=>$resultXml->Error->Description[0], "DeliveryDate"=>"N/A", "Summary"=>"The shipment cannot be tracked.","Events"=>[]];
                }
            }
            else
            {
                if (isset($resultXml->TrackInfo->TrackDetail) && $resultXml->TrackInfo->TrackDetail != null)
                {
                    foreach ($resultXml->TrackInfo->TrackDetail as $trackDetail)
                    {
                        $eventUid = $trackDetail->EventDate[0].$trackDetail->EventTime[0]."A".$trackDetail->EventCode[0];
                        $eventUid = $trackDetail->EventCode[0].substr($trackDetail->EventDate[0], 0, 1).substr($trackDetail->EventTime[0], 0, 1).crc32($eventUid);
                        $events[] = ["Uid"=>$eventUid, "EventDate"=>strtotime($trackDetail->EventDate[0]." ".$trackDetail->EventTime[0]), "Description"=>$trackDetail->Event[0], "City"=>$trackDetail->EventCity[0], "State"=>$trackDetail->EventState[0], "Country"=>$trackDetail->EventCountry[0], "Postal"=>$trackDetail->EventZIPCode[0], "ShipperCode"=>$trackDetail->EventCode[0], "EventCode"=>self::_lookupEventCode($trackDetail->EventCode[0])];
                    }
                }
                if (isset($resultXml->TrackInfo->TrackSummary) && $resultXml->TrackInfo->TrackSummary != null)
                {
                    $trackDetail = $resultXml->TrackInfo->TrackSummary;
                    $eventUid = $trackDetail->EventDate[0].$trackDetail->EventTime[0]."A".$trackDetail->EventCode[0];
                    $eventUid = $trackDetail->EventCode[0].substr($trackDetail->EventDate[0], 0, 1).substr($trackDetail->EventTime[0], 0, 1).crc32($eventUid);
                    $events[] = ["Uid"=>$eventUid, "EventDate"=>strtotime($trackDetail->EventDate[0]." ".$trackDetail->EventTime[0]), "Description"=>$trackDetail->Event[0], "City"=>$trackDetail->EventCity[0], "State"=>$trackDetail->EventState[0], "Country"=>$trackDetail->EventCountry[0], "Postal"=>$trackDetail->EventZIPCode[0], "ShipperCode"=>$trackDetail->EventCode[0], "EventCode"=>self::_lookupEventCode($trackDetail->EventCode[0])];
                }
                return ["Status"=>$resultXml->TrackInfo->Status[0], "StatusDescription"=>$resultXml->TrackInfo->StatusSummary[0], "DeliveryDate"=>$resultXml->TrackInfo->ExpectedDeliveryDate[0], "Summary"=>$resultXml->TrackInfo->StatusSummary[0], "Events"=>$events];
            }
        }
    }

    private static function _lookupEventCode($uspsEventCode)
    {
        $code = 999;
        switch ($uspsEventCode)
        {
            case "MA":
            case "TM":
                $code = 5;
                break;
            case "02":
            case "04": //refused
            case "52": //notice left
            case "53": //notice left
            case "54":
            case "55":
            case "56":
            case "05": //bad address
                //attempted delivery
                $code = 130;
                break;
            case "03":
            case "OA":
                //accepted by carrier
                $code = 50;
                break;
            case "10":
            case "07":
            case "SF":
            case "PC":
            case "D0":
            case "BB":
                //facility process
                $code = 60;
                break;
            case "DD":
            case "EF":
            case "AH":
                //left facility
                $code = 65;
                break;
            case "OF":
                //out to be delivered to recipient
                $code = 100;
                break;
            case "01":
            case "I0":
                //delivered to address
                $code = 110;
                break;
            default:
                $code = 999;
                break;
        }
        return $code;
    }

    function VerifyAddress($address1, $address2, $city, $state, $country, $postalCode = null, $company = null)
    {
        if (strlen($postalCode) > 5)
        {
            $zip5 = substr($postalCode, 0, 5);
            $zip4 = substr($postalCode, 6);
        } else {
            $zip5 = $postalCode;
            $zip4 = "";
        }
        if (strlen($address1) > 83 && !$address2)
        {
            $address2 = substr($address1, 83);
            $address1 = substr($address1, 0, 83);
        }
        if ($address1 && !$address2)
        {
            //usps ignores the first line and fills the second address line if there is only one line
            $address2 = $address1;
            $address1 = "";
        }
        if (!$company)
        {
            $company = "";
        }
        if (!$postalCode)
        {
            $postalCode = "";
        }
        $xmlString = "  <AddressValidateRequest USERID=\"$this->_userId\" >
                            <Address ID=\"0\">
                                <FirmName>$company</FirmName>
                                <Address1>$address1</Address1>
                                <Address2>$address2</Address2>
                                <City>$city</City>
                                <State>$state</State>
                                <Zip5>$zip5</Zip5>
                                <Zip4>$zip4</Zip4>
                            </Address>
                        </AddressValidateRequest>";

        $xmlString = str_replace("\r\n", "\n", $xmlString);
        $xmlString = str_replace("\n\r", "\n", $xmlString);
        $xmlString = preg_replace("/\n\s+/s", "", $xmlString);
        $xmlString = str_replace("#","%23",$xmlString);
        $xmlString = str_replace(" ","%20",$xmlString);
        $url = "http://production.shippingapis.com/ShippingAPI.dll?API=Verify&XML=".$xmlString;
        $result = \DblEj\Communication\Http\Util::SendRequest($url);
        if ($result)
        {
            $resultXml = simplexml_load_string($result);
            $returnValue = -1;
            if ($resultXml && isset($resultXml->Address))
            {
                if (!isset($resultXml->Address->Error))
                {
                    $correctedCompany = (string)$resultXml->Address->FirmName;
                    $correctedAddress1 = (string)$resultXml->Address->Address1;
                    $correctedAddress2 = (string)$resultXml->Address->Address2;
                    $correctedCity = (string)$resultXml->Address->City;
                    $correctedState = (string)$resultXml->Address->State;
                    $correctedZip5 = (string)$resultXml->Address->Zip5;
                    $correctedZip4 = (string)$resultXml->Address->Zip4;
                    $verificationResultText = isset($resultXml->Address->ReturnText)?(string)$resultXml->Address->ReturnText:"";
                    if (
                        strcasecmp($company, $correctedCompany) == 0 &&
                        strcasecmp($address1, $correctedAddress1) == 0 &&
                        strcasecmp($address2, $correctedAddress2) == 0 &&
                        strcasecmp($city, $correctedCity) == 0 &&
                        strcasecmp($state, $correctedState) == 0 &&
                        strcasecmp($zip5, $correctedZip5) == 0 &&
                        strcasecmp($zip4, $correctedZip4) == 0
                    )
                    {
                        $returnValue = 0;
                    } else {
                        $specificAddress = true;
                        if ($verificationResultText == "Default address: The address you entered was found but more information is needed (such as an apartment, suite, or box number) to match to a specific address.")
                        {
                            $verificationResultText = "There appears to be multiple results for this address.  Did you enter the correct apartment, suite, or unit number?";
                            $specificAddress = false;
                        }
                        $returnValue = ["Company"=>$correctedCompany, "Address1"=>$correctedAddress1, "Address2"=>$correctedAddress2,
                                        "City"=>$correctedCity, "RegionCode"=>$correctedState, "PostalCode"=>"$correctedZip5-$correctedZip4",
                                        "Notes"=>$verificationResultText, "IsOnlyMatch"=>$specificAddress, "CountryCode"=>"US"];
                    }
                } else {
                    $errorText = trim($resultXml->Address->Error->Description);
                    if ($errorText == "Multiple addresses were found for the information you entered, and no default exists.")
                    {
                        $returnValue = 1;
                    }
                    else if ($errorText == "Address Not Found.")
                    {
                        $returnValue = -1;
                    }
                    else if ($errorText == "Invalid State Code.")
                    {
                        $returnValue = -2;
                    }
                    else if ($errorText == "Invalid City.")
                    {
                        $returnValue = -2;
                    }
                    else {
                        die("unhandled error text: $errorText");
                    }
                }
            }
        } else {
            throw new \Exception("No response from USPS API");
        }
        return $returnValue;
    }

    function GetServiceNames()
    {
        return  [
                    "FIRST CLASS"=>"First Class",
                    "FIRST CLASS COMMERCIAL"=>"First Class Commercial",
                    "FIRST CLASS HFP COMMERCIAL"=>"First Class HFP Commercial",
                    "PRIORITY"=>"Priority",
                    "PRIORITY COMMERCIAL"=>"Priority Commercial",
                    "PRIORITY CPP"=>"Priority CPP",
                    "PRIORITY HFP COMMERCIAL"=>"Priority HFP Commercial",
                    "PRIORITY HFP CPP"=>"Priority HFP CPP",
                    "PRIORITY MAIL EXPRESS"=>"Priority Mail Express",
                    "PRIORITY MAIL EXPRESS COMMERCIAL"=>"Priority Mail Express Commercial",
                    "PRIORITY MAIL EXPRESS CPP"=>"Priority Mail Express CPP",
                    "PRIORITY MAIL EXPRESS SH"=>"Priority Mail Express SH",
                    "PRIORITY MAIL EXPRESS SH COMMERCIAL"=>"Priority Mail Express SH Commercial",
                    "PRIORITY MAIL EXPRESS HFP"=>"Priority Mail Express HFP",
                    "PRIORITY MAIL EXPRESS HFP COMMERCIAL"=>"Priority Mail Express HFP Commercial",
                    "PRIORITY MAIL EXPRESS HFP CPP"=>"Priority Mail Express HFP CPP",
                    "STANDARD POST"=>"Standard Post",
                    "MEDIA"=>"Media Mail",
                    "LIBRARY"=>"Library",
                    1=>"Priority Mail Express International",
                    2=>"Priority Mail International",
                    4=>"Global Express Guaranteed (GXG)",
                    5=>"Global Express Guaranteed Document",
                    6=>"Global Express Guaranteed Non-Document Rectangular",
                    7=>"Global Express Guaranteed Non-Document Non-Rectangular",
                    8=>"Priority Mail International Flat Rate Envelope",
                    9=>"Priority Mail International Medium Flat Rate Box",
                    10=>"Priority Mail Express International Flat Rate Envelope",
                    11=>"Priority Mail International Large Flat Rate Box",
                    13=>"First-Class Mail International Letter",
                    14=>"First-Class Mail International Large Envelope",
                    15=>"First-Class Package International Service",
                    16=>"Priority Mail International Small Flat Rate Box",
                    17=>"Priority Mail Express International Legal Flat Rate Envelope",
                    18=>"Priority Mail International Gift Card Flat Rate Envelope",
                    19=>"Priority Mail International Window Flat Rate Envelope",
                    20=>"Priority Mail International Small Flat Rate Envelope",
                    21=>"First-Class Mail International Postcard",
                    22=>"Priority Mail International Legal Flat Rate Envelope",
                    23=>"Priority Mail International Padded Flat Rate Envelope",
                    24=>"Priority Mail International DVD Flat Rate Box",
                    25=>"Priority Mail International Large Video Flat Rate Box",
                    26=>"Priority Mail Express International Flat Rate Box",
                    27=>"Priority Mail Express International Padded Flat Rate Envelope"
                ];
    }

    function GetPackageTypes($serviceName = null)
    {
        switch ($serviceName)
        {
            case "FIRST CLASS":
                return  ["LETTER"=>"Letter", "FLAT"=>"Large Envelope", "PARCEL"=>"Parcel", "POSTCARD"=>"Postcard"];
                break;
            case "FIRST CLASS COMMERCIAL":
            case "FIRST CLASS HFP COMMERCIAL":
                return  ["PACKAGE SERVICE"=>"Package Service"];
                break;
            case "PRIORITY":
                return  [
                            "FLAT RATE ENVELOPE"=>"Flat Rate Envelope", "PADDED FLAT RATE ENVELOPE"=>"Padded Flat Rate Envelope",
                            "LEGAL FLAT RATE ENVELOPE"=>"Legal Flat Rate Envelope", "SM FLAT RATE ENVELOPE"=>"Small Flat Rate Envelope",
                            "WINDOW FLAT RATE ENVELOPE"=>"Flat Rate Envelope w/ Window", "GIFT CARD FLAT RATE ENVELOPE"=>"Gift Card Flat Rate Envelope",
                            "FLAT RATE BOX"=>"Flat Rate Box", "SM FLAT RATE BOX"=>"Small Flat Rate Box", "MD FLAT RATE BOX"=>"Medium Flat Rate Box",
                            "LG FLAT RATE BOX"=>"Large Flat Rate Box"
                        ];
                break;
            case "PRIORITY COMMERCIAL":
            case "PRIORITY CPP":
            case "PRIORITY HFP COMMERCIAL":
            case "PRIORITY HFP CPP":
                return  [
                            "FLAT RATE ENVELOPE"=>"Flat Rate Envelope", "PADDED FLAT RATE ENVELOPE"=>"Padded Flat Rate Envelope",
                            "LEGAL FLAT RATE ENVELOPE"=>"Legal Flat Rate Envelope", "SM FLAT RATE ENVELOPE"=>"Small Flat Rate Envelope",
                            "WINDOW FLAT RATE ENVELOPE"=>"Flat Rate Envelope w/ Window", "GIFT CARD FLAT RATE ENVELOPE"=>"Gift Card Flat Rate Envelope",
                            "FLAT RATE BOX"=>"Flat Rate Box", "SM FLAT RATE BOX"=>"Small Flat Rate Box", "MD FLAT RATE BOX"=>"Medium Flat Rate Box",
                            "LG FLAT RATE BOX"=>"Large Flat Rate Box", "REGIONALRATEBOXA"=>"Regional Rate Box A",
                            "REGIONALRATEBOXB"=>"Regional Rate Box B", "REGIONALRATEBOXC"=>"Regional Rate Box C",
                            "RECTANGULAR"=>"Package", "NONRECTANGULAR"=>"Non-Rectangular Package"
                        ];
                break;
            case "PRIORITY MAIL EXPRESS":
            case "PRIORITY MAIL EXPRESS SH":
            case "PRIORITY MAIL EXPRESS HFP":
            case "PRIORITY MAIL EXPRESS COMMERCIAL":
            case "PRIORITY MAIL EXPRESS CPP":
            case "PRIORITY MAIL EXPRESS SH COMMERCIAL":
            case "PRIORITY MAIL EXPRESS HFP COMMERCIAL":
            case "PRIORITY MAIL EXPRESS HFP CPP":
                return  [
                            "FLAT RATE ENVELOPE"=>"Flat Rate Envelope", "PADDED FLAT RATE ENVELOPE"=>"Padded Flat Rate Envelope",
                            "LEGAL FLAT RATE ENVELOPE"=>"Legal Flat Rate Envelope", "FLAT RATE BOX"=>"Flat Rate Box",
                            "RECTANGULAR"=>"Package", "NONRECTANGULAR"=>"Non-Rectangular Package"
                        ];
                break;
            case "STANDARD POST":
            case "MEDIA":
            case "LIBRARY":
                return  [
                            "RECTANGULAR"=>"Package", "NONRECTANGULAR"=>"Non-Rectangular Package"
                        ];
                break;

            case 15: //"First-Class Package International Service":
                return  ["Package"=>"Package"];
                break;
            case 1: //"Priority Mail Express International":
            case 2: //"Priority Mail International":
                return  ["Envelope"=>"Envelope"];
                break;
            case 21: //"First-Class Mail International Postcard":
                return ["Postcards"=>"Postcard"];
                break;
            case 13: //"First-Class Mail International Letter":
                return  ["Letter"=>"Letter"];
                break;
            case 14: //"First-Class Mail International Large Envelope":
                return  ["LargeEnvelope"=>"Large Envelope"];
                break;
            case 8: //"Priority Mail International Flat Rate Envelope":
            case 10: //"Priority Mail Express International Flat Rate Envelope":
            case 17: //"Priority Mail Express International Legal Flat Rate Envelope":
            case 18: //"Priority Mail International Gift Card Flat Rate Envelope":
            case 19: //"Priority Mail International Window Flat Rate Envelope":
            case 20: //"Priority Mail International Small Flat Rate Envelope":
            case 22: //Priority Mail International Legal Flat Rate Envelope":
            case 23: //"Priority Mail International Padded Flat Rate Envelope":
            case 27: //"Priority Mail Express International Padded Flat Rate Envelope":
                return ["FlatRate"=>"Flat Rate Enveloper"];
                break;
            case 9: //"Priority Mail International Medium Flat Rate Box":
            case 11: //"Priority Mail International Large Flat Rate Box":
            case 16: //"Priority Mail International Small Flat Rate Box":
            case 24: //"Priority Mail International DVD Flat Rate Box":
            case 25: //"Priority Mail International Large Video Flat Rate Box":
            case 26: //"Priority Mail Express International Flat Rate Box":
                return ["FlatRateBox"=>"Flat Rate Box"];
                break;
            default:
                return ["DEFAULT"=>"Package"];
        }
    }

    function GetPackageQualifiers($serviceName = null, $packageType = null)
    {
        if (\DblEj\Util\Strings::StartsWith($serviceName, "FIRST CLASS") && ($packageType == "PARCEL" || $packageType == "PACKAGE SERVICE"))
        {
            return ["RECTANGULAR"=>"Rectangular", "NONRECTANGULAR"=>"Non-Rectangular"];
        }
        elseif (stristr($serviceName, "INTERNATIONAL"))
        {
            return ["RECTANGULAR"=>"Rectangular", "NONRECTANGULAR"=>"Non-Rectangular"];
        } else {
            return ["DEFAULT"=>"None"];
        }
    }

    public function GetServiceFlagNames($serviceName = null, $packageType = null, $packageQualifier = null)
    {
        $flags = [];
        if (($serviceName == "FIRST CLASS" && ($packageType == "LETTER" || $packageType == "FLAT")) || $serviceName == "STANDARD POST")
        {
            $flags[] = ["MACHINABLE"=>"Machinable"];
        }
        return $flags;
    }

    function GetShippingCost(
        $service, $sourceAddress = null, $sourceCity = null, $sourceStateOrRegion = null, $sourceCountry = null, $sourcePostalCode = null,
        $destAddress = null, $destCity = null, $destStateOrRegion = null, $destCountry = null, $destPostalCode = null,
        $packageType = null, $packageQualifier = null, $weight = null, $packageWidth = null, $packageHeight = null, $packageLength = null, $packageGirth = null,
        $valueOfContents = null, $tracking = false, $insuranceAmount = null, $codAmount = null, $contentsType = null, $serviceFlags = []
    )
    {
        if ($packageType == "DEFAULT")
        {
            $packageType = null;
        }
        if ($packageQualifier == "DEFAULT")
        {
            $packageQualifier = null;
        }
        if (!$weight)
        {
            $weight = 0;
        }
        if (!is_array($serviceFlags))
        {
            $serviceFlags = [];
        }
        if (!isset($serviceFlags["Machinable"]))
        {
            $serviceFlags["Machinable"] = true;
        }
        $postage = 0;
        if (!$sourcePostalCode)
        {
            throw new \Exception("USPS requires a source zip code to calculate shipping cost");
        }
        if (!$destCountry && !$destPostalCode)
        {
            throw new \Exception("USPS requires a destination zip code to calculate domestic shipping cost");
        }
        if ($weight > 0)
        {
            $service = strtoupper($service);
            if (!stristr($packageType, "FLAT") && !stristr($packageType, "ENVELOPE") && ($packageWidth > 12 || $packageHeight > 12 || $packageLength > 12 || $packageGirth > 12))
            {
                $size = "LARGE";
            } else {
                $size = "REGULAR";
                if ($packageQualifier == "RECTANGULAR" || $packageQualifier == "NONRECTANGULAR")
                {
                    $packageQualifier = null;
                }
            }
            $weightLbs = \floor($weight/16);
            $weightOzs = \ceil($weight % 16);


            $machinable = isset($serviceFlags["MACHINABLE"])?$serviceFlags["MACHINABLE"]:true;
            $machinable = $machinable?"True":"False";
            if (\DblEj\Util\Strings::StartsWith(strtolower($destCountry),"united states"))
            {
                if ($service == "FIRST CLASS" || $service == "FIRST CLASS COMMERCIAL" || $service == "FIRST CLASS HFP COMMERCIAL")
                {
                    $firstClassMailType = $packageType;
                    $container = $packageQualifier;
                } else {
                    $firstClassMailType = null;
                    $container = $packageType;
                }

                $xmlString = "<RateV4Request USERID=\"$this->_userId\" >
                                <Revision/>
                                <Package ID=\"1ST\">
                                    <Service>$service</Service>
                                    <FirstClassMailType>$firstClassMailType</FirstClassMailType>
                                    <ZipOrigination>$sourcePostalCode</ZipOrigination>
                                    <ZipDestination>$destPostalCode</ZipDestination>
                                    <Pounds>$weightLbs</Pounds>
                                    <Ounces>$weightOzs</Ounces>
                                    <Container>$container</Container>
                                    <Size>$size</Size>
                                    <Width>$packageWidth</Width>
                                    <Length>$packageLength</Length>
                                    <Height>$packageHeight</Height>
                                    <Girth>$packageGirth</Girth>
                                    <Machinable>$machinable</Machinable>
                                </Package>
                           </RateV4Request>";
                $apiEndpoint = "http://production.shippingapis.com/ShippingAPI.dll?API=RateV4";
            } else {
                //intl
                if (!$valueOfContents)
                {
                    $valueOfContents = 0;
                }
                $xmlString = "<IntlRateV2Request USERID=\"$this->_userId\" >
                                <Revision>2</Revision>
                                <Package ID=\"1ST\">
                                    <Pounds>$weightLbs</Pounds>
                                    <Ounces>$weightOzs</Ounces>
                                    <Machinable>$machinable</Machinable>
                                    <MailType>$packageType</MailType>
                                    <ValueOfContents>$valueOfContents</ValueOfContents>
                                    <Country>$destCountry</Country>
                                    <Container>$packageQualifier</Container>
                                    <Size>$size</Size>
                                    <Width>$packageWidth</Width>
                                    <Length>$packageLength</Length>
                                    <Height>$packageHeight</Height>
                                    <Girth>$packageGirth</Girth>
                                    <OriginZip>$sourcePostalCode</OriginZip>
                                </Package>
                           </IntlRateV2Request>";
                $apiEndpoint = "http://production.shippingapis.com/ShippingAPI.dll?API=IntlRateV2";
            }
            $xmlString = str_replace("\r\n", "\n", $xmlString);
            $xmlString = str_replace("\n\r", "\n", $xmlString);
            $xmlString = preg_replace("/\n\s+/s", "", $xmlString);
            $xmlString = str_replace(" ","%20",$xmlString);
            $url = "$apiEndpoint&XML=".$xmlString;
            $result = \DblEj\Communication\Http\Util::SendRequest($url);
            if ($result)
            {
                $resultXml = simplexml_load_string($result);
                if ($resultXml && isset($resultXml->Package))
                {
                    if (\DblEj\Util\Strings::StartsWith(strtolower($destCountry),"united states"))
                    {
                        if (isset($resultXml->Package->Error))
                        {
                            if (stristr($resultXml->Package->Error->Description, "Invalid First Class Mail Type"))
                            {
                                $errorMessage = "$firstClassMailType is not valid packaging for the ".ucwords($service) ." shipment service";
                            } else {
                                $errorMessage = "Failed to get shipping charges from USPS due to: ".$resultXml->Package->Error->Description;
                            }
                            throw new \Exception($errorMessage);
                        } else {
                            if (isset($resultXml->Package->Postage->CommercialRate))
                            {
                                $postage = $resultXml->Package->Postage->CommercialRate[0];
                            } else {
                                $postage = $resultXml->Package->Postage->Rate[0];
                            }
                        }
                    } elseif (isset($resultXml->Package) && isset($resultXml->Package->Service)) {
                        foreach ($resultXml->Package->Service as $serviceNode)
                        {
                            if ($serviceNode->attributes()->ID == $service)
                            {
                                if (isset($serviceNode->CommercialPostage))
                                {
                                    $postage = $serviceNode->CommercialPostage;
                                } else {
                                    $postage = $serviceNode->Postage;
                                }
                            }
                        }
                    }
                } elseif ($resultXml && isset($resultXml->Number)) {
                    throw new \Exception("Failed to get shipping charges from USPS due to ".$resultXml->Description);
                } else {
                    throw new \Exception("Unknown error getting shipping charges from USPS");
                }
            } else {
                throw new \Exception("No response from USPS");
            }
        }
        return $postage;
    }


    function GetLocationFromPostalCode ($postalCode)
    {
        $city = "";
        $state = "";

        $postage = 0;

        if (!$postalCode)
        {
            throw new \Exception("Invalid postal code");
        }
        $xmlString = "  <CityStateLookupRequest USERID=\"$this->_userId\" >
                            <ZipCode ID=\"0\">
                                <Zip5>$postalCode</Zip5>
                            </ZipCode>
                        </CityStateLookupRequest>";

        $xmlString = str_replace("\r\n", "\n", $xmlString);
        $xmlString = str_replace("\n\r", "\n", $xmlString);
        $xmlString = preg_replace("/\n\s+/s", "", $xmlString);
        $xmlString = str_replace(" ","%20",$xmlString);
        $url = "http://production.shippingapis.com/ShippingAPI.dll?API=CityStateLookup&XML=".$xmlString;
        $result = \DblEj\Communication\Http\Util::SendRequest($url);
        if ($result)
        {
            $resultXml = simplexml_load_string($result);
            if ($resultXml && isset($resultXml->ZipCode))
            {
                if (!isset($resultXml->ZipCode->Error))
                {
                    $city = (string)$resultXml->ZipCode->City;
                    $state = (string)$resultXml->ZipCode->State;
                }
            }
        } else {
            throw new \Exception("No response from USPS");
        }
        return ["City"=>$city, "State"=>$state];
    }
}
?>