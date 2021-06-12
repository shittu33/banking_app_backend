<?php
class DbConnect{

    public function connect(){
        try {
            include_once dirname(__FILE__) . '/constants.php';
            $dsn = "mysql:host=". DB_HOST . ";dbname=". DB_NAME;
            $con =new PDO($dsn,USER_NAME,PASSWORD);
            $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
            // $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully";
        } catch (\Throwable $t) {
            throw $t;
            // echo "Connection failed! ". $e.getMessage();
        }
        return $con;
    }
}