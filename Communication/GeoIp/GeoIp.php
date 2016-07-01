<?php

namespace Wafl\Extensions\Communication\GeoIp;

use DblEj\Application\IApplication,
    DblEj\Extension\ExtensionBase;

class GeoIp extends ExtensionBase
{
    private $_geoReader = null;
    private $_datFile = "GeoLite2-Country.mmdb";
    private $_selfLookupIp = "127.0.0.1";
    public function Initialize(IApplication $app)
    {
		require_once("phar://" . __DIR__ . "/geoip2.phar");
    }

    protected static function getAvailableSettings()
    {
        return ["DatabaseFile", "SelfLookupIpAddress"];
    }

    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            case "DatabaseFile":
                $this->_datFile = $settingValue;
                break;
            case "SelfLookupIpAddress":
                $this->_selfLookupIp = $settingValue;
                break;
        }
    }

    public function Lookup($ip)
    {
        try
        {
            if ($ip == "127.0.0.1")
            {
                $ip = $this->_selfLookupIp;
            }
            $filePath = __DIR__.DIRECTORY_SEPARATOR.$this->_datFile;
            if (!file_exists($filePath))
            {
                //no dat file installed.  Try downloading it from http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz OR SIMILAR FILE
                $tempFileGz = tempnam(sys_get_temp_dir(),"geogz");
                \DblEj\Communication\Http\Util::DownloadFile("http://geolite.maxmind.com/download/geoip/database/".basename($filePath).".gz", $tempFileGz);
                if (file_exists($tempFileGz))
                {
                    $tempFileGzH = gzopen($tempFileGz, 'r');
                    $datFileH = @fopen($filePath, 'xb');
                    if ($datFileH === false)
                    {
                        throw new \Exception("Cannot download the GeoIp database because I cannot create the file: $filePath (probably due to permissions).");
                    }
                    while (!feof($tempFileGzH)) {
                        fwrite($datFileH, gzread($tempFileGzH, 2048)); // writes decompressed data from $tempFileGzH to $datFileH
                    }

                    fclose($tempFileGzH);
                    fclose($datFileH);
                }
            }
            if (!file_exists($filePath))
            {
                throw new \Exception("The GeoIP extension cannot lookup geoip data without a valid database file.  Currently I am looking in '$filePath' and cannot find the file. I tried to download it from geolite.maxmind.com but that did not work (probably because of no internet or no write permissions). Please check your settings in Extensions.syrp for the DatabaseFile setting.");
            }
            if (!$this->_geoReader)
            {
                $this->_geoReader = new \GeoIp2\Database\Reader($filePath);
            }
            $metadata = $this->_geoReader->metadata();
            if (stristr($metadata->databaseType, "City"))
            {
                $cityInfo = $this->_geoReader->city($ip);
                $countryInfo = $cityInfo->country;
                $city = $cityInfo->city->name;
                $region = $cityInfo->mostSpecificSubdivision->name;
                $regionCode = $cityInfo->mostSpecificSubdivision->isoCode;
                $postal = $cityInfo->postal->code;
                $countryCode = $countryInfo->isoCode;
                $country = $countryInfo->name;
            }
            elseif (stristr($metadata->databaseType, "Country"))
            {
                $countryInfo = $this->_geoReader->country($ip);
                $countryCode = $countryInfo->isoCode;
                $country = $countryInfo->name;
                $city = "";
                $region = "";
                $regionCode = "";
                $postal = "";
            }
        } catch (\GeoIp2\Exception\AddressNotFoundException $ex) {
            $country = "";
            $countryCode = "";
            $region = "";
            $regionCode = "";
            $postal = "";
            $city = "";
        }

        return ["City"=>$city, "Country"=>$country, "CountryCode"=>$countryCode,"Region"=>$region,"RegionCode"=>$regionCode,"Postal"=>$postal];
    }
}