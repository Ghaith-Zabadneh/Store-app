<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\HttpResponses;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StorUserRequest;
use App\Http\Requests\ResetPassword;
use App\Notifications\TestNotification;
use App\Notifications\VerificationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Str;
// use App\Mail\VerificationMail;
// use Illuminate\Support\Facades\Mail;


class UserController extends Controller
{
    use HttpResponses;

   

    function Login(LoginUserRequest $request){
       
        $request->validated($request->only(['email', 'password']));

        if(!Auth::attempt($request->only(['email', 'password']))) {
            return $this->error('', 'Credentials do not match', 401);
        }

        $user = User::where('email', $request->email)->first();
        return $this->success([
            'customer' => [
                'id' => $user->id,
                'name' =>$user->name,
                'numOfNotofocztion' =>$user->numofnotification,
            ],
            'contacts' => [
                'phone' => $user->mobile_phone,
                'email' => $user->email,
                'link' => $user->link
            ],
            'token' => $user->createToken('API Token')->plainTextToken
        ],'User Logged In Successfully');

    }

    public function register(StorUserRequest $request)
    {
        $request->validated($request->only(['name', 'email', 'password','mobile_number']));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile_phone' => $request->mobile_phone,
            'verification_token' => Str::random(40),

        ]);

        
        $user->notify(new VerificationNotification());
       
        //Mail::to($user)->send(new VerificationMail($user));

        return $this->success('','Verification Message sended , plesa open your email box');
    }

    public function forgetpassword (Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ],
        [
            'email.required' => 'Please Input Your Email',
            'email.email' => 'Please Input Correct Email'
        ]);
        $input= $request->only('email');
        $user= User::where('email',$input)->first();
        $code = Str::random(6);
        $user->reset_code = $code;
        $user->reset_code_expiry = now()->addMinutes(15); // Set the reset code to expire after 15 minutes
        $user->save();
        $user->notify(new ResetPasswordNotification($code));

        return $this->success('','Reset Password Message sended , plesa open your email box');


    }
    public function ResetPassword (ResetPassword $request){

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->reset_code !== $request->code || $user->reset_code_expiry < now()) {
            return $this->error([], 'Code is not correct or expired', 401);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->reset_code = null;
        $user->reset_code_expiry = null;
        $user->save();

        return $this->success('', 'Password Updated');

    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success([
            'message' => 'You Have Succesfully Been Logged Out And Your Token Has Been Removed'
        ]);
    }
       

}
