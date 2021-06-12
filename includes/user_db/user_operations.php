<?php
include_once dirname(__DIR__). '/db_connect.php';
include_once dirname(__DIR__). '/constants.php';
include_once dirname(__DIR__). '/transaction_db/transaction_op.php';
include_once dirname(__FILE__). '/user.php';
include_once dirname(__DIR__). '/db_helper.php';



class UserOperation extends TransactionOp{
    public $t_user;
    public $c_pass ;
    public $c_name ;
    public $c_actNo;
    public $c_id;
    public  $c_balance;
    public $c_created;
    public $c_role;

    public function __construct(){
        parent::__construct();
        $this->t_user =User::$T_USER;
        $this->c_pass =User::$C_PASSWORD;
        $this->c_actNo =User::$C_ACT_NO;
        $this->c_name =User::$C_NAME;
        $this->c_id =User::$C_ID;
        $this->c_balance =User::$C_BALANCE;
        $this->c_created =User::$C_CREATED;
        $this->c_role =User::$C_ROLE;
    }
    public function createUser(User $user){
        if(!$this->isaccountNumberExist($user->accountNumber)):
        $sql =DBH::insert(
            User::$T_USER,
            [   
                User::$C_ACT_NO=>$user->accountNumber,
                User::$C_NAME=>$user->name,
                User::$C_PASSWORD=>$user->password,
                User::$C_CREATED=>$this->getCurrentTime(),
                User::$C_BALANCE=>$user->balance
                ]
            );
        try {
            $this->connect()->exec($sql);
            return USER_CREATED;
            } catch (\Throwable $th) {
                // echo '<br>';
                // echo $sql;
                // echo '<br>';
                // throw $th;
            return USER_FAILURE;
            }
        else:
            return USER_EXIST;
        endif;
    }
    public function isaccountNumberExist($accountNumber){
        $stm =$this->connect()->prepare("SELECT " .User::$C_ID . " FROM " . User::$T_USER . 
        " WHERE " . User::$C_ACT_NO . "= ?");
        $stm->execute([$accountNumber]);
        while($stm->fetch()){
            return true;
        }
        return false;
    }
    
    public function isIdExist($id){
        $stm =$this->connect()->prepare("SELECT " .User::$C_ID . " FROM " . User::$T_USER . 
        " WHERE " . User::$C_ID . "= ?");
        $stm->execute([$id]);
        while($stm->fetch()){
            // echo "true ";
            return true;
        }
        // echo "false ";
        return false;
    }
    
    public function loginUser(User $user){
        if($this->isaccountNumberExist($user->accountNumber)){
            $hashed_pass = $this->getUserPwdByActNo($user->accountNumber)->password;
            if(password_verify($user->password,$hashed_pass)){
                return USER_AUTHENTICATED;
            }else{
                return USER_PWD_NOT_MATCHED;
            }
        }else {
            return USER_NOT_FOUND;
        }
    }

    public function getUserPwdByActNo($accountNumber){
        $stm =$this->connect()->prepare("SELECT " . User::$C_PASSWORD . " FROM " . User::$T_USER . 
        " WHERE " . User::$C_ACT_NO . "= ?");
        $stm->execute([$accountNumber]);
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        return $stm->fetch();
    }

    public function getUserByActNumber($accountNumber){
        $sql = "SELECT `$this->c_id` , `$this->c_actNo`, `$this->c_name` , `$this->c_role` ,`$this->c_created`,`$this->c_balance`  FROM  `$this->t_user` WHERE `$this->c_actNo` = ? ";
        $stm =$this->connect()->prepare($sql);
        $stm->execute([$accountNumber]);
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        // echo $sql; 
        return $stm->fetch();
    }


    public function getUserIDByActNo($accountNumber){
        $sql = "SELECT `$this->c_id`  FROM  `$this->t_user` WHERE `$this->c_actNo` = ? ";
        $stm =$this->connect()->prepare($sql);
        $stm->execute([$accountNumber]);
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        // echo $sql;
        $arr = $stm->fetch(); 
        return $arr!=null?$arr->id:-2;
    }

    public function getUserById($id){
        $sql = "SELECT `$this->c_id` , `$this->c_actNo`, `$this->c_name` ,`$this->c_created`,`$this->c_balance`  FROM  `$this->t_user` WHERE `$this->c_id` = ? ";
        $stm =$this->connect()->prepare($sql);
        $stm->execute([$id]);
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        // echo $sql; 
        return $stm->fetch();
    }

    public function getAllUsers($q){
        $whereSearch =$q!=null? " WHERE `$this->c_actNo` LIKE '%$q%' 
                                OR `$this->c_name` LIKE '%$q%' 
                                OR `$this->c_created` LIKE '%$q%'  " 
                            :"";
        $sql = "SELECT `$this->c_id` , `$this->c_actNo`, `$this->c_name` ,`$this->c_created`,`$this->c_balance`
                ,`$this->c_role`  FROM  `$this->t_user` $whereSearch ORDER BY $this->c_created DESC;";
        $stm =$this->connect()->query($sql);
        // echo $sql;
        // echo $stm;
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        
        return $stm->fetchAll();
    }
    public function updateUser(User $user){
        $sql = "UPDATE `$this->t_user` SET `$this->c_actNo` = ?, `$this->c_name` = ? ,`$this->c_balance` = ? WHERE $this->c_id = ? ";
        $stm= $this->connect()->prepare($sql);
        // echo $sql; 
        // print_r([$user->accountNumber,$user->name,$user->created,$user->id,]);
        // print_r(array_values(User::toArray($user)));
        return $stm->execute([$user->accountNumber,$user->name,$user->balance,$user->id,]);
    }
 
    public function updatePassword(User $user,$new_password){
        $hashed_pass = $this->getUserPwdByActNo($user->accountNumber)->password;
        if(password_verify($user->password,$hashed_pass)){ //check if old_password matched with db password
            $sql = "UPDATE `$this->t_user` SET `$this->c_pass` = ? WHERE $this->c_actNo = ? ";
            $stm= $this->connect()->prepare($sql);

            $new_password =password_hash($new_password,PASSWORD_DEFAULT);

            $updated = $stm->execute([$new_password,$user->accountNumber]);
            if($updated):
                return PWD_CHANGED;
            else:
                return PWD_NOT_CHANGED;
            endif;
            
        }else{
            return PWD_NOT_MATCHED;
        }
    }

    public function deleteUser($id){
        $sql = "DELETE FROM $this->t_user WHERE $this->c_id = ? ";
        $stm= $this->connect()->prepare($sql);
        return $stm->execute([$id]);
    }

    //Return user balance
    public function getUserBalance(String $id){
        $sql = "SELECT $this->c_balance from $this->t_user WHERE $this->c_id = ?";
        // $sql = "SELECT * from $this->table_name WHERE $this->c_created = ?";
        $stm = $this->connect()->prepare($sql);
        $stm->execute([$id]);
        return $stm->fetch()[$this->c_balance];
    }

    public function isBalanceSufficient($sender_id,$amount){
        $sql ="(SELECT $this->c_balance FROM $this->t_user where $this->c_id = $sender_id )";
        $stm =$this->connect()->prepare($sql);
        $stm->execute();
        $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,'User');// call constructor b4
        return $stm->fetch()->balance - $amount >= 0;
    }

    public function updateBalance($sender_id,$amount,$add){
        $sign =$add?"+":"-";
        $prev_balance;
        $update ="(SELECT $this->c_balance FROM $this->t_user where id = $sender_id ) $sign $amount";
        $andIsSufficientBal =$add? "": "AND ($update)>=0";
        $sql = "UPDATE `$this->t_user` SET `$this->c_balance` = $update WHERE $this->c_id = $sender_id  $andIsSufficientBal";
        // echo $sql;
        $stm= $this->connect()->prepare($sql);
        $stm->execute();
        return $stm->rowCount() > 0;
    }
    public function deposit($sender_id,$amount){
        return $this->updateBalance($sender_id,$amount,true);
    }
    
    public function withdraw($sender_id,$amount){
         //check if there is enough balance
         if($this->isBalanceSufficient($sender_id,$amount)){
            if($this->updateBalance($sender_id,$amount,false)){
                return MONEY_SENT;
            }
        }else{
            return INSUFFICIENT_BALANCE;
        }
        return TRANSACTION_FAILED;
    }
    public function transfer($sender_id,$amount){
        //check if there is enough balance
        if($this->isBalanceSufficient($sender_id,$amount)){
            if($this->updateBalance($sender_id,$amount,false)){
                return MONEY_SENT;
            }
        }else{
            return INSUFFICIENT_BALANCE;
        }
        return TRANSACTION_FAILED;
    }
    public function receiveMoney($sender_id,$amount){
        if($this->updateBalance($sender_id,$amount,true))
            return MONEY_SENT;
        else
            return TRANSACTION_FAILED;
    }
}