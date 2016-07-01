<?php

namespace Wafl\Extensions\Commerce\GoogleMerchant;

class GoogleProduct
{
    private $_id;
    private $_title;
    private $_description;
    private $_googleCategory;
    private $_customCategory;
    private $_url;
    private $_mobileUrl;
    private $_imageUrls;
    private $_condition;
    private $_availability;
    private $_price;
    private $_salePrice;
    private $_saleDateRange;
    private $_mpn;
    private $_gtin;
    private $_brand;
    private $_attributes;
    private $_variantGroupId;
    private $_channel;
    private $_currency;
    private $_weight;
    private $_weightUnit;

    const CONDITION_NEW = "new";
    const CONDITION_USED= "used";
    const CONDITION_REFURBISHED = "refurbished";

    const AVAIL_INSTOCK = "in stock";
    const AVAIL_PREORDER = "preorder";
    const AVAIL_OUTSTOCK = "out of stock";

    const CHANNEL_ONLINE = "online";
    const CHANNEL_LOCAL = "local";

    const CURRENCY_USD = "usd";
    const CURRENCY_GBP = "gbp";

    public function __construct($id, $title, $gtin=null, $mpn=null, $brand=null, $description=null, $googleCategory=null, $customCategory=null, $url=null, $price=null, 
                                $imageUrls=null, $mobileUrl=null, $attributes=null, $availability=self::AVAIL_INSTOCK, $weight=null, $weightUnit="lbs", $variantGroupId=null,
                                $salePrice=null, $saleDateStart=null, $saleDateEnd=null, $condition=self::CONDITION_NEW,
                                $currency=self::CURRENCY_USD, $timezoneName="GMT", $channel=self::CHANNEL_ONLINE)
    {
        if (!$attributes)
        {
            $attributes = [];
        }
        if (!$imageUrls)
        {
            $imageUrls = [];
        }

        if ($condition != self::CONDITION_NEW && $condition != self::CONDITION_USED && $condition != self::CONDITION_REFURBISHED)
        {
            throw new \Exception("Invalid condition specified");
        }
        if ($availability != self::AVAIL_INSTOCK && $availability != self::AVAIL_PREORDER && $availability != self::AVAIL_OUTSTOCK)
        {
            throw new \Exception("Invalid availability specified");
        }

        $this->_id = $id;
        $this->_title = $title;
        $this->_gtin = $gtin;
        $this->_mpn = $mpn;
        $this->_brand = $brand;
        $this->_description = $description;
        $this->_googleCategory = $googleCategory;
        $this->_customCategory = $customCategory;
        $this->_url = $url;
        $this->_mobileUrl = $mobileUrl;
        $this->_imageUrls = $imageUrls;
        $this->_condition = $condition;
        $this->_availability = $availability;
        $this->_price = $price;
        $this->_salePrice = $salePrice;
        $this->_channel = $channel;
        $this->_currency = $currency;
        $this->_weight = $weight;
        $this->_weightUnit = $weightUnit;
        
        if ($saleDateStart && $saleDateEnd)
        {
            $timezone = new \DateTimeZone($timezoneName);
            $startTime = new \DateTime("@$saleDateStart");
            $endTime = new \DateTime("@$saleDateEnd");

            $startTime->setTimezone($timezone);
            $endTime->setTimezone($timezone);
            $this->_saleDateRange = $startTime->format("Y-m-d\TH:iO")."/".$endTime->format("Y-m-d\TH:iO");
        } else {
            $this->_saleDateRange = null;
        }
        $this->_attributes = $attributes;
        $this->_variantGroupId = $variantGroupId;
    }

    public function ToGoogleSdkObject()
    {
        $product = new \Google_Service_ShoppingContent_Product();
        $product->setTitle(preg_replace('/[^\PC\s]/u', '', $this->_title));
        $product->setDescription(preg_replace('/[^\PC\s]/u', '', $this->_description));
        $product->setLink($this->_url);
        $product->setId($this->_id);
        if (count($this->_imageUrls) > 0)
        {
            $product->setImageLink(reset($this->_imageUrls));
        }
        if (count($this->_imageUrls) > 1)
        {
            $product->setAdditionalImageLinks(array_slice($this->_imageUrls, 1, count($this->_imageUrls) > 8?8:null));
        }
        $customAttributes = [];
        foreach ($this->_attributes as $attribute)
        {
            if (!isset($customAttributes[$attribute["name"]]))
            {
                $customAttribute = new \Google_Service_ShoppingContent_ProductCustomAttribute();
                $customAttribute->setName($attribute["name"]);
                $customAttribute->setValue($attribute["value"]);
                if (isset($attribute["type"]) && $attribute["type"])
                {
                    $customAttribute->setType($attribute["type"]);
                } else {
                    $customAttribute->setType("text");
                }
                if (isset($attribute["unit"]) && $attribute["unit"])
                {
                    $customAttribute->setUnit($attribute["unit"]);
                }
                $customAttributes[$attribute["name"]] = $customAttribute;
            }
        }
        $unkeyedAttributes = [];
        foreach ($customAttributes as $customAttribute)
        {
            $unkeyedAttributes[] = $customAttribute;
        }
        $product->setCustomAttributes($unkeyedAttributes);
        $product->setChannel($this->_channel);
        $product->setAvailability($this->_availability);
        $product->setCondition($this->_condition);
        $product->setGoogleProductCategory($this->_googleCategory);
        $product->setProductType($this->_customCategory);
        if ($this->_gtin)
        {
            $product->setGtin($this->_gtin);
        }
        $product->setMpn($this->_mpn);
        $product->setBrand($this->_brand);

        $price = new \Google_Service_ShoppingContent_Price();
        $price->setValue($this->_price);
        $price->setCurrency($this->_currency);
        $product->setOfferId($this->_mpn);
        $product->setTargetCountry("us");
        $product->setContentLanguage("en");
        $product->setItemGroupId($this->_variantGroupId);

//        $shipping_price = new \Google_Service_ShoppingContent_Price();
//        $shipping_price->setValue('0.99');
//        $shipping_price->setCurrency('GBP');
//
//        $shipping = new \Google_Service_ShoppingContent_ProductShipping();
//        $shipping->setPrice($shipping_price);
//        $shipping->setCountry('GB');
//        $shipping->setService('Standard shipping');

        $product->setPrice($price);

        if ($this->_weight > 0)
        {
            $shipping_weight = new \Google_Service_ShoppingContent_ProductShippingWeight();
            $shipping_weight->setValue($this->_weight);
            $shipping_weight->setUnit($this->_weightUnit);
            $product->setShippingWeight($shipping_weight);
        }

        return $product;
    }

    public function Get_GoogleCategory()
    {
        return $this->_googleCategory;
    }
    public function ToText($sellerName, $useGoogleId = true, $separateSizeUnit = false)
    {
        $imageUrl = count($this->_imageUrls)?reset($this->_imageUrls):"";
        if (isset($this->_attributes["material"]))
        {
            $material = $this->_attributes["material"]["value"];
        } else {
            $material = "";
        }

        if (isset($this->_attributes["gender"]))
        {
            $gender = $this->_attributes["gender"]["value"];
        } else {
            $gender = "";
        }
        if (isset($this->_attributes["color"]))
        {
            $color = $this->_attributes["color"]["value"];
        } else {
            $color = "";
        }
        if (isset($this->_attributes["size"]) && $this->_attributes["size"])
        {
            if ($separateSizeUnit)
            {
                $size = $this->_attributes["size"]["value"];
                $unit = isset($this->_attributes["unit"])?$this->_attributes["unit"]["value"]:"";
            } else {
                $size = $this->_attributes["size"]["value"].(isset($this->_attributes["unit"])?$this->_attributes["unit"]["value"]:"");
                $unit = "";
            }
        } else {
            $size = "";
            $unit = "";
        }
        
        $title = preg_replace('/[^\PC\s]/u', '', $this->_title);
        $description = preg_replace('/[^\PC\s]/u', '', $this->_description);

        $title = str_replace("\r\n", " ", $title);
        $title = str_replace("\n", " ", $title);
        $title = str_replace("\t", " ", $title);
        $description = str_replace("\r\n", " ", $description);
        $description = str_replace("\n", " ", $description);
        $description = str_replace("\t", " ", $description);
        $price = round($this->_price, 2);
        $salePrice = $this->_salePrice?round($this->_salePrice, 2):"";
        return ($useGoogleId?$this->_id:$this->_mpn)."\t$title\t$this->_brand\t$this->_mpn\t$this->_url\t$price\t$description\t$imageUrl\t$sellerName\t$this->_variantGroupId\t$material\t$gender\t$color\t$size\t".($separateSizeUnit?"$unit\t":"")."$this->_availability\t$this->_googleCategory\t$this->_customCategory\t$salePrice\t$this->_saleDateRange";
    }

    public function HasImage()
    {
        return $this->_imageUrls && (count($this->_imageUrls) > 0);
    }
    public function Get_Id()
    {
        return $this->_id;
    }

    public function Get_Mpn()
    {
        return $this->_mpn;
    }

    public function Get_Brand()
    {
        return $this->_brand;
    }

    public function Get_Price()
    {
        return $this->_price;
    }

    public function Get_Currency()
    {
        return $this->_currency;
    }
    public function Set_Id()
    {
        return $this->_id;
    }
}