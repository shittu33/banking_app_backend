<?php
class Transaction{
    static $TABLE_NAME ='transaction';
    static $C_ID ='id';
    static $C_SENDER ='sender';
    static $C_TYPE ='type';
    static $C_AMOUNT ='amount';
    static $C_TO ='to';
    static $C_CREATED ='created';

    public $id;
    public $sender;
    public $type;
    public $amount;
    public $to;
    public $created;

    
    public static function fromArray(Array $tansaction_assoc){
        $transaction = new Transaction();
        $transaction->id=$tansaction_assoc[self::$C_ID]??null;
        $transaction->sender=$tansaction_assoc[self::$C_SENDER]??null;
        $transaction->type=$tansaction_assoc[self::$C_TYPE]??null;
        $transaction->amount=$tansaction_assoc[self::$C_AMOUNT]??null;
        $transaction->to=$tansaction_assoc[self::$C_TO]??null;
        $transaction->created=$tansaction_assoc[self::$C_CREATED]??null;
        return $transaction;
    }
    
    public static function init($id,$sender, $type,$amount,$to,$created){
        $transaction = new Transaction();
        $transaction->id=$id;
        $transaction->sender=$sender;
        $transaction->type=$type;
        $transaction->amount=$amount;
        $transaction->created=$created;
        $transaction->to=$to;
        return $transaction;
    }
    
    public static function initTransact($sender, $type,$amount,$to){
        $transaction = new Transaction();
        $transaction->sender=$sender;
        $transaction->type=$type;
        $transaction->amount=$amount;
        $transaction->created=CURRENT_DATE;
        $transaction->to=$to;
        return $transaction;
    }

    public static function toArray(Transaction $transaction){
        $arr = Array();
        if($transaction->id!=null)
        $arr[self::$C_ID]=$transaction->id;
        if($transaction->sender!=null)
        $arr[self::$C_SENDER]=$transaction->sender;
        if($transaction->type!=null) 
        ($arr[self::$C_TYPE]=$transaction->type);
        if($transaction->amount!=null)
        $arr[self::$C_AMOUNT]=$transaction->amount;
        if($transaction->to!=null)
        $arr[self::$C_TO]=$transaction->to;
        if($transaction->created!=null)
        $arr[self::$C_CREATED]=$transaction->created;
        return $arr;
    }
    //this contain some column aliases {sender_name,recepient,recepient_name}
    public static function toTransArray(Transaction $transaction){
        $arr = Array();
        if($transaction->id!=null)
        $arr[self::$C_ID]=$transaction->id;
        if($transaction->sender!=null)
        $arr[self::$C_SENDER]=$transaction->sender;
        if($transaction->type!=null) 
        ($arr[self::$C_TYPE]=$transaction->type);
        if($transaction->amount!=null)
        $arr[self::$C_AMOUNT]=$transaction->amount;
        if($transaction->to!=null)
        $arr[self::$C_TO]=$transaction->to;
        if($transaction->created!=null)
        $arr[self::$C_CREATED]=$transaction->created;
        if($transaction->recipient!=null)
        $arr["recepient"]=$transaction->recipient;
        if($transaction->recepient_name!=null)
        $arr["recepient_name"]=$transaction->recepient_name;
        if($transaction->sender_name!=null)
        $arr["sender_name"]=$transaction->sender_name;
        return $arr;
    }

    public static function toJson(Transaction $transaction){
        return json_encode(self::toArray($transaction));
    }

    public function print(){
        echo "id is $this->id,amount is $this->amount, type is $this->type, from sender $this->sender, sent to $this->to, created on $this->created";
    }
}