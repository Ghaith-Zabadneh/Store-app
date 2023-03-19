<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\HttpResponses;

class VerificationController extends Controller
{
    use HttpResponses;

    public function verify($token)
{
    $user = User::where('verification_token', $token)->firstOrFail();

    $user->verified = true;
    $user->verification_token = null;
    $user->email_verified_at = now();
    $user->save();

    return $this->success([
        'user' => $user,
        'token' => $user->createToken('API Token')->plainTextToken
    ],'Your email has been verified.');
}

}
