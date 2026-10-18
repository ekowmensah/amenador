@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')

@section('content')
    @php
        $username = old('username');
        $password = null;

        if (config('app.env') == 'demo') {
            $username = 'admin';
            $password = '123456';
            $demo_types = [
                'all_in_one' => 'admin',
                'super_market' => 'admin',
                'pharmacy' => 'admin-pharmacy',
                'electronics' => 'admin-electronics',
                'services' => 'admin-services',
                'restaurant' => 'admin-restaurant',
                'superadmin' => 'superadmin',
                'woocommerce' => 'woocommerce_user',
                'essentials' => 'admin-essentials',
                'manufacturing' => 'manufacturer-demo',
            ];

            if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
                $username = $demo_types[$_GET['demo_type']];
            }
        }
    @endphp

    <link rel="stylesheet" href="{{ asset('css/bar-pages.css?v=' . filemtime(public_path('css/bar-pages.css'))) }}">
    <main class="modern-login-shell">
        <section class="modern-login-visual" aria-label="{{ config('app.name', 'ultimatePOS') }}">
            <div class="modern-login-brand">
                <span class="modern-login-brand-mark"><i class="fas fa-cocktail" aria-hidden="true"></i></span>
                <span>{{ config('app.name', 'ultimatePOS') }}</span>
            </div>

            <div class="modern-login-copy">
                <span class="modern-login-eyebrow"><i class="fas fa-glass-martini-alt" aria-hidden="true"></i> Behind the bar</span>
                <h1>Good nights.<br>Great service.</h1>
                <p>Your shift starts here. Keep orders flowing, tabs organised and your bar running smoothly, from the first pour to the last call.</p>
                <div class="modern-login-points">
                    <span><i class="fas fa-shield-alt"></i> Secure access</span>
                    <span><i class="fas fa-check-circle"></i> Every pour accounted for</span>
                </div>
                <div class="mart-preview" aria-hidden="true">
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-cocktail"></i></span>
                        <span><strong>Quick orders</strong><small>Pour. Serve. Repeat.</small></span>
                    </div>
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-receipt"></i></span>
                        <span><strong>Open tabs</strong><small>Keep the night moving</small></span>
                    </div>
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-wine-bottle"></i></span>
                        <span><strong>Bar inventory</strong><small>Know your stock</small></span>
                    </div>
                </div>
            </div>

            <div class="modern-login-footnote">
                <a href="{{ url('/') }}">&larr; Back to the bar</a>
                <a href="https://wa.me/233545644749" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-phone-alt" aria-hidden="true"></i> 0545644749
                </a>
            </div>
        </section>

        <section class="modern-login-form-panel">
            <div class="modern-login-form-wrap">
                <header class="modern-login-heading">
                    <span class="bar-eyebrow">STAFF PORTAL</span><h2>Welcome back.</h2>
                    <p>Sign in and get ready for a great shift.</p>
                </header>

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    {{ csrf_field() }}

                    <div class="modern-field {{ $errors->has('username') ? 'has-error' : '' }}">
                        <div class="modern-field-top">
                            <label for="username">@lang('Username')</label>
                        </div>
                        <div class="modern-field-control">
                            <i class="far fa-user" aria-hidden="true"></i>
                            <input class="modern-field-input" id="username" type="text" name="username"
                                value="{{ $username }}" required autofocus autocomplete="username"
                                placeholder="@lang('lang_v1.username')" data-last-active-input="">
                        </div>
                        @if ($errors->has('username'))
                            <span class="help-block"><strong>{{ $errors->first('username') }}</strong></span>
                        @endif
                    </div>

                    <div class="modern-field {{ $errors->has('password') ? 'has-error' : '' }}">
                        <div class="modern-field-top">
                            <label for="password">@lang('Password')</label>
                            @if (config('app.env') != 'demo')
                                <a href="{{ route('password.request') }}" class="modern-forgot-link">
                                    @lang('lang_v1.forgot_your_password')
                                </a>
                            @endif
                        </div>
                        <div class="modern-field-control">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input class="modern-field-input" id="password" type="password" name="password"
                                value="{{ $password }}" required autocomplete="current-password"
                                placeholder="@lang('lang_v1.password')">
                            <button type="button" id="show_hide_icon" class="modern-password-toggle"
                                aria-label="Show password" aria-pressed="false">
                                <i class="far fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                            <span class="help-block"><strong>{{ $errors->first('password') }}</strong></span>
                        @endif
                    </div>

                    <div class="modern-login-options">
                        <label class="modern-remember">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>@lang('lang_v1.remember_me')</span>
                        </label>
                    </div>

                    @if (config('constants.enable_recaptcha'))
                        <div class="form-group">
                            <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                            @if ($errors->has('g-recaptcha-response'))
                                <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
                            @endif
                        </div>
                    @endif

                    <button type="submit" class="modern-login-submit">
                        <span>@lang('lang_v1.login')</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register') && config('constants.allow_registration'))
                    <p class="modern-login-register">
                        {{ __('business.not_yet_registered') }}
                        <a href="{{ route('business.getRegister') }}@if (!empty(request()->lang)){{ '?lang=' . request()->lang }}@endif"
                            class="modern-register-link">{{ __('business.register_now') }}</a>
                    </p>
                @endif
            </div>
        </section>

        @if (config('app.env') == 'demo')
            <section class="modern-demo-panel">
                <h3>Choose a demo business</h3>
                <div class="modern-demo-grid">
                    @foreach ($demo_types as $demo_type => $demo_username)
                        <a href="?demo_type={{ $demo_type }}" class="demo-login" data-admin="{{ $demo_username }}">
                            {{ ucwords(str_replace('_', ' ', $demo_type)) }}
                        </a>
                    @endforeach
                    <a href="{{ url('docs') }}" target="_blank">API documentation</a>
                </div>
            </section>
        @endif
    </main>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.change_lang').click(function() {
                window.location = "{{ route('login') }}?lang=" + $(this).attr('value');
            });

            $('a.demo-login').click(function(e) {
                e.preventDefault();
                $('#username').val($(this).data('admin'));
                $('#password').val("{{ $password }}");
                $('form#login-form').submit();
            });

            $('#show_hide_icon').off('click').on('click', function(e) {
                e.preventDefault();
                const passwordInput = $('#password');
                const isHidden = passwordInput.attr('type') === 'password';

                passwordInput.attr('type', isHidden ? 'text' : 'password');
                $(this)
                    .attr('aria-pressed', isHidden ? 'true' : 'false')
                    .attr('aria-label', isHidden ? 'Hide password' : 'Show password')
                    .html('<i class="far ' + (isHidden ? 'fa-eye-slash' : 'fa-eye') + '" aria-hidden="true"></i>');
            });
        });
    </script>
@endsection
