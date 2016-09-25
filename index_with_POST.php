<?php
/**
 * File to handle all API requests
 * Accepts GET and POST
 *
 * Each request will be identified by TAG
 * Response will be JSON data
 /**
 * check for POST request
 */
if (isset($_POST['tag']) && $_POST['tag'] != '') {
	// get tag
	$tag = $_POST['tag'];
	// include db handler
	require_once 'include/DB_Functions.php';
	$db = new DB_Functions();
	// response Array
	$response = array("tag" => $tag, "success" => 0, "error" => 0);
	// check for tag type
	if ($tag == 'login') {
		// Request type is check Login
		$email = $_POST['email'];
		$password = $_POST['password'];
		// check for user
		$user = $db->getUserByEmailAndPassword($email, $password);
		if ($user != false) {
			// user found
			// echo json with success = 1
			$response["success"] = 1;
			// $response["uid"] = $user["unique_id"];
			$response["user"]["name"] = $user["name"];
			$response["user"]["email"] = $user["email"];
			$response["user"]["created_at"] = $user["created_at"];
			$response["user"]["updated_at"] = $user["updated_at"];
			echo json_encode($response);
		} else {
			// user not found
			// echo json with error = 1
			$response["error"] = 1;
			$response["error_msg"] = "Incorrect email or password!";
			echo json_encode($response);
		}
	} else if ($tag == 'register') {
	    //push error if the input fields are not correct
		if((isset($_POST['type']) && $_POST['type']!= '')){
			$type = $_POST['type'];
			if($type == 'user'){
				//check user existes
				$name = $_POST['name'];
				$mobile = $_POST['mobile'];
				$type = $_POST['type'];
				$nic = $_POST['nic'];



		            // check if user is already existed
		            if ($db->isUserExisted_ViaMail($nic)) {
		                // user is already existed - error response
		                $response["error"] = 2;
		                $response["error_msg"] = "User already existed";
		                echo json_encode($response);
		            } else {
		                // store user
		                $user = $db->storeUser($name, $mobile, $password,$nic,$type);
		                if ($user) {
		                    // user stored successfully
		                    $response["success"] = 1;
		                    $response["uid"] = $user["nic"];
		                    $response["user"]["name"] = $user["name"];
		                    // $response["user"]["email"] = $user["email"];
		                    $response["user"]["created_at"] = $user["created_at"];
		                    $response["user"]["updated_at"] = $user["updated_at"];
		                    echo json_encode($response);
							include_once 'include/libs/sms/SmsReceiver.php';
							include_once 'include/libs/sms/SmsSender.php';
							include_once 'include/log.php';
							ini_set('error_log', 'sms-app-error.log');
							try {
								$responseMsg = "You has successfully Registered";
								echo $responseMsg;
								$applicationId = "APP_000001";
							 	$encoding = "0";
							 	$version =  "1.0";
							    $password = "password";
							    $sourceAddress = "77000";
							    $deliveryStatusRequest = "1";
							    $charging_amount = ":15.75";
							    $destinationAddresses = array("tel:94771122336");
							    $binary_header = "";
							    sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header);
							} catch (SmsException $ex) {
								error_log("ERROR: {$ex->getStatusCode()} | {$ex->getStatusMessage()}");
							}
							echo "get the SMS Registration Process here";
		                } else {
		                    // user failed to store
		                    $response["error"] = 1;
		                    $response["error_msg"] = "Invalid Request";
		                    echo json_encode($response);
		                }
		            }
			}else{
				if((isset($_POST['name']) && $_POST['name']!= '') || (isset($_POST['email']) && $_POST['email']!= '') || (isset($_POST['password']) && $_POST['password']!= ''))
			    {
		            // Request type is Register new user
		            $name = $_POST['name'];
		            $email = $_POST['email'];
		            $password = $_POST['password'];
		            $type = $_POST['type'];
		            $nic = $_POST['nic'];


		            // check if user is already existed
		            if ($db->isUserExisted_ViaMail($nic)) {
		                // user is already existed - error response
		                $response["error"] = 2;
		                $response["error_msg"] = "User already exist";
		                echo json_encode($response);
		            } else {
		                // store user
		                if($type == 'doctor'){
		                	$regNo = $_POST["regNo"];
		                	$speciality = $_POST["speciality"];
		                	$location = $_POST["location"];
		                	if($db->isDoctorExisted_ViaMail($regNo,$userId)){
		                			// user is already existed - error response
					                $response["error"] = 2;
					                $response["error_msg"] = "Doctor data already exist";
					                echo json_encode($response);
		                		}else{
		                			$user = $db->storeOtherUser($name, $email, $password, $type , $nic);
					                if ($user) {
			                			$userId = $user["usr_id"];
				                		$doctor = $db->storeDoctorData($regNo, $userId, $speciality,$location );
		                				if ($doctor) {
						                    // user stored successfully
						                    $response["success"] = 1;
						                    $response["uid"] = $user["nic"];
						                    $response["user"]["name"] = $user["name"];
						                    // $response["user"]["email"] = $user["email"];
						                    $response["user"]["created_at"] = $user["created_at"];
						                    $response["user"]["updated_at"] = $user["updated_at"];
						                    echo json_encode($response);
					                	
						                } else {
						                    // user failed to store
						                    $response["error"] = 1;
						                    $response["error_msg"] = "Invalid Request";
						                    echo json_encode($response);
						                }
			                		}
		                	}
		                }else{
			                $user = $db->storeOtherUser($name, $email, $password, $type , $nic);
			                if ($user) {
			                	
				                    // user stored successfully
				                    $response["success"] = 1;
				                    $response["uid"] = $user["nic"];
				                    $response["user"]["name"] = $user["name"];
				                    // $response["user"]["email"] = $user["email"];
				                    $response["user"]["created_at"] = $user["created_at"];
				                    $response["user"]["updated_at"] = $user["updated_at"];
				                    echo json_encode($response);
			                	
			                } else {
			                    // user failed to store
			                    $response["error"] = 1;
			                    $response["error_msg"] = "Invalid Request";
			                    echo json_encode($response);
			                }
		            	}
		            }
		        }
		        else {
		            $response["error"] = 1;
		            $response["error_msg"] = "Invalid Request";
		            echo json_encode($response);
		        }

			}
		    
        }
	} else if($tag == 'report'){
		//get the count of the array to get howmany images needed. 
		// $length = sizeof($_POST);
		// if()

	}else{
		echo "Invalid Request";
	}
} else {
	echo "Access Denied";
}


function sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header){
    // Create the sender object server url
    $sender = new SmsSender("https://localhost:7443/sms/send");
    $res = $sender->sms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header);
}
?>