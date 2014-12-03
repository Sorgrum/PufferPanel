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

$klein->respond('*', function($request, $response) use ($core, $twig, $pageStartTime) {
	if(!$core->user->hasPermission('files.edit')) {
		$response->redirect('index?error=no_permission', 302)->send();
	}

	$canEdit = array('txt', 'yml', 'log', 'conf', 'html', 'json', 'properties', 'props', 'cfg', 'lang');

	if(isset($_POST['file'])) {
		$_POST['file'] = str_replace('..', '', urldecode($_POST['file']));
	}

	if(isset($_POST['dir'])) {
		$_POST['dir'] = str_replace('..', '', urldecode($_POST['dir']));
	}

	$error = null;
	
	if(!isset($_POST['file'])) {
		$error = '<div class="alert alert-warning">'.$_l->tpl('node_files_edit_no_file').'</div>';
	} else {

		if(!in_array(pathinfo($_POST['file'], PATHINFO_EXTENSION), $canEdit)) {
			$error = '<div class="alert alert-danger">'.$_l->tpl('node_files_edit_type_error').'</div>';
		} else {

			/*
			* Create File Path
			*/
			$file = pathinfo($_POST['file'], PATHINFO_BASENAME);
			$directory = dirname($_POST['file']).'/';

			/*
			* Directory Cleaning
			*/
			if($directory == './' || $directory == '.') {
				$directory = '';
			}

			if(substr($directory, 0, 1) == '/') {
				$directory = substr($directory, 1);
			}

			$url = "http://".$core->server->nodeData('ip').":".$core->server->nodeData('gsd_listen')."/gameservers/".$core->server->getData('gsd_id')."/file/".$directory.$file;

			$context = stream_context_create(array(
				"http" => array(
					"method" => "GET",
					"header" => 'X-Access-Token: '.$core->server->getData('gsd_secret'),
					"timeout" => 3
				)
			));

			$content = file_get_contents($url, 0, $context);

			if(!$content) {
				$error = '<div class="alert alert-danger">'.$_l->tpl('node_files_edit_daemon_error').'</div>';
			} else {
				$json = json_decode($content, true);
			}

			if(!array_key_exists('contents', $json)) {
				$error = '<div class="alert alert-danger">'.$_l->tpl('node_files_edit_daemon_error').'</div>';
			}
		}

	}

	/*
	* Prevent Errors
	*/
	if(!is_null($error)) {
		$_POST['file'] = null;
		$directory = null;
		$json['contents'] = null;
	}

	/*
	* Display Page
	*/
	echo $twig->render('node/files/edit.html', array(
		'server' => $core->server->getData(),
		'error' => $error,
		'xsrf' => $core->auth->XSRF(),
		'file' => $_POST['file'],
		'directory' => $directory,
		'contents' => $json['contents'],
		'footer' => array(
			'seconds' => number_format((microtime(true) - $pageStartTime), 4)
		)
	));
});
