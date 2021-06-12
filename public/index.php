<?php
declare (strict_types =1);
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require '../includes/user_db/user_operations.php';
// require '../includes/transaction_db/operation.php';

$app = AppFactory::create();

$app->setBasePath("/newApi/public");
$app->addBodyParsingMiddleware();


$app->get('/test',function(Request $request,Response $response,array $args){
    $user_op =new UserOperation();
    // $user_op->getUserTransaction(63);
    // $res = $user_op->getUserTransactions(["limit"=>10,"orderBy"=>'created',"order"=>'DESC']);
    $res = $user_op->getDataSummarry();
    // print_r($res);
    
    $response->getBody()->write(json_encode($res));
    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
});

$app->get('/summary',function(Request $request,Response $response,array $args){
    $user_op =new UserOperation();
    $res = $user_op->getDataSummarry();
    
    $response->getBody()->write(json_encode($res));
    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
});

/**
 * Endpoint to fetch users transaction history.
 * @param Request $request accept the following as param or body 
 * [limit,orderBy={created,sender,recipient},order={ASC,DESC}]
 */
$app->get('/transactions',function(Request $request,Response $response){
    // $req_param = ["limit","orderBy","order"]; //Transaction::$C_CREATED,"recipient",
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();

        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        // print_r($request_array);
        $transactions = $user_op->getUserTransactions($request_array);
        $response_array['error']=false;
        $response_array['message']="Transactions loaded Successfully";
        $response_array['transactions']= $transactions;
        $status_code =200;
        
        $response->getBody()->write(json_encode($response_array));
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});

/**
 * Endpoint to fetch user withdrawal history.
 * @param Request $request accept the following as param or body [accountNumber]
 * @return Respone $response with body={
 *  error,message,
 *  transaction:{senderNumber,senderName,recipientNumber,recipientName,amount,type,created}
 * }
 */
$app->get('/withdrawal',function(Request $request,Response $response){
    $required_param = [User::$C_ACT_NO];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();
        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $user = User::fromArray($request_array);
        $user->id = $user_op->getUserIDByActNo($user->accountNumber);

        $transactions =$user_op->getUserWithdrawal($user->id); //Array
        $response_array['error']=false;
        $response_array['message']="Transaction loaded";
        $response_array['transaction']= $transactions;
        $status_code =200;

        
        $response->getBody()->write(json_encode($response_array));
    
    }else{
        $status_code =202;
     }
    return $response->withStatus($status_code)
    ->withHeader("Content-Type", "application/json")
    ->withAddedHeader('Access-Control-Allow-Origin', '*')
    ->withAddedHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
    ->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type')
    ->withAddedHeader('Access-Control-Allow-Credentials', 'true' );
});

/**
 * Endpoint to fetch user transaction history.
 * @param Request $request accept the following as param or body [accountNumber]
 * Optional Parameter {deposit}, default is false
 * @return Respone $response with body={
 *  error,message,
 *  transaction:{senderNumber,senderName,recipientNumber,recipientName,amount,type,created}
 * }
 */
$app->get('/transaction',function(Request $request,Response $response){
    $required_param = [User::$C_ACT_NO];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();
        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $deposit =isset($request_array["deposit"])?$request_array["deposit"]:"false";
        $user = User::fromArray($request_array);
        $user->id = $user_op->getUserIDByActNo($user->accountNumber);
        $transactions;
        // print_r($request_array);
        // echo boolval($deposit);
        // echo $deposit;
        if($deposit=="true")
        $transactions =$user_op->getUserDepositTransaction($user->id); //Array
        else
        $transactions =$user_op->getUserTransaction($user->id); //Array
        $response_array['error']=false;
        $response_array['message']="Transaction loaded";
        $response_array['transaction']= $transactions;
        $status_code =200;

        
        $response->getBody()->write(json_encode($response_array));
    
    }else{
        $status_code =202;
     }
    return $response->withStatus($status_code)
    ->withHeader("Content-Type", "application/json")
    ->withAddedHeader('Access-Control-Allow-Origin', '*')
    ->withAddedHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
    ->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type')
    ->withAddedHeader('Access-Control-Allow-Credentials', 'true' );
});

/**
 * Endpoint to withdraw money
 * @param Request $request accept the following as param or body [sender,amount]
 */
$app->post('/withdraw',function(Request $request,Response $response){
    $required_param = [Transaction::$C_SENDER,Transaction::$C_AMOUNT];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();

        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $transaction = Transaction::fromArray($request_array);

        //Save both account Number
        $sender_no = $transaction->sender;
        //Change account numbers to ids and initialize with ivalid ids 
        // if not match id was found
        $transaction->sender =$user_op->getUserIDByActNo($sender_no) ?? -12;
        $transaction->type =TYPE_WITHDRAWAL;
        $transaction->created =$user_op->getCurrentTime();
        // $transaction->print();
        $trans_res =$user_op->withdrawMoney($transaction);
        switch ($trans_res) {
            case TRANSACTION_DONE:
                $balance =$user_op->getUserBalance($transaction->sender);
                $transaction =$user_op->getUserWithdrawWithTime($transaction->sender,$transaction->created); //Array

                $response_array['error']=false;
                $response_array['message']="Transaction Successful";
                $response_array['balance']= (Int) $balance ;
                $response_array['transaction']= $transaction;
                $status_code =200;
                break;
            case INSUFFICIENT_BALANCE:
                $balance =$user_op->getUserBalance($transaction->sender);
        
                $response_array['error']=true;
                $response_array['message']="Insufficient balance #$balance";
                $response_array['balance']= (Int) $balance ;
                $response_array['transaction']= [];
                $status_code =201;
                break;
            case TRANSACTION_FAILED:
                $response_array['error']=true;
                $response_array['message']="Transaction failed";
                $response_array['transaction']= [];
                $status_code =202;
                break;
            case INVALID_SENDER:
                $response_array['error']=true;
                $response_array['message']="This sender doesn't exist";
                $response_array['transaction']= [];
                $status_code =207;
                break;
        }
        $response->getBody()->write(json_encode($response_array));
    
    }else{
        $status_code =206;
     }
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});
/**
/**
 * Endpoint to deposit money
 * @param Request $request accept the following as param or body [sender,amount]
 */
$app->post('/deposit',function(Request $request,Response $response){
    $required_param = [Transaction::$C_SENDER,Transaction::$C_AMOUNT];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();

        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $transaction = Transaction::fromArray($request_array);

        //Save both account Number
        $sender_no = $transaction->sender;
        //Change account numbers to ids and initialize with ivalid ids 
        // if not match id was found
        $transaction->sender =$user_op->getUserIDByActNo($sender_no) ?? -12;
        $transaction->type =TYPE_DEPOSIT;
        $transaction->created =$user_op->getCurrentTime();
        // $transaction->print();
        $trans_res =$user_op->depositMoney($transaction);
        switch ($trans_res) {
            case TRANSACTION_DONE:
                $balance =$user_op->getUserBalance($transaction->sender);
                $transaction =$user_op->getUserDepositWithTime($transaction->sender,$transaction->created); //Array

                $response_array['error']=false;
                $response_array['message']="Transaction Successful";
                $response_array['balance']= (Int) $balance ;
                $response_array['transaction']= $transaction;
                $status_code =200;
                break;
            case TRANSACTION_FAILED:
                $response_array['error']=true;
                $response_array['message']="Transaction failed";
                $response_array['transaction']= [];
                $status_code =202;
                break;
            case INVALID_SENDER:
                $response_array['error']=true;
                $response_array['message']="This sender doesn't exist";
                $response_array['transaction']= [];
                $status_code =207;
                break;
        }
        $response->getBody()->write(json_encode($response_array));
    
    }else{
        $status_code =206;
     }
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});
/**
 * Endpoint to transfer money
 * @param Request $request accept the following as param or body [sender,amount,to]
 */
$app->post('/transfer',function(Request $request,Response $response){
    $required_param = [Transaction::$C_SENDER,Transaction::$C_AMOUNT,Transaction::$C_TO];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;
        $user_op =new UserOperation();

        //accept request
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $transaction = Transaction::fromArray($request_array);

        //Save both account Number
        $sender_no = $transaction->sender;
        $receipient_no = $transaction->to;
        //Change account numbers to ids and initialize with ivalid ids 
        // if not match id was found
        $transaction->sender =$user_op->getUserIDByActNo($sender_no) ?? -12;
        $transaction->to =$user_op->getUserIDByActNo($receipient_no) ?? -12;
        $transaction->type =TYPE_TRANSFER;
        $transaction->created =$user_op->getCurrentTime();
        // $transaction->print();
        $trans_res =$user_op->transferMoney($transaction);
        switch ($trans_res) {
            case TRANSACTION_DONE:
                $balance =$user_op->getUserBalance($transaction->sender);
                $transaction =$user_op->getUserTransactionWithTime($transaction->sender,$transaction->created); //Array

                $response_array['error']=false;
                $response_array['message']="Transaction Successful";
                $response_array['balance']= (Int) $balance ;
                $response_array['transaction']= $transaction;
                $status_code =200;
                break;
            case INSUFFICIENT_BALANCE:
                $response_array['error']=true;
                $response_array['message']="Insufficient balance";
                $response_array['transaction']= [];
                $status_code =201;
                break;
            case TRANSACTION_FAILED:
                $response_array['error']=true;
                $response_array['message']="Transaction failed";
                $response_array['transaction']= [];
                $status_code =202;
                break;
            case INVALID_SENDER:
                $response_array['error']=true;
                $response_array['message']="This sender doesn't exist";
                $response_array['transaction']= [];
                $status_code =207;
                break;
            case INVALID_RECEIPIENT:
                $response_array['error']=true;
                $response_array['message']="The receipient doesn't exist";
                $response_array['transaction']= [];
                $status_code =207;
                break;
        }
        $response->getBody()->write(json_encode($response_array));
    
    }else{
        $status_code =206;
     }
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});

/**
 * Endpoint to delete user
 * @param $args id
 */
$app->delete("/deleteuser/{id}", function (Request $request,Response $response,$args){
    $response_array = array();
    $status_code;

     $id =$args['id'];
     $user_op =new UserOperation();
     $idExist= $user_op->isIdExist($id);
     $deleted= $user_op->deleteUser($id);
     if($idExist){
         if($deleted):
             $response_array['error'] =false;
             $response_array['message'] ="User of id $id was successfully deleted!";
             // $response_array['user'] = User::toArray($user_op->getUserByActNumber($user->accountNumber));
             $status_code =200;
             // echo "user updated";
         else:
             $response_array['error'] =true;
             $response_array['message'] ="User not deleted";
             // $response_array['user'] = User::toArray($user_op->getUserByActNumber($user->accountNumber));
             $status_code =400;
         endif;
    }else{
        $response_array['error'] =true;
        $response_array['message'] ="No user with this id!";
        $status_code =400;
    
    }
    $response->getBody()->write(json_encode($response_array));
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});

/**
 * Endpoint for user password update
 * @param $requests {accountNumber,password,newpassword}
 * @param $args id
 */
$app->put("/updatepassword", function(Request $request,Response $response){
    $response_array = array();
    $status_code;

    $required_param = [User::$C_ACT_NO,User::$C_PASSWORD,"newpassword"];
    if(!haveEmptyParams($required_param,$response,$request)){
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $user = User::fromArray($request_array);
        $new_password = $request_array['newpassword'];
        $user_op =new UserOperation();
        $isaccountNumberExist= $user_op->isaccountNumberExist($user->accountNumber);
        $result= $user_op->updatePassword($user,$new_password);
        if($isaccountNumberExist){
             if($result== PWD_CHANGED):
                 $response_array['error'] =false;
                 $response_array['message'] ="User password updated!";
                 $response_array['user'] = User::toArray($user_op->getUserByActNumber($user->accountNumber));
                 $status_code =200;
                 // echo "user updated";
             elseif($result== PWD_NOT_MATCHED):
                 $response_array['error'] =true;
                 $response_array['message'] ="incorrect old password";
                //  $response_array['user'] = User::toArray($user_op->getUserByActNumber($user->accountNumber));
                 $status_code =400;
             else:
                 $response_array['error'] =true;
                 $response_array['message'] ="password update failed!";
                 $response_array['user'] = User::toArray($user_op->getUserByActNumber($user->accountNumber));
                 $status_code =400;
             endif;
        }else{
            $response_array['error'] =true;
            $response_array['message'] ="No user with this id!";
            $status_code =422;
       
        }
        $response->getBody()->write(json_encode($response_array));
    }else{
        $status_code =402;
     }
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});

/**
 * Endpoint for user update
 * @param accountNumber,name,balance
 */
$app->put('/updateuser/{id}',function(Request $request,Response $response,array $args){
    $response_array = array();
    $status_code;

    $id =$args['id'];
    $required_param = [User::$C_ACT_NO,User::$C_NAME,User::$C_BALANCE];
    if(!haveEmptyParams($required_param,$response,$request)){
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $user = User::fromArray($request_array);
        $user->id = $id;
        $user_op =new UserOperation();
        $idExist= $user_op->isIdExist($id);
        $updated= $user_op->updateUser($user);
        if($idExist){
             if($updated):
                 $response_array['error'] =false;
                 $response_array['message'] ="User data updated!";
                 $newUser = $user_op->getUserByActNumber($user->accountNumber);
                 $response_array['user'] = User::toArray($newUser);
                 $status_code =200;
             else:
                 $response_array['error'] =true;
                 $response_array['message'] ="User data update failed!";
                 $newUser = $user_op->getUserByActNumber($user->accountNumber);
                 $response_array['user'] = User::toArray($newUser);
                 $status_code =400;
             endif;
        }else{
            $response_array['error'] =true;
            $response_array['message'] ="No user with this id!";
            $status_code =400;
       
        }
        $response->getBody()->write(json_encode($response_array));
     }else{
        $status_code =402;
     }
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json");
});


/**
 * Endpoint to fetch a user with an id
 * @param $args accountNumber
 */
$app->get('/user/{accountNumber}',function(Request $request,Response $response,array $args){

    $accountNumber= $args['accountNumber'];
    $user_op =new UserOperation();
    $user = $user_op->getUserByActNumber($accountNumber);
    // $response_array= $user;
    
    $response_array['error'] =false;
    $response_array['message'] =" User Loaded successfuly";
    $response_array['user'] = $user;
    // $response_array= User::toArray($user);
    $response->getBody()->write(json_encode($response_array));

    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
});


/**
 * Endpoint to fetch list of users
 * @param Response $response {q} as a search query to filter the list of users
 */
$app->get('/allusers',function(Request $request,Response $response){
    $response_array = array();
    $request_array = $request->getParsedBody()??$request->getQueryParams();
        
    $user_op =new UserOperation();
    $search =isset($request_array['q'])? $request_array['q']:null;
    $users = $user_op->getAllUsers($search);
    $userCount = count($users);
    $response_array['error'] =false;
    $response_array['message'] =" Successfully fetched $userCount Users";
    $response_array['users'] = $users;
    $response->getBody()->write(json_encode($response_array));

    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
    
});
/**
 * Endpoint for login
 * @* @param Response $response {accountNumber,password}
 */
$app->post('/login',function(Request $request,Response $response,array $args){
    $required_param = [User::$C_ACT_NO,User::$C_PASSWORD];
    if(!haveEmptyParams($required_param,$response,$request)){
        $response_array = array();
        $status_code;

        $request_array = $request->getParsedBody()??$request->getQueryParams();
        $c_user = User::fromArray($request_array);
        $user_op =new UserOperation();
        $res= $user_op->loginUser($c_user);
        switch ($res) {
            case USER_AUTHENTICATED:
                $user = $user_op->getUserByActNumber($c_user->accountNumber); 
                if($user === false){
                    $response_array['error'] =true;
                    $response_array['message'] ="User login failed";
                    $status_code =200;
                        break;
                }
                $response_array['error'] =false;
                $response_array['message'] =" Login Successfully";
                $response_array['data'] = User::toArray($user);
                $status_code =200;
                break;
            
            case USER_PWD_NOT_MATCHED:
                $response_array['error'] =true;
                $response_array['message'] ="User password does not match";
                $status_code =206;
                break;
            
            case USER_NOT_FOUND:
                $response_array['error'] =true;
                $response_array['message'] ="Invalid accountNumber or password";
                $status_code =201;
                break;
        }        
        $response->getBody()->write(json_encode($response_array));
    }
    else{
        $status_code =207;
    }
    return $response->withStatus($status_code)
    ->withHeader("Content-Type", "application/json")
    ->withAddedHeader('Access-Control-Allow-Origin', '*')
    ->withAddedHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
    ->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type')
    ->withAddedHeader('Access-Control-Allow-Credentials', 'true' );
});

/**
 * Endpoint to register user
 * @* @param Response $response {accountNumber,password,name}
 */
$app->post('/signup',function(Request $request,Response $response,array $args){
    $required_param = [User::$C_ACT_NO,User::$C_PASSWORD,User::$C_NAME];
    if(!haveEmptyParams($required_param,$response,$request)):
        $response_array = array();
        $status_code;
    
        $request_array = $request->getParsedBody()??$request->getQueryParams();
        // echo $request->getBody();
        $user = User::fromArray($request_array);
        $pass =password_hash($user->password,PASSWORD_DEFAULT);
        $user->setPassword($pass);
        $user->balance = 0;

        $user_op =new UserOperation();
        $result=$user_op->createUser($user);

        switch ($result) {
            case USER_CREATED:
                $user = $user_op->getUserByActNumber($user->accountNumber);
                    if($user === false){
                        $response_array['error'] =true;
                        $response_array['message'] ="can't create user";
                        $status_code =200;
                            break;
                    } 
                $response_array['error'] =false;
                $response_array['message'] =" User created successfully";
                $response_array['data'] =$user;
                $status_code =200;
                break;
            case USER_EXIST:
                $response_array['error'] =true;
                $response_array['message'] =" User already Exist";
                $status_code =208;
                break;
            
            case USER_FAILURE:
                $response_array['error'] =true;
                $response_array['message'] ="Some Error occur";
                $status_code =206;
                break;
      
            }
        $response->getBody()->write(json_encode($response_array));
        else:
            $status_code =207;
    endif;
    return $response->withStatus($status_code)->withHeader("Content-Type", "application/json")
    ->withAddedHeader('Access-Control-Allow-Origin', '*')
    ->withAddedHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
    ->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type')
    ->withAddedHeader('Access-Control-Allow-Credentials', 'true' );
});

function haveEmptyParams($required_param,Response $response,Request $request){
    $error =false;
    $error_param = '';
    $request_params =$request->getParsedBody()??$request->getQueryParams();

        foreach ($required_param as $param):
            if(isset($request_params[$param])):
                $cParam = $request_params[$param];
                if(!is_numeric($cParam)&&strlen($cParam)<=0):
                    $error= true;
                    $error_param.=$param . ', ';
                endif;
            else:
                $error= true;
                $error_param.=$param . ', ';
            endif;
        endforeach;

        if($error):
            $error_detail = array();
            $error_detail['error'] =true;
            $error_detail['message'] = 'Required parameters ' 
            . substr($error_param,0,-2) . ' are missing, you can pass it as parameter or body';
            $response->getBody()->write(json_encode($error_detail));
        endif;
        return $error;
}

$app->run();