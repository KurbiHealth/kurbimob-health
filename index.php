<?php 

header('Access-Control-Allow-Origin: *');

// if the app needs to be secure, check to see if incoming request is for http, and if it, redirect to https
define('SECURE_APP',FALSE);

/**
 * EDITABLE APP SETTINGS
 */

$siteSettingsFilename = ''; // 'kurbiSettings_siteKurbiNew'

define('APP_GROUP_CONFIGURATIONS','');

define('HOME_URL','/sign_in/user');
define('LOGGED_IN_HOME_URL','/invitation/home');
define('SIGN_UP_URL','SIGNUP_APP_URL'); // this de facto redirects all signup urls to the signup app
define('SIGN_IN_URL','/sign_in/user');
define('DEFAULT_PAGE','/sign_in/user');

define('USER_ROLE','doctors');

// if true session created for every page call, if false then control given to the Session class or the Controller class
define('AUTO_SESSION', TRUE);

// if TRUE, then every page requires a login, with FALSE some pages can be set to public and others to private
define('LOGIN_REQUIRED', TRUE); 

// if value equals 'database', then user rights/groups are loaded from database
// for now that's  the only option, but could use 'ldap' in future
define('USER_GROUPS_IN', 'database');

// These pages are only accessible to a member who has logged in
$protectedPages = array(
	'none'
);

/****************************************************************
 * DO NOT EDIT BELOW THIS LINE
 ***************************************************************/

/**
 * Load initial configuration values
 */
if($siteSettingsFilename != '' && is_file('../'.$siteSettingsFilename.'.php'))
	include '../'.$siteSettingsFilename.'.php';
else{
	if(!defined('ROOT_URL')){
		if(isset($_SERVER['HTTP_HOST'])){
			define('ROOT_URL','http://'.$_SERVER['HTTP_HOST']);
		}else
			die('Unable to set constant ROOT_URL in index.php at line '.__LINE__);
	}
	
	if(!defined('SERVER_ROOT_PATH')){
		if(isset($_SERVER['DOCUMENT_ROOT'])){
			$tempPath = $_SERVER['DOCUMENT_ROOT'];
			$tempPath = rtrim($tempPath, '/').'/';
			define('SERVER_ROOT_PATH',$tempPath);
		}else
			die('Unable to set constant SERVER_ROOT_PATH in index.php at line '.__LINE__);
	}
}

if(is_file('../kurbiSettings_serverEnvironment.php'))
	include '../kurbiSettings_serverEnvironment.php';
else
	die('Could not load settings file ../kurbiSettings_serverEnvironment.php');

if(AUTO_SESSION){session_start();}ob_start();

/**
 * ERROR MANAGEMENT
 * 1) Load errorManagement class
 * 2) Load FirePHP 
 */
 
require_once(MVC_CORE_PATH.'errorManagement.php');
$Errors = errorManagement::singleton();

if(ENVIRONMENT == 'dev'){
	define('USE_FIREPHP',TRUE);
}else{
	define('USE_FIREPHP',FALSE);
}

if(file_exists(PATH_TO_MVC_LIBRARIES.'FirePHPCore/FirePHP.class.php')){
	require_once(PATH_TO_MVC_LIBRARIES.'FirePHPCore/FirePHP.class.php');
	$firephp = FirePHP::getInstance(true);
	if(USE_FIREPHP){
		$firephp->log('CURR - index.php');
	}
}else{
	if(defined('ENVIRONMENT') && ENVIRONMENT == 'dev')
		die('Could not load FirePHP');
}



/**
 * Kick off MVC framework process. The bootstrap file loads classes needed by the framework.
 */

require_once MVC_CORE_PATH.'bootstrap.php';