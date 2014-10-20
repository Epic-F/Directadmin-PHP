<?php
/**
* DirectAdmin File
* Object Class to interact with DirectAdmin (http://www.directadmin.com/)
* Web Pannels
* 
* @author Hadar Porat <hpman28@gmail.com>
* @version 1.5
* GNU Lesser General Public License (Version 2, June 1991)
*
* This program is free software; you can redistribute
* it and/or modify it under the terms of the GNU
* Lesser General Public License as published by the Free
* Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will
* be useful, but WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A
* PARTICULAR PURPOSE. See the GNU Lesser General Public License
* for more details.
*/

/**
* DirectAdmin Class
* @access public
* @author Hadar Porat <hpman28@gmail.com>
* @version 1.5
*/

class DirectAdmin {
	private $ch;
	private $cookiePath;

	
	/**
	* @return void
	* @param string $domain domain name
	* @param string $cookiePath temp location to save cookie
	* @param string $url url of directadmin
	* @param string $username directadmin username
	* @param string $password directadmin password
	* @desc class constructor
	*/		
	function __construct($domain, $cookiePath, $url, $username, $password) {

		$this -> data = array (
		'username' => $username,
		'password' => $password,
		'referer' => '/',
		);

		$this -> domain = $domain;

		$this -> params = array();

		$this -> url = $url;
		$this -> setCookiePath($cookiePath);

		$this -> setCommand('CMD_LOGIN');
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $path cookie path
	* @desc set the cookie path
	*/		
	function setCookiePath($path) {
		$this -> cookiePath = $path;
		$this -> cookieFile = 'cookie_' . rand(0, 1000) . '.txt';
		fopen($path . $this -> cookieFile, 'w+');

	}

	/**
	* @return void
	* @param string $command command name
	* @param string $params paramters for the command
	* @desc set the directadmin command
	*/	
	function setCommand($command, $params = '') {
		$this -> command = $this -> url . $command;
	}

	/**
	* @return void
	* @param string $command command name
	* @param string $params paramters for the command
	* @desc set the directadmin command for a certain domain action
	*/		
	function setDomainCommand($command, $params = '') {
		$this -> command = $this -> url . $command . '?domain=' . $this -> domain;

		if (is_array($params)) {
			$this -> data = array_merge($this -> data, $params);

		}

	}

	/**
	* @return void
	* @desc execute the command
	*/		
	function executeCommand() {
 
 		$this -> ch = curl_init();
		curl_setopt($this -> ch, CURLOPT_POST, 1);
		curl_setopt($this -> ch, CURLOPT_HEADER, 0);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER,  array('Accept: application/json', 'X-HTTP-Method-Override: POST'));
	 	curl_setopt($this -> ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($this -> ch, CURLOPT_COOKIEJAR, $this -> cookiePath . $this -> cookieFile);  //initiates cookie file if needed
		curl_setopt($this -> ch, CURLOPT_COOKIEFILE, $this -> cookiePath . $this -> cookieFile);  // Uses cookies from previous session if exist
		curl_setopt($this -> ch, CURLOPT_URL, $this -> command);
		curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $this -> data);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this -> ch, CURLOPT_VERBOSE, 1);
		curl_setopt ($this -> ch, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt ($this -> ch, CURLOPT_SSL_VERIFYPEER, false); 
 		$result = curl_exec($this -> ch);
 		
 		
		if(curl_errno($this -> ch)){ 
 		  echo 'Curl error: ' . curl_error($this -> ch); 
		} 

		curl_close($this -> ch); 
 		return $result;
	}

	/**
	* @return void
	* @desc get stats array
	*/		
	function getStats() {
		$this -> setCommand('');
		$html = $this -> executeCommand();

		preg_match_all("/<td width=\"3[3-4]%\" class=bar-row[1-2] align=\"center\">(.*?)<\/td>/", $html, $out, PREG_PATTERN_ORDER);

		$array = array(
		'used' => array('diskspace' => $out[1][0], 'bandwidth' => $out[1][2], 'emails' => $out[1][4], 'ftp' => $out[1][6], 'database' => $out[1][8]),
		'max' => array('diskspace' => $out[1][1], 'bandwidth' => $out[1][3], 'emails' => $out[1][5], 'ftp' => $out[1][7], 'database' => $out[1][9]),

		);

		return $array;
	}

	/**
	* @return void
	* @param string $username new account username
	* @param string $email account email
	* @param string $password new account password
	* @param string $domain account domain
	* @param string $package account package
	* @param string $ip ip address 
	* @desc create a new user account
	*/	
	function createUserAccount($username, $email, $password, $domain, $package, $ip) {
		$this -> setDomainCommand('CMD_ACCOUNT_USER', array('action' => 'create', 'domain' => $this -> domain, 'username' => $username, 'email' => $email, 'passwd' => $password, 'passwd2' => $password, 'domain' => $domain, 'package' => 'auto', 'ip' => $ip, 'notify' => 'yes'));
		$html = $this -> executeCommand();
		$this -> customizeUserAccountPackage($username, 1, 1000, 100, 1);
		
		return $html;
 	}

	/**
	* @return void
	* @param string $username account username
	* @param string $password account password
	* @desc modify account to auto package
	*/		
	function modifyUserAccountPackage($username, $package) {
		$this -> setDomainCommand('CMD_MODIFY_USER', array('action' => 'package', 'user' => $username, 'package' => 'auto'));
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $username account username
	* @param string $vdomains virtual domains number
	* @param string $bandwidth bandwidth allowed
	* @param string $quota disk quota
	* @param string $mysql mysql database number
	* @desc customize account package
	*/		
	function customizeUserAccountPackage($username, $vdomains, $bandwidth, $quota, $mysql) {
		$this -> setDomainCommand('CMD_MODIFY_USER', array('action' => 'customize', 'user' => $username, 'php' => 'ON', 'unemails' => 'unlimited', 'vdomains' => $vdomains, 'bandwidth' => $bandwidth, 'quota' => $quota, 'mysql' => $mysql));
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $name database name
	* @param string $username account username
	* @param string $password mysql database number
	* @desc creates a new mysql database
	*/		
	function createDatabase($name, $username, $password) {
		$this -> setDomainCommand('CMD_DB', array('action' => 'create', 'domain' => $this -> domain, 'user' => $username, 'name' => $name, 'passwd' => $password, 'passwd2' => $password));
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $domain domain name
	* @param boolean $database include databases in backup
	* @param boolean $email include emails in backup
	* @param boolean $ftp include ftp in backup
	* @param boolean $ftpsettings include ftp settings in backup
	* @desc creates a account backup
	*/			
	function createBackup($domain, $database, $email, $ftp, $ftpsettings) {
		if ($domain==1) $domain = 'domain';
		else $domain = '';

		if ($database==1) $database = 'database';
		else $database = '';

		if ($email==1) $email = 'email';
		else $email = '';

		if ($ftp==1) $ftp = 'ftp';
		else $ftp = '';
		
		if ($ftpsettings==1) $ftpsettings = 'ftpsettings';
		else $ftpsettings = '';		
		
		
		$this -> setDomainCommand('CMD_SITE_BACKUP', array('action' => 'backup', 'domain' => $this -> domain, 'select0' => $domain, 'select1' => 'subdomain', 'select2' => 'email', 'select8' => 'ftp', 'select9' => 'ftpsettings', 'select10' => 'database'));
		$this -> executeCommand();
	}
	

	/**
	* @return void
	* @param string $filename backup filename
	* @desc chmod a account backup for downloading
	*/	
	function chmodBackup($filename) {
		$this -> setDomainCommand('CMD_FILE_MANAGER', array('action' => 'multiple', 'button' => 'permission', 'permission' => '1', 'chmod' => 777, 'path' => '/backups', 'select0' => '/backups/' . $filename));
		$this -> executeCommand();
	}		
	
	/**
	* @return void
	* @desc returns backups array list
	*/		
	function getBackupsList() {

		$this -> setDomainCommand('CMD_FILE_MANAGER/backups');
		$html = $this -> executeCommand();

		preg_match_all("/<td class=list[2]?>(.*?)<img border=0 alt=\"File\" src=\"\/IMG_FILE\"><\/a><\/td ><td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td >/", $html, $out, PREG_PATTERN_ORDER);

		$array = array();

		for ($i=0;$i<count($out[0]);$i++) {
			$array[] = array('filename' => strip_tags($out[2][$i]), 'size' => strip_tags($out[3][$i]), 'timeStamp' => strtotime(strip_tags($out[6][$i])));
		}

		return $array;
	}

	/**
	* @return void
	* @desc returns email accounts array list
	*/		
	function getEmailList() {

		$this -> setDomainCommand('CMD_EMAIL_POP');
	 	$html = $this -> executeCommand();
 
		preg_match_all("/<td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td ><td class=list[2]?>(.*?)<\/td >/", $html, $out, PREG_PATTERN_ORDER);

		$array = array();

		for ($i=0;$i<count($out[0]);$i++) {
			$out[5][$i] = explode('@', $out[2][$i]);
			$array[$out[5][$i][0]] = array('email' => $out[1][$i], 'username' => $out[2][$i], 'boxname' => $out[5][$i][0], 'quota' => $out[3][$i]);
		}

		return $array;
	}

	/**
	* @return void
	* @param string $username email account username
	* @desc returns email account information
	*/			
	function getEmail($username) {
		$array = $this -> getEmailList();
		return $array[$username];
	}

	/**
	* @return void
	* @param string $username email username
	* @param string $password email password
	* @param string $quota emailbox size
	* @desc creates new email account
	*/		
	function createEmail($username, $password, $quota) {
		$this -> setDomainCommand('CMD_EMAIL_POP', array('action' => 'create', 'domain' => $this -> domain, 'user' => $username, 'passwd' => $password, 'passwd2' => $password, 'quota' => $quota));
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $username email username
	* @param string $password new email password
	* @desc updates emailbox password
	*/		
	function updateEmail($username, $password) {
		$this -> setDomainCommand('CMD_EMAIL_POP', array('action' => 'modify', 'domain' => $this -> domain, 'user' => $username, 'passwd' => $password, 'passwd2' => $password));
		$this -> executeCommand();
	}

	/**
	* @return void
	* @param string $username email username
	* @desc deletes email account
	*/	
	function deleteEmail($username) {
		$this -> setDomainCommand('CMD_EMAIL_POP', array('action' => 'delete', 'domain' => $this -> domain, 'select1' => $username));
		$this -> executeCommand();
	}
}
?>