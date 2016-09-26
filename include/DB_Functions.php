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
        $result = mysql_query("INSERT INTO users( usr_name,usr_type,usr_nic, usr_mobile, usr_encrypted_password, usr_salt, usr_created_at) VALUES( '$name','$type','$nic', '$mobile', '$encrypted_password', '$salt', NOW())");
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
     * Storing new user
     * returns user details
     */
    public function storeOtherUser($name, $email, $password,$type,$nic ) {
        //$hash = $this->hashSSHA($password);
        //$encrypted_password = $hash["encrypted"]; // encrypted password
        //$salt = $hash["salt"]; // salt
		$result = mysql_query("INSERT INTO users(usr_name,usr_type,usr_nic, usr_email, usr_encrypted_password, usr_salt, usr_created_at) VALUES('$name','$type','$nic', '$email', '$encrypted_password', '$salt', NOW())");
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
}

?>
