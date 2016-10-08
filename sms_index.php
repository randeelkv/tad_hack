<?php
/**
 *   (C) Copyright 1997-2013 hSenid International (pvt) Limited.
 *   All Rights Reserved.
 *
 *   These materials are unpublished, proprietary, confidential source code of
 *   hSenid International (pvt) Limited and constitute a TRADE SECRET of hSenid
 *   International (pvt) Limited.
 *
 *   hSenid International (pvt) Limited retains all title to and intellectual
 *   property rights in these materials.
 */

include_once 'include/libs/sms/SmsReceiver.php';
include_once 'include/libs/sms/SmsSender.php';
include_once 'include/log.php';
require_once 'include/DB_Functions.php';
ini_set('error_log', 'sms-app-error.log');

//check whether it is a file upload
if (isset($_POST) and isset($_POST['file_upload'])) {

    //report vars
    $doctor_name = $_POST['doctor_name'];
    $patient_tel = $_POST['patient_tel'];
    $heading = $_POST['heading'];
    $content = '';
    if (isset($_POST['wbc']) && !empty($_POST['wbc']) && isset($_POST['rbc']) &&
        !empty($_POST['rbc']) && isset($_POST['platelets']) && !empty($_POST['platelets'])
    ) {
        $content .= 'wbc:' . $_POST['wbc'] . 'rbc:' . $_POST['rbc'] . 'platelets:' . $_POST['platelets'];
    }

    //file_upload_vars
    $valid_formats = array("jpg", "jpeg", "png", "gif", "zip", "bmp");
    $max_file_size = 1024 * 1000; //100 kb
    $path = "uploads/"; // Upload directory
    $count = 0;

    //report store in db
    $db = new DB_Functions();
    $report_id = $db->storeReport($doctor_name, $patient_tel, $heading, $content);

    //save images to server space
    if (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] == 'xray') {
        //file upload
        foreach ($_FILES['files']['name'] as $f => $name) {
            if ($_FILES['files']['error'][$f] == 4) {
                continue; // Skip file if any error found
            }
            if ($_FILES['files']['error'][$f] == 0) {
                if ($_FILES['files']['size'][$f] > $max_file_size) {
                    $message[] = "$name is too large!.";
                    continue; // Skip large files
                } elseif (!in_array(pathinfo($name, PATHINFO_EXTENSION), $valid_formats)) {
                    $message[] = "$name is not a valid format";
                    continue; // Skip invalid file formats
                } else { // No error found! Move uploaded files
                    $db->storeImages($report_id, $name, '');
                    if (move_uploaded_file($_FILES["files"]["tmp_name"][$f], $path . $name)) {
                        $count++; // Number of successfully uploaded file
                    }
                }
            }
        }
    }

    //send SMS notification when upload the blood report
    if (isset($_POST['type']) && $_POST['type'] != '') {

        switch ($_POST['type']) {
            case 'xray':
                $responseMsg = "Your Report is ready, To access in web payto report id:" . $report_id;
                break;
            case 'blood':
                if (($_POST['wbc'] < 11000 || $_POST['wbc'] > 4500) || ($_POST['rbc'] < 6.1 || $_POST['rbc'] > 4.7)
                    || ($_POST['platelets'] < 450000 || $_POST['platelets'] > 150000)
                ) {
                    $responseMsg = "Your Report is ready, To access in web payto report id:" . $report_id;
                } else {
                    $responseMsg = "your Report is not good,Please Consult a doctor.To access in web payto report id:" . $report_id;
                }
                break;
                $receiver = array('0' => $tel[1]);
                $applicationId = "APP_000001";
                $encoding = "0";
                $version = "1.0";
                $password = "password";
                $sourceAddress = "77001";
                $deliveryStatusRequest = "1";
                $charging_amount = ":15.75";
                $destinationAddresses = array("tel:94771122336");
                $binary_header = "";
                sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress,
                    $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header);
        }

        header('Location: http://localhost:8080/LabUser/home.jsp');
    }
} else if (isset($_POST) and isset($_POST['add_patient'])) {

    //register a patient to the system via web by lab assistant
    $name = $_POST['username'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $nic = $_POST['nic'];
    $type = 'user';
    $password = generateRandomString(); //assign random password to the user account.

    //new user store in db
    $db = new DB_Functions();
    $result = $db->storeUserWeb($name, $mobile, $email, $password, $nic, $type, '', '', '');

    if ($result) {
        header('Location: http://localhost:8080/LabUser/addpatient.jsp');
    }

} else if (isset($_POST) and isset($_POST['register'])) {

    //sign up process in web
    $name = $_POST['username'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $nic = $_POST['nic'];
    $type = $_POST['type'];
    $password = $_POST['password'];
    $speciality = '';
    $base_location = '';
    $reg_no = '';

    //check whether doctor registraion
    if ($_POST['type'] == 'doctor') {
        $speciality = $_POST['speciality'];
        $base_location = $_POST['based_location'];
        $reg_no = $_POST['reg_no'];
    }

    //new user store in db
    $db = new DB_Functions();
    $result = $db->storeUserWeb($name, $mobile, $email, $password, $nic, $type, $speciality, $base_location, $reg_no);

    if ($result) {
        header('Location: http://localhost:8080/index.jsp');
    }

} else {

    try {
        $receiver = new SmsReceiver(); // Create the Receiver object
        $content = $receiver->getMessage(); // get the message content
        $address = $receiver->getAddress(); // get the sender's address
        $requestId = $receiver->getRequestID(); // get the request ID
        $applicationId = $receiver->getApplicationId(); // get application ID
        $encoding = $receiver->getEncoding(); // get the encoding value
        $version = $receiver->getVersion(); // get the version
        logFile("[ content=$content, address=$address, requestId=$requestId,
         applicationId=$applicationId, encoding=$encoding, version=$version ]");
        $responseMsg;
        //your logic goes here......
        $split = explode(' ', $content);
        $split[0] = strtolower($split[0]);
        $tel = explode(':', $address);

        if ($split[0] == 'reg') {
            //for Registrations it should come as REG <name> <pin> <nic>
            // validate number name in here
            if (sizeof($split) == 4) {
                // check user existes
                $name = $split[1];
                $mobile = $tel[1];
                $type = 'user';
                $nic = $split[3];
                $password = $split[2];
                $responseMsg = $tel[1];
                $db = new DB_Functions();
                // check if user is already existed
                if ($db->isUserExisted_ViaMail($name)) {
                    // rending responce when already user  is there by the nic
                    $responseMsg = "You are already a User";
                } else {
                    // store user
                    $user = $db->storeUser($name, $mobile, $password, $nic, $type);
                    if ($user) {
                        //sending responce when sucess
                        $responseMsg = "You has successfully Registered";
                    } else {
                        //if the user query failed wile running
                        $responseMsg = "Invalid Request";
                    }
                }
            } else {
                $responseMsg = "invalid Message format";
            }
            // $responseMsg = "this is the Registration space";
        } elseif ($split[0] == 'get') {
            $responseMsg = "this is the getspace";
        } elseif ($split[0] == 'trend') {
            //TODO: LBS integration

            $trends=[];
            $lat = 6.051297;
            $lan = 80.208906;
            $city = '';

            //Send request and receive json data by address
            $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($lat) . ',' . trim($lan) . '&sensor=false&key=AIzaSyAg0p1PaRCdTffQ2TfjM094OYkPkyXukz0');
            $output = json_decode($geocodeFromLatLong);
            $status = $output->status;
            //Get city from json data
            if($status=="OK"){
                $address_components = $output->results[1]->address_components;
                foreach ($address_components as $component){
                    $types = $component->types;
                    if($types[0]=="administrative_area_level_2"){
                        $city = $component->long_name;
                        break;
                    }
                }
            }

            //retrieve contents from db table
            $city = strtoupper($city);
            $contents = $db->getReportContent($city);
            $dengue = 0;$bacterial_viral_infection=0;$parasitic_allergic=0;

            foreach ($contents as $content){
                //split content into fields
                $data = explode(",", $content);

                $abnormals = ['Haemoglobin'=>'Normal','WBC'=>'Normal','Platelets'=>'Normal','Eosinophils'=>'Normal','Lymphocytes'=>'Normal'];

                //searching for abnormal data of the fields
                foreach($data as $field){
                    $key_value_pair = explode(":",$field);
                    switch($key_value_pair[0]){
                        case 'Haemoglobin':
                            ($key_value_pair[1]>180)?$abnormals['Haemoglobin'] = 'High':'';
                            ($key_value_pair[1]<135)?$abnormals['Haemoglobin']= 'Low':'';
                            break;
                        case 'WBC':
                            ($key_value_pair[1]>11.00)?$abnormals['WBC']= 'High':'';
                            ($key_value_pair[1]<4.00)?$abnormals['WBC']= 'Low':'';
                            break;
                        case 'Platelets':
                            ($key_value_pair[1]>400)?$abnormals['Platelets'] = 'High':'';
                            ($key_value_pair[1]<150)?$abnormals['Platelets'] = 'Low':'';
                            break;
                        case 'Eosinophils':
                            ($key_value_pair[1]>0.4)?$abnormals['Eosinophils'] = 'High':'';
                            ($key_value_pair[1]<0.04)?$abnormals['Eosinophils'] = 'Low':'';
                            break;
                        case 'Lymphocytes':
                            ($key_value_pair[1]>4.5)?$abnormals['Lymphocytes'] = 'High':'';
                            ($key_value_pair[1]<1.0)?$abnormals['Lymphocytes'] = 'Low':'';
                            break;
                    }
                }

                if($abnormals['WBC']=='Low' && $abnormals['Platelets']=='Low'){
                    $dengue++;
                }else if($abnormals['Lymphocytes']=='High'){
                    $bacterial_viral_infection++;
                }else if($abnormals['Eosinophils']=='High'){
                    $parasitic_allergic++;
                }
            }

            //calculate percentage
            $dengue_percentage = round(($dengue/count($contents))*100,2);
            $bacterial_viral_infection_percentage = round(($bacterial_viral_infection/count($contents))*100,2);
            $parasitic_allergic_percentage = round(($parasitic_allergic/count($contents))*100,2);

            $responseMsg = 'Dengue: '.$dengue_percentage.'%, '.'Bacterial or Viral infections: '.$bacterial_viral_infection_percentage.'%, '.'Parasitic Allergic: '.$parasitic_allergic_percentage.'%';

        }

        //$responseMsg = bmiLogicHere($split);
        //sending a one message
        $receiver = array('0' => $tel[1]);
        $applicationId = "APP_000001";
        $encoding = "0";
        $version = "1.0";
        $password = "password";
        $sourceAddress = "77001";
        $deliveryStatusRequest = "1";
        $charging_amount = ":15.75";
        $destinationAddresses = $address;
        $binary_header = "";
        sendSms($responseMsg, $destinationAddresses, $password, $applicationId,
            $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header);

    } catch (SmsException $ex) {
        //throws when failed sending or receiving the sms
        error_log("ERROR: {$ex->getStatusCode()} | {$ex->getStatusMessage()}");
    }

}

function sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress,
                 $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header)
{
    // Create the sender object server url
    $sender = new SmsSender("https://localhost:7443/sms/send");
    $res = $sender->sms($responseMsg, $destinationAddresses, $password, $applicationId,
        $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header);
}

function registerUser($content)
{
    require_once 'include/DB_Functions.php';
}

function getReportNo($content)
{
    # code...
}

//to generate random string for password when adding a patient by lab assistant
function generateRandomString($length = 8)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

?>