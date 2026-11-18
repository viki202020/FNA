<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sign-in in two steps: password, then the 6-digit code from an authenticator app.
 * A user with no authenticator yet sets one up right after the password step.
 * Recovery codes (one-time) are shown once, at setup.
 */
class AuthController extends Controller
{
    private function pendingUser()
    {
        $id = session('pending_id');
        if (!$id || (time() - (int) session('pending_at', 0)) > 600) {
            session()->forget(['pending_id', 'pending_at', 'totp_setup_secret']);
            return null;
        }
        return Merchant::where('id', $id)->where('status', 1)->first();
    }

    private function completeLogin($user)
    {
        session()->forget(['pending_id', 'pending_at', 'totp_setup_secret']);
        session()->regenerate();
        session()->put('id', $user->id);
        session()->put('merchant_id', $user->id);
        session()->put('name', $user->name);
        session()->put('mobile', $user->mobile);
        session()->put('email', $user->email);
    }

    // step 1: email and password
    public function password(Request $req)
    {
        $req->validate(['username' => 'required', 'password' => 'required']);
        $key = 'login:' . strtolower($req->input('username')) . '|' . $req->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['Too many attempts. Try again in ' . ceil(RateLimiter::availableIn($key) / 60) . ' minutes.']);
        }
        $user = Merchant::where('status', 1)->where('email', $req->input('username'))
                ->where('password', md5($req->input('password')))->first();
        if (!$user) {
            RateLimiter::hit($key, 900);
            return back()->withErrors(['Invalid username/password']);
        }
        RateLimiter::clear($key);
        session()->regenerate();
        session()->put('pending_id', $user->id);
        session()->put('pending_at', time());
        return redirect($user->totp_enabled ? 'login/code' : 'login/setup');
    }

    // step 2: the code
    public function codeForm()
    {
        if (!$this->pendingUser()) { return redirect('login'); }
        return view('login.code');
    }

    public function codeCheck(Request $req)
    {
        $user = $this->pendingUser();
        if (!$user) { return redirect('login'); }
        if (!$user->totp_enabled) { return redirect('login/setup'); }
        $key = 'totp:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['Too many wrong codes. Wait ' . ceil(RateLimiter::availableIn($key) / 60) . ' minutes, or use a recovery code.']);
        }
        $input = trim((string) $req->input('code'));
        $step = Totp::matchStep((string) $user->totp_secret, $input);
        if ($step !== null && $step > (int) $user->totp_last_step) {
            $user->totp_last_step = $step;       // a code can be used only once
            $user->save();
            RateLimiter::clear($key);
            $this->completeLogin($user);
            return redirect('merchant');
        }
        // a one-time recovery code
        $codes = json_decode((string) $user->recovery_codes, true) ?: [];
        $hash = hash('sha256', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input)));
        if (strlen($input) > 6 && ($i = array_search($hash, $codes, true)) !== false) {
            unset($codes[$i]);
            $user->recovery_codes = json_encode(array_values($codes));
            $user->save();
            RateLimiter::clear($key);
            $this->completeLogin($user);
            session()->flash('rule_msg', 'You signed in with a recovery code. ' . count($codes) . ' left.');
            return redirect('merchant');
        }
        RateLimiter::hit($key, 900);
        return back()->withErrors(['That code is not right. Check the code in your app and try again.']);
    }

    // first time: set up the authenticator
    public function setupForm()
    {
        $user = $this->pendingUser();
        if (!$user) { return redirect('login'); }
        if ($user->totp_enabled) { return redirect('login/code'); }
        if (!session('totp_setup_secret')) { session()->put('totp_setup_secret', Totp::newSecret()); }
        $secret = session('totp_setup_secret');
        return view('login.setup', ['secret' => $secret, 'uri' => Totp::uri($secret, $user->email)]);
    }

    public function setupCheck(Request $req)
    {
        $user = $this->pendingUser();
        if (!$user || $user->totp_enabled) { return redirect('login'); }
        $secret = session('totp_setup_secret');
        $step = $secret ? Totp::matchStep($secret, (string) $req->input('code')) : null;
        if ($step === null) {
            return back()->withErrors(['That code is not right. Scan the picture again, then type the 6 digits shown in your app.']);
        }
        $plain = [];
        for ($i = 0; $i < 8; $i++) { $plain[] = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2))); }
        $user->totp_secret = $secret;
        $user->totp_enabled = 1;
        $user->totp_last_step = $step;
        $user->recovery_codes = json_encode(array_map(function ($c) { return hash('sha256', preg_replace('/[^A-Z0-9]/', '', $c)); }, $plain));
        $user->save();
        session()->forget('totp_setup_secret');
        session()->flash('recovery_codes', $plain);   // shown once
        return redirect('login/recovery');
    }

    public function recovery()
    {
        $user = $this->pendingUser();
        if (!$user || !session('recovery_codes')) { return redirect('login'); }
        session()->keep('recovery_codes');
        return view('login.recovery', ['codes' => session('recovery_codes')]);
    }

    public function finish()
    {
        $user = $this->pendingUser();
        if (!$user || !$user->totp_enabled) { return redirect('login'); }
        $this->completeLogin($user);
        return redirect('merchant');
    }
}
