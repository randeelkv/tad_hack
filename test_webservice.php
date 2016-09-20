<?php
/**
* This is just a test php file. You can try your web service using it.
* @author Seval U.
* Date: 24.12.2013
*/

$webservice_url = "http://localhost/php_login_webservice"; //change this with your own server url address
//register a user:

$jsonObj = file_get_contents($webservice_url.'/?tag=register&name=seval&email=seval@test.com&password=12345');
$final_res =json_decode($jsonObj, true) ;

if($final_res['success']==0){
	echo $final_res['error_msg'];
} else if ($final_res['success']==1){
	$uid = $final_res["uid"];
	$user = $final_res["user"];
	$name = $user["name"];
	$email = $user["email"];
	$created_at = $user["created_at"];
	$updated_at = $user["updated_at"];
	echo "Successfully added user:<br />Name: ".$name."<br />Email: ".$email."<br />Created at: ".$created_at."<br />";
}

echo "<br /><br />";

//login a user:

$jsonObj = file_get_contents($webservice_url.'/?tag=login&email=seval@test.com&password=12345');
$final_res =json_decode($jsonObj, true) ;

if($final_res['success']==0){
	echo $final_res['error_msg'];
} else if ($final_res['success']==1){
	$uid = $final_res["uid"];
	$user = $final_res["user"];
	$name = $user["name"];
	$email = $user["email"];
	$created_at = $user["created_at"];
	$updated_at = $user["updated_at"];
	echo "Successfully logined user:<br />Name: ".$name."<br />Email: ".$email."<br />Created at: ".$created_at."<br />";
}



?>