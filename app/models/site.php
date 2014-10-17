<?php

class site extends appModel{
	
	function log_out(){
		$Reg = registry::singleton();
		$Session = $Reg->get('sessionSingleton');
		$Session->isLoggedIn = FALSE;
		
		unset($_SESSION);
		
		$params = session_get_cookie_params();
		setcookie(session_name(), session_id(), time()-3600,$params["path"], $params["domain"],$params["secure"]);
		
		session_destroy();
	}
	

	
}

?>