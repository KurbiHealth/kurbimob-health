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
		global $firephp;
		$post = $this->getPost();

		$message = $post['message'];
		unset($post['message']);
		unset($post['Send']);
		
		$members = array();
		$return = '';

		$user = currentUser();

		// $post will have 3 types of keys: [id's], [newX], [add]. The first type are care team members that are already 
		// in the database. We may want to add edit functionality, but for now we're going to pull out the [newX] and [add] keys
		// to see if they have values, which then get saved to the database.
		
		// pull out values
		$firephp->log($post,'$post, line '.__LINE__);
		
		// the "new" form entry tends to have the placeholder values, unset it if that happens
		if($post['new']['first_name'] == 'First Name' && $post['new']['last_name'] == 'Last Name' && $post['new']['email'] == 'Email Address'){
			unset($post['new']);
		}
		
		// pull out form entries with actual values
		foreach($post as $key => $entry){
			if(preg_match('`blank(.*)`',$key) || $key == 'new'){
				if($entry['first_name'] != '' || $entry['last_name'] != '' || $entry['email'] != ''){
					$members[] = $entry;
				}
			}
		}
		
		// save new members
		$firephp->log($members,'$members, line '.__LINE__);

		// check to see if there is a record in "care_teams" for this patient (if there isn't one, create it)
		$sql = 'SELECT * FROM care_teams WHERE patient_id='.$user['id'];
		$result = $this->doQuery($sql);
		if($this->sqlCount > 0){
			$row = mysqli_fetch_assoc($result);
			$careTeamId = $row['id']; // this ID value used in foreach loop when inserting new records to "care_team_members" table
		}else{
			// create "care_teams" record for this patient
			$arr = array(
				'patient_id' => $user['id'],
			);
			$result = $this->doInsert('care_teams',$arr);
			$careTeamId = $this->sqlInsertId; // this ID value used in foreach loop when inserting new records to "care_team_members" table
		}

		foreach($members as $new){
			$type = $new['type'];
			$user = currentUser();
			$userPatientId = $user['id'];
			$userPersonId = $user['person_id'];

			// save the lines to the database as invited but not yet accepted
			// 1=General Practitioner, 2=Neurologist, 3=Nurse
			// 4=Spouse, 5=Child, 6=Parent, 7=Sibling, 8=Friend
			if($type == 1 || $type == 2 || $type == 3){
				$table = 'doctors';
				$role = 'doctors'; 
			}
			if($type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 8){
				$table = 'friendsfamily';
				$role = 'friendsfamily';
			}
			
			// CHECK WHETHER THIS EMAIL ALREADY RECEIVED AN INVITATION
			// i.e. is already in care team
			$sql = 'SELECT * FROM care_team_members JOIN care_team ON (care_team.id=care_team_members.care_team_id) ';
			$sql .= 'patient ON (care_team.patient_id=patients.id) people ON (patient.person_id=people.id) WHERE people.email=\''.$new['email'].'\'';
			$firephp->log($sql,'$sql at line '.__LINE__);
			$result = $this->doQuery($sql);
			if($this->sqlCount > 0){
				// send a message to user
				$return['userFeedback'][] = 'Unable to send an invitation to '.$new['email'].' as you already sent an invitation to that email address.';
				continue;
			}
			
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
	
			// CREATE RECORD IN 'DOCTOR' OR 'FRIENDSFAMILY' TABLE (if needed)
			$arr = array(
				'person_id' => $personId
			);
			if($table == 'doctors')
				$arr['specialty'] = $new['type'];
			if($table == 'friendsfamily')
				$arr['type'] = $new['type'];

			$result = $this->doInsert($table,$arr);
			$roleId = $this->sqlInsertId;

			// CREATE 'CARE_TEAM_MEMBERS' TABLE RECORD
			$arr = array(
				'care_team_id' => $careTeamId, // $careTeamId defined before this foreach loop starts
				'role_id' => $roleId,
				'role' => $table
			);
			$result = $this->doInsert('care_team_members',$arr);
			$careTeamMemberId = $this->sqlInsertId;
		
			// SEND MESSAGE TO INVITATION RECIPIENT
	
			// save the message (message to other people table) in the database
			$arr = array(
				'author_person_id' => $userPersonId,
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
		} // end foreach($members)
		
		if(!empty($return)){return json_encode($return);}
	}

}
