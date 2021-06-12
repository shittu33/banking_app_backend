<?php
include_once dirname(__DIR__). '/db_connect.php';
include_once dirname(__DIR__). '/constants.php';
include_once dirname(__DIR__). '/user_db/user_operations.php';
include_once dirname(__FILE__). '/transaction.php';
include_once dirname(__DIR__). '/db_helper.php';

abstract class TransactionOp extends DbConnect{
    public $table_name;
    public $c_id;
    public $C_SENDER;
    public $c_type;
    public $c_amount;
    public $c_to;
    public $c_created;

    
    public function __construct(){
        $this->table_name =Transaction::$TABLE_NAME;
        $this->c_id =Transaction::$C_ID;
        $this->C_SENDER =Transaction::$C_SENDER;
        $this->c_type =Transaction::$C_TYPE;
        $this->c_amount =Transaction::$C_AMOUNT;
        $this->c_to =Transaction::$C_TO;
        $this->c_created =Transaction::$C_CREATED;
    }

    public function createTransaction(Transaction $transaction){
        $sql = DBH::insert(Transaction::$TABLE_NAME,
                        [
                            Transaction::$C_SENDER=>$transaction->sender,
                            Transaction::$C_TYPE=>$transaction->type,
                            Transaction::$C_AMOUNT=>$transaction->amount,
                            Transaction::$C_TO=>$transaction->to,
                            Transaction::$C_CREATED=>$transaction->created,
                        ]
                    );
        try {
            $this->connect()->exec($sql);
            // echo "sender added successfully<br>";
            // echo $sql;
            // echo '<br>';
            return true;
            } catch (\Throwable $th) {
                // echo '<br>';
                // echo $sql;
                // echo '<br>';
                throw $th;
            return false;
            }
    }
    
    public function getCurrentTime(){
        $sql = "SELECT NOW() from transaction";
        $stm = $this->connect()->query($sql);
        $time =$stm->fetch();
        return $time['NOW()'];
    }
    
    /**
     * function to load transaction
     * 
     */
    public function loadTransaction(Transaction $transaction){
        $transaction->to=$transaction->sender;
        $transaction->type=TYPE_DEPOSIT;
        //check if the sender exist
        if($this->isInvalidId($transaction->sender))
            return INVALID_SENDER;
        //check if money is deposited 
        if($this->deposit($transaction->sender,$transaction->amount)){
            //check if transaction history is updated
            if($this->createTransaction($transaction)){
                return TRANSACTION_DONE;
            }
        }
        return TRANSACTION_FAILED;
    }

    public function getUserTransaction($id){
        return $this->getUserTransactionRaw($id,null,TYPE_TRANSFER);
    }

    
    public function getUserTransactionWithTime($id,$time){
        return $this->getUserTransactionRaw($id,$time,TYPE_TRANSFER);
    }

    public function getUserDepositTransaction($id){
        return $this->getUserTransactionRaw($id,null,TYPE_DEPOSIT);
    }

    public function getUserDepositWithTime($id,$time){
        return $this->getUserTransactionRaw($id,$time,TYPE_DEPOSIT);
    }

    public function getUserWithdrawal($id){
        return $this->getUserTransactionRaw($id,null,TYPE_WITHDRAWAL);
    }

    public function getUserWithdrawWithTime($id,$time){
        return $this->getUserTransactionRaw($id,$time,TYPE_WITHDRAWAL);
    }

    // SELECT (SUM(`balance`)) as total_balance, (COUNT(user.id)) as total_user, (COUNT(transaction.id)) as total_transactions FROM `user` JOIN `transaction` ON user.id = transaction.sender
    public function getDataSummarry(){
        $user_table =User::$T_USER;
        $trans_table = Transaction::$TABLE_NAME;
        $user_tableId ="$user_table.". User::$C_ID;
        $user_balance ="$user_table.". User::$C_BALANCE;
        $trans_tableId ="$trans_table.". Transaction::$C_ID;
        $senderId = "$trans_table." . Transaction::$C_SENDER;

        $sql = "SELECT (SUM($user_balance)) as totalBalance, (COUNT($user_tableId)) as numUser,
                (SELECT COUNT($trans_tableId) FROM $trans_table) as numTransactions 
                FROM $user_table";
        $stm = $this->connect()->prepare($sql);
        $stm->execute();
        return $stm->fetch();
    }


    private function getUserTransactionRaw($id,$time,$type){
        //declear tables and columns
        $deposit = $type===TYPE_DEPOSIT;
        $withdraw = $type===TYPE_WITHDRAWAL;
        $trans_table = Transaction::$TABLE_NAME;
        $user_table =User::$T_USER;
        $user_tableId ="$user_table.". User::$C_ID;
        $actNo = "$user_table." . User::$C_ACT_NO;
        $name = "$user_table." . User::$C_NAME;
        $senderId = "$trans_table." . Transaction::$C_SENDER;
        $receipientId = "$trans_table." . Transaction::$C_TO;
        $amount = "$trans_table." . Transaction::$C_AMOUNT;
        $created = "$trans_table." . Transaction::$C_CREATED;
        $cType = "$trans_table." . Transaction::$C_TYPE;
        $andAtTime = $time!=null?" AND $created = '$time'":"";
        // $ifDeposit = "IF($senderId = $receipientId)";
        $andIsDeposit = $deposit==true?" AND $senderId = $receipientId"
                        :($withdraw?" AND $receipientId='NULL'"
                        :" AND $senderId != $receipientId AND $receipientId!='NULL'");
        $type = $time==null
                    ?"IF($receipientId=$user_tableId,'credit','debit' ) as `type`"
                    :$cType;
        $sql = "SELECT
                    (SELECT $actNo FROM $user_table WHERE $user_tableId= $senderId) as `senderNumber`,
                    (SELECT $name FROM $user_table WHERE $user_tableId= $senderId) as `senderName`,
                    (SELECT $actNo FROM $user_table WHERE $user_tableId= $receipientId) as `recipientNumber`,
                    (SELECT $name FROM $user_table WHERE $user_tableId= $receipientId) as `recipientName`,
                    $amount,
                    $type, 
                    $created as time
                FROM $trans_table 
                INNER JOIN $user_table ON $senderId = $user_tableId  OR $receipientId =$user_tableId 
                WHERE $user_tableId = ? $andIsDeposit $andAtTime ORDER BY $created DESC";
        $stm = $this->connect()->prepare($sql);
        $stm->execute([$id]);
        return $stm->fetchAll();
    }    

    public function getFilterStatement($filters){
        $trans_table = Transaction::$TABLE_NAME;
        $user_table =User::$T_USER;
        $user_tableId ="$user_table.". User::$C_ID;
        $name = "$user_table." . User::$C_NAME;
        $senderId = "$trans_table." . Transaction::$C_SENDER;
        $receipientId = "$trans_table." . Transaction::$C_TO;
        $conditionStatement =""; 
        $limit; 
        $orderBy; 
        $order;

        foreach ($filters as $key => $filter):
            switch ($key) {
                case 'limit':
                    $limit = "limit $filter";
                    break;
                case 'orderBy':
                    $orderBy = $filter;
                    break;
                case 'order':
                    $order = $filter;
                    break;
            }
        endforeach;
            if(isset($orderBy)):
                $conditionStatement .= "ORDER BY ";
                switch ($orderBy) {
                    case 'created':
                $conditionStatement .= " time";
                        break;
                    case 'sender':
                $conditionStatement .= " (SELECT $name FROM $user_table WHERE $user_tableId= $senderId)";
                        break;
                    case 'recipient':
                $conditionStatement .= " (SELECT $name FROM $user_table WHERE $user_tableId= $receipientId)";
                        break;
                }
                if(isset($order)):
                    $conditionStatement .= " $order";
                endif;
            endif;
            
            if(isset($limit)):
                $conditionStatement .= " $limit";
            endif;
    return $conditionStatement;
    }

    public function getUserTransactions($filters){
        //declear tables and columns
        $trans_table = Transaction::$TABLE_NAME;
        $user_table =User::$T_USER;
        $user_tableId ="$user_table.". User::$C_ID;
        $actNo = "$user_table." . User::$C_ACT_NO;
        $name = "$user_table." . User::$C_NAME;
        $senderId = "$trans_table." . Transaction::$C_SENDER;
        $receipientId = "$trans_table." . Transaction::$C_TO;
        $amount = "$trans_table." . Transaction::$C_AMOUNT;
        $created = "$trans_table." . Transaction::$C_CREATED;
        $type = "$trans_table." . Transaction::$C_TYPE;
        
        $filterStatement = $this->getFilterStatement($filters);    
        $sql = "SELECT
                    (SELECT $actNo FROM $user_table WHERE $user_tableId= $senderId) as `senderNumber`,
                    (SELECT $name FROM $user_table WHERE $user_tableId= $senderId) as `senderName`,
                    (SELECT $actNo FROM $user_table WHERE $user_tableId= $receipientId) as `recipientNumber`,
                    (SELECT $name FROM $user_table WHERE $user_tableId= $receipientId) as `recipientName`,
                    $amount,
                    $type,
                    $created as time
    
                FROM $trans_table 
                INNER JOIN $user_table ON $senderId = $user_tableId  OR $receipientId =$user_tableId  $filterStatement ";
        $stm = $this->connect()->prepare($sql);
        $stm->execute();
        // $stm->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE,get_class(new Transaction()));// call constructor b4
        return $stm->fetchAll();
    }    

    /**
     * function to deposit money to database
     * 
     */
    public function withdrawMoney(Transaction $transaction){
        $transaction->to='NULL';
        $transaction->type=TYPE_WITHDRAWAL;
        //check if the sender exist
        if($this->isInvalidId($transaction->sender))
            return INVALID_SENDER;
        //check if money is deposited 
        $transfer_response =$this->withdraw($transaction->sender,$transaction->amount);
        switch ($transfer_response) {
            case MONEY_SENT:
                //check if the transaction history is updated
                if($this->createTransaction($transaction))
                    return TRANSACTION_DONE;
                else
                    break;    
            case INSUFFICIENT_BALANCE:
                return INSUFFICIENT_BALANCE;
            default:
                return TRANSACTION_FAILED;
        }
        return TRANSACTION_FAILED;
    }

    /**
     * function to deposit money to database
     * 
     */
    public function depositMoney(Transaction $transaction){
        $transaction->to=$transaction->sender;
        $transaction->type=TYPE_DEPOSIT;
        //check if the sender exist
        if($this->isInvalidId($transaction->sender))
            return INVALID_SENDER;
        //check if money is deposited 
        if($this->deposit($transaction->sender,$transaction->amount)){
            //check if transaction history is updated
            if($this->createTransaction($transaction)){
                return TRANSACTION_DONE;
            }
        }
        return TRANSACTION_FAILED;
    }

    /**
     * function to transfer money to other customer.
     * before we update the trnsaction history, we have to verify
     * if the money is actually sent
     */
    public function transferMoney(Transaction $transaction){
            //check if the sender exist
            if($this->isInvalidId($transaction->sender))
                return INVALID_SENDER;
            //check if receiver exist.
            if($this->isInvalidId($transaction->to))
                return INVALID_RECEIPIENT;
            // check if money is sent
            $transfer_response =$this->transfer($transaction->sender,$transaction->amount);
            switch ($transfer_response) {
                case MONEY_SENT:
                    //check if receipient has received the payment
                    if($this->receiveMoney($transaction->to,$transaction->amount)==MONEY_SENT){
                        //check if the transaction history is updated
                        if($this->createTransaction($transaction))
                            return TRANSACTION_DONE;
                        else
                            break;
                    }else {
                        return TRANSACTION_FAILED;
                    }
                case INSUFFICIENT_BALANCE:
                    return INSUFFICIENT_BALANCE;
                default:
                    return TRANSACTION_FAILED;
            }
        return TRANSACTION_FAILED;
    }

    
    //check if the user id retrieved with account number is invalid
    private function isInvalidId($id){
        return $id <0;
    }

    //functions to be implemented by The child class
    public abstract function receiveMoney($sender_id,$amount);
    public abstract function transfer($sender_id,$amount);
    public abstract function deposit($sender_id,$amount);
    public abstract function withdraw($sender_id,$amount);
}