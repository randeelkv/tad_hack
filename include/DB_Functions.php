<?php

class DB_Functions {

    private $db;

    //put your code here
    // constructor
    function __construct() {
        require_once 'DB_Connect.php';
        // connecting to database
        $this->db = new DB_Connect();
        $this->db->connect();
    }

    // destructor
    function __destruct() {
        
    }

    /**
     * Storing new user
     * returns user details
     */
    public function storeUser($name, $mobile, $password,$nic,$type) {
        //$hash = $this->hashSSHA($password);
        //$encrypted_password = $hash["encrypted"]; // encrypted password
        //$salt = $hash["salt"]; // salt

        $result = mysql_query("INSERT INTO users(usr_email,usr_type,usr_nic, usr_mobile, usr_encrypted_password, usr_created_at) VALUES( '$name','$type','$nic', '$mobile', '$password', NOW())");
        // check for successful store
        if ($result) {
            // get user details 
            $result = mysql_query("SELECT * FROM users WHERE usr_nic = \"$nic\"");
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Storing new user by lab assistant
     * returns user details
     */
    public function storeUserWeb($name, $mobile,$email, $password,$nic,$type,$speciality,$base_location,$reg_no) {
//        $hash = $this->hashSSHA($password);
//        $encrypted_password = $hash["encrypted"]; // encrypted password
//        $salt = $hash["salt"]; // salt
        $result = mysql_query("INSERT INTO users( usr_name,usr_type,usr_nic, usr_mobile, usr_email, usr_encrypted_password, usr_created_at) VALUES( '$name','$type','$nic', '$mobile','$email', '$password', NOW())");
        // check for successful store
        if ($result) {
            $user_id='';
            $query = "SELECT LAST_INSERT_ID() as usr_id";
            if ($query_run = mysql_query($query)) {
                if (mysql_num_rows($query_run) != NULL) {
                    while ($row = mysql_fetch_assoc($query_run)) {
                        $user_id = $row['usr_id'];
                    }
                }
            }
            if($type=='doctor'){
                $result = mysql_query("INSERT INTO med_doctor(doc_reg_no,doc_usr_id,doc_speciality,doc_base_location) VALUES( '$reg_no','$user_id','$speciality', '$base_location')");
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Storing new user
     * returns user details
     */
    public function storeOtherUser($name, $email, $password,$type,$nic ) {
        //$hash = $this->hashSSHA($password);
        //$encrypted_password = $hash["encrypted"]; // encrypted password
        //$salt = $hash["salt"]; // salt
		$result = mysql_query("INSERT INTO users(usr_name,usr_type,usr_nic, usr_email, usr_encrypted_password, usr_created_at) VALUES('$name','$type','$nic', '$email', '$password', NOW())");
        if ($result) {
            // get user details 
            $result = mysql_query("SELECT * FROM users WHERE usr_nic = \"$nic\"");
            echo "SELECT * FROM users WHERE usr_nic = \"$nic\"";
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Storing new user
     * returns user details
     */
    public function storeDoctorData($regNo, $userId, $speciality,$location ) {
        $result = mysql_query("INSERT INTO med_doctor(doc_reg_no, doc_usr_id, doc_speciality, doc_base_location) VALUES('$regNo','$userId','$speciality', '$location')");
        if ($result) {
            // get user details 
            $result = mysql_query("SELECT * FROM `med_doctor` WHERE usr_nic = \"$nic\"");
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Storing new report
     * returns report details
     */
    public function storeReportData($doctor, $userId, $heading,$content , $status) {
        $result = mysql_query("INSERT INTO med_report(rep_doctor, rep_user, rep_heading, rep_content, rep_status, rep_created_in) VALUES ('$doctor', '$userId', '$heading','$content' , '$status',NOW())");
        if ($result) {
            // get user details
            $result = mysql_query("SELECT * FROM `med_report` WHERE rep_user = \"$userId\" AND rep_doctor = \"doctor\" AND rep_content= \"$content\" AND rep_status = \"$status\"");
            echo "SELECT * FROM `med_report` WHERE rep_user = \"$userId\" AND rep_doctor = \"doctor\" AND rep_content= \"$content\" AND rep_status = \"$status\"";
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }

    /**
     * Storing new report
     * returns report details
     */
    public function storeInitialImageData($report_id, $report_name) {
        $result = mysql_query("INSERT INTO `med_scanned`(`scn_imagereport`, `scn_image_name`) VALUES ('$report_id', '$report_name')");
        if ($result) {
            // get user details
            $result = mysql_query("SELECT * FROM `med_scanned` WHERE scn_imagereport = \"$report_name\"");
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }
    /**
     * Storing new user
     * returns user details
     */
    public function storeSscannedData($doctor, $userId, $heading,$content , $status) {
        $result = mysql_query("INSERT INTO `med_report`(`rep_doctor`, `rep_user`, `usr_heading`, `usr_content`, `usr_status`, `usr_created_in`) VALUES ('$doctor', '$userId', '$heading','$content' , '$status',NOW())");
        if ($result) {
            // get user details
            $result = mysql_query("SELECT * FROM `med_doctor` WHERE usr_nic = \"$nic\"");
            // return user details
            return mysql_fetch_array($result);
        } else {
            return false;
        }
    }
    /**
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($email, $password) {
        $result = mysql_query("SELECT * FROM users WHERE usr_email = \"$email\"") or die(mysql_error());
        // check for result 
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            $result = mysql_fetch_array($result);
            $salt = $result['usr_salt'];
            $encrypted_password = $result['usr_encrypted_password'];
            $hash = $this->checkhashSSHA($salt, $password);
            // check for password equality
            if ($encrypted_password == $hash) {
                // user authentication details are correct
                return $result;
            }
        } else {
            // user not found
            return false;
        }
    }

    public function isDoctorExisted_ViaMail($regNo,$userId){
        $result = mysql_query("SELECT * FROM med_doctor WHERE   doc_usr_id = \"$userId\" AND  doc_reg_no = \"$regNo\"");
        // check for result 
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed 
            return true;
        } else {
            // user not existed
            return false;
        }
    }

    /**
     * Check user is existed or not via mail
     */
    public function isUserExisted_ViaMail($mail) {
        $result = mysql_query("SELECT usr_id , usr_email from users WHERE usr_email = \"$mail\"");
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed 
            return true;
        } else {
            // user not existed
            return false;
        }
    }

    /**
     * Check user is existed or not via mail
     */
    public function isUserExisted_ViaNIC($nic) {
        $result = mysql_query("SELECT usr_id , usr_nic from users WHERE usr_nic = \"$nic\"");
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed
            return true;
        } else {
            // user not existed
            return false;
        }
    }

    /**
     * Check user is existed or not via mail
     */
    public function get_userid_ViaNIC($nic) {
        $result = mysql_query("SELECT usr_id , usr_nic from users WHERE usr_nic = \"$nic\"");
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed
            $result = mysql_fetch_array($result);
            return $result;
        } else {
            // user not existed
            return false;
        }
    }

    /**
     * Check user is existed or not via sms
     */
    public function isUserExisted_ViaSMS($mobile) {
        $result = mysql_query("SELECT usr_moblie from users WHERE usr_moblie = \"$mobile\"");
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed 
            return true;
        } else {
            // user not existed
            return false;
        }
    }

    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password) {

        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }

    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password) {

        $hash = base64_encode(sha1($password . $salt, true) . $salt);

        return $hash;
    }

    /**
     * Check user is existed or not via sms
     */
    public function isreportExist($report_id) {
        $result = mysql_query("SELECT rep_id from med_report WHERE rep_id = \"$report_id\"");
        
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            // user existed 
            return true;
        } else {
            // user not existed
            return false;
        }
    }
    
    /**
     * Check user is existed or not via sms
     */
    public function setReportStatusShared($report_id) {
        $result = mysql_query("UPDATE `med_report` SET rep_status=\"payed\" WHERE rep_id = \"$report_id\" ");
            $myfile = fopen("new.txt", "w") or die("Unable to open file!");
            $txt = "UPDATE `med_report` SET rep_status=\"payed\" WHERE rep_id = \"$report_id\"";
            fwrite($myfile, $txt);
            fclose($myfile);
        if ($result) {
            // get user details
            $result_1 = mysql_query("SELECT * FROM med_report WHERE rep_id = \"$report_id\"");
            $result_1 = mysql_fetch_array($result_1);
            $person = $result_1['rep_user'];
            $result_2 = mysql_query("INSERT INTO med_share( sha_item, sha_person) VALUES ('$report_id','$person' ) ");
            if ($result_2) {
                return true;
            }else{
                return false;
            }
            // $result = mysql_query("SELECT * FROM `med_doctor` WHERE usr_nic = \"$nic\"");
            // return user details
            
        } else {
            return false;
        }
    }
    /**
     * Store images and report data
     */
    public function  storeReport($doctor_name,$user_tel,$heading,$content){
        $doctor_id = '';
        $patient_id = '';
        $report_id = '';

        //search doctor id
        $query = "SELECT doc_usr_id FROM med_doctor d, users u WHERE d.doc_usr_id=u.usr_id and u.usr_name='$doctor_name'";
        if ($query_run = mysql_query($query)) {
            if (mysql_num_rows($query_run) != NULL) {
                while ($row = mysql_fetch_assoc($query_run)) {
                    $doctor_id = $row['doc_usr_id'];
                }
            }
        }


        //search patient id
        $query = "SELECT usr_id FROM users WHERE usr_mobile='$user_tel'";
        if ($query_run = mysql_query($query)) {
            if (mysql_num_rows($query_run) != NULL) {
                while ($row = mysql_fetch_assoc($query_run)) {
                    $patient_id = $row['usr_id'];
                }
            }
        }

        $result = mysql_query("INSERT INTO med_report(rep_doctor,rep_user,rep_heading,rep_content,rep_status,rep_created_in) VALUES('$doctor_id','$patient_id','$heading','$content','waiting',now())");

        //get last id
        $query = "SELECT LAST_INSERT_ID() as report_id";
        if ($query_run = mysql_query($query)) {
            if (mysql_num_rows($query_run) != NULL) {
                while ($row = mysql_fetch_assoc($query_run)) {
                    $report_id = $row['report_id'];
                }
            }
        }

        return $report_id;

    }

    public function storeImages($scn_imagereport,$scn_image_name,$scn_image_description){
        $result = mysql_query("INSERT INTO med_scanned(scn_imagereport,scn_image_name,scn_image_description) VALUES('$scn_imagereport','$scn_image_name','$scn_image_description')");
    }

    /**
     * return content of lab reports for analysing trends
     */
    function getReportContent($city){
        $content = [];
        $query="SELECT rep_content FROM tad_med.med_report r, users u WHERE u.usr_id=r.rep_user AND u.usr_location='$city' and r.rep_heading = 'FBC'";
        if ($query_run = mysql_query($query)) {
            if (mysql_num_rows($query_run) != NULL) {
                while ($row = mysql_fetch_assoc($query_run)) {
                    $row_content = $row['rep_content'];
                    if($row_content!=''){
                        $content[] = $row_content;
                    }
                }
            }
        }
        return $content;
    }
}

?>
