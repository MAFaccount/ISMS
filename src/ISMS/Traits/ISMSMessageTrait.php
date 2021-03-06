<?php

namespace ISMS\Traits;

use Illuminate\Support\Facades\Config;

trait ISMSMessageTrait {
	/**
	 * GuzzleHttp Object will be stored in this field to allow us to send HTTP requests and get response
	 * @var GuzzleHttp\Client instance
	 */
	protected $_http;


	/**
	 * Logger instance will be stored in this field to allow us to log to the log file
	 * @var Monolog\Logger instance
	 */
	protected $_logger;


	/**
	 * The path of the log file
	 * @var string
	 */
	protected $_logPath;


	/**
	 * Errors happend during the execution of sendSms function will be pushed to this array
	 * @var array
	 */
	protected $_errors = [];


	/**
	 * Opt validation process errors will be pushed to this array
	 * @var array
	 */
	protected $_errorsOtp = [];

	/**
	 * Call Verfication process errors will be pushed to this array
	 * @var array
	 */
	protected $_errorsCall = [];


    protected $_errorsBulk = [];

	/**
	 * List of API error codes and there meaning
	 * @var [type]
	 */
	protected $_apiErrors = [
		'102'  => 'OTP is expired',
		'103'  => 'Entry for OTP not found',
		'104'  => 'MSISDN not found',
		'1025' => 'Insufficient user credit',
		'1702' => 'One of the parameter is missing or OTP is not numeric',
		'1703' => 'Authentication failed',
		'1705' => 'Message does not contain %m',
		'1706' => 'Given destination is invalid',
		'1707' => 'Invalid source',
		'1710' => 'Some error occurred',
		'1715' => 'Response time out',
		//Call Verfication Errors list
		'1010' => 'USERNAME NOT PROVIDED',
		'1011' => 'PASSWORD NOT PROVIDED',
		'1012' => 'JOB TYPE NOT PROVIDED',
		'1013' => 'MOBILE NUMBER(s) TO CALL ARE NOT PROVIDED',
		'1014' => 'USERNAME OR/AND PASSWORD ARE INVALID',
		'1019' => 'INVALID OTP MESSAGE(ONLY NUMERIC AND 6 DIGIT ALLOWED)',
		'2010' => 'TTS MESSAGE IS NOT PROVIDED',
		'3001' => 'SUCCESS',
		'3002' => 'GIVEN COUNT DOES NOT MATCH ACTUAL PULSE COUNT',
		'3010' => 'UNABLE TO GENERATE CALLS'
	];


    protected $_bulkApiErrors = [
        '1702' => 'One of the parameter is missing.',
        '1703' => 'Bad Username Password. This means user authentication has failed',
        '1704' => 'Invalid message type',
        '1705' => 'Invalid message',
        '1706' => 'Invalid MSISDN',
        '1707' => 'Invalid source/sender',
        '1709' => 'Bind failed',
        '1710' => 'Unknown error',
        '1713' => 'To many destinations',
        '1715' => 'Response timeout',
        '1025' => 'Insufficient user credit',
        '1028' => 'Spam message',
        '1032' => 'DND destination',
        '1033' => 'Message template mismatch.',
        '1035' => 'User opt out',
        '1041' => 'Duplicate message',
        '1042' => 'Explicit DND',
    ];


	/**
	 * Query string data of sendOTP process
	 * these will be filled from config file
	 * @var array
	 */
    protected $_queryData;


    /**
     * Mandatory fields for sending SMS if one or more are empty or where not set,
     * the lib will push a corrosponding error to the protected _errors array
     * @var [array]
     */
	protected $_mandatoryFields = [
		'username',
		'password',
        'msisdn',
		'exptime',
		'source',
        'msg',
		'otplen',
	];


    /**
     * Mandatory fields for sending OPT validation request if one or more are empty or where not set,
     * the lib will push a corrosponding error to the protected _errorsOpt array
     * @var [array]
     */
	protected $_mandatoryFieldsOtp = [
		'username',
		'password',
        'msisdn',
	];

    /**
     * Mandatory fields for sending call verficaiton request if one or more are empty or where not set,
     * the lib will push a corrosponding error to the protected _errorsCall array
     * @var [array]
     */
	protected $_mandatoryFieldsCall = [
		'user',
		'pwd',
		'jobType',
		'dest',
	];


    protected $_mandatoryFieldsBulk = [
        'username',
        'password',
        'source',
        'message',
        'destination',
        'type',
        'dlr',
    ];


	/**
	 * optional fields in the send Sms process
	 * @var array
	 */
	protected $_optionalFileds = [
		'tagname',
	];


	/**
	 * initialization function will load data from config file and assign them in there correct places
	 */
	protected function init(){
		$this->_queryData = config('isms.data');
		$this->_logPath = config('isms.log_path');
	}



    /**
     * validateSmsQueryStringData will validate the passed array if it has all the mandatory fields
     * for validating OPT request
     * @param       $data           array of query string data for validate opt request
     * @return array
     */
    protected function validateSmsQueryStringData(array $data = []){
        foreach ($this->_mandatoryFieldsBulk as $mandatoryFiled) {
            if(!isset($data[$mandatoryFiled]) || $data[$mandatoryFiled] === '')
                $this->_errorsBulk[] = $mandatoryFiled . " Is Mandatory field should exist and shouldn't be empty";
        }

        return $data;
    }

	/**
	 * validateQueryStringData will validate the protected _queryData and check if all the mandatory fields are there and are not empty
	 * @return array
	 */
	protected function validateQueryStringData(){
        $data = $this->_queryData;

		foreach ($this->_mandatoryFields as $mandatoryFiled) {
			if(!isset($data[$mandatoryFiled]) || empty($data[$mandatoryFiled]))
				$this->_errors[] = $mandatoryFiled . " Is Mandatory field should exist and shouldn't be empty";
		}

		foreach ($this->_optionalFileds as $optionalField) {
			if(!isset($data[$optionalField]))
				$data[$optionalField] = '';
		}

		return $data;
	}


	/**
	 * validateOptQueryStringData will validate the passed array if it has all the mandatory fields
	 * for validating OPT request
	 * @param  		$data 			array of query string data for validate opt request
	 * @return array
	 */
	protected function validateOtpQueryStringData(array $data = []){
		foreach ($this->_mandatoryFieldsOtp as $mandatoryFiled) {
			if(!isset($data[$mandatoryFiled]) || empty($data[$mandatoryFiled]))
				$this->_errorsOtp[] = $mandatoryFiled . " Is Mandatory field should exist and shouldn't be empty";
		}

		return $data;
	}


	/**
	 * validateCallData will validate the passed array if it has all the mandatory fields
	 * for verfication via call request
	 * @param  		$data 			array of query string data for call verfication request
	 * @return array
	 */
	protected function validateCallData(array $data = []){
		foreach ($this->_mandatoryFieldsCall as $mandatoryFiled) {
			if(!isset($data[$mandatoryFiled]) || empty($data[$mandatoryFiled]))
				$this->_errorsCall[] = $mandatoryFiled . " Is Mandatory field should exist and shouldn't be empty";
		}

		return $data;
	}
}
