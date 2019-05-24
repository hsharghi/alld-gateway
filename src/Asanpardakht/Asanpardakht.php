<?php

namespace Larabookir\Gateway\Asanpardakht;

use Illuminate\Support\Facades\Input;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Asanpardakht extends PortAbstract implements PortInterface
{
    protected $gatewayConfig = 'asanpardakht1';

    public function __construct($port = 1)
    {
        parent::__construct();

        $this->serverUrl = 'https://services.asanpardakht.net/paygate/merchantservices.asmx?wsdl';

        if ($port == 2) {
            $this->gatewayConfig = 'asanpardakht2';
        }

    }

    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl;

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        $this->sendPayRequest();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        \Log::info('refId: ' . $this->refId);
        return view('gateway::asan-pardakht-redirector')->with([
            'refId' => $this->refId
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->userPayment();
        $this->verifyAndSettlePayment();
        return $this;
    }

    /**
     * Sets callback url
     * @param $url
     */
    function setCallback($url)
    {
        $this->callbackUrl = $url;
        return $this;
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.'.$this->gatewayConfig.'.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws AsanpardakhtException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        $username = $this->config->get('gateway.'.$this->gatewayConfig.'.username');
        $password = $this->config->get('gateway.'.$this->gatewayConfig.'.password');
        $orderId = $this->transactionId();
        $price = $this->amount;
        $localDate = date("Ymd His");
        $additionalData = "";
        $callBackUrl = $this->getCallback();
        $req = "1,{$username},{$password},{$orderId},{$price},{$localDate},{$additionalData},{$callBackUrl},0";

        $encryptedRequest = $this->encrypt($req);
        $params = array(
            'merchantConfigurationID' => $this->config->get('gateway.'.$this->gatewayConfig.'.merchantConfigId'),
            'encryptedRequest' => $encryptedRequest
        );

        try {

            $soap = $this->getSoapClient($this->serverUrl);

            $response = $soap->RequestOperation($params);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }


        $response = $response->RequestOperationResult;
        $responseCode = explode(",", $response)[0];
        if ($responseCode != '0') {
            $this->transactionFailed();
            $this->newLog($response, AsanpardakhtException::getMessageByCode($response));
            throw new AsanpardakhtException($response);
        }
        $this->refId = substr($response, 2);
        $this->transactionSetRefId();
    }


    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws AsanpardakhtException
     */
    protected function userPayment()
    {
        $ReturningParams = Input::get('ReturningParams');
        $ReturningParams = $this->decrypt($ReturningParams);

        $paramsArray = explode(",", $ReturningParams);
        $Amount = $paramsArray[0];
        $SaleOrderId = $paramsArray[1];
        $RefId = $paramsArray[2];
        $ResCode = $paramsArray[3];
        $ResMessage = $paramsArray[4];
        $PayGateTranID = $paramsArray[5];
        $RRN = $paramsArray[6];
        $LastFourDigitOfPAN = $paramsArray[7];


        $this->trackingCode = $PayGateTranID;
        $this->cardNumber = $LastFourDigitOfPAN;
        $this->refId = $RefId;


        if ($ResCode == '0' || $ResCode == '00') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($ResCode, $ResMessage . " - " . AsanpardakhtException::getMessageByCode($ResCode));
        throw new AsanpardakhtException($ResCode);
    }


    /**
     * Verify and settle user payment from bank server
     *
     * @return bool
     *
     * @throws AsanpardakhtException
     * @throws SoapFault
     */
    protected function verifyAndSettlePayment()
    {

        $username = $this->config->get('gateway.'.$this->gatewayConfig.'.username');
        $password = $this->config->get('gateway.'.$this->gatewayConfig.'.password');

        $encryptedCredintials = $this->encrypt("{$username},{$password}");
        $params = array(
            'merchantConfigurationID' => $this->config->get('gateway.'.$this->gatewayConfig.'.merchantConfigId'),
            'encryptedCredentials' => $encryptedCredintials,
            'payGateTranID' => $this->trackingCode
        );


        try {
            $soap = $this->getSoapClient($this->serverUrl);
            $response = $soap->RequestVerification($params);
            $response = $response->RequestVerificationResult;

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if ($response != '500') {
            $this->transactionFailed();
            $this->newLog($response, AsanpardakhtException::getMessageByCode($response));
            throw new AsanpardakhtException($response);
        }


        try {

            $response = $soap->RequestReconciliation($params);
            $response = $response->RequestReconciliationResult;

            if ($response != '600')
                $this->newLog($response, AsanpardakhtException::getMessageByCode($response));

        } catch (\SoapFault $e) {
            //If fail, shaparak automatically do it in next 12 houres.
        }


        $this->transactionSucceed();

        return true;
    }



    /**
     * Encrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function encrypt($string = "")
    {

        $key = $this->config->get('gateway.'.$this->gatewayConfig.'.key');
        $iv = $this->config->get('gateway.'.$this->gatewayConfig.'.iv');

        try {

            $soap = $this->getSoapClient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL");
            $params = array(
                'aesKey' => $key,
                'aesVector' => $iv,
                'toBeEncrypted' => $string
            );

            $response = $soap->EncryptInAES($params);
            return $response->EncryptInAESResult;

        } catch (\SoapFault $e) {
            return "";
        }
    }


    /**
     * Decrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function decrypt($string = "")
    {
        $key = $this->config->get('gateway.'.$this->gatewayConfig.'.key');
        $iv = $this->config->get('gateway.'.$this->gatewayConfig.'.iv');

        try {

            $soap = $this->getSoapClient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL");
            $params = array(
                'aesKey' => $key,
                'aesVector' => $iv,
                'toBeDecrypted' => $string
            );

            $response = $soap->DecryptInAES($params);
            return $response->DecryptInAESResult;

        } catch (\SoapFault $e) {
            return "";
        }
    }

    /**
     * @param $url
     * @return SoapClient
     * @throws \SoapFault
     */
    private function getSoapClient($url) {
        try {
            // change ip for gateway if available in config
            if ($ip = $this->config->get('gateway.' . $this->gatewayConfig . '.gatewayIP')) {
                $options = array('socket' => array('bindto' => $ip));
                $context = stream_context_create($options);
                return new SoapClient($url, array('stream_context' => $context));
            } else {    // use default server's ip address
                return new SoapClient($url);
            }
        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

    }

}