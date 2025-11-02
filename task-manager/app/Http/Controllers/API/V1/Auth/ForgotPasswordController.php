<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\API\V1\Auth\ForgotPasswordVerifyOtpRequest;
use App\Http\Requests\API\V1\Auth\ResetPasswordRequest;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function forgotPasswordSendOtp(ForgotPasswordRequest $request)
    {
        try {
            $otp = rand(100000, 999999);
            PasswordResetOtp::updateOrCreate(
                [
                    'email' => $request->email
                ],
                [
                    'otp' => $otp,
                    'expires_at' => Carbon::now()->addMinutes(10)
                ]
            );

            Mail::raw("Your password reset OTP is: $otp. OTP expires in 10 minutes.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Password Reset OTP');
            });
            return $this->success([], 'Password reset OTP sent to your email.');
        } catch (\Exception $e) {
            Log::error('Forgot Password Otp Send Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function forgotPasswordVerifyOtp(ForgotPasswordVerifyOtpRequest $request)
    {
        try {
            $record = PasswordResetOtp::where('email', $request->email)
                ->where('otp', $request->otp)
                ->first();

            if (!$record) {
                return $this->error(['Invalid OTP'], 422);
            }

            if (Carbon::parse($record->expires_at)->isPast()) {
                return $this->error(['OTP has expired'], 422);
            }

            return $this->success('OTP Verified.');
        } catch (\Exception $e) {
            Log::error('Forgot Password Otp Verify Error: ' . $e->getMessage());
            return $this->error();
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $record = PasswordResetOtp::where('email', $request->email)
                ->where('otp', $request->otp)
                ->first();

            if (!$record) {
                return $this->error(['Invalid OTP'], 422);
            }

            if (Carbon::parse($record->expires_at)->isPast()) {
                return $this->error(['OTP has expired'], 422);
            }

            $user = User::where('email', $request->email)->first();
            $user->password = $request->password;
            $user->save();

            $record->delete();

            return $this->success('Password has been reset successfully.');

        } catch (\Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());
            return $this->error();
        }
    }
}
