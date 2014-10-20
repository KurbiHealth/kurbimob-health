<?php

class invitation extends appModel{
	
	var $typeList = array(
		'1' => 'General Practitioner',
		'2' => 'Neurologist',
		'3' => 'Nurse',
		'4' => 'Spouse',
		'5' => 'Child',
		'6' => 'Parent',
		'7' => 'Sibling',
		'8' => 'Friend'
	);


	function post_form($data){
$return = '';
$post = $this->getPost();
if(!empty($post)){
	if(post['token'] == 'sd9fnt348y1232'){
		// check to see if user already in database
$return['status'] = 'success';
		// add user to database

		// send email to user

		// return status to mobile app

	}else{
		$return['status'] = 'tokenUnrecognized';
	}
}else{
	$return['status'] = 'failed';
}
return json_encode($return);

		global $firephp;
		$new = $this->getPost();

		$message = htmlentities("Hi, I'm sending you this email invitation from my Kurbi account.

Kurbi is a safe and private collaborative health journal that helps me to more easily keep track of my symptoms, medications, and physical activities throughout the day.

I'd like to share access to my journal with you.

Thank you.");

		$return = '';

		$user = currentUser();

		// GENERATE A RANDOM PASSWORD
		$password = '';
		for($i=0;$i<7;$i++){
			$password .= chr(mt_rand(64, 122));
		}

		// 'PERSON' TABLE RECORD
		// if the person already exists in our system, we need to use that
		// record, if not create a new one
		$sql = 'SELECT * FROM people WHERE email='.$new['email'];
		$result = $this->doQuery($sql);
		if($this->sqlCount > 0){
			$row = mysqli_fetch_assoc($result);
			$personId = $row['id'];
		}else{
			$arr = array(
				'first_name' => $new['first_name'],
				'last_name' => $new['last_name'],
				'email' => $new['email'],
				'password'=> $password
			);	

/* ---------- ADD ENCRYPTION HERE ---------- */

			$result = $this->doInsert('people',$arr);
			$personId = $this->sqlInsertId;
		}

		// CREATE RECORD IN PATIENT TABLE
		$arr = array(
			'person_id' => $personId
		);

		$result = $this->doInsert('patients',$arr);
		$patientId = $this->sqlInsertId;

		// CREATE 'CARE_TEAM' TABLE RECORD
		$arr = array(
			'patient_id' => $patientId
		);
		$result = $this->doInsert('care_teams',$arr);
		$careTeamId = $this->sqlInsertId;

		// CREATE 'CARE_TEAM_MEMBERS' TABLE RECORD
		$sql = 'SELECT * FROM doctors WHERE health_care_org_id='.$user['health_care_org_id'];
		$result = $this->doQuery($sql);
		while($row = mysqli_fetch_assoc($result)){
			$arr = array(
				'care_team_id' => $careTeamId, // $careTeamId defined before this foreach loop starts
				'role_id' => $row['id'],
				'role' => 'doctors'
			);
			$result = $this->doInsert('care_team_members',$arr);
		}
	
// SEND MESSAGE TO INVITATION RECIPIENT

		// save the message (message to other people table) in the database
		$arr = array(
			'author_person_id' => $user['person_id'],
			'text' => $message,
			'type' => 'invitation'
		);
		$result = $this->doInsert('messages',$arr);
		$messageId = $this->sqlInsertId;

		$arr = array(
			'recipient_person_id' => $personId,
			'message_id' => $messageId
		);
		$result = $this->doInsert('message_recipients',$arr);
		$messageFromPatientId = $this->sqlInsertId;

		// insert invitation link into message
		$rootUrl = SIGNUP_APP_URL;

		$slug = $this->encryptUrlKey($messageFromPatientId);	
		$link = $rootUrl.'/invitation/verification/'.$slug;
		$firephp->log($link,'$link in /careteam/get_careteam_invite_form at line '.__LINE__);
				
		$finalMessageHTML = nl2br($message);
		$finalMessageHTML .= '<br/><br/><a href="';
		$finalMessageHTML .= $link;
		$finalMessageHTML .= '">Click on this link to learn more about Kurbi.</a>';
		
		//$finalMessageTEXT = str_replace('<br/>', '\n', $post['message']);
		$finalMessageTEXT = $message;
		$finalMessageTEXT .= 'Copy and paste this link into a browser: \n';
		$finalMessageTEXT .= $link;
		
		// send the message via email (have a link that triggers model/page that signs invitee in to 
		// Kurbi, notates them as having accepted, and brings them to their home page)
		$to = array(
			$new['email'] => $new['first_name'].' '.$new['last_name']
		); // array('receiver@domain.org', 'other@domain.org' => 'A name')
		$from = array('invitation@gokurbi.com'=>'Kurbi - MS Communication Platform');
		$subject = 'An Invitation From '.$user['first_name'].' '.$user['last_name'];
		$emailResult = send_email($finalMessageTEXT,$to,$from,$subject,$finalMessageHTML);

		if($emailResult){
			$arr = array(
				'sent_invitation' => 1
			);
			$this->doUpdate('care_team_members',$arr,$careTeamMemberId);
			$return['userFeedback'][] = 'Successfully sent invitation to '.$new['email'].'.';
		}else{
			$return['userFeedback'][] = 'Failed in sending invitation to '.$new['email'].'.';
		}
	
		if(!empty($return)){return json_encode($return);}
	}
}
