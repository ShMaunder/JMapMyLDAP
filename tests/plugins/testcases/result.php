<?php

defined('JPATH_PLATFORM') or die;

abstract class TPluginsTestcasesResult
{
	protected $msg = array();
	
	protected $status = null;
	
	const STATUS_UNSET = '-1';
	
	const STATUS_SUCCESS = '0';
	
	const STATUS_FAILED = '1';
	
	public function setStatus($status)
	{
		if (is_numeric($status)) {
			$this->status = (int)$status;
		}
	}
	
	public function getStatus()
	{
		return $this->status;
	}
	
	public function addMessage($msg)
	{
		$this->msg[] = $msg;
	}
	
	public function getMessages()
	{
		return $this->msg;
	}
	
	public function getNextMessage()
	{
		if ($count = count($this->msg)) {
			$msg = $this->msg[$count-1];
			unset($this->msg[$count-1]);
			return $msg;
		}
	}
	
	public function getLastMessage()
	{
		if ($count = count($this->msg)) {
			return $this->msg[$count-1];
		}
	}
	
	public function __toString()
	{
		return (string)$this->getStatus();
	}
	
}