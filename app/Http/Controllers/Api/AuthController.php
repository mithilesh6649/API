<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Jobs\SendVerificationMailJob;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Verification;
use Validator,Auth;

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

       //Send Mobile OTP..Pending...

            $verfication = Verification::updateOrCreate([
               'username' => $request->phone_number,
            ],[
            // 'expiry_at' => ,
             'random_string' => generateRandomString(),
             'otp' => generateOTP()
            ]);

            $otpData['otp'] = $verfication->otp;
            $otpData['otp_message_slug'] = 'new_user_registration';
            $otpData['phone_number'] = $request->country_code.$request->phone_number;
            // dd($otpData);
            //Send Msg in progress.... 


       
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

                   if ($request->fcm_token != null) {

                        $user->fcm_token = $request->fcm_token;
                        $user->device_type = $request->device_type;
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

                   if ($request->fcm_token != null) {

                        $user->fcm_token = $request->fcm_token;
                        $user->device_type = $request->device_type;
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

    
     /**
     * Fnc _resendOTP trigger to resend the OTP in email
     * Create SendVerificationMailJob Job
     *
     * @return APIResponse
     */
    
    public function resendOtp(Request $request){
          $rules = [ 
                 //'user_id' => 'required',
                 'otp' => 'required',
                 'resend_type' => 'required|in:email,phone',
                 ];
        $validator = Validator::make($request->all(), $rules); 

        if ($validator->fails()) 
        { 
            return response()->json(['errors' => $validator->errors()], 422);
        }

         if ($request->resend_type == "email") {
          
          if($user = User::whereEmail($request->email)->first()) {

             $data = [
                    'email' => $user->email,
                    'name' => $user->name,
                    'userId' => $user->id,
                ];

                dispatch(new SendVerificationMailJob($data));

            return returnSuccessResponse('Otp resend successfully!', $user);    

          }else{
            return returnNotFoundResponse('User Not Found');
          }   

         }else if($request->resend_type == "phone"){
            
            if ($user = User::where('phone_number', $request->phone_number)->first()){

               $verfication = Verification::updateOrCreate([
                    'username' => $request->phone_number,
                ],[
                   // 'expiry_at' => ,
                    'random_string' => generateRandomString(),
                    'otp' => generateOTP()
                ]);

                $otpData['otp'] = $verfication->otp;
                $otpData['otp_message_slug'] = 'new_user_registration';
                $otpData['phone_number'] = $request->country_code.$request->phone_number;
               // dd($otpData);
               //Send Msg in progress....
             return returnSuccessResponse('Otp resend successfully!',$user ); 
            }else{
             return returnNotFoundResponse('User Not Found');    
            }
         }
 
    }



    public function login(LoginRequest $request){
        
        $inputArr = $request->all(); 
        $userObj = User::where('email', $inputArr['email'])->first();

        if (empty($userObj))
         return returnNotFoundResponse('User Not found.'); 

        // if ($userObj->status == 0) 
        //  return returnErrorResponse("Your account is inactive please contact with admin."); 
        
        if (empty($userObj->email_verified_at))
          return returnNotFoundResponse('Please verify your email.', $userObj);

        if (!Auth::attempt(['email' => $inputArr['email'], 'password' => $inputArr['password']])) { 
          return returnNotFoundResponse('Invalid credentials'); 
        } 

         $user = auth()->user();

         if ($request->fcm_token != null ) {
           auth()->user()->update(['fcm_token' => $request->fcm_token, 'device_type' => $request->device_type]);
         }

         $user->tokens()->delete(); 

        $authToken = $user->createToken('authToken')->plainTextToken; 
        $returnArr = $userObj; 
        $returnArr['auth_token'] = $authToken; 
        return returnSuccessResponse('User logged in successfully', $returnArr); 
        
    }

    /**
     * Fnc logout will logout the user from the current app
     * Revoke its token
     *
     */

    public function logout(Request $request){
       $user = auth()->user()->tokens();
       $user->delete();
       return returnSuccessResponse('User logged out successfully');
    }


}
