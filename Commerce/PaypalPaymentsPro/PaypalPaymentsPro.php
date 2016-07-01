<?php
namespace Wafl\Extensions\Commerce\PaypalPaymentsPro;

/**
 * Paypal Payments Pro Gateway
 */
class PaypalPaymentsPro
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Commerce\Integration\IPaymentReceiverExtension
{
	private static $_returnUrl;
	private static $_cancelUrl;

    private $_recipientEmailAddress          = null;

	public static function Set_CancelUrl($url)
	{
		self::$_cancelUrl = $url;
	}

    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }

    public static function Get_Dependencies()
    {
        return \DblEj\Extension\DependencyCollection::Get_EmptyCollection();
    }


	public static function Set_ReturnUrl($url)
	{
		self::$_returnUrl = $url;
	}

	/**
	 *
	 * @param string $accountUser
	 * @param string $accountPassword
	 * @param string $accountKey
	 * @param string $payerFirstName
	 * @param string $payerLastName
	 * @param decimal $amount
	 * @param string $paymentMethod
	 * @param string $accountNumber
	 * @param int $expirationMonth
	 * @param int $expirationYear
	 * @param string $securityCode
	 * @param string $currencyType
	 * @param string $notes
	 * @param string $referenceCode
	 * @return \DblEj\Ecommerce\PaymentResult
	 */
    public function ProcessPayment($payerFirstName, $payerLastName, $payerAddress, $payerCity, $payerState, $payerPostal, $payerCountryCode, $amount, $paymentMethod, $accountNumber, $expirationMonth = null, $expirationYear = null, $securityCode = null, $currencyType = null, $notes = null, $invoiceId = null, $referenceCode = null, $recipientData = null, $paidForItems = null, $shippingAmount = null, $testMode = false, &$rawSentToApi = null, $customData = [])
	{
		$response = null;
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}

		$paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::ProcessWebProPayment("Sale", $payerFirstName, $payerLastName, $paymentMethod,
																	$accountNumber, $expirationMonth, $expirationYear, $securityCode, $payerAddress, $payerCity, $payerState,
																	$payerPostal, $payerCountryCode, round($amount, 2), $invoiceId, $currencyType, $this->_recipientEmailAddress?$this->_recipientEmailAddress:$recipientEmailOrId, $shippingAmount, $paidForItems, $rawSentToApi);

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		return $response;
	}

	/**
	 * @param string $accountUser
	 * @param string $accountPassword
	 * @param string $accountKey
	 * @param string $payerFirstName
	 * @param string $payerLastName
	 * @param decimal $amount
	 * @param string $paymentMethod
	 * @param string $accountNumber
	 * @param int $expirationMonth
	 * @param int $expirationYear
	 * @param string $securityCode
	 * @param string $currencyType
	 * @param string $notes
	 * @param string $referenceCode
	 * @return \DblEj\Ecommerce\PaymentResult
	 */
	function AuthorizePayment($payerFirstName, $payerLastName, $payerAddress,
	$payerCity, $payerState, $payerPostal, $payerCountry, $amount, $paymentMethod, $accountNumber, $expirationMonth = null,
	$expirationYear = null, $securityCode = null, $currencyType = "USD", $notes = null, $referenceCode = null, $testMode = false, $recipientEmailOrId = null)
	{
		$response = null;
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
        $paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::ProcessWebProPayment("Authorization", $payerFirstName, $payerLastName,
                                                                 $paymentMethod, $accountNumber, $expirationMonth, $expirationYear, $securityCode, $payerAddress, $payerCity,
                                                                 $payerState, $payerPostal, $payerCountry->Get_AbbreviatedTitle(), round($amount, 2), $currencyType, $this->_recipientEmailAddress?$this->_recipientEmailAddress:$recipientEmailOrId, $paidForItems);

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()), $paypalResponse->Get_Result());
		}
		return $response;
	}

	function CapturePayment($amount, $authorizationId, $notes = null,
	$referenceCode = null, $testMode = false)
	{
		$response = null;
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
		$paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::CapturePreauthorizesWebProPayment($authorizationId, round($amount, 2));

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		return $response;
	}

	function VoidPayment($amount, $authorizationId, $notes = null,
	$referenceCode = null, $testMode = false)
	{
		$response = null;
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
		$paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::VoidPreauthorizesWebProPayment($authorizationId, round($amount, 2));

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_CANCELLED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result());
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result());
		}
		return $response;
	}

	function RefundPayment($authorizationId, $amount, $partialRefund = false,
	$notes = null, $referenceCode = null, $testMode = false)
	{
		$response = null;
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
		$paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::RefundTransaction($authorizationId, round($amount, 2), $partialRefund);

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result());
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result());
		}
		return $response;
	}

	public function GetFraudRiskLevel($cvv2MatchCode = null)
	{
		$level = \DblEj\Integration\Ecommerce\PaymentProcessResult::RISK_NONE;

		switch ($cvv2MatchCode)
		{
			// Address matched with zip-code, do not flag
			case 'D': // International "X" Address and Postal Code
			case 'M': // Address Address and Postal Code
			case 'F': // UK-specific "X" Address and Postal Code
			case 'X': // Exact match Address and nine-digit ZIP code
			case 'Y': // Yes Address and five-digit ZIP
			case 'I': // International Unavailable //moved this to risk_none because every single transaction returning with it
				break;

			// Address only, no zip-code
			case 'A': // Address Address only (no ZIP code)
			case 'B': // International "A" Address only (no ZIP code)
			// Zip code only, no address
			case 'P': // Postal (International "Z") Postal Code only (no Address)
			case 'Z': // ZIP Five-digit ZIP code (no Address)
			case 'W': // Whole ZIP Nine-digit ZIP code (no Address)
			default: // anything else - flag
				$level = \DblEj\Integration\Ecommerce\PaymentProcessResult::RISK_LOW;
				break;

			// other codes
			case 'C': // International "N" The transaction is declined.
			case 'E': // Not allowed for MOTO (Internet/Phone) transactions
			case 'G': // Global Unavailable
			case 'N': // No (The transaction is declined.)
			case 'R': // Retry
			case 'S': // Service not Supported
			case 'U': // Unavailable
				$level = \DblEj\Integration\Ecommerce\PaymentProcessResult::RISK_MEDIUM;
				break;
		}

		return $level;
	}

	public function GetFraudRiskCode($cvv2MatchCode = null)
	{
		$code = null;

		if (!is_null($cvv2MatchCode))
		{
			switch ($cvv2MatchCode)
			{
				// Address matched with zip-code, do not flag
				case 'D': $code	 = 'MATCH: D - International "X" Address and Postal Code';
					break;
				case 'M': $code	 = 'MATCH: M - Address Address and Postal Code';
					break;
				case 'F': $code	 = 'MATCH: F - UK-specific "X" Address and Postal Code';
					break;
				case 'X': $code	 = 'MATCH: X - Exact match Address and nine-digit ZIP code';
					break;
				case 'Y': $code	 = 'MATCH: Y - Yes Address and five-digit ZIP';
					break;

				// Address only, no zip-code
				case 'A': $code	 = 'NO-MATCH: A - Address matches, zip code doesn\'t';
					break;
				case 'B': $code	 = 'NO-MATCH: B - Cannot verify international postal code';
					break;

				// Zip code only, no address
				case 'P': $code	 = 'P - Postal code matches; cannot verify address';
					break;
				case 'Z': $code	 = 'Z - Zip code matches, address does not)';
					break;
				case 'W': $code	 = 'W - Address does not match but the full zip code does';
					break;


				// other codes
				case 'C': $code	 = 'C - International "N" The transaction is declined.';
					break;
				case 'E': $code	 = 'E - Not allowed for MOTO (Internet/Phone) transactions';
					break;
				case 'G': $code	 = 'G - Non-US. Issuer does not participate';
					break;
				case 'N': $code	 = 'N - No (The transaction is declined.)';
					break;
				case 'R': $code	 = 'R - Retry';
					break;
				case 'S': $code	 = 'S - Service not Supported';
					break;
				case 'U': $code	 = 'U - Unavailable';
					break;
				case 'I': $code	 = 'I - Address Information not verified by International issuer';
					break;


				default: // anything else - flag
					$code = 'NO-MATCH: ' . $cvv2MatchCode . ' - Check paypal documentation for more details';
					break;
			}
		}

		return $code;
	}

    public function SavePaymentCard($userId, $firstName, $lastName, $cardType, $cardNumber, $expMonth, $expYear, $cvv2 = null, $streetAddress = null, $city = null, $state = null, $zip = null, $country = null, $testMode = false, $payerEmailAddress = null, $customData = null, $institutionNumber = null)
    {
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}

        $rawSaveResult = \Wafl\SharedSdks\Paypal\Paypal::SaveCardInVault($userId, $firstName, $lastName, $cardType, $cardNumber, $expMonth, $expYear, $cvv2, $streetAddress, $city, $state, $zip, $country);

        if (isset($rawSaveResult["id"]) && $rawSaveResult["id"] != "")
        {
            $cardId = $rawSaveResult["id"];
            $errorName = null;
            $errorDetails = null;
        } else {
            $cardId = null;
            $errorName = $rawSaveResult["name"];
            $errorDetails = "";
            $errorFields = $rawSaveResult["details"];
            foreach ($errorFields as $errorField)
            {
                if ($errorField["field"] == "number")
                {
                    $errorDetails .= "The card number is not valid";
                } else {
                    $errorDetails .= $errorField["field"]." - ".$errorField["issue"];
                }
            }
        }
        $saveResult = new \DblEj\Integration\Ecommerce\SaveCardResult($cardId, $rawSaveResult, $errorName, $errorDetails);
        return $saveResult;
    }
    public function ProcessSavedCardPayment($cardKey, $amount, $description = "", $payerId = null, $currencyType = "USD", $invoiceId = null, $paidForItems = null, $shippingAmount = null, $recipientData = null, $testMode = false, $buyerEmail = null, $buyerPhone = null, &$rawSentToApi = null, $customData = [])    {
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
        $rawResult = \Wafl\SharedSdks\Paypal\Paypal::ProcessVaultPayment($cardKey, $amount, $description, $payerId, $this->_recipientEmailAddress, $invoiceId, $currencyType, $paidForItems, $shippingAmount, $buyerEmail, $buyerPhone, $rawSentToApi);
        if (isset($rawResult["state"]) && $rawResult["state"] == "approved")
        {
            $transaction = $rawResult["transactions"][0];
            $sale = $transaction["related_resources"][0]["sale"];
            $refId1 = $sale["id"]; //sale transaction id
            $refId2 = $rawResult["id"]; // payment id
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResult, $refId1, $refId2);
        } elseif (isset($rawResult["name"]) && $rawResult["name"]=="VALIDATION_ERROR") {
            $details = $rawResult["details"];
            $detailString = "";
            foreach ($details as $detail)
            {
                $detailString .= $detail["field"].": ".$detail["issue"]."\n";
            }
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResult, $rawResult["name"], "", null, null, $detailString);
        }
        elseif (isset($rawResult["name"]) && isset($rawResult["message"]))
        {
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResult, $rawResult["name"], "", null, null, $rawResult["message"]);
        } else {
            $returnResult = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResult, $rawResult["name"], "", null, null, "Unknown Error");
        }
        return $returnResult;
    }

    protected static function getAvailableSettings()
    {
        $settings = parent::getAvailableSettings();
        $settings[] = "Username";
        $settings[] = "Password";
        $settings[] = "API Signature";
        $settings[] = "App Client ID";
        $settings[] = "App Secret";
        $settings[] = "App Name";
        $settings[] = "Paypal Email Address";
        return $settings;
    }
    public function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            case "Username":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Username($settingValue);
                break;
            case "Password":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Password($settingValue);
                break;
            case "API Signature":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Signature($settingValue);
                break;
            case "App Client ID":
               \Wafl\SharedSdks\Paypal\Paypal::Set_ClientAppId($settingValue);
                break;
            case "App Secret":
               \Wafl\SharedSdks\Paypal\Paypal::Set_ClientAppSecret($settingValue);
                break;
            case "App Name":
               \Wafl\SharedSdks\Paypal\Paypal::Set_ApplicationId($settingValue);
               break;
            case "Paypal Email Address":
                $this->_recipientEmailAddress = $settingValue;
               break;
        }
    }

    public function SupportsSavedCards()
    {
        return true;
    }

    public function Get_RefundTimeLimit()
    {
        return 60 * 86400;
    }

    public function RequiresPayerAuthorization($cardIsSaved = false)
    {
        return false;
    }
}
?>