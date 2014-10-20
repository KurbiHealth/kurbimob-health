<?php

class sign_in extends appModel{

	private $Session = '';
	private $dbConn = '';
	private $Request = '';

	/**
	 * @todo Need to save IP that user is signing in with to Session var, and then compare that IP 
	 * to current IP for life of session, do it via frontController (?), and redirect to sign-in page
	 * if the IP ever changes mid-session
	 */

/* ================== WHEN USER IS SIGNING IN FROM PATIENT APP (THIS APP) ================== */

	public function user($data){
		// if current request is coming from the sign-in url, then use the ->checkIsUser function to check
		// whether the user has valid credentials
		// NOTE: $user at the next line will have the contents of the "people" table ONLY
$return = '';
$post = $this->getPost();
if(!empty($post)){
	$user = array(
		'id' => 1,
		'person_id' => 10,
		'role' => 'doctors',
		'email' => 'doctor@test.com',
		'password' => '1234'
	);
	if(($post['email'] == $user['email']) && ($post['password'] == $user['password'])){
		$this->_initializeUser($user);
		$return['status'] = 'success';
		$return['token'] = 'sd9fnt348y1232';
	}else{
		$return['status'] = 'failed';
	}
}else{
	$return['status'] = 'failed';
}
return json_encode($return);

		if(!SKIP_LOGIN_FOR_DEV){

			$reg = registry::singleton();

			$this->dbConn = $reg->get('databaseConnectionSingleton');
			$this->Session = $reg->get('sessionSingleton');
			$this->Request = $reg->get('requestSingleton');
	
			$user = $this->_check_is_user();

			$reg->set('userRecord',$user);

			if($user){
				if(USE_FIREPHP){$this->firephp->log($user,'Authentication.php, retrieved $user, signing in at line '.__LINE__);}
				$this->_initializeUser($user);
				setFlashMessage('text','Welcome!','Welcome back to Kurbi');
				redirect(ROOT_URL.LOGGED_IN_HOME_URL);
			}else{
				$this->Session->set('queryString',$queryString);
				setFlashMessage('text','Notice!','Your credentials were not found in our database, you must sign up for an account.');
				redirect(SIGNUP_APP_URL.'/home/index');
			}

		}else{
			// skipping login requirement for dev purposes
			if(USE_FIREPHP){$this->firephp->log('--Skipping login for dev purposes, line '.__LINE__);}
		}

	}

/* ================= INTERNAL HELPER FUNCTIONS ================= */

	private function _initializeUser($user){
		if(empty($this->Session)){
			$reg = registry::singleton();
			$this->Session = $reg->get('sessionSingleton');
		}
		$this->Session->set('loggedIn','y');
		$this->isLoggedIn = TRUE;
		$this->Session->set('userId',$user['id']);
		$this->currUserId = $user['id'];
		$this->Session->set('userPersonId',$user['person_id']);
		$this->Session->set('userRole',$user['role']);
 	}

	private function _check_is_user(){
		/**
		 * Check if Post is set, meaning there's a log-in action going on, and then check the database.
		 */
		$post = $this->Request->getPost();
		$dbConn = $this->dbConn;

		if(!isset($post['email_address'])){
			if(isset($post['email'])){
				$post['email_address'] = $post['email'];
				unset($post['email']); 
			}else{
				$post['email_address'] = '';
			}
		}

		if($post['email_address'] == '' || $post['password'] == '')
			return FALSE;

		$sql = 'SELECT * FROM people WHERE email=\''.$post['email_address'].'\' AND password=\''.$post['password'].'\' LIMIT 1';
		$result = mysqli_query($dbConn,$sql);

		if(is_object($result)){
			if(mysqli_num_rows($result) > 0){
				$userInfo = mysqli_fetch_assoc($result);
				$userInfo['role'] = USER_ROLE;
			}else{
				return FALSE;
			}
		}else{
			return FALSE;
		}

		// get role info
		$sql = 'SELECT * FROM '.USER_ROLE.' WHERE person_id='.$userInfo['id'];
		if(USE_FIREPHP){$this->firephp->log($sql,'Authentication.php, $sql at '.__LINE__);}
		$result = mysqli_query($dbConn,$sql);
		if(is_object($result) && mysqli_num_rows($result) > 0){
			$roleInfo = mysqli_fetch_assoc($result);
			$userInfo['role'] = USER_ROLE;
			foreach($roleInfo as $key=>$value){
				$userInfo[$key] = $value;
			}
		}else{
			if(USE_FIREPHP){$this->firephp->log($result,'WARNING! in Authentication.php unable to get role fields at line '.__LINE__);}
			return FALSE;
		}

		return $userInfo;
	}

}