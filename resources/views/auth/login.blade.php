<!-- resources/views/auth/login.blade.php -->

@extends('layouts.base')

@section('title', 'SIIMMS | Authorized Access Only')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login/login.css') }}">
    <link rel="icon" href="{{ asset('images/icon.png') }}" type="image/x-icon">
@endpush

@section('content')

    <div class="bg-pattern"></div>

    <div class="card">
        <div class="card-accent"></div>

        <div class="authority-badge-wrapper">
            <div class="authority-badge">
                <svg class="badge-seal" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer ring -->
                    <circle cx="60" cy="60" r="56" fill="none" stroke="#1a3557" stroke-width="3"/>
                    <circle cx="60" cy="60" r="50" fill="#1a3557"/>
                    <path d="M60 22 L80 32 L80 58 Q80 76 60 86 Q40 76 40 58 L40 32 Z" fill="#c9a84c"/>
                    <path d="M60 28 L76 36 L76 58 Q76 72 60 80 Q44 72 44 58 L44 36 Z" fill="#1a3557"/>
                    <!-- Lock icon centered in shield -->
                    <rect x="53" y="52" width="14" height="11" rx="2" fill="#c9a84c"/>
                    <path d="M55 52 L55 48 Q55 43 60 43 Q65 43 65 48 L65 52" fill="none" stroke="#c9a84c" stroke-width="2.2" stroke-linecap="round"/>
                    <circle cx="60" cy="57" r="1.8" fill="#1a3557"/>
                    <!-- Star accents at cardinal points -->
                    <polygon points="60,2 61.5,6 65,6 62.3,8.2 63.5,12 60,9.5 56.5,12 57.7,8.2 55,6 58.5,6" fill="#c9a84c"/>
                    <polygon points="60,118 61.5,114 65,114 62.3,111.8 63.5,108 60,110.5 56.5,108 57.7,111.8 55,114 58.5,114" fill="#c9a84c"/>
                    <polygon points="2,60 6,61.5 6,65 8.2,62.3 12,63.5 9.5,60 12,56.5 8.2,57.7 6,55 6,58.5" fill="#c9a84c"/>
                    <polygon points="118,60 114,61.5 114,65 111.8,62.3 108,63.5 110.5,60 108,56.5 111.8,57.7 114,55 114,58.5" fill="#c9a84c"/>
                    <!-- Text arc top -->
                    <path id="top-arc" d="M 18,60 A 42,42 0 0,1 102,60" fill="none"/>
                    <text font-size="7.5" fill="#c9a84c" font-family="DM Sans, sans-serif" font-weight="600" letter-spacing="2">
                        <textPath href="#top-arc" startOffset="8%">INMATE MANAGEMENT</textPath>
                    </text>
                    <!-- Text arc bottom -->
                    <g transform="translate(0, -10)">
                        <path id="bot-arc" d="M 13,78 A 28,28 0 0,0 105,60" fill="none"/>
                        <text font-size="7.5" fill="#c9a84c" font-family="DM Sans, sans-serif" font-weight="600" letter-spacing="2">
                            <textPath href="#bot-arc" startOffset="5%">& MONITORING SYSTEM</textPath>
                        </text>
                    </g>
                </svg>
                <div class="badge-label">
                    <span class="badge-label-top">AUTHORIZED PERSONNEL ONLY</span>
                    <span class="badge-label-sub">Law Enforcement · Wardens · Admin</span>
                </div>
            </div>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
            <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Create Account</button>
        </div>

        <div class="card-body">

            {{-- ── LOGIN PANEL ── --}}
            <div class="panel active" id="panel-login">

                <div class="panel-header">
                    <div class="badge">Secure Access</div>
                    <h2>Welcome Back</h2>
                    <p>Integrated Inmate Management and Monitoring System</p>
                </div>

                <div class="divider"><span>Sign in to continue</span></div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="_form" value="login">

                    <div class="form-group">
                        <label for="login-badge">Authorized Badge</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="text" id="login-badge" name="badge_number" required
                                placeholder="KVRED12"
                                value="{{ old('badge_number') }}"
                                autocomplete="off"
                                style="text-transform:uppercase">
                            <span class="email-status-icon" id="login-email-status"></span>
                        </div>
                        <div class="email-hint" id="login-email-hint"></div>
                    </div>

                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="login-password" name="password" required placeholder="••••••••" autocomplete="current-password">
                        </div>
                    </div>

                    <div class="remember-row">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn-submit" id="login-submit-btn">Sign In</button>
                </form>

                <p class="switch-prompt">
                    Don't have an account? <a onclick="switchTab('register')">Create one</a>
                </p>
            </div>

            {{-- ── REGISTER PANEL ── --}}
            <div class="panel" id="panel-register">

                <div class="panel-header">
                    <div class="badge">New Account</div>
                    <h2>Request Access</h2>
                    <p>Authorized Personnel Registration</p>
                </div>

                <div class="divider"><span>Fill in your details</span></div>

                @if ($errors->any() && old('_form') === 'register')
                    <div class="error-box">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <input type="hidden" name="_form" value="register">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="first-name" name="first_name" required placeholder="Juan" value="{{ old('first_name') }}" autocomplete="given-name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="last-name" name="last_name" required placeholder="dela Cruz" value="{{ old('last_name') }}" autocomplete="family-name">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-email">Email Address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="reg-email" name="email" required
                                   placeholder="you@agency.gov.ph"
                                   value="{{ old('email') }}"
                                   autocomplete="email"
                                   oninput="validateEmail(this, 'reg-email-status')">
                            <span class="email-status-icon" id="reg-email-status"></span>
                        </div>
                        <div class="email-hint" id="reg-email-hint"></div>
                    </div>

                    <div class="form-group">
                        <label for="reg-badge">Authorized Badge</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M9 9h6M9 13h4"/>
                                <circle cx="7" cy="9" r="1" fill="currentColor"/>
                            </svg>
                            <input type="text" id="reg-badge" name="badge_number" required
                                   placeholder="KV12RED"
                                   value="{{ old('badge_number') }}"
                                   autocomplete="off"
                                   style="text-transform:uppercase">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="reg-password" name="password" required placeholder="Min. 8 characters" autocomplete="new-password" oninput="checkStrength(this.value)">
                        </div>
                        <div class="strength-bar">
                            <div class="strength-seg" id="seg1"></div>
                            <div class="strength-seg" id="seg2"></div>
                            <div class="strength-seg" id="seg3"></div>
                            <div class="strength-seg" id="seg4"></div>
                        </div>
                        <div class="strength-label" id="strength-label"></div>
                    </div>

                    <div class="form-group">
                        <label for="reg-password-confirm">Confirm Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"/>
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="reg-password-confirm" name="password_confirmation" required placeholder="Re-enter password" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="remember-row">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#" style="color: var(--navy-light); text-decoration: none; font-weight: 500;">Terms of Use</a> and system policies</label>
                    </div>

                    <button type="submit" class="btn-submit" id="reg-submit-btn">Create Account</button>
                </form>

                <p class="switch-prompt">
                    Already have an account? <a onclick="switchTab('login')">Sign in</a>
                </p>
            </div>

        </div>

        <div class="card-footer">
            <p>⚠ Unauthorized access is a criminal offense &nbsp;|&nbsp; © {{ date('Y') }} <span>IIMMS</span>. All rights reserved.</p>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.requireOtpVerification = @json(!app()->environment('local'));
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('status'))
                showToast(@json(session('status')), 'success');
            @endif

            @if ($errors->any())
                showToast(@json($errors->first()), 'error');
                @if (old('_form') === 'register')
                    switchTab('register');
                @endif
            @endif
        });
    </script>
@endpush
