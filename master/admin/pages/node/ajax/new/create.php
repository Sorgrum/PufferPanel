<?php
session_start();
error_reporting('E_ALL');
require_once('../../../../../core/framework/framework.core.php');

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token'), true) !== true){
	exit('<div class="error-box round">Failed to Authenticate Account.</div>');
}
	
//Cookies :3
setcookie("__TMP_pp_admin_newnode", json_encode($_POST), time() + 10, '/', $core->framework->settings->get('cookie_website'));

/*
 * Agree Warning
 */
if(!isset($_POST['read_warning']))
	$core->framework->page->redirect('../../add.php?disp=agree_warn');

/*
 * Are they all Posted?
 */
if(!isset($_POST['node_name'], $_POST['node_url'], $_POST['node_ip'], $_POST['node_sftp_ip'], $_POST['s_dir'], $_POST['s_dir_backup'], $_POST['ssh_user'], $_POST['ssh_pass'], $_POST['ip_port']))
	$core->framework->page->redirect('../../add.php?disp=missing_args');

/*
 * Validate Node Name
 */
if(!preg_match('/^[\w.-]{1,15}$/', $_POST['node_name']))
	$core->framework->page->redirect('../../add.php?error=node_name&disp=n_fail');
	
/*
 * Validate Node URL
 */
if(!preg_match('/^https?\:\/\/(?:[\w](?:[-\w]*[\w])?\.)+[a-zA-Z]{1,15}(?:\:[\d]+)?\/?$/', $_POST['node_url']))
	$core->framework->page->redirect('../../add.php?error=node_url&disp=url_fail');
	
/*
 * Validate node_ip & node_sftp_ip
 */
if(!filter_var($_POST['node_ip'] , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) || !filter_var($_POST['node_sftp_ip'] , FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
	$core->framework->page->redirect('../../add.php?error=node_ip|node_sftp_ip&disp=ip_fail');

if(!preg_match('/^[a-zA-Z0-9_\.\/-]+[^\/]\/$/', $_POST['s_dir']) || !preg_match('/^[a-zA-Z0-9_\.\/-]+[^\/]\/$/', $_POST['s_dir_backup']))
	$core->framework->page->redirect('../../add.php?error=s_dir|s_dir_backup&disp=dir_fail');
	
if($_POST['s_dir'] == $_POST['s_dir_backup'])
	$core->framework->page->redirect('../../add.php?error=s_dir|s_dir_backup&disp=dir_match_fail');
	
if(strlen($_POST['ssh_user']) < 1 || $_POST['ssh_user'] == 'root')
	$core->framework->page->redirect('../../add.php?error=ssh_user&disp=user_fail');

if(strlen($_POST['ssh_pass']) < 12)
	$core->framework->page->redirect('../../add.php?error=ssh_pass&disp=pass_fail');

/*
 * Process IPs and Ports
 */
$IPP = array();
$IPA = array();
$lines = explode("\r\n", $_POST['ip_port']);
foreach($lines as $id => $values)
	{
	
		list($ip, $ports) = explode('|', $values);
		
		$IPA = array_merge($IPA, array($ip => array()));
		$IPP = array_merge($IPP, array($ip => array()));
		
		$ports = explode(',', $ports);

		for($l=0; $l<count($ports); $l++)
			{
							
				$IPP[$ip] = array_merge($IPP[$ip], array($ports[$l] => $ports[$l]));
			
			}
		
		/*
		 * Ghetto Function to Flip the Array since PHP wants to set the keys equal to random numbers and not the port.
		 */
		$IPP[$ip] = array_flip($IPP[$ip]);
		$IPP[$ip] = array_fill_keys(array_keys($IPP[$ip]), 1);
		
		$IPA[$ip] = array_merge($IPA[$ip], array("ports_free" => count($IPP[$ip]), "ports_used" => 0, "ports_suspended" => 0));
			
	}
	
$IPA = json_encode($IPA);
$IPP = json_encode($IPP);

$iv = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CBC), MCRYPT_RAND));
$_POST['ssh_pass'] = openssl_encrypt($_POST['ssh_pass'], 'AES-256-CBC', file_get_contents(HASH), false, base64_decode($iv));

$create = $mysql->prepare("INSERT INTO `nodes` VALUES(NULL, :name, :link, :ip, :sftp_ip, :sdir, :bdir, :suser, :iv, :spass, :ips, :ports)");
$create->execute(array(
	':name' => $_POST['node_name'],
	':link' => $_POST['node_url'],
	':ip' => $_POST['node_ip'],
	':sftp_ip' => $_POST['node_sftp_ip'],
	':sdir' => $_POST['s_dir'],
	'bdir' => $_POST['s_dir_backup'],
	':suser' => $_POST['ssh_user'],
	':iv' => $iv,
	':spass' => $_POST['ssh_pass'],
	':ips' => $IPA,
	':ports' => $IPP
));

$core->framework->page->redirect('../../view.php?id='.$mysql->lastInsertId());

?>