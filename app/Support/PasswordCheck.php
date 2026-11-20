<?php

namespace App\Support;

use App\Models\Merchant;
use Illuminate\Support\Facades\Hash;

/**
 * Checks a password against the stored one. Old passwords are stored as MD5 (weak); they keep working,
 * and the moment someone signs in correctly the stored value is replaced by a strong hash (bcrypt).
 */
class PasswordCheck
{
    public static function user($email, $plain)
    {
        $user = Merchant::where('status', 1)->where('email', $email)->first();
        if (!$user || !self::matches($user, $plain)) { return null; }
        return $user;
    }

    private static function matches($user, $plain)
    {
        $stored = (string) $user->password;
        if (strlen($stored) === 60 && str_starts_with($stored, '$2y$')) {
            return Hash::check($plain, $stored);
        }
        if (hash_equals($stored, md5($plain))) {
            $user->password = Hash::make($plain);   // upgrade on the first correct sign-in
            $user->save();
            return true;
        }
        return false;
    }
}
