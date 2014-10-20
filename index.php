<?php
session_start();
include_once('directadmin.inc.php');
include_once('simple_html_dom.php');
error_reporting(E_ALL);

######### ADMIN Login Deatil ##################
$uname = 'resellername';
$pass = 'password';
$domain = 'domain.com';
$cookiepath = 'cache/';

######### ADMIN Login Process ##################
$da = new DirectAdmin($domain, $cookiepath, 'http://domain.com:2222/', $uname , $pass);
$loginresponse = (array) $da;
$cookiefile = $loginresponse['cookieFile'];

if(!empty($cookiefile))
{
	$data = file_get_contents($cookiepath.$cookiefile);
	$s = explode("session",$data);
	$sessionId = trim(@$s[1]);
	$_SESSION['Login_ID'] = $sessionId;
}

if(empty($_SESSION['Login_ID'])){
	
	echo "<br>Login Failed!!!";
	exit;

}


 
if (isset($_REQUEST['submit'])) {
 
	$username = $_POST['username'];
	$email = $_POST['email'];
	$password = $_POST['password'];
	$domain = $_POST['domain'];
	$package = '';
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$useres = $da -> createUserAccount($username, $email, $password, $domain, $package, $ip);
	//$da -> createEmail($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['quota']);
	//$da -> getEmailList();
	
	$html = file_get_html($useres);
	$msgs = '';
 	foreach($html->find('table p') as $e)
	{
 		$msgs .= $e;
  	}
  	
 }
?>
<style>
table.list { display:none !important; }
</style>
<script>
function valid()
{
	var pass = document.getElementById('password').value;
	var repass = document.getElementById('repassword').value;
	
	if(pass != repass)
	{
		alert("Password is not match!!!");
		return false;
	}
}
</script>
<?php if(!empty($msgs)) { ?>
	<div><?=$msgs?></div>
	<hr />
 <?php } ?>
<form action="" method="post" name="adminForm" id="adminForm" onsubmit="javascript: return valid();">
<table class="adminform" border="0">
 	<tr>
		<th colspan="3">Create New Account</th>
	</tr>
	<tr>
		<td width="20%" valign="top"><b>Username</b></td>
		<td valign="top" dir="ltr"><input class="text_area" size="50" name="username" type="text" /></td>

	</tr>
	<tr>
		<td width="20%" valign="top"><b>Password</b></td>
		<td valign="top"><input class="text_area" size="50" name="password" type="password" id="password" /></td>
	</tr>
	<tr>
		<td width="20%" valign="top"><b>Password Again</b></td>
		<td valign="top"><input class="text_area" size="50" name="repassword" type="password" id="repassword" /></td>

	</tr>
	<tr>
		<td width="20%" valign="top"><b>Email</b></td>
		<td valign="top"><input class="text_area" size="50" name="email" type="text" /></td>

	</tr>
	<tr>
		<td width="20%" valign="top"><b>Domain</b></td>
		<td valign="top"><input class="text_area" size="50" name="domain" type="text" /></td>

	</tr>
	<!--<tr>
		<td width="20%" valign="top"><b>Email Box Size</b><br />(0 for unlimited)</td>
		<td valign="top"><input name="quota" type="text" /></td>
	</tr>-->
	<tr>
	  <td><input type="submit" name="submit" value="Create" ></td>
	</tr>
</table>
</form> 
