<?php
/**
 * Oempro Subscribe Add-on
 *
 * @package    Oempro_Subscribe
 * @author     Octeth <support@octeth.com>
 * @copyright  Copyright (c) Octeth 1999-2013
 * @license    http://www.octeth.com
 * @version    $Id$
 * @link       http://octeth.com/
 */

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

define('OEMPRO_TARGET_SUBSCRIBER_LIST_ID', 1);

function oempro_subscribe_config()
{
	$configarray = array(
		"name" => "Oempro Subscribe",
		"description" => "This add-on subscribes new client information to your mail list hosted on Oempro",
		"version" => "1.0",
		"author" => "Octeth",
		"language" => "english",
		"fields" => array(
			"option1" => array("FriendlyName" => "Oempro URL", "Type" => "text", "Size" => "25", "Description" => "Enter the URL your Oempro installation", "Default" => "http://mydomain.com/oempro/",),
			"option2" => array("FriendlyName" => "Username", "Type" => "text", "Size" => "25", "Description" => "Enter the username of the user account", "Default" => "",),
			"option3" => array("FriendlyName" => "Password", "Type" => "password", "Size" => "25", "Description" => "Enter the password of the user account", "Default" => "",),
		));
	return $configarray;
}

function oempro_subscribe_activate()
{

}

function oempro_subscribe_deactivate()
{
}

function oempro_subscribe_upgrade($Variables)
{
}

//function oempro_subscribe_output($Variables)
//{
//	$OemproSubscribe = new oempro_subscribe();
//	$OemproSubscribe->get_configuration();
//	$SubscriberLists = $OemproSubscribe->get_lists();
//
//	return;
//}






class oempro_subscribe
{
	public $Configuration = array();
	private $API_SessionID = '';

	function _construct()
	{

	}

	public function get_lists()
	{
		$URL = preg_replace('/\/$/i', '', $this->Configuration['option1']).'/api.php';
		$PostParameters = array(
			'Command=User.Login',
			'ResponseFormat=JSON',
			'Username='.$this->Configuration['option2'],
			'Password='.$this->Configuration['option3'],
		);
		$Response = $this->DataPostToRemoteURL($URL, $PostParameters, 'POST', false, '', '', 30, false);
		$Response = json_decode($Response[1]);

		if ($Response->Success == false && $Response->ErrorCode[0] == 4)
		{
			die('Oempro Subscribe add-on module error: Please make sure that CAPTCHA is disabled for Oempro user login in Oempro Admin Area &gt; Settings &gt; Preferences');
		}
		elseif ($Response->Success == false)
		{
			die('Oempro Subscribe add-on module error: Error #'.$Response->ErrorCode[0]);
		}

		$this->API_SessionID = $Response->SessionID;

		$PostParameters = array(
			'SessionID='.$this->API_SessionID,
			'Command=Lists.Get',
			'ResponseFormat=JSON',
			'OrderField=Name',
			'OrderType=ASC',
		);
		$Response = $this->DataPostToRemoteURL($URL, $PostParameters, 'POST', false, '', '', 30, false);
		$Response = json_decode($Response[1]);

		if (isset($Response->Success) == false || $Response->Success == false)
		{
			die('Oempro Subscribe add-on module error: Error #'.$Response->ErrorCode[0]);
		}

		$SubscriberLists = array();

		if (count($Response->Lists) > 0)
		{
			foreach ($Response->Lists as $Index=>$EachList)
			{
				$SubscriberLists[] = $EachList;
			}
		}

		return $SubscriberLists;
	}

	public function subscribe($NewClientInfo)
	{
		$URL = preg_replace('/\/$/i', '', $this->Configuration['option1']).'/api.php';
		$PostParameters = array(
			'Command=User.Login',
			'ResponseFormat=JSON',
			'Username='.$this->Configuration['option2'],
			'Password='.$this->Configuration['option3'],
		);
		$Response = $this->DataPostToRemoteURL($URL, $PostParameters, 'POST', false, '', '', 30, false);
		$Response = json_decode($Response[1]);

		if ($Response->Success == false && $Response->ErrorCode[0] == 4)
		{
			die('Oempro Subscribe add-on module error: Please make sure that CAPTCHA is disabled for Oempro user login in Oempro Admin Area &gt; Settings &gt; Preferences');
		}
		elseif ($Response->Success == false)
		{
			die('Oempro Subscribe add-on module error: Error #'.$Response->ErrorCode[0]);
		}

		$this->API_SessionID = $Response->SessionID;

		$PostParameters = array(
			'SessionID='.$this->API_SessionID,
			'Command=Subscriber.Subscribe',
			'ResponseFormat=JSON',
			'ListID='.OEMPRO_TARGET_SUBSCRIBER_LIST_ID,
			'EmailAddress='.$NewClientInfo['email'],
			'IPAddress='.$_SERVER['REMOTE_ADDR']
		);
		$Response = $this->DataPostToRemoteURL($URL, $PostParameters, 'POST', false, '', '', 30, false);
		$Response = json_decode($Response[1]);

		if (isset($Response->Success) == true && $Response->Success == true)
		{
			logActivity("Success: New client has been subscribed to Oempro mail list successfully");
		}
		else
		{
			logActivity("Error: New client has been subscribed to Oempro mail list successfully");
		}

	}

	public function get_configuration()
	{
		$ConfigurationParameters = mysql_query("SELECT `setting`, `value` FROM tbladdonmodules WHERE module = 'oempro_subscribe'");
		if (!$ConfigurationParameters)
		{
			die("Oempro Subscribe add-on MySQL error: " . mysql_error());
		}

		$this->Configuration = array();

		while ($EachRow = mysql_fetch_assoc($ConfigurationParameters))
		{
			$this->Configuration[$EachRow["setting"]] = $EachRow["value"];
		}

		if (count($this->Configuration) == 0)
		{
			die("Oempro Subscribe add-on module not configured");
		}

		if ((isset($this->Configuration['option1']) == false || $this->Configuration['option1'] == '') || (isset($this->Configuration['option2']) == false || $this->Configuration['option2'] == '') || (isset($this->Configuration['option3']) == false || $this->Configuration['option3'] == ''))
		{
			die("Oempro Subscribe add-on module not configured properly");
		}

		return;
	}

	private function DataPostToRemoteURL($URL, $ArrayPostParameters, $HTTPRequestType = 'POST', $HTTPAuth = false, $HTTPAuthUsername = '', $HTTPAuthPassword = '', $ConnectTimeOutSeconds = 60, $ReturnHeaders = false)
	{
		$PostParameters = implode('&', $ArrayPostParameters);

		$CurlHandler = curl_init();
		curl_setopt($CurlHandler, CURLOPT_URL, $URL);

		if ($HTTPRequestType == 'GET')
		{
			curl_setopt($CurlHandler, CURLOPT_HTTPGET, true);
		}
		elseif ($HTTPRequestType == 'PUT')
		{
			curl_setopt($CurlHandler, CURLOPT_PUT, true);
		}
		elseif ($HTTPRequestType == 'DELETE')
		{
			curl_setopt($CurlHandler, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($CurlHandler, CURLOPT_POST, true);
			curl_setopt($CurlHandler, CURLOPT_POSTFIELDS, $PostParameters);
		}
		else
		{
			curl_setopt($CurlHandler, CURLOPT_POST, true);
			curl_setopt($CurlHandler, CURLOPT_POSTFIELDS, $PostParameters);
		}

		curl_setopt($CurlHandler, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($CurlHandler, CURLOPT_CONNECTTIMEOUT, $ConnectTimeOutSeconds);
		curl_setopt($CurlHandler, CURLOPT_TIMEOUT, $ConnectTimeOutSeconds);
		curl_setopt($CurlHandler, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		curl_setopt($CurlHandler, CURLOPT_SSL_VERIFYPEER, false);

		// The option doesn't work with safe mode or when open_basedir is set.
		if ((ini_get('safe_mode') != false) && (ini_get('open_basedir') != false))
		{
			curl_setopt($CurlHandler, CURLOPT_FOLLOWLOCATION, true);
		}

		if ($ReturnHeaders == true)
		{
			curl_setopt($CurlHandler, CURLOPT_HEADER, true);
		}
		else
		{
			curl_setopt($CurlHandler, CURLOPT_HEADER, false);
		}

		if ($HTTPAuth == true)
		{
			curl_setopt($CurlHandler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($CurlHandler, CURLOPT_USERPWD, $HTTPAuthUsername . ':' . $HTTPAuthPassword);
		}

		$RemoteContent = curl_exec($CurlHandler);

		if (curl_error($CurlHandler) != '')
		{
			return array(false, curl_error($CurlHandler));
		}

		curl_close($CurlHandler);

		return array(true, $RemoteContent);
	}
}