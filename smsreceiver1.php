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
include_once 'include/TAP-conf.php';
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


                if ($tel[1].length == 10) {
                    //remove the first character of the text
                    $tel[1][0]="";
                }

                $receiver = array('0' => 'tel:94'.$tel[1]);
                $applicationId = $APP_ID;
                $encoding = $ENCORRD;
                $version = $VERSION;
                $password = $PASSWORD;
                $sourceAddress = $SOURSE_ADDRESS;
                $deliveryStatusRequest = $DELIVERY_STATUS_REQ;
                $charging_amount = $CHARGING_AMMOUNT;
                $destinationAddresses = $receiver;//array("tel:94771122336");
                $binary_header = "";

                sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress,
                    $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header,$SERCER_URL_SMS );

                break;

        }

        header('Location: http://localhost:8080/LabUser/home.jsp');

    }

} else if (isset($_POST) and isset($_POST['add_patient'])) {
    //register a patient to the system via web by lab assistant
    $name = $_POST['username'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $nic = $_POST['nic'];
    $location = $_POST['location'];
    $type = 'user';
    $password = generateRandomString(); //assign random password to the user account.

    //new user store in db
    $db = new DB_Functions();
    $result = $db->storeUserWeb($name, $mobile, $email, $password, $nic, $type, '', $location, '');

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

    $address = $mobile;
    if ($address.length == 10) {
        $address[0]="";
    }

    $address = "tel:94".$address;
    $SUB_RESULT = addSubscription($address,$APP_ID,$PASSWORD,$VERSION,$SERVER_URL_SUBSCRIBE);

    if($SUB_RESULT){
        //new user store in db
        $db = new DB_Functions();
        $result = $db->storeUserWeb($name, $mobile, $email, $password, $nic, $type, $speciality, $base_location, $reg_no);
    }

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
        $responseMsg;

        logFile("[ content=$content, address=$address, requestId=$requestId,
         applicationId=$applicationId, encoding=$encoding, version=$version ]");

        //your logic goes here......
        $split = explode(' ', $content);
        $split[1] = strtolower($split[1]);
        $tel = explode(':', $address);
        error_log($split[1].' '.$split[0]);

        if ($split[1] == 'reg') {
            //for Registrations it should come as REG <name> <pin> <nic>
            // validate number name in here
             
            if (sizeof($split) == 5) {
                // check user existes
                $name = $split[2];
                $mobile = $tel[1];
                $type = 'user';
                $nic = $split[4];
                $password = $split[3];
                $responseMsg = $tel[1];
                $db = new DB_Functions();
                // error_log("working fine till 215");
                // check if user is already existed
                if ($db->isUserExisted_ViaMail($name)) {
                    // rending responce when already user  is there by the nic
                    $responseMsg = "You are already a User";
                } else {
                    $address = "tel:".$tel[1];

                    // subscribe the user
                    $SUB_RESULT = addSubscription($address,$APP_ID,$PASSWORD,$VERSION,$SERVER_URL_SUBSCRIBE);

                    // error_log($city);
                    if($SUB_RESULT){
                        //store user
                        $lat_lon = getLocationFromAPI($LBS_QUERY_SERVER_URL,$APP_ID,$PASSWORD,$address,$SERVICE_TYPE,$FRESHNESS,$HORIZONTAL_ACCURACY,$RESPONSE_TIME);
                        // error_log($lat_lon);
                        $split_lat_lon = explode('#', $lat_lon);
                        $latitude = $split_lat_lon[0];
                        $longatude = $split_lat_lon[1];
                        $city = getCity($latitude,$longatude);
                        // error_log($city);
                        $user = $db->storeUser($name, $mobile, $password, $nic, $type, $city);

                        // error_log($user);

                        if ($user) {
                            //sending responce when sucess
                            $responseMsg = "You has successfully Registered";
                        } else {
                            //if the user query failed wile running
                            $responseMsg = "Invalid Request";
                        }

                    }else{
                        $responseMsg = "Subscription Unsucessful";
                    }
                }

            } else {
                $responseMsg = "invalid Message format";
            }

        } elseif ($split[1] == 'pay') {
            $responseMsg = getReportPayment($content,$APP_ID,$PASSWORD,$EXTERNAL_TRX_ID,$PAYMENT_INSTRUMENT_NAME,$ACCOUNT_ID,$CURRENCY,$SEVER_URL_DIRECT_DEBIT_SENDER);

        } elseif ($split[1] == 'get') {
            // $subscriberId = "tel:94771122336";
            // $lat_lon = getLocationFromAPI($LBS_QUERY_SERVER_URL,$APP_ID,$PASSWORD,$address,$SERVICE_TYPE,$FRESHNESS,$HORIZONTAL_ACCURACY,$RESPONSE_TIME);
            // error_log($lat_lon);
            // $split_lat_lon = explode('#', $lat_lon);
            // $latitude = $split_lat_lon[0];
            // $longatude = $split_lat_lon[1];
            // $city = getCity($latitude,$longatude);
            // error_log($city);
            $responseMsg = "this is the getspace";

        }elseif ($split[1] == 'trend') {
            
            $db = new DB_Functions();
            $trends=[];
            $city = getCity();//get the current location of user
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
        $applicationId = $APP_ID;
        $encoding = $ENCORRD;
        $version = $VERSION;
        $password = $PASSWORD;
        $sourceAddress = $SOURSE_ADDRESS;
        $deliveryStatusRequest = $DELIVERY_STATUS_REQ;
        $charging_amount = $CHARGING_AMMOUNT;
        $destinationAddresses = $address;
        $binary_header = "";

        sendSms($responseMsg, $destinationAddresses, $password, $applicationId,
            $sourceAddress, $deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header,$SERCER_URL_SMS );

    } catch (SmsException $ex) {
        //throws when failed sending or receiving the sms
        error_log("ERROR: {$ex->getStatusCode()} | {$ex->getStatusMessage()}");

    }
}
function sendSms($responseMsg, $destinationAddresses, $password, $applicationId, $sourceAddress,$deliveryStatusRequest, $charging_amount, $encoding, $version, $binary_header,$SERCER_URL_SMS )
{
    // Create the sender object server url
    $sender = new SmsSender($SERCER_URL_SMS);
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

function getReportPayment($content,$APP_ID,$PASSWORD,$EXTERNAL_TRX_ID,$PAYMENT_INSTRUMENT_NAME,$ACCOUNT_ID,$CURRENCY,$SEVER_URL_DIRECT_DEBIT_SENDER)
{
    // error_log('test');
    $split = explode(' ', $content);
    $db = new DB_Functions();

    if($db->isreportExist($split[1])){
        include_once 'include/libs/cass/DirectDebitSender.php';
        include_once 'include/libs/cass/KLogger.php';

        ini_set('error_log', 'query-balance-error.log');
        $logger = new KLogger ( "cass_debug.log" , KLogger::DEBUG );

        // //Get data from configuration file
        $applicationId = $APP_ID;
        $password = $PASSWORD;
        $externalTrxId = $EXTERNAL_TRX_ID;
        $subscriberId = '94771122336';
        $paymentInstrumentName = $PAYMENT_INSTRUMENT_NAME;
        $accountId = $ACCOUNT_ID;
        $currency = $CURRENCY;
        $amount = 50;

        $logger->LogDebug("DirectDebitHandler : Received msisdn=".$subscriberId);
        $logger->LogDebug("DirectDebitHandler : Received amount=".$amount);

        // Create the sender object server url
        // ================================================

        try {
            $sender = new DirectDebitSender($SEVER_URL_DIRECT_DEBIT_SENDER);
            $jsonResponse = $sender->cass($applicationId, $password, $externalTrxId, $subscriberId, $paymentInstrumentName, $accountId, $currency, $amount);
            // ==================================================
            //     //update the imagestatus and share

            $db->setReportStatusShared($split[1]);
            $responseMsg = "Thank You for the payment, Now you can access the report";
            // ==========================================================

        } catch (CassException $ex) {
            // $myfile = fopen("new.txt", "w") or die("Unable to open file!");
            //      $txt = "$ex";
            //     fwrite($myfile, $txt);
            //     fclose($myfile);
            error_log("CASS direct-debit ERROR: {$ex->getStatusCode()} | {$ex->getStatusMessage()}");
            $responseMsg = "Sorry, Error While Processing ";
        }
        // ===================================================

    }else{
        $responseMsg = "Invalid Req No, Please try again";

    }

    return $responseMsg;
}
function addSubscription($tel,$APP_ID,$PASSWORD,$VERSION,$SERVER_URL_SUBSCRIBE)
{
    // error_log(json_encode($response));
    $url = $SERVER_URL_SUBSCRIBE;
    // use key 'http' even if you send the request to https://...
    $response["applicationId"] = $APP_ID;
    $response["password"] = $PASSWORD;
    $response["version"] = $VERSION;
    $response["action"] = "1";
    $response["subscriberId"] = $tel;

    // echo json_encode($response);
    error_log(json_encode($response));
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($response)
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    error_log($result);
    if ($result === false) { 
        return false; 
    }else{
        // if ($result.statusCode = 'S1000' || $result.statusCode = 'E1351') {
            return true;
        // }else false; 
    }

// var_dump($result);
}

function getCity($latitude,$longatude){
    //TODO: LBS integration
    $lat = $latitude;
    $lan = $longatude;
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
    return $city;
}

function getLocationFromAPI($LBS_QUERY_SERVER_URL,$APP_ID,$PASSWORD,$subscriberId,$SERVICE_TYPE,$FRESHNESS,$HORIZONTAL_ACCURACY,$RESPONSE_TIME)
{
    include_once 'include/libs/lbs/LbsClient.php';
    include_once 'include/libs/lbs/LbsRequest.php';
    include_once 'include/libs/lbs/LbsResponse.php';
    include_once 'include/libs/lbs/KLogger.php';

    $log = new KLogger ( "lbs_debug.log" , KLogger::DEBUG );

    // $subscriberId = "tel:".$_POST['msisdn'];
    // $log->LogDebug("Received msisdn = ".$subscriberId);

    $request = new LbsRequest($LBS_QUERY_SERVER_URL);
    $request->setAppId($APP_ID);
    $request->setAppPassword($PASSWORD);
    $request->setSubscriberId($subscriberId);
    $request->setServiceType($SERVICE_TYPE);
    $request->setFreshness($FRESHNESS);
    $request->setHorizontalAccuracy($HORIZONTAL_ACCURACY);
    $request->setResponseTime($RESPONSE_TIME);

    function getModifiedTimeStamp($timeStamp){
        try {
            $date= new DateTime($timeStamp,new DateTimeZone('Asia/Colombo'));
        } catch (Exception $e) {
            echo $e->getMessage();
            exit(1);
        }
        return $date->format('Y-m-d H:i:s');
    }

    $lbsClient = new LbsClient();
    $lbsResponse = new LbsResponse($lbsClient->getResponse($request));
    $lbsResponse->setTimeStamp(getModifiedTimeStamp($lbsResponse->getTimeStamp()));//Changing the timestamp format. Ex: from '2013-03-15T17:25:51+05:30' to '2013-03-15 17:25:51'
    $log->LogDebug("Lbs response:".$lbsResponse->toJson());
    // $responce = $lbsResponse->longitude . '#' . $lbsResponse->latitude;
    $lat = $lbsResponse->latitude;
    $lon = $lbsResponse->longitude;
    $responce = $lat . '#'. $lon;
    return   $responce;
}
?>