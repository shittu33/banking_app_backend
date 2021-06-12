<?php

define("DB_HOST","localhost");
define("DB_NAME","veegil_api");
define("USER_NAME","root");
define("PASSWORD","");

define("CURRENT_DATE","CURRENT_DATE");
define("TYPE_TRANSFER","transfer");
define("TYPE_WITHDRAWAL","withdraw");
define("TYPE_DEPOSIT","deposit");

//response
define("USER_CREATED",101);
define("USER_EXIST",102);
define("USER_FAILURE",103);

define("USER_AUTHENTICATED",201);
define("USER_NOT_FOUND",202);
define("USER_PWD_NOT_MATCHED",203);

define("PWD_NOT_MATCHED",301);
define("PWD_NOT_CHANGED",302);
define("PWD_CHANGED",303);

define("SUFFICIENT_BALANCE",401);
define("INSUFFICIENT_BALANCE",402);
define("TRANSACTION_DONE",403);
define("MONEY_SENT",404);
define("TRANSACTION_FAILED",405);
define("INVALID_SENDER",406);
define("INVALID_RECEIPIENT",407);


// define("T_USER","users");
// define("C_NAME","name");
// define("C_MAIL","email");
// define("C_PASSWORD","password");
// define("C_SCHOOL","school");