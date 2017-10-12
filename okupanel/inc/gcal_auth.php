<?php

if (!defined('ABSPATH'))
	exit();

	
function &okupanel_gcal_get_client(){ 
	static $client = null;

	if ($client !== null)
		return $client;

	if (
		!($app_id = get_option('okupanel_gcal_app_id', false))
		|| !($app_secret = get_option('okupanel_gcal_app_secret', false))
	)
		return false;

	try {
		require_once OKUPANEL_PATH.'/inc/lib/google-api-php-client-2.0.3_PHP54/vendor/autoload.php';

		$client = new Google_Client();
		$client->setClientId($app_id);
		$client->setClientSecret($app_secret);

		$client->setAccessType('offline');
		$client->setApprovalPrompt('force');
		
		$client->setRedirectUri(admin_url('admin-ajax.php?action=okupanel&target=gcal_auth_cb'));
		
		// $client->setIncludeGrantedScopes(true);

		$client->setScopes(array(
			'https://www.googleapis.com/auth/calendar.readonly',
			//'https://www.googleapis.com/auth/calendar'
		));
		return $client;
		
	} catch (Exception $e){
		return false;
	}
}

function &okupanel_gcal_get_service(){ // set to 0 to get the global service
	static $service = null;

	if ($service !== null)
		return $service;

	if (
		!($token = get_option('okupanel_gcal_auth', false))
		|| !($client = okupanel_gcal_get_client())
	){
		$service = false;
		
	} else {
		try {
			$client->setAccessToken($token['token']);
			$service = new Google_Service_Calendar($client);
			
		} catch (Exception $e){
			$service = false;
		}
	}
	return $service;
}



function okupanel_ajax_gcal_auth(){
	if (!current_user_can('manage_options'))
		return false;
		
	if (!($client = okupanel_gcal_get_client()))
		return false;
		
	try {
		$client->setState(wp_create_nonce('okupanel_gcal_auth_try'));
			
		if ($authUrl = $client->createAuthUrl()){
			header('Location: '.$authUrl);
			exit();
		}
	} catch (Exception $e){
	}
	echo 'auth failed';
	exit();
}

function okupanel_ajax_gcal_auth_cb(){
	$success = false;
	if (
		current_user_can('manage_options') 
		&& wp_verify_nonce($_GET['state'], 'okupanel_gcal_auth_try')
	){
		if (isset($_GET['code']) && ($client = okupanel_gcal_get_client())){
			
			try {
				$client->authenticate(sanitize_text_field($_GET['code']));

				$token = $client->getAccessToken();
				update_option('okupanel_gcal_auth', array('time' => time(), 'token' => $token));

				$success = true;

			} catch (Exception $e){
			}
			
		}
	}

	$msg = $success ? 'You\'ve been authenticated successfuly' : 'Authentication failed';
	?>
	<script type="text/javascript">
		alert("<?= esc_js($msg) ?>");
		<?php if ($success){ ?>
			window.close();
		<?php } ?>
	</script>
	<?php
	exit;
}
