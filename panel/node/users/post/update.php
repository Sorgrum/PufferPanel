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

$klein->respond('*', function($request, $response) use ($core) {
	if($core->settings->get('allow_subusers') != 1) {
		$response->redirect('list?error=not_enabled', 302)->send();
	}

	if(!isset($_POST['uuid'], $_POST['permissions'])) {
		$response->redirect('list', 302)->send();
	}

	if($core->auth->XSRF(@$_POST['xsrf']) !== true) {
		$response->redirect('list?id='.$_POST['uuid'].'&error', 302)->send();
	}

	if(empty($_POST['permissions'])) {
		$response->redirect('view?id='.$_POST['uuid'].'&error', 302)->send();
	}

	$query = ORM::forTable('users')->select('permissions')->where('uuid', $_POST['uuid'])->findOne();

	if($query === false) {
		$response->redirect('list?error', 302)->send();
	}

	$permissions = @json_decode($query->permissions, true);
	if(!is_array($permissions) || !array_key_exists($core->server->getData('hash'), $permissions)) {
		$response->redirect('view?id='.$_POST['uuid'].'&error', 302)->send();
	}

	$permissions[$core->server->getData('hash')] = $_POST['permissions'];
	$query->permissions = json_encode($permissions);
	$query->save();

	$response->redirect('view?id='.$_POST['uuid'], 302)->send();
});
