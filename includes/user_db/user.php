<?php

class User {
    public static $T_USER = "user";
    public static $C_ID = "id";
    public static $C_NAME = "name";
    public static $C_ACT_NO = "accountNumber";
    public static $C_PASSWORD = "password";
    public static $C_BALANCE = "balance";
    public static $C_CREATED = "created";
    public static $C_ROLE = "role";
    
    public $id; 
    public $accountNumber; 
    public $password ="hidden"; 
    public $name;
    public $balance;
    public $created;
    public $role;


    public function __construct(){
    }

    public function setPassword($password){
        $this->password = $password;
    }
    
    public static function fromArray(Array $user_assoc){
        $user = new User();
        $user->id=$user_assoc[self::$C_ID]??null;
        $user->accountNumber=$user_assoc[self::$C_ACT_NO]??null;
        $user->password=$user_assoc[self::$C_PASSWORD]??null;
        $user->name=$user_assoc[self::$C_NAME]??null;
        $user->created=$user_assoc[self::$C_CREATED]??null;
        $user->balance=$user_assoc[self::$C_BALANCE]??null;
        $user->role=$user_assoc[self::$C_ROLE]??null;
        return $user;
    }
    
    public static function init($id,$accountNumber, $password,$name,$created){
        $user = new User();
        $user->id=$id;
        $user->accountNumber=$accountNumber;
        $user->password=$password;
        $user->name=$name;
        $user->created=$created;
        $user->balance=$balance;
        $user->role=$role;
        return $user;
    }

    public static function toArray(User $user){
        $arr = Array();
        if($user->id!=null)
        $arr[self::$C_ID]=$user->id;
        if($user->accountNumber!=null)
        $arr[self::$C_ACT_NO]=$user->accountNumber;
        if($user->password!=null) 
        ($arr[self::$C_PASSWORD]=$user->password);
        if($user->name!=null)
        $arr[self::$C_NAME]=$user->name;
        if($user->created!=null)
        $arr[self::$C_CREATED]=$user->created;
        if($user->balance!=null)
        $arr[self::$C_BALANCE]=$user->balance;
        if($user->role!=null)
        $arr[self::$C_ROLE]=$user->role;
        return $arr;
    }

    public static function toJson(User $user){
        return json_encode(self::toArray($user));
    }

    public function print(){
        // echo "okay";
        echo "id is $this->id,accountNumber is $this->accountNumber, name is $this->name
            , password is $this->password, balance is $this->balance
            , and created is $this->created , and role is $this->role";
    }
}

// class User {
//     public static $T_USER = "users";
//     public static $C_ID = "id";
//     public static $C_NAME = "name";
//     public static $C_MAIL = "accountNumber";
//     public static $C_PASSWORD = "password";
//     public static $C_CREATED = "created";
    
//     public $id; 
//     public $accountNumber; 
//     public $password; 
//     public $name;
//     public $created;

//     // public function __construct($accountNumber, $password,$name,$created){
//     //     $this->accountNumber=$accountNumber;
//     //     $this->password=$password;
//     //     $this->name=$name;
//     //     $this->created=$created;
//     // }

//     public function __construct(){
//     }

//     public function setPassword($password){
//         $this->password = $password;
//     }
    
//     public static function fromArray(Array $user_assoc){
//         $user = new User();
//         $user->id=$user_assoc[self::$C_ID]??null;
//         $user->accountNumber=$user_assoc[self::$C_ACT_NO]??null;
//         $user->password=$user_assoc[self::$C_PASSWORD]??null;
//         $user->name=$user_assoc[self::$C_NAME]??null;
//         $user->created=$user_assoc[self::$C_CREATED]??null;
//         return $user;
//         // return new User($user_assoc[self::$C_ACT_NO],$user_assoc[self::$C_PASSWORD],
//         //                 $user_assoc[self::$C_NAME],$user_assoc[self::$C_CREATED]);
//     }
    
//     public static function init($id,$accountNumber, $password,$name,$created){
//         $user = new User();
//         $user->id=$id;
//         $user->accountNumber=$accountNumber;
//         $user->password=$password;
//         $user->name=$name;
//         $user->created=$created;
//         return $user;
//     }

//     public static function toArray(User $user){
//         $arr = Array();
//         if($user->id!=null)
//         $arr[self::$C_ID]=$user->id;
//         if($user->accountNumber!=null)
//         $arr[self::$C_MAIL]=$user->accountNumber;
//         if($user->password!=null) 
//         ($arr[self::$C_PASSWORD]=$user->password);
//         if($user->name!=null)
//         $arr[self::$C_NAME]=$user->name;
//         if($user->created!=null)
//         $arr[self::$C_CREATED]=$user->created;
//         return $arr;
//     }

//     public static function toJson(User $user){
//         return json_encode(self::toArray($user));
//     }

//     public function print(){
//         // echo "okay";
//         echo "accountNumber is $this->accountNumber, name is $this->name, password is $this->password, and created is $this->created";
//     }
// }