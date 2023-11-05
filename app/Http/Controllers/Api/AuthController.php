<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Jobs\SendVerificationMailJob;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Verification;
use Validator;

class AuthController extends Controller
{
    public function _register(RegisterRequest $request){

        $data = new \stdClass();
        $data = $request->all();
        $user = User::create($data); 

        if (!$user->save()) { 
          return returnErrorResponse('Unable to register user. Please try again later'); 
        } 

         $data = [
                    'email' => $user->email,
                    'name' => $user->name,
                    'userId' => $user->id,
                ];
        // dd($data);
        dispatch(new SendVerificationMailJob($data));

       //Send Mobile OTP..Pending         
       
       return returnSuccessResponse('You are registered successfully. ', $user); 

    }


    public function verifyOtp(Request $request){
        
        $rules = [ 
                 //'user_id' => 'required',
                 'otp' => 'required',
                 'verify_type' => 'required|in:email,phone',
                 ];
        $validator = Validator::make($request->all(), $rules); 

        if ($validator->fails()) 
        { 
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->verify_type == "email") {
           
           if ($user = User::whereEmail($request->email)->first()){
             
              $userEmailVerification = Verification::whereUsername($request->email)->first();
              
               if($userEmailVerification){

                if ($userEmailVerification->otp == $request->otp) {
                  
                   $user->email_verified = 1;
                   $user->email_verified_at = now();

                   if ($request->deviceToken != null) {

                        $user->device_token = $request->deviceToken;
                        $user->device_type = $request->deviceType;
                    }


                    if ($user->save()) {

                        $userEmailVerification->delete();

                        $authToken = $user->createToken('authToken')->plainTextToken;
                        //$returnArr = $user->jsonResponse();
                        $returnArr = $user;
                        $returnArr['auth_token'] = $authToken; 
                        return returnSuccessResponse('Otp verified successfully', $returnArr);
                        
                    } 

                } else{
                   return returnNotFoundResponse('OTP Not Matched');  
                }


               }   

           }else{
              return returnNotFoundResponse('User Not Found');
           } 

        }
        else if ($request->verify_type == "phone") {
           
            if ($user = User::where('phone_number', $request->phone_number)->first()){

                if ($verificationInfo = Verification::where('username', $request->phone_number)->first()) {
                   
                    if ($verificationInfo->otp == $request->otp) {

                    $user->phone_verified = 1;
                    $user->phone_verified_at = now();

                   if ($request->deviceToken != null) {

                        $user->device_token = $request->deviceToken;
                        $user->device_type = $request->deviceType;
                    }


                    if ($user->save()) {

                        $verificationInfo->delete();

                        $authToken = $user->createToken('authToken')->plainTextToken;
                        //$returnArr = $user->jsonResponse();
                        $returnArr = $user;
                        $returnArr['auth_token'] = $authToken; 
                        return returnSuccessResponse('Otp verified successfully', $returnArr);
                        
                    }  

                    }
                    else{
                   return returnNotFoundResponse('OTP Not Matched');  
                } 

                }

                
            }else{
              return returnNotFoundResponse('User Not Found');
           } 


        }

    }




    
}
