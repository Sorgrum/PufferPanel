<?php
/*
	PufferPanel - A Minecraft Server Management Panel
	Copyright (c) 2013 Dane Everitt

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see http://www.gnu.org/licenses/.
*/
namespace PufferPanel\Core;
use \ORM as ORM;
require_once('../src/core/core.php');

if($core->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->auth->getCookie('pp_auth_token')) === true)
	Components\Page::redirect('account.php?token='.@$_GET['token']);

if(isset($_GET['do']) && $_GET['do'] == 'register' && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if(!isset($_POST['token'])) {
		Components\Page::redirect('register.php?error=token');
	}

	/* XSRF Check */
	if($core->auth->XSRF(@$_POST['xsrf']) !== true)
		Components\Page::redirect('register.php?error=xsrf&token='.urlencode($_POST['token']));

	$query = ORM::forTable('account_change')
		->where(array(
			'type' => 'user_register',
			'key' => $_POST['token'],
			'verified' => 0
		))->findOne();

	if(!$query) {
		Components\Page::redirect('register.php?error=t_fail&token='.$_POST['token']);
	}

	if(!preg_match('/^[\w-]{4,35}$/', $_POST['username']))
		Components\Page::redirect('register.php?error=u_fail&token='.urlencode($_POST['token']));

	if(strlen($_POST['password']) < 8 || $_POST['password'] != $_POST['password_2'])
		Components\Page::redirect('register.php?error=p_fail&token='.urlencode($_POST['token']));

	$user = ORM::forTable('users')->where_any_is(array(array('username' => $_POST['username']), array('email' => $query->content)))->findOne();
	if($user) {
		Components\Page::redirect('register.php?error=a_fail&token='.$_POST['token']);
	}

	$user = ORM::forTable('users')->create();
	$user->set(array(
		'uuid' => $core->auth->gen_UUID(),
		'username' => $_POST['username'],
		'email' => $query->content,
		'password' => $core->auth->hash($_POST['password']),
		'permissions' => null,
		'language' => $core->settings->get('default_language'),
		'register_time' => time()
	));
	$user->save();

	$query->verified = 1;
	$query->save();

	Components\Page::redirect('index.php?registered');

}else
	echo $twig->render(
			'panel/register.html', array(
				'xsrf' => $core->auth->XSRF(),
				'footer' => array(
					'seconds' => number_format((microtime(true) - $pageStartTime), 4)
				)
		));
?>
