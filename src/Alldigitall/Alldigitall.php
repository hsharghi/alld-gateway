<?php

namespace Larabookir\Gateway\Alldigitall;

use Illuminate\Support\Facades\Input;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Alldigitall extends PortAbstract implements PortInterface
{
	/**
	 * Url of Alldigitall gateway web service
	 *
	 * @var string
	 */
	protected $serverUrl = '';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'callback?refId=';


	/**
	 * RefId of transaction
	 *
	 * @var int
	 */
	protected $refId;


	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = intval($amount);
		return $this;
	}

	/**
	 * Set RefId 
	 */
	public function setRefId($refId) 
	{
		$this->refId = $refId;
		return $this->ready();
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
		$url = $this->gateUrl . $this->refId();

		return \View::make('gateway::alldigitall-redirector')->with(compact('url'));
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->verifyPayment();

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
			$this->callbackUrl = $this->config->get('gateway.alldigitall.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to parsian gateway
	 *
	 * @return bool
	 *
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$this->transactionSetRefId();
		return true;
	}

	/**
	 * Verify payment
	 *
	 * @throws AlldigitallErrorException
	 */
	protected function verifyPayment()
	{
		if (!Input::has('au') && !Input::has('rs'))
			throw new AlldigitallErrorException('درخواست غیر معتبر', -1);

		$authority = Input::get('au');
		$status = Input::get('rs');

		if ($status != 0) {
			$errorMessage = AlldigitallResult::errorMessage($status);
			$this->newLog($status, $errorMessage);
			throw new AlldigitallErrorException($errorMessage, $status);
		}

		if ($this->refId != $authority)
			throw new AlldigitallErrorException('تراکنشی یافت نشد', -1);

		$params = array(
			'pin' => 0,
			'authority' => $authority,
			'status' => 1
		);

		$this->trackingCode = $authority;
		$this->transactionSucceed();
		$this->newLog(0, AlldigitallResult::errorMessage(0));
	}
}
