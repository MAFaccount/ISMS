<?php

namespace ISMS\ISMS;

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use ISMS\Traits\ISMSMessageTrait;


class ISMS{
	use ISMSMessageTrait;

	public function __construct(){
        $this->init();
		$this->_http = new Client;
		$this->_logger = new Logger('ISMS');
		$this->_logger->pushHandler(new StreamHandler($this->_logPath, Logger::WARNING));
	}

	/**
	 * @param  string 			$msg 			Ths SMS message
	 * @param  string 			$msisdn 		Mobile number to send the message to
	 * @param  string 			$method 		HTTP Request Method
	 * @return json
	 */
	public function sendSms($msg = '' , $msisdn = '' , $method = 'GET'){
		$msg =  $msg . '%m';

        $this->_queryData['msg'] = $msg;
        $this->_queryData['msisdn'] = $msisdn;

		$data = $this->validateQueryStringData();

		if($this->_errors){
			$jsonErrors = json_encode(['status' => 'failed' , 'errors' => $this->_errors]);
			$this->logError($jsonErrors);
			return $jsonErrors;
		}

		$url = config('isms.send_sms_url');


		$res = $this->_http->request($method ,$url , ['query' => $data]);
		$body = $res->getBody();
        $data = $body->read(100);

        //check for success 
        if(preg_match('/^1701/', $data))
        	return json_encode(['status' => 'success' , 'api-errors' => []]);

        //if it didn't success check for Api Failure
		foreach ($this->_apiErrors as $key => $value) {
            if($data == (string) $key){
                $jsonRes = json_encode(['status' => 'failed' , 'api-errors' => [$key => $value]]);
                $this->logError($jsonRes);
                return $jsonRes;
            }
        }
	}


	/**
	 * @param  string 			$msisdn 			Mobile numbre to validate OPT
	 * @return json
	 */
	public function validateOTP($otp = '' , $msisdn = '' , $method = 'GET'){
		$otpQueryData = config('isms.data');

		//unset unneccessary fields
		unset($otpQueryData['exptime']);
        unset($otpQueryData['source']);
		unset($otpQueryData['otplen']);

        //push neccessary fields to be validated
        $otpQueryData['msisdn'] = $msisdn;
		$otpQueryData['otp'] = $otp;

        //validate OptQueryData
		$otpQueryData = $this->validateOptQueryStringData($otpQueryData);

        //if any ISMS errors log and return them
		if($this->_errorsOpt){
			$jsonErrors = json_encode(['status' => 'failed' , 'errors' => $this->_errorsOpt]);
			$this->logError($jsonErrors);
			return $jsonErrors;
		}

        //grab the validation url from config file
		$url = config('isms.send_validation_url');
		$res = $this->_http->request($method ,$url , ['query' => $otpQueryData]);
		$body = $res->getBody();
		$data = $body->read(100);

		//check for success
        if(preg_match('/^101/', $data))
			return json_encode(['status' => 'success' , 'api-errors' => []]);
        
        //Check for api errors during the validation of OTP Process and log if any
        foreach ($this->_apiErrors as $key => $value) {
            if($data == (string) $key){
                $jsonRes = json_encode(['status' => 'failed' , 'api-errors' => [$key => $value]]);
                $this->logError($jsonRes);
                return $jsonRes;
            }
        }
	}


	/**
	 * @param  string 			$mobile 			Mobile numbre to call
	 * @param  string 			$otp 			    OTP code so the API can read it on the call
	 * @param  string 			$method 			Method of HTTP Request
	 * @return json
	 */
	public function call(string $mobile = '' , string $otp = '' , $method = 'GET'){
		//get call verfiction comfiguration data
		$callData = config('isms.call_data');
		$callData['dest'] = $mobile;

		//validate the data
		$callData = $this->validateCallData($callData);

		//check if errors log and return if any
		if($this->_errorsCall){
			$jsonErrors = json_encode(['status' => 'failed' , 'errors' => $this->_errorsCall]);
			$this->logError($jsonErrors);
			return $jsonErrors;
		}

		//push the otp code to the call array
		$callData['otp'] = $otp;

		//get the call endpoint from the config file
		$url = config('isms.call_url');

		//send the request using guzzle and store the response 
		$res = $this->_http->request($method ,$url , ['query' => $callData]);
		$body = $res->getBody();
		//read the response from the stream
        $data = $body->read(100);
		
		//check for success
        if(preg_match('/^3001/', $data))
			return json_encode(['status' => 'success' , 'api-errors' => []]);
        
        //Check for api errors during the call verfication process and log if any
        foreach ($this->_apiErrors as $key => $value) {
            if($data == (string) $key){
                $jsonRes = json_encode(['status' => 'failed' , 'api-errors' => [$key => $value]]);
                $this->logError($jsonRes);
                return $jsonRes;
            }
        }
	}


	/**
	 * @param  string 			$error 			Error message to log in the log files
	 */
	public function logError($error = ''){
		$this->_logger->error($error);
	}

	/**
	 * @param  string 			$warning 			Warning message to log in log files
	 */
	public function logWarning($warning = ''){
		$this->_logger->warning($warning);
	}
}
