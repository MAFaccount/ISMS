<?php

return [
	'send_sms_url' => env('SEND_SMS_URL'),
	'send_validation_url' => env('SEND_VALIDATION_URL'),
    'call_url' => 'http://voip.routevoice.com/httpApi/genCalls.php',
    'send_bulk_url' => env('SEND_BULK_URL'),

	'log_path' => env('ISMS_LOG_PATH'),

    'data' => [
        'username' => env('ISMS_USERNAME'),
        'password' => env('ISMS_PASSWORD'),
        'exptime' => env('ISMS_EXPIRY_TIME'),
        'source' => env('ISMS_SOURCE'),
        'otplen' => env('OTP_LENGTH'),
    ],

    'call_data' => [
        'user' => env('ISMS_CALL_USER'),
        'pwd' => env('ISMS_CALL_PWD'),
        'jobType' => env('ISMS_CALL_JOB_TPYE' , 'otp')
    ]
];
