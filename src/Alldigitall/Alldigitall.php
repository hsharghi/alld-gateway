<?php

namespace Larabookir\Gateway\Alldigitall;

use Illuminate\Support\Facades\Input;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;
use NumberFormatter;

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
		$success_url = $this->gateUrl . $this->refId() . '&transaction_id=' . $this->transactionId();
		$cancel_url = $this->gateUrl . $this->refId() . '&transaction_id=' . $this->transactionId() . '&cancel=true';
		$order_id = $this->refId();
		$price = number_format($this->getPrice(), 0, '.', ',') . ' ریال';

		return \View::make('gateway::alldigitall-redirector')->with(compact('success_url', 'cancel_url', 'order_id', 'price'));
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
		$params = array(
			'pin' => 0,
			'authority' => '0',
			'status' => 1
		);

		$this->trackingCode = '0000';
		if (request()->has('cancel')) {
			$this->transactionFailed();
			$this->newLog(104, 'پرداخت توسط کاربر لغو شده است');
		} else {
			$this->cardNumber = '0000-0000-0000-0000';
			$this->transactionSucceed();
			$this->newLog(0, 'پرداخت با موفقیت');
		}
	}
}
