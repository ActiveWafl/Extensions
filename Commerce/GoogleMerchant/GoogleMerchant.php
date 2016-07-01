<?php

namespace Wafl\Extensions\Commerce\GoogleMerchant;

class GoogleMerchant extends \DblEj\Extension\ExtensionBase
{
    private $_apiKeyFile;
    private $_apiUrl;
    private $_appName;
    private $_apiClientEmail;
    private $_googleClient;
    private $_merchantId;

    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }

    /**
     * Connect to google api server and authenticate.
     * Use the apiKeyFile and apiClientEmail that is set in the Extensions.syrp config,
     * or alternately pass in those settings explicitly.
     * If the values are not set in the syrup file and are also not passed in, then do nothing.
     *
     * @param string $apiKeyFile Full absolute path to a .p12 api key file.
     * @param type $apiClientEmail Google api service account client email. (note: this email must also be added to merchant center list of users)
     */
    private function _authenticate($apiKeyFile = null, $apiClientEmail = null)
    {
        $googleSdkSrc = __DIR__.DIRECTORY_SEPARATOR."Sdk".DIRECTORY_SEPARATOR."src";
        $googleSdkSrc = realpath($googleSdkSrc);
        $googleClientFile = $googleSdkSrc . DIRECTORY_SEPARATOR . "Google" . DIRECTORY_SEPARATOR . "Client.php";
        set_include_path(get_include_path() . PATH_SEPARATOR . $googleSdkSrc);
        require_once($googleClientFile);

        if ($apiKeyFile)
        {
            if (file_exists($apiKeyFile))
            {
                $this->_apiKeyFile = $apiKeyFile;
            } else {
                throw new \Exception("Cannot connect to google merchant api because the api key file is invalid");
            }
        }
        if ($apiClientEmail)
        {
            $this->_apiClientEmail = $apiClientEmail;
        }

        $client = null;
        if ($this->_apiKeyFile && $this->_apiClientEmail)
        {
            $private_key = file_get_contents($this->_apiKeyFile);
            $scopes = array('https://www.googleapis.com/auth/content');
            $credentials = new \Google_Auth_AssertionCredentials(
                $this->_apiClientEmail,
                $scopes,
                $private_key
            );
            $client = new \Google_Client();
            $client->setAssertionCredentials($credentials);
        }
        return $client;
    }

    private function _refreshAuthToken()
    {
        if (!$this->_googleClient)
        {
            $this->_googleClient = $this->_authenticate();
        }
        if ($this->_googleClient->getAuth()->isAccessTokenExpired())
        {
            $this->_googleClient->getAuth()->refreshTokenWithAssertion();
        }
        return $this->_googleClient->getAuth();
    }

    /**
     *
     * @param GoogleProduct[] $googleProducts
     */
    public function AddProducts(array $googleProducts, $merchantId = null, $dryRun = false, $printProgress = false)
    {
        $results = [];
        if (count($googleProducts) > 1)
        {
            $batchMode = true;
        } else {
            $batchMode = false;
        }
        $this->_refreshAuthToken();

        if ($merchantId != null)
        {
            $this->_merchantId = $merchantId;
        }
        $service = new \Google_Service_ShoppingContent($this->_googleClient);

        $productCount = count($googleProducts);
        $countPerBatch = 10;

        $productIdx = 0;
        if ($batchMode)
        {
            $batchCount = floor($productCount/$countPerBatch);
            if ($productCount % $countPerBatch != 0)
            {
                $batchCount++;
            }
        } else {
            $batchCount= 1;
        }
        $results = [];
        for ($batchIdx = 0; $batchIdx < $batchCount; $batchIdx++)
        {
            if ($printProgress)
            {
                print "\n\nstarting batch $batchIdx...";
            }
            if ($batchMode)
            {
                $batchEntries = [];
            }
            foreach (array_slice($googleProducts, $productIdx, min($countPerBatch, $productCount-$productIdx), true) as $productKey=>$googleProduct)
            {
                if ($printProgress)
                {
                    print ".";
                }

                $googleSdkProduct = $googleProduct->ToGoogleSdkObject();
                $googleSdkProductKey = $productKey;
                $productIdx++;
                if ($batchMode)
                {
                    $batchEntry = new \Google_Service_ShoppingContent_ProductsCustomBatchRequestEntry();
                    $batchEntry->setMethod('insert');
                    $batchEntry->setBatchId($productKey);
                    $batchEntry->setProduct($googleSdkProduct);
                    $batchEntry->setMerchantId($this->_merchantId);
                    $batchEntries[] = $batchEntry;
                }
            }

            if ($batchMode)
            {
                if ($printProgress)
                {
                    print "\nsending batch $batchIdx to google...";
                }

                $batchRequest = new \Google_Service_ShoppingContent_ProductsCustomBatchRequest();
                $batchRequest->setEntries($batchEntries);
                $batchResult = $service->products->custombatch($batchRequest, ["dryRun"=>$dryRun]);

                if ($printProgress)
                {
                    print "\nbatch $batchIdx sent to google, checking response...";
                }

                foreach ($batchResult->getEntries() as $batchEntryResult)
                {
                    if ($batchEntryResult->getProduct())
                    {
                        $results[$batchEntryResult->getBatchId()] = $batchEntryResult->getProduct();
                    } else {
                        $results[$batchEntryResult->getBatchId()] = $batchEntryResult->getErrors();
                    }
                }
            } else {
                if ($printProgress)
                {
                    print "\nsending product to google...";
                }
                $results[$googleSdkProductKey] = $service->products->insert($this->_merchantId, $googleSdkProduct, ["dryRun"=>$dryRun]);
            }
        }
        return $results;
    }
    public function DeleteProduct(GoogleProduct $product, $merchantId = null)
    {
        $this->_refreshAuthToken();
        if ($merchantId != null)
        {
            $this->_merchantId = $merchantId;
        }
        $service = new \Google_Service_ShoppingContent($this->_googleClient);
        return $service->products->delete($this->_merchantId, $product->Get_Id());
    }
    public function UpdateProductsPricingAndInventory(array $products, $merchantId = null, $storeCode = "online")
    {
        $results = [];
        if (count($products) > 1)
        {
            $batchMode = true;
        } else {
            $batchMode = false;
        }
        $this->_refreshAuthToken();

        if ($batchMode)
        {
            $this->_googleClient->setUseBatch(true);
            $batch = new \Google_Http_Batch($this->_googleClient);
        }
        if ($merchantId != null)
        {
            $this->_merchantId = $merchantId;
        }
        $service = new \Google_Service_ShoppingContent($this->_googleClient);
        foreach ($products as $product)
        {
            $inv = new \Google_Service_ShoppingContent_InventorySetRequest();
            $price = new \Google_Service_ShoppingContent_Price();
            $price->setValue($product->Get_Price());
            $price->setCurrency($product->Get_Currency());
            $inv->setPrice($price);
            $results[] = $service->inventory->set($this->_merchantId, $storeCode, $product->Get_Id(), $inv);
        }

        if ($batchMode)
        {
            $opIdx = 0;
            foreach ($results as $addBatchResult)
            {
                $batch->add($addBatchResult, $opIdx);
                $opIdx++;
            }
            $results = $batch->execute();
        }

        return $results;
    }

	protected static function getAvailableSettings()
	{
		return array("AppName", "ApiKeyFile", "ApiUrl", "ApiClientEmail", "MerchantId");
	}

	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
            case "ApiKeyFile":
                $this->_apiKeyFile = $settingValue;
                break;
            case "ApiUrl":
                $this->_apiUrl = $settingValue;
                break;
            case "AppName":
                $this->_appName = $settingValue;
                break;
            case "ApiClientEmail":
                $this->_apiClientEmail = $settingValue;
                break;
            case "MerchantId":
                $this->_merchantId = $settingValue;
		}
	}

    public function GetSettingDefault($settingName)
    {
        if ($settingName == "ApiUrl")
        {
            return "https://www.googleapis.com/content/v2";
        } else {
            return parent::GetSettingDefault($settingName);
        }
    }
    protected function getLocalSettingValue($settingName)
    {
		switch ($settingName)
		{
			case "ApiKeyFile":
				return $this->_apiKeyFile;
				break;
			case "ApiUrl":
				return $this->_apiUrl;
				break;
			case "AppName":
				return $this->_appName;
				break;
            case "ApiClientEmail":
				return $this->_apiClientEmail;
                break;
            case "MerchantId":
                return $this->_merchantId;
                break;
		}
    }
}