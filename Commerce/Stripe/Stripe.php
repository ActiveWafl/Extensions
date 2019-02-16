<?php

namespace Wafl\Extensions\Commerce\Stripe;

class Stripe
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Commerce\Integration\IPaymentGatewayExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Util".DIRECTORY_SEPARATOR."RequestOptions.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Util".DIRECTORY_SEPARATOR."Set.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."JsonSerializable.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."StripeObject.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."AttachedObject.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."ApiResponse.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."HttpClient".DIRECTORY_SEPARATOR."ClientInterface.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."HttpClient".DIRECTORY_SEPARATOR."CurlClient.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Util".DIRECTORY_SEPARATOR."Util.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."ApiRequestor.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."StripeObject.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."ApiResource.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."ExternalAccount.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."SingletonApiResource.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Balance.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Card.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Source.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Charge.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Collection.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Refund.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Account.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."ExternalAccount.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Customer.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."BankAccount.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Event.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."Base.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."Card.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."InvalidRequest.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."RateLimit.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."Authentication.php");
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Error".DIRECTORY_SEPARATOR."ApiConnection.php");

    }

    /**
     * A Managed Account is needed if you want to be able to transfer money into someones bank account
     *
     * @param string $emailAddress
     * @param string $nameOnAccount
     * @param string $postalCode
     * @param string $city
     * @param string $stateOrCountyOrProvince
     * @param string $country 2 letter country code
     * @param string $streetAddress
     * @param string $businessName
     * @param string $dob yyyy-m-d
     * @return string the new account token
     * @throws \Exception
     */
    public static function AddManagedAccount($emailAddress, $nameOnAccount = null,
        $postalCode = null, $city = null, $stateOrCountyOrProvince = null,
        $country = null, $streetAddress = null, $businessName = null, $dob = null,
        $termsAgreementDate = null, $termsAgreementIp = null, $taxId = null, $ssnLastFour = null)
	{
        require_once(__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar");
        $nameParts = explode(" ", $nameOnAccount);
        $accountArray =
            [
                "country" => $country,
                "managed" => true,
                "email" => $emailAddress
            ];
        if (count($nameParts) > 1)
        {
            $firstName = array_splice($nameParts, 0, 1)[0];
            $lastName = implode(" ", $nameParts);

            if ($firstName && $lastName)
            {
                $accountArray["legal_entity"] =
                    [
                        "type"=>"individual",
                        "first_name" => $firstName,
                        "last_name" => $lastName,
                        "ssn_last_4" => $ssnLastFour
                    ];
            }
            if ($businessName)
            {
                if (!isset($accountArray["legal_entity"]))
                {
                    $accountArray["legal_entity"] = [];
                }
                $accountArray["business_name"] = $businessName;
                if ($taxId)
                {
                    $accountArray["legal_entity"]["business_tax_id"] = $taxId;
                }
                $accountArray["legal_entity"]["business_name"] = $businessName;
                $accountArray["legal_entity"]["type"] = "company";
            }
            elseif ($taxId)
            {
                $accountArray["legal_entity"]["personal_id_number"] = $taxId;
            }
            if ($streetAddress || $city || $postalCode || $country || $stateOrCountyOrProvince)
            {
                if (!isset($accountArray["legal_entity"]))
                {
                    $accountArray["legal_entity"] = ["type"=>"individual"];
                }
                $accountArray["legal_entity"]["address"] = [];
            }
            if ($streetAddress)
            {
                $accountArray["legal_entity"]["address"]["line1"] = $streetAddress;
            }
            if ($city)
            {
                $accountArray["legal_entity"]["address"]["city"] = $city;
            }
            if ($postalCode)
            {
                $accountArray["legal_entity"]["address"]["postal_code"] = $postalCode;
            }
            if ($country)
            {
                $accountArray["legal_entity"]["address"]["country"] = $country;
            }
            if ($stateOrCountyOrProvince)
            {
                $accountArray["legal_entity"]["address"]["state"] = $stateOrCountyOrProvince;
            }
            if ($dob)
            {
                $dobParts = $dob?["day"=>date("d", $dob), "month"=>date("m", $dob), "year"=>date("Y", $dob)]:null;
                if (count($dobParts) < 3)
                {
                    throw new \Exception("Invalid dob: $dob");
                }
                $accountArray["legal_entity"]["dob"] = $dobParts;
            }
            if ($termsAgreementDate)
            {
                $accountArray["tos_acceptance"] = ["date"=>$termsAgreementDate, "ip"=>$termsAgreementIp];
            }
            $stripeAccount = \Stripe\Account::create($accountArray);
            return $stripeAccount->id;
        } else {
            throw new \Exception("Invalid full name passed");
        }
	}

    public static function GetBalance()
    {
        return \Stripe\Balance::retrieve();
    }
    /**
     * A Customer is needed to save (and later charge) credit cards.
     * (You do not need a customer to charge a card; only to charge a *saved* card)
     *
     * @param string $emailAddress
     * @return type
     */
	public static function AddCustomer($emailAddress = null, $description = null)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $accountArray = ["email" => $emailAddress, "description" => $description];
        $customer = \Stripe\Customer::create($accountArray);
        return $customer->id;
	}

	public static function EditCustomer($customerToken, $emailAddress = null, $description = null)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $customer = \Stripe\Customer::retrieve($customerToken);

        if ($customer)
        {
            $customer->email = $emailAddress;
            $customer->description = $description;
            $customer->save();
        }
        return $customer->id;
	}

	public static function GetAllPaypalCustomers()
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $lastCustomerId = null;
        $chunkSize = 100;

        $ids = [];
        $options = array("limit" => $chunkSize);
        $customersObject = \Stripe\Customer::all($options);
        $customers = $customersObject->data;
        while (count($customers) > 0)
        {
            foreach ($customers as $customer)
            {
                if (isset($customer->metadata["paypal_id"]))
                {
                    $ids[] = ["PaypalId"=>$customer->metadata["paypal_id"], "CustomerId"=>$customer->id, "CustomerObject"=>$customer];
                    $lastCustomerId = $customer->id;
                }
            }
            $options["starting_after"] = $lastCustomerId;
            $customersObject = \Stripe\Customer::all($options);
            $customers = $customersObject->data;
        }
        return $ids;
	}

	public static function GetAllEvents($eventType, $startDate, $endDate)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $lastEventId = null;
        $chunkSize = 100;

        $allEvents = [];
        $options = array("limit" => $chunkSize, "type" => $eventType ,"created"=>["gte"=>$startDate, "lte"=>$endDate]);
        $eventsObject = \Stripe\Event::all($options);
        $events = $eventsObject->data;
        while (count($events) > 0)
        {
            foreach ($events as $event)
            {
                $allEvents[] = $event;
                $lastEventId = $event->id;
            }
            $options["starting_after"] = $lastEventId;
            $eventsObject = \Stripe\Event::all($options);
            $events = $eventsObject->data;
        }
        return $allEvents;
	}

    /**
     * Add a Bank Account to a Stripe Managed Account
     *
     * @param string $managedAccountToken returned from AddManagedAccount
     * @param string $routingNumber
     * @param string $accountNumber
     * @param string $countryCode 2 digit country code
     * @return string
     */
	public static function AddBankAccount ($managedAccountToken, $routingNumber, $accountNumber, $countryCode)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        if (substr($managedAccountToken, 0, 4) == "cus_")
        {
            $stripeUserAccount = \Stripe\Customer::retrieve($managedAccountToken);
            $arrayKey = "source";
        }
        elseif (substr($managedAccountToken, 0, 5) == "acct_")
        {
            $stripeUserAccount = \Stripe\Account::retrieve($managedAccountToken);
            $arrayKey = "external_account";
        }

        $currency = "usd";
        switch (strtoupper($countryCode))
        {
            case "US":
                $currency = "usd";
                break;
            case "AU":
                $currency = "aud";
                break;
            case "CA":
                $currency = "cad";
                break;
            case "DK":
                $currency = "eur";
                break;
            case "FI":
                $currency = "eur";
                break;
            case "FR":
                $currency = "eur";
                break;
            case "GB":
                $currency = "eur";
                break;
            case "IE":
                $currency = "eur";
                break;
            case "NO":
                $currency = "eur";
                break;
            case "SE":
                $currency = "eur";
                break;
        }
        $accountData = [
                            "object" => "bank_account",
                            "account_number" => $accountNumber,
                            "country" => $countryCode,
                            "currency" => $currency,
                            "routing_number" => $routingNumber
                        ];
        if ($routingNumber)
        {
            $accountData["routing_number"] = $routingNumber;
        }
        $bankAccount = $stripeUserAccount->external_accounts->create(
            [
                $arrayKey => $accountData
            ]);
		return $bankAccount;
	}

    /**
     * Delete a Bank Account from a Managed Account
     *
     * @param string $managedAccountToken
     * @param string $bankAccountToken
     * @return string
     */
	public static function DeleteBankAccount($managedAccountToken, $bankAccountToken)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        if (substr($managedAccountToken, 0, 4) == "cus_")
        {
            $stripeUserAccount = \Stripe\Customer::retrieve($managedAccountToken);
            $bank_account	 = $stripeUserAccount->sources->retrieve($bankAccountToken);
        }
        elseif (substr($managedAccountToken, 0, 5) == "acct_")
        {
            $stripeUserAccount = \Stripe\Account::retrieve($managedAccountToken);
            $bank_account	 = $stripeUserAccount->external_accounts->retrieve($bankAccountToken);
        }
		$result	= $bank_account->delete();
		return $result->id;
	}

    /**
     * Store a card to a Customer for later charges
     * @param string $customerToken
     * @param string $nameOnCard
     * @param string $cardNumber
     * @param int $expirationMonth m
     * @param int $expirationYear yyyy
     * @param string $securityCode
     * @param string $address
     * @param string $city
     * @param string $state
     * @param string $postal Postal code or zip code
     * @param string $email
     * @param string $countryCode 2 digit code
     * @return string
     */
	public static function AddCard
    (
        $customerToken, $nameOnCard, $cardNumber, $expirationMonth, $expirationYear,
        $securityCode = null, $address = null, $city = null, $stateOrCountyOrProvince = null, $postal = null, $email = null, $countryCode = "US"
    )
	{
        require_once(__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar");
        if (substr($customerToken, 0, 4) == "cus_")
        {
            $stripeCustomer = \Stripe\Customer::retrieve($customerToken);
            $arrayKey = "source";
        }
        elseif (substr($customerToken, 0, 5) == "acct_")
        {
            $stripeCustomer = \Stripe\Account::retrieve($customerToken);
            $arrayKey = "external_account";
        }

        $accountArray =
        [
            $arrayKey =>
            [
                "object" => "card",
                "number" => $cardNumber,
                "exp_month" => $expirationMonth,
                "exp_year" => $expirationYear,
                "currency" => "usd"
            ]
        ];

        if ($nameOnCard)
        {
            $accountArray[$arrayKey]["name"] = $nameOnCard;
        }
        if ($securityCode)
        {
            $accountArray[$arrayKey]["cvc"] = $securityCode;
        }
        if ($address)
        {
            $accountArray[$arrayKey]["address_line1"] = $address;
        }
        if ($city)
        {
            $accountArray[$arrayKey]["address_city"] = $city;
        }
        if ($postal)
        {
            $accountArray[$arrayKey]["address_zip"] = $postal;
        }
        if ($countryCode)
        {
            $accountArray[$arrayKey]["address_country"] = $countryCode;
        }
        if ($stateOrCountyOrProvince)
        {
            $accountArray[$arrayKey]["address_state"] = $stateOrCountyOrProvince;
        }
        $card = $stripeCustomer->sources->create($accountArray);
        return $card->id;
	}

	public static function AddCardByToken ($customerToken, $cardToken)
	{
            require_once(__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar");
            if (substr($customerToken, 0, 4) == "cus_")
            {
                $stripeCustomer = \Stripe\Customer::retrieve($customerToken);
                $arrayKey = "source";
            }
            elseif (substr($customerToken, 0, 5) == "acct_")
            {
                $stripeCustomer = \Stripe\Account::retrieve($customerToken);
                $arrayKey = "external_account";
            }

            $accountArray =
            [
                $arrayKey => $cardToken
            ];

            $card = $stripeCustomer->sources->create($accountArray);
            return $card->id;
        }

	public static function ChargeCardByToken ($cardToken, $amount, $description)
	{
            $returnResult = null;
            try
            {
                require_once(__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar");
                $accountArray =
                [
                    "amount" => $amount,
                    "currency" => "usd",
                    "source" => $cardToken
                ];
                $stripeResult = \Stripe\Charge::create($accountArray);
                if ($stripeResult->status == "succeeded")
                {
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status);
                } else {
                    switch ($stripeResult->failure_code)
                    {
                        case "invalid_number";
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                        case "invalid_expiry_month";
                        case "invalid_expiry_year";
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_EXPIRATION, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                        case "invalid_cvc";
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_SECURITY, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                        case "incorrect_zip";
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_ADDRESS, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                        case "card_declined";
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                        default:
                            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                            break;
                    }
                }
            } catch(\Stripe\Error\Card $ex) {
              // Since it's a decline, \Stripe\Error\Card will be caught
                $body = $ex->getJsonBody();
                switch ($body["error"]["code"])
                {
                    case "invalid_number";
                    case "invalid_cvc";
                    case "invalid_zip";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                        break;
                    case "invalid_expiry_month";
                    case "invalid_expiry_year";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_EXPIRATION, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                        break;
                    case "incorrect_cvc";
                    case "incorrect_zip";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_SECURITY, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                        break;
                    case "card_declined";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                        break;
                    case "missing";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "Payment account information is missing or invalid");
                        break;
                    default:
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                        break;
                }
            } catch (\Stripe\Error\RateLimit $ex) {
              // Too many requests made to the API too quickly
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "The payment processor is busy. Please try again soon.");
            } catch (\Stripe\Error\InvalidRequest $ex) {
              // Invalid parameters were supplied to Stripe's API
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "The card was rejected. Please verify all of the card information and try again.");
            } catch (\Stripe\Error\Authentication $ex) {
              // Authentication with Stripe's API failed (maybe you changed API keys recently)
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error authenticating with the payment processor");
            } catch (\Stripe\Error\ApiConnection $ex) {
              // Network communication with Stripe failed
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error communicating with the payment processor");
            } catch (\Stripe\Error\Base $ex) {
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error with the payment processor");
            } catch (\Exception $ex) {
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was a system error while processing the payment");
            }
            
            return $returnResult;
        }
                
    /**
     * Charge a card
     *
     * @param decimal $amount
     * @param string $nameOnCard
     * @param string $cardNumber
     * @param int $expirationMonth m
     * @param int $expirationYear yyyy
     * @param string $securityCode
     * @param string $address
     * @param string $city
     * @param string $state
     * @param string $postal
     * @param string $email
     * @param string $countryCode 2 digit code
     * @return array
     */
	public static function ChargeCard
    (
        $amount, $nameOnCard, $cardNumber, $expirationMonth, $expirationYear,
        $securityCode = null, $address = null, $city = null, $state = null, $postal = null, $email = null, $countryCode = "US", $recipientAccountId = null, $commission = 0
    )
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $accountArray =
        [
            "amount" => $amount,
            "currency" => "usd",
            "source" =>
            [
                "object" => "card",
                "number" => $cardNumber,
                "exp_month" => $expirationMonth,
                "exp_year" => $expirationYear,
                "currency" => "usd"
            ]
        ];

        if ($nameOnCard)
        {
            $accountArray["source"]["name"] = $nameOnCard;
        }
        if ($securityCode)
        {
            $accountArray["source"]["cvc"] = $securityCode;
        }
        if ($address)
        {
            $accountArray["source"]["address_line1"] = $address;
        }
        if ($city)
        {
            $accountArray["source"]["address_city"] = $city;
        }
        if ($postal)
        {
            $accountArray["source"]["address_zip"] = $postalCode;
        }
        if ($country)
        {
            $accountArray["source"]["address_country"] = $country;
        }
        if ($stateOrCountyOrProvince)
        {
            $accountArray["source"]["address_state"] = $stateOrCountyOrProvince;
        }
        if ($recipientAccountId)
        {
            $accountArray["destination"] = $recipientAccountId;
        }
        if ($commission)
        {
            $accountArray["application_fee"] = $commission;
        }

        return \Stripe\Charge::create($accountArray);
	}

    /**
     * Delete a Card from a Customer
     * @param string $customerToken
     * @param string $cardToken
     * @return string
     */
	public static function DeleteCard($customerToken, $cardToken)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        if (substr($customerToken, 0, 4) == "cus_")
        {
            $stripeCustomer = \Stripe\Customer::retrieve($customerToken);
            $card               = $stripeCustomer->sources->retrieve($cardToken);
            $arrayKey = "source";
        }
        elseif (substr($customerToken, 0, 5) == "acct_")
        {
            $stripeCustomer = \Stripe\Account::retrieve($customerToken);
            $card               = $stripeCustomer->external_accounts->retrieve($cardToken);
            $arrayKey = "external_account";
        }

		$result             = $card->delete();
		return $result->id;
	}

    /**
     * Transfer money to a Bank Account that has been saved to a Managed Account
     *
     * @param decimal $amount
     * @param string $bankAccountToken Returned from AddBankAccount
     * @param string $statementDescription
     * @param string $internalDescription
     * @return string
     */
	public static function CreditBankAccount($amount, $bankAccountToken, $statementDescription = null, $internalDescription = null)
	{
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $xferArray =
            array(
                "amount" => $amount,
                "currency" => "usd",
                "destination" => $bankAccountToken);
        if ($internalDescription)
        {
            $xferArray["description"] = $internalDescription;
        }
        if ($statementDescription)
        {
            $xferArray["statement_descriptor"] = $statementDescription;
        }
        $credit = \Stripe\Transfer::create($xferArray);
        return $credit;
	}

    /**
     * Charge a Card that has been saved to a Customer
     *
     * @param decimal $amount The amount to charge in cents
     * @param string $customerToken
     * @param string $cardToken
     * @param string $description
     * @param mixed $rawSentToApi
     * @param string $recipientAccountId
     * @param float $commission
     *
     * @return array
     */
	public static function ChargeSavedCard($amount, $customerToken, $cardToken, $description = null, &$rawSentToApi = null, $recipientAccountId = null, $commission = 0)
	{
        $chargeArray =
            array(
                "amount" => $amount,
                "currency" => "usd",
                "customer" => $customerToken,
                "source" => $cardToken);
        if ($description)
        {
            $chargeArray["description"] = $description;
        }
        if ($recipientAccountId)
        {
            $chargeArray["destination"] = $recipientAccountId;
            if ($commission)
            {
                $chargeArray["application_fee"] = $commission;
            }
        }
        $rawSentToApi = print_r($chargeArray, true);
        return \Stripe\Charge::create($chargeArray);
	}

    public function AuthorizePayment($payerFirstName, $payerLastName, $payerAddress, $payerCity, $payerState, $payerPostal, $payerCountry, $amount, $paymentMethod, $accountNumber, $expirationMonth = null, $expirationYear = null, $securityCode = null, $currencyType = null, $notes = null, $referenceCode = null, $testMode = false, $recipientData = null)
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe->AuthorizePayment");
    }

    public function CapturePayment($amount, $authorizationId, $notes = null, $referenceCode = null, $testMode = false)
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe->Capture");
    }

    protected static function getAvailableSettings()
    {
        $settings = parent::getAvailableSettings();
        $settings[] = "Api Key";
        return $settings;
    }
    public function ConfirmedConfigure($settingName, $settingValue)
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");

        switch ($settingName)
        {
            case "Api Key":
                \Stripe\Stripe::setApiKey($settingValue);
                break;
        }
    }

    public function GetFraudRiskCode($criteria = null)
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe->GetFraudRiskCode");
    }

    public function GetFraudRiskLevel($criteria = null)
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe->GetFraudRiskLevel");
    }

    public function Get_RefundTimeLimit()
    {
        return 60 * 86400;
    }

    /**
     * Process a payment
     * @param string $payerFirstName
     * @param string $payerLastName
     * @param string $payerAddress
     * @param string $payerCity
     * @param string $payerState
     * @param string $payerPostal
     * @param string $payerCountry
     * @param float $amount
     * @param string $paymentMethod
     * @param string $accountNumber
     * @param int $expirationMonth
     * @param int $expirationYear
     * @param string $securityCode
     * @param string $currencyType
     * @param string $notes
     * @param string $invoiceId
     * @param string $referenceCode
     * @param mixed $recipientData
     * @param array $paidForItems
     * @param float $shippingAmount
     * @param boolean $testMode
     * @param mixed $rawSentToApi
     * @param mixed $customData
     *
     * @return \DblEj\Integration\Ecommerce\PaymentProcessResult
     */
    public function ProcessPayment($payerFirstName, $payerLastName, $payerAddress, $payerCity, $payerState, $payerPostal, $payerCountry, $amount, $paymentMethod, $accountNumber, $expirationMonth = null, $expirationYear = null, $securityCode = null, $currencyType = null, $notes = null, $invoiceId = null, $referenceCode = null, $recipientData = null, $paidForItems = null, $shippingAmount = null, $testMode = false, &$rawSentToApi = null, $customData = [])
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        try
        {
            $stripeResult = self::ChargeCard($amount*100, $payerFirstName." ".$payerLastName, $accountNumber, $expirationMonth, $expirationYear, $securityCode, $payerAddress, $payerCity, $payerState, $payerPostal, null, "US", (isset($recipientData["Data"]) && isset($recipientData["Data"]["AccountToken"]))?$recipientData["Data"]["AccountToken"]:null, ((isset($recipientData["Settings"]) && isset($recipientData["Settings"]["Commission"]))?($recipientData["Settings"]["Commission"]*100):null));
            if ($stripeResult->status == "succeeded")
            {
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status);
            } else {
                switch ($stripeResult->failure_code)
                {
                    case "invalid_number";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "invalid_expiry_month";
                    case "invalid_expiry_year";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_EXPIRATION, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "invalid_cvc";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_SECURITY, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "incorrect_zip";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_ADDRESS, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "card_declined";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    default:
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                }
            }
        } catch (\Exception $ex) {
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, "", "", "", null, null, $ex->getMessage());
        }
        return $returnResult;
    }

    public function ProcessSavedCardPayment($cardKey, $amount, $description = "", $payerId = null, $currencyType = "USD", $invoiceId = null, $paidForItems = null, $shippingAmount = null, $recipientData = null, $testMode = false, $buyerEmail = null, $buyerPhone = null, &$rawSentToApi = null, $customData = [])
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        try
        {
            if (!is_array($customData))
            {
                $customData = [];
            }
            if (!isset($customData["CustomerToken"]))
            {
                throw new \Exception("You must include a CustomerToken element in the customData argument to process saved card payments");
            }

            $customerId = $customData["CustomerToken"];
            $stripeResult = self::ChargeSavedCard($amount*100, $customerId, $cardKey, $description, $rawSentToApi, ((isset($recipientData["Data"]) && isset($recipientData["Data"]["AccountToken"]))?$recipientData["Data"]["AccountToken"]:null), ((isset($recipientData["Settings"]) && isset($recipientData["Settings"]["Commission"]))?($recipientData["Settings"]["Commission"]*100):null));
            if ($stripeResult->status == "succeeded")
            {
                $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status);
            } else {
                switch ($stripeResult->failure_code)
                {
                    case "invalid_number";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "invalid_expiry_month";
                    case "invalid_expiry_year";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_EXPIRATION, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "invalid_cvc";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_SECURITY, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "incorrect_zip";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_ADDRESS, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    case "card_declined";
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                    default:
                        $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, \DblEj\Communication\JsonUtil::EncodeJson($stripeResult), $stripeResult->id, $stripeResult->status, null, null, $stripeResult->failure_message);
                        break;
                }
            }
        } catch(\Stripe\Error\Card $ex) {
          // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $ex->getJsonBody();
            switch ($body["error"]["code"])
            {
                case "invalid_number";
                case "invalid_cvc";
                case "invalid_zip";
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                    break;
                case "invalid_expiry_month";
                case "invalid_expiry_year";
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_EXPIRATION, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                    break;
                case "incorrect_cvc";
                case "incorrect_zip";
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_SECURITY, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                    break;
                case "card_declined";
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                    break;
                case "missing";
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "Payment account information is missing or invalid");
                    break;
                default:
                    $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, $body["error"]["message"]);
                    break;
            }
        } catch (\Stripe\Error\RateLimit $ex) {
          // Too many requests made to the API too quickly
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "The payment processor is busy. Please try again soon.");
        } catch (\Stripe\Error\InvalidRequest $ex) {
          // Invalid parameters were supplied to Stripe's API
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_INVALID, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "The card was rejected. Please verify all of the card information and try again.");
        } catch (\Stripe\Error\Authentication $ex) {
          // Authentication with Stripe's API failed (maybe you changed API keys recently)
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error authenticating with the payment processor");
        } catch (\Stripe\Error\ApiConnection $ex) {
          // Network communication with Stripe failed
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error communicating with the payment processor");
        } catch (\Stripe\Error\Base $ex) {
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was an error with the payment processor");
        } catch (\Exception $ex) {
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $ex->getMessage()." ".$ex->getTraceAsString(), "", "", null, null, "There was a system error while processing the payment");
        }
        return $returnResult;
    }

    public function RefundPayment($authorizationId, $amount, $partialRefund = false, $notes = null, $referenceCode = null, $testMode = false)
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        $originalPayment = \Stripe\Charge::retrieve($authorizationId);

        $refundArray =
        [
            "charge" => $authorizationId,
            "amount" => $amount*100
        ];
        if ($originalPayment->application_fee != null)
        {
            $refundArray["refund_application_fee"] = true;
        }
        if ($originalPayment->transfer != null)
        {
            $refundArray["reverse_transfer"] = true;
        }

        $refund = \Stripe\Refund::create($refundArray);
        return new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, print_r($refund, true), $refund->id, $refund->status);
    }

    public function RequiresPayerAuthorization($cardIsSaved = false)
    {
        return false;
    }

    public function SavePaymentCard($userId, $firstName, $lastName, $cardType, $cardNumber, $expMonth, $expYear, $cvv2 = null, $streetAddress = null, $city = null, $state = null, $zip = null, $country = null, $testMode = false, $payerEmailAddress = null, $customData = null, $institutionNumber = null)
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        try
        {
            $customerToken = null;

            if ($customData && is_array($customData))
            {
                $customerToken = isset($customData["CustomerToken"])?$customData["CustomerToken"]:null;
            }

            if (!$customerToken)
            {
                $customerToken = self::AddCustomer($payerEmailAddress, "Customer: $userId");
            }
            if ($customerToken)
            {

                if ($institutionNumber)
                {
                    $stripeResult = self::AddBankAccount($customerToken, $institutionNumber, $cardNumber, $country?$country:"US");
                    if ($stripeResult)
                    {
                        $stripeResult = $stripeResult->id;
                    }
                } else {
                    $stripeResult = self::AddCard($customerToken, $firstName." ".$lastName, $cardNumber, $expMonth, $expYear, $cvv2, $streetAddress, $city, $state, $zip, $payerEmailAddress, $country);
                }
                if ($stripeResult)
                {
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult($stripeResult, $stripeResult, null, null, ["CustomerToken"=>$customerToken]);
                } else {
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", "", "Invalid", "Couldn't add card, invalid", ["CustomerToken"=>$customerToken]);
                }
            } else {
                $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", "", "Invalid id", "Couldn't add card because the customer account is not valid");
            }
        } catch(\Stripe\Error\Card $ex) {
          // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $ex->getJsonBody();
            switch ($body["error"]["code"])
            {
                case "invalid_number";
                case "invalid_expiry_month";
                case "invalid_expiry_year";
                case "invalid_cvc";
                case "incorrect_cvc";
                case "incorrect_zip";
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", $body["error"]["message"]);
                    break;
                case "card_declined";
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", $body["error"]["message"]);
                default:
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", $body["error"]["message"]);
                    break;
            }
        } catch (\Stripe\Error\RateLimit $ex) {
          // Too many requests made to the API too quickly
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "Payout system is temporarily down. Please try again soon.");
        } catch (\Stripe\Error\InvalidRequest $ex) {
          // Invalid parameters were supplied to Stripe's API
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "Invalid information provided.  Please double-check the card information and try again.");
        } catch (\Stripe\Error\Authentication $ex) {
          // Authentication with Stripe's API failed
          // (maybe you changed API keys recently)
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "There was an error while saving the card.  Please contact Plazko.com support.");
        } catch (\Stripe\Error\ApiConnection $ex) {
          // Network communication with Stripe failed
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "There was an error while saving the card. This error might be temporary. Please try again.");
        } catch (\Stripe\Error\Base $ex) {
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "There was an error while saving the card.");
        } catch (\Exception $ex) {
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving card", "There was an error while saving the card.");
        }
        return $saveResult;
    }

    public function SupportsSavedCards()
    {
        return true;
    }

    public function VoidPayment($amount, $authorizationId, $notes = null, $referenceCode = null, $testMode = false)
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe::VoidPayment");
    }

    public function ProcessPayout($payeeFirstName, $payeeLastName, $payeeAddress, $payeeCity, $payeeState, $payeePostal, $payeeCountry, $amount, $paymentMethod, $accountNumber, $institutionNumber = null, $expirationMonth = null, $expirationYear = null, $securityCode = null, $currencyType = null, $notes = null, $invoiceId = null, $referenceCode = null, $senderData = null, $paidForItems = null, $shippingAmount = null, $testMode = false, &$rawSentToApi = null, $customData = array())
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe::ProcessPayout");
    }

    public function ProcessSavedAccountPayout($accountToken, $amount, $description = "", $payeeId = null, $currencyType = "USD", $invoiceId = null, $paidForItems = null, $shippingAmount = null, $recipientData = null, $testMode = false, $buyerEmail = null, $payeePhone = null, &$rawSentToApi = null, $customData = array())
    {
        throw new \DblEj\System\NotYetImplementedException("Stripe::ProcessSavedAccountPayout");
    }

    public function RequiresPayeeAuthorization($cardIsSaved = false)
    {
        return true;
    }

    public function SavePayoutAccount($userId, $firstName, $lastName, $accountType, $accountNumber, $businessName = null, $institutionNumber = null, $expMonth = null, $expYear = null, $cvv2 = null, $streetAddress = null, $city = null, $state = null, $zip = null, $country = null, $taxId = null, $dob = null, $payeeEmailAddress = null, $customData = array(), $testMode = false)
    {
        require_once("phar://".__DIR__.DIRECTORY_SEPARATOR."Stripe4.1.1.phar".DIRECTORY_SEPARATOR."Stripe.php");
        try
        {
            $accountToken = null;
            $lastTermsAgreeDate = null;
            $lastTermsAgreeIp = null;
            if ($customData && is_array($customData))
            {
                $accountToken = isset($customData["AccountToken"])?$customData["AccountToken"]:null;
                $lastTermsAgreeDate = isset($customData["LastTermsAgreeDate"])?$customData["LastTermsAgreeDate"]:null;
                $lastTermsAgreeIp = isset($customData["LastTermsAgreeIp"])?$customData["LastTermsAgreeIp"]:null;
                $ssnLastFour = isset($customData["SsnLastFour"])?$customData["SsnLastFour"]:null;
            }

            if (!$accountToken)
            {
                $accountToken = self::AddManagedAccount($payeeEmailAddress, $firstName." ".$lastName, $zip, $city, $state, $country, $streetAddress, $businessName, $dob, $lastTermsAgreeDate, $lastTermsAgreeIp, $taxId, $ssnLastFour);
            } else {
                $dobArray = $dob?["day"=>date("d", $dob), "month"=>date("m", $dob), "year"=>date("Y", $dob)]:null;
                $stripeAccount = \Stripe\Account::retrieve($accountToken);
                $stripeAccount->email = $payeeEmailAddress;
                $stripeAccount->legal_entity["first_name"] = $firstName;
                $stripeAccount->legal_entity["last_name"] = $lastName;
                if ($ssnLastFour)
                {
                    $stripeAccount->legal_entity["ssn_last_4"] = $ssnLastFour;
                }

                if ($businessName)
                {
                    $stripeAccount->legal_entity["business_name"] = $businessName;
                }
                if ($country && ($country != $stripeAccount->country))
                {
                    //throw new \Wafl\Exceptions\Exception("Payout account added with country different than the account country", E_ERROR, null, "Cannot add an account that is in a different country than the country you used to sign up your shop.");
                }
                if ($state)
                {
                    $stripeAccount->legal_entity["address"]["state"] = $state;
                }
                $stripeAccount->legal_entity["address"]["city"] = $city;
                if ($zip)
                {
                    $stripeAccount->legal_entity["address"]["postal_code"] = $zip;
                }
                if ($streetAddress)
                {
                    $stripeAccount->legal_entity["address"]["line1"] = $streetAddress;
                } else {
                    $stripeAccount->legal_entity["address"]["line1"] = "";
                }

                if ($taxId)
                {
                    $stripeAccount->legal_entity["business_tax_id"] = $taxId;
                }
                $stripeAccount->legal_entity["type"] = $businessName?"company":"individual";
                if ($dobArray)
                {
                    $stripeAccount->legal_entity["dob"] = $dobArray;
                }
                if ($lastTermsAgreeDate)
                {
                    $stripeAccount->tos_acceptance["date"] = $lastTermsAgreeDate;
                    $stripeAccount->tos_acceptance["ip"] = $lastTermsAgreeIp;
                }
                $saveResult = $stripeAccount->save();
            }
            if ($accountToken)
            {
                if ($accountType == "Bank Account")
                {
                    $stripeResult = self::AddBankAccount($accountToken, $institutionNumber, $accountNumber, $country?$country:"US");
                    if ($stripeResult)
                    {
                        $stripeResult = $stripeResult->id;
                    }
                } else {
                    $stripeResult = self::AddCard($accountToken, $firstName." ".$lastName, $accountNumber, $expMonth, $expYear, $cvv2, $streetAddress, $city, $state, $zip, $payeeEmailAddress, $country);
                }
                if ($stripeResult)
                {
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult($stripeResult, $stripeResult, null, null, ["AccountToken"=>$accountToken]);
                } else {
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", "", "Invalid", "Couldn't add account, invalid", ["AccountToken"=>$accountToken]);
                }
            } else {
                $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", "", "Invalid id", "Account is not valid");
            }
        } catch(\Stripe\Error\Card $ex) {
          // Since it's a decline, \Stripe\Error\Card will be caught
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Invalid or unauthorized account.");
        } catch (\Stripe\Error\RateLimit $ex) {
          // Too many requests made to the API too quickly
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Payout account system is temporarily down. Please try again soon.");
        } catch (\Stripe\Error\InvalidRequest $ex) {
          // Invalid parameters were supplied to Stripe's API
            if ($ex->getStripeParam() == "external_account")
            {
                $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", $ex->getMessage());
            }
            elseif (substr($ex->getStripeParam(), 0, 16) == "external_account")
            {
                if (substr($ex->getMessage(), 0, 7) == "Missing")
                {
                    $errParam = str_replace("external_account[", "", $ex->getStripeParam());
                    $errParam = str_replace("]", "", $errParam);
                    $errParam = str_replace("_", " ", $errParam);
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "$errParam is required");
                } else {
                    $errMsg = str_replace("external_account[", "", $ex->getMessage());
                    $errMsg = str_replace("]", "", $errMsg);
                    $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", $errMsg);
                }
            }
            else
            {
                $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Unknown error: ".$ex->getMessage());
            }
        } catch (\Stripe\Error\Authentication $ex) {
          // Authentication with Stripe's API failed
          // (maybe you changed API keys recently)
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Please contact Plazko.com support.");
        } catch (\Stripe\Error\ApiConnection $ex) {
          // Network communication with Stripe failed
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "This error might be temporary. Please try again.");

        } catch (\Stripe\Error\Base $ex) {
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Unknown error.".$ex->getMessage());
        } catch (\Exception $ex) {
            $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult("", $ex->getMessage()." ".$ex->getTraceAsString(), "Error saving account", "Unknown error.".$ex->getMessage());
        }

        return $saveResult;

    }

    public function SupportsSavedPayoutAccounts()
    {
        return true;
    }
//	public static function VerificationDeposit($bankAccountUri)
//	{
//		if (!$bankAccountUri)
//		{
//			throw new \Exception("Invalid bank account uri: $bankAccountUri");
//		}
//		Balanced\Bootstrap::init();
//		$marketplace = Balanced\Marketplace::mine();
//		$bankAccount = Balanced\BankAccount::get($bankAccountUri);
//		try
//		{
//			$verifyReturn = $bankAccount->verify();
//		}
//		catch (Balanced\Errors\Error $err)
//		{
//			if ($err->status_code == 409)
//			{
//				//the deposit has already been made, retrieve the verification
//				$verifyReturn = $bankAccount->getVerification();
//			}
//		}
//
//		return $verifyReturn;
//	}
//
//	public static function VerifyBankAccount($bankAccountUri, $verificationUri, $amount1, $amount2)
//	{
//		Balanced\Bootstrap::init();
//		$verification = Balanced\BankAccountVerification::get($verificationUri);
//		if ($verification->state != "verified")
//		{
//			try
//			{
//				$verification->confirm($amount1 * 100, $amount2 * 100);
//			}
//			catch (Balanced\Errors\Error $err)
//			{
//				throw new ECommerceException("The amounts you entered do not match the amounts that were deposited into your account.&nbsp;&nbsp;Please check your bank account deposit history and try again.&nbsp;&nbsp;Attempts remaining: " . $verification->remaining_attempts);
//				if ($verification->remaining_attempts == 0)
//				{
//					throw new ECommerceException("We're sorry but you have run out of chances to verify your account");
//				}
//			}
//		}
//		return $verification;
//	}
}