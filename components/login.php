<!-- Login Screen -->
<div id="loginScreen" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 via-white to-slate-200 px-4">
    <div class="w-full max-w-md">
        <div class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 p-10">
            <div class="text-center mb-8">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600">
                    <img src="logos/logo.jpg" alt="School Logo" class="h-12 w-12 rounded-xl object-cover border-2 border-white/80">
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Bestlink College of the Philippines</h1>
                <p class="mt-1 text-sm text-slate-600">Property Custodian Management System</p>
                <p class="text-xs text-slate-500 mt-1">Sign in to your account</p>
            </div>

            <div id="authMessageContainer" class="space-y-3 mb-6"></div>

            <div id="credentialsStep" class="space-y-6">
                <form id="loginForm" class="space-y-6">
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-medium text-slate-600">Email Address</label>
                        <input type="text" id="username" name="username" required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30" placeholder="you@example.com">
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-slate-600">Password</label>
                        <input type="password" id="password" name="password" required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30" placeholder="Enter your password">
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/30 transition hover:shadow-blue-500/45 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 focus:ring-offset-white">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-xs text-slate-500">
                    Don't have an account?
                    <a href="#" class="font-semibold text-indigo-500 hover:text-indigo-600">Register</a>
                </p>
            </div>

            <div id="otpStep" class="hidden space-y-6">
                <div class="text-center">
                    <p class="text-sm font-medium text-slate-700">Enter the verification code</p>
                    <p class="text-xs text-slate-500 mt-1">We sent a 6-digit code to <span id="otpEmailHint" class="font-semibold text-slate-600"></span></p>
                </div>

                <form id="otpForm" class="space-y-4">
                    <div>
                        <label for="otpCode" class="block text-sm font-medium text-slate-600">Verification Code</label>
                        <input type="text" id="otpCode" name="otpCode" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                               class="tracking-widest text-center text-lg font-semibold w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30" placeholder="••••••">
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/30 transition hover:shadow-blue-500/45 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 focus:ring-offset-white">
                        Verify and Sign In
                    </button>
                </form>

                <div class="flex flex-col gap-2 text-xs text-slate-500">
                    <button type="button" id="resendOtpBtn" class="text-indigo-500 hover:text-indigo-600 font-semibold self-start">Resend code</button>
                    <button type="button" id="changeAccountBtn" class="self-start text-slate-500 hover:text-slate-700">Use a different account</button>
                </div>
            </div>
        </div>

    </div>
</div>