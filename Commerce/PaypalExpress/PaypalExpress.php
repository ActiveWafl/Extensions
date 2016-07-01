<?php
namespace Wafl\Extensions\Commerce\PaypalExpress;

/**
 * Paypal Express Gateway
 */
class PaypalExpress
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Commerce\Integration\IPaymentGatewayExtension
{
	private static $_returnUrl;
	private static $_cancelUrl;
    private $_recipientEmailAddress          = null;

    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }
	public static function Set_CancelUrl($url)
	{
		self::$_cancelUrl = $url;
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
	 * @return \DblEj\Integration\Ecommerce\PaymentProcessResult
	 */
	function ProcessPayment($payerFirstName, $payerLastName, $payerAddress, $payerCity,
	$payerState, $payerPostal, $payerCountryCode, $amount, $paymentMethod, $paypalEmailAddress, $expirationMonth = null,
	$expirationYear = null, $paypalAuthToken = null, $currencyType = "USD", $notes = null, $invoiceId = null, $paypalPayerId = null, $recipientData = null, $paidForItems = null, $shippingAmount = null, $testMode = false, &$rawSentToApi = null, $customData = [])
	{
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}
        if (is_array($recipientData) && isset($recipientData["Settings"]) && isset($recipientData["Settings"]["Paypal Chained"]) && ($this->_recipientEmailAddress != $recipientData["Settings"]["Secondary Recipient Account Number"]))
        {
            $recipientData["Settings"]["Primary Paypal Email Address"] = $this->_recipientEmailAddress;
            $isChained = true;
        } else {
            $isChained = false;
        }

        if ($paypalAuthToken && $paypalPayerId)
        {
            if ($isChained)
            {
                $paymentResult = \Wafl\SharedSdks\Paypal\Paypal::ProcessChainedPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, $paypalAuthToken, $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
            } else {
                $paymentResult = \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, $paypalAuthToken, $paypalPayerId, $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
            }
            $statusExplain = "";
            switch ($paymentResult->Get_Status())
            {
                case "Completed":
                case "Processed":
                case "Completed-Funds-Held":
                    $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED;
                    $statusExplain = $paymentResult->Get_Status();
                    break;
                case "Pending":
                    switch ($paymentResult->Get_PendingReason())
                    {
                        case "address":
                            $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_ADDRESS_VERIFICATION;
                            break;
                        case "authorization":
                        case "order":
                            $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_NOTSETTLED;
                            break;
                        case "echeck":
                            $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_BANKPROCESSING;
                            break;
                        case "payment-review":
                            $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_RISK_ASSESSMENT;
                            break;
                        default:
                            $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_OTHER;
                            break;
                    }
                    $statusExplain = $paymentResult->Get_PendingReason();
                    break;
                case "Failed":
                case "Denied":
                    $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_REJECTED;
                    break;
                case "Voided":
                case "Reversed":
                    $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_CANCELLED;
                    $statusExplain = $paymentResult->Get_Status();
                    break;
                default:
                    $returnStatus = \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_UNKNOWN;
                    $statusExplain = $paymentResult->Get_Status();
                    break;
            }
            return new \DblEj\Integration\Ecommerce\PaymentProcessResult($returnStatus, $paymentResult->Get_Result() == "Success", $paymentResult->Get_RawResponse(), $paymentResult->Get_TransactionId(), $paymentResult->Get_CorrelationId(), null, $paymentResult->Get_Cvv2Match(), $statusExplain);
        } else {
            if (!$paypalAuthToken)
            {
                if ($isChained)
                {
                    $paypalAuthToken = \Wafl\SharedSdks\Paypal\Paypal::ProcessChainedPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, null, $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
                } else {
                    $paypalAuthToken = \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, null, "", $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
                }
            }

            if ($paypalAuthToken)
            {
                if ($isChained)
                {
                    \Wafl\SharedSdks\Paypal\Paypal::ProcessChainedPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, $paypalAuthToken, $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
                } else {
                    \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Sale", $paypalEmailAddress, round($amount, 2), self::$_returnUrl, self::$_cancelUrl, $paypalAuthToken, "", $currencyType, $recipientData, $paidForItems, $shippingAmount, $invoiceId, $rawSentToApi);
                }
            } else {
                throw new \Exception("Could not get authorization from payment processor.");
            }
            return new \DblEj\Integration\Ecommerce\PaymentProcessResult( \DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_PENDING_OTHER, true, "", $invoiceId, "ok", null, null, null, [], $paypalAuthToken);
        }
	}

	function GetExpressPaymentDetails($token, $testMode = false)
	{
		if ($testMode)
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("sandbox");
		}
		else
		{
			\Wafl\SharedSdks\Paypal\Paypal::Set_Environment("live");
		}

		return \Wafl\SharedSdks\Paypal\Paypal::GetExpressPaymentDetails($token);
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
	$expirationYear = null, $securityCode = null, $currencyType = "USD", $notes = null, $referenceCode = null, $testMode = false, $recipientData = null)
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
        $token				 = \Wafl\RequestVar("token");
        $payerid			 = \Wafl\RequestVar("PayerID");
        $paypalAuthResponse	 = \Wafl\RequestVar("PayPalResponse");
        if ($paypalAuthResponse)
        {
            $paypalResponse = \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Authorization", $payerid, round($amount, 2),
                                                                                                    self::$_returnUrl, self::$_cancelUrl, $token, $paypalAuthResponse, $currencyType, $recipientData);
        }
        else
        {
            if ($token)
            {
                //make sure the stored token is still valid
                $payment = \Wafl\SharedSdks\Paypal\Paypal::GetExpressPaymentDetails($token);
                if (isset($payment["L_ERRORCODE0"]) && $payment["L_ERRORCODE0"] > 0)
                {
                    $token = null;
                }
            }
            if (!$token)
            {
                $token = \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Authorization", $accountNumber, round($amount, 2),
                                                                                                  self::$_returnUrl, self::$_cancelUrl, null, "", $currencyType, $recipientData);
            }

            \Wafl\SharedSdks\Paypal\Paypal::ProcessExpressPayment("Authorization", $accountNumber, round($amount, 2), self::$_returnUrl,
                                                                                        self::$_cancelUrl, $token, "", $currencyType, $recipientData);
        }

		$rawResponseString = var_export($paypalResponse, true);
		if ($paypalResponse->Get_Result() == "Success" || $paypalResponse->Get_Result() == "SuccessWithWarning")
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(self::$STATUS_APPROVED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(self::$STATUS_DECLINED, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
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
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(self::$STATUS_APPROVED, true, $rawResponseString, $paypalResponse->Get_TransactionId(),
										$paypalResponse->Get_Result(), $this->GetFraudRiskLevel($paypalResponse->Get_Cvv2Match()),
																  $this->GetFraudRiskCode($paypalResponse->Get_Cvv2Match()));
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(self::$STATUS_DECLINED, false, $rawResponseString, $paypalResponse->Get_TransactionId(),
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
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_CLEARED, true, $rawResponseString, $paypalResponse->Get_TransactionId(), $paypalResponse->Get_Result());
		}
		else
		{
			$response = new \DblEj\Integration\Ecommerce\PaymentProcessResult(\DblEj\Integration\Ecommerce\PaymentProcessResult::PAYMENTSTATUS_FAILED_OTHER, false, $rawResponseString, $paypalResponse->Get_TransactionId(), $paypalResponse->Get_Result());
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
        throw new \Exception("You cannot save payment cards with paypal express");
    }

    protected static function getAvailableSettings()
    {
        $settings = parent::getAvailableSettings();
        $settings[] = "Paypal Email Address";
        $settings[] = "API Username";
        $settings[] = "API Password";
        $settings[] = "App Name";
        $settings[] = "API Signature";
        return $settings;
    }
    public function ConfirmedConfigure($settingName, $settingValue)
    {
        switch ($settingName)
        {
            case "Paypal Email Address":
                $this->_recipientEmailAddress = $settingValue;
                break;
            case "API Username":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Username($settingValue);
                break;
            case "API Password":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Password($settingValue);
                break;
            case "App Name":
               \Wafl\SharedSdks\Paypal\Paypal::Set_ApplicationId($settingValue);
               break;
            case "API Signature":
               \Wafl\SharedSdks\Paypal\Paypal::Set_Signature($settingValue);
                break;
            default:
        }
    }

    public function ProcessSavedCardPayment($cardKey, $amount, $description = "", $payerId = null, $currencyType = null, $invoiceId = null, $paidForItems = null, $shippingAmount = null, $recipientData = null, $testMode = false, $buyerEmail = null, $buyerPhone = null, &$rawSentToApi = null, $customData = [])
    {
        throw new Exception("Paypal express does not support vault payments");
    }

    public function SupportsSavedCards()
    {
        return false;
    }

    public function Get_RefundTimeLimit()
    {
        return 60 * 86400;
    }

    public function RequiresPayerAuthorization($cardIsSaved = false)
    {
        return true;
    }

    public function ProcessPayout($payeeFirstName, $payeeLastName, $payeeAddress, $payeeCity, $payeeState, $payeePostal, $payeeCountry, $amount, $paymentMethod, $accountNumber, $institutionNumber = null, $expirationMonth = null, $expirationYear = null, $securityCode = null, $currencyType = null, $notes = null, $invoiceId = null, $referenceCode = null, $senderData = null, $paidForItems = null, $shippingAmount = null, $testMode = false, &$rawSentToApi = null, $customData = array())
    {
        throw new \DblEj\System\NotYetImplementedException("PaypalExpress:ProcessPayout");
    }

    public function ProcessSavedAccountPayout($accountToken, $amount, $description = "", $payerId = null, $currencyType = "USD", $invoiceId = null, $paidForItems = null, $shippingAmount = null, $recipientData = null, $testMode = false, $buyerEmail = null, $buyerPhone = null, &$rawSentToApi = null, $customData = array())
    {
        throw new \DblEj\System\NotYetImplementedException("PaypalExpress:ProcessSavedAccountPayout");
    }

    public function RequiresPayeeAuthorization($cardIsSaved = false)
    {
        return true;
    }

    public function SavePayoutAccount($userId, $firstName, $lastName, $accountType, $accountNumber, $businessName = null, $institutionNumber = null, $expMonth = null, $expYear = null, $cvv2 = null, $streetAddress = null, $city = null, $state = null, $zip = null, $country = null, $taxId = null, $dob = null, $payeeEmailAddress = null, $customData = array(), $testMode = false)
    {
        return new \DblEj\Integration\Ecommerce\SaveCardResult($accountNumber, "Save account");
    }

    public function SupportsSavedPayoutAccounts()
    {
        return true;
    }
}
?>