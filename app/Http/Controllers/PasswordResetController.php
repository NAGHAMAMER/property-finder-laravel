<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const CODE_EXPIRATION_MINUTES = 10;

    /* Normal user - API */
    public function sendUserCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $this->findNormalUser($request->string('email')->toString());
        if (! $user) {
            return response()->json(['message' => 'لا يوجد حساب مستخدم عادي بهذا البريد الإلكتروني.'], 404);
        }

        $this->issueAndSendCode($user);

        return response()->json([
            'message' => 'تم إرسال رمز استعادة كلمة المرور إلى بريدك الإلكتروني. الرمز صالح لمدة 10 دقائق.',
        ]);
    }

    public function resetUserPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $this->findNormalUser($request->string('email')->toString());
        if (! $user) {
            return response()->json(['message' => 'لا يوجد حساب مستخدم عادي بهذا البريد الإلكتروني.'], 404);
        }

        $error = $this->validateCode($user->email, $request->string('code')->toString());
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $this->completePasswordReset($user, $request->string('password')->toString());
        $token = $user->createToken('mobile-password-reset')->plainTextToken;

        return response()->json([
            'message' => 'تم تغيير كلمة المرور وتسجيل الدخول بنجاح.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
        ]);
    }

    public function changeAuthenticatedPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();
        if (! $user || $user->isAdmin()) {
            return response()->json(['message' => 'هذا المسار مخصص للمستخدم العادي.'], 403);
        }

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $this->completePasswordReset($user, $request->string('password')->toString());
        $token = $user->createToken('mobile-password-change')->plainTextToken;

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
        ]);
    }

    /* Normal user - Blade Web */
    public function showUserForgot(): View
    {
        return view('user.auth.forgot-password');
    }

    public function sendUserCodeWeb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $this->findNormalUser($validated['email']);
        if (! $user) {
            return back()
                ->withErrors(['email' => 'لا يوجد حساب مستخدم عادي بهذا البريد الإلكتروني.'])
                ->onlyInput('email');
        }

        $this->issueAndSendCode($user);

        // Sending the code only opens the reset form. It never logs the user in.
        return redirect()
            ->route('user.password.reset.form', ['email' => $user->email])
            ->with('success', 'تم إرسال رمز من 6 أرقام إلى بريدك الإلكتروني. الرمز صالح لمدة 10 دقائق.');
    }

    public function showUserReset(Request $request): View
    {
        return view('user.auth.reset-password', [
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetUserPasswordWeb(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->findNormalUser($validated['email']);
        if (! $user) {
            return back()
                ->withErrors(['email' => 'لا يوجد حساب مستخدم عادي بهذا البريد الإلكتروني.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $error = $this->validateCode($user->email, $validated['code']);
        if ($error) {
            return back()
                ->withErrors(['code' => $error])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        // Only after the code and password are valid do we change the password,
        // create a new token, and enter the user application.
        $this->completePasswordReset($user, $validated['password']);
        $loginToken = $user->createToken('web-password-reset')->plainTextToken;

        return view('user.auth.password-reset-complete', [
            'loginToken' => $loginToken,
            'userData' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /* Admin - Blade Web only */
    public function showAdminForgot(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendAdminCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->where('role', 'admin')->first();
        if (! $user) {
            return back()->withErrors(['email' => 'لا يوجد حساب أدمن بهذا البريد الإلكتروني.'])->onlyInput('email');
        }

        $this->issueAndSendCode($user);

        return redirect()
            ->route('admin.password.reset.form', ['email' => $user->email])
            ->with('success', 'تم إرسال رمز من 6 أرقام إلى بريد الأدمن. الرمز صالح لمدة 10 دقائق.');
    }

    public function showAdminReset(Request $request): View
    {
        return view('admin.auth.reset-password', [
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetAdminPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->where('email', $validated['email'])->where('role', 'admin')->first();
        if (! $user) {
            return back()->withErrors(['email' => 'لا يوجد حساب أدمن بهذا البريد الإلكتروني.'])->withInput();
        }

        $error = $this->validateCode($user->email, $validated['code']);
        if ($error) {
            return back()->withErrors(['code' => $error])->withInput();
        }

        $this->completePasswordReset($user, $validated['password']);

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('admin.login')->with('success', 'تم تغيير كلمة مرور الأدمن بنجاح. يمكنك تسجيل الدخول الآن.');
    }

    private function findNormalUser(string $email): ?User
    {
        return User::query()->where('email', $email)->where('role', '!=', 'admin')->first();
    }

    private function issueAndSendCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        Mail::to($user->email)->send(new PasswordResetCodeMail($code, $user->name));
    }

    private function validateCode(string $email, string $code): ?string
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return 'لم يتم طلب رمز استعادة كلمة المرور لهذا البريد.';
        }

        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : null;
        if (! $createdAt || $createdAt->lt(now()->subMinutes(self::CODE_EXPIRATION_MINUTES))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return 'انتهت صلاحية الرمز. اطلب رمزًا جديدًا.';
        }

        if (! Hash::check($code, $record->token)) {
            return 'رمز التحقق غير صحيح.';
        }

        return null;
    }

    private function completePasswordReset(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
    }
}
