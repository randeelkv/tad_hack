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
        $uuid = uniqid('', true);
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
        $uuid = uniqid('', true);
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
		$result = mysql_query("INSERT INTO users(usr_name,usr_type,usr_nic, usr_email, usr_encrypted_password, usr_salt, usr_created_at) VALUES('$name','$type','$nic', '$email', '$encrypted_password', '$salt', NOW())");
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
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($email, $password) {
        $result = mysql_query("SELECT * FROM users WHERE mobile = '$email'") or die(mysql_error());
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

    /**
     * Check user is existed or not via mail
     */
    public function isUserExisted_ViaMail($nic) {
        $result = mysql_query("SELECT usr_nic from users WHERE usr_nic = '$nic'");
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
        $result = mysql_query("SELECT usr_moblie from users WHERE usr_moblie = '$mobile'");
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
