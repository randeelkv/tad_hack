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
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
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
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
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
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($email, $password) {
        $result = mysql_query("SELECT * FROM users WHERE mobile = \"$email\"") or die(mysql_error());
        // check for result 
        $no_of_rows = mysql_num_rows($result);
        if ($no_of_rows > 0) {
            $result = mysql_fetch_array($result);
            $salt = $result['salt'];
            $encrypted_password = $result['encrypted_password'];
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
        echo $no_of_rows;
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
    public function isUserExisted_ViaMail($nic) {
        $result = mysql_query("SELECT usr_nic from users WHERE usr_nic = \"$nic\"");
        $no_of_rows = mysql_num_rows($result);
        echo $no_of_rows;
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
}

?>
