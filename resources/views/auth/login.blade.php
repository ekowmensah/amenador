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

    <style>
        body {
            background:
                radial-gradient(circle at 12% 18%, rgba(21, 87, 176, .09), transparent 26%),
                linear-gradient(135deg, #f7f9fd 0%, #edf3fc 100%) !important;
            background-attachment: fixed !important;
        }

        .right-col {
            min-height: 100vh;
        }

        .modern-login-shell {
            width: min(1120px, calc(100% - 30px));
            min-height: 650px;
            margin: 18px auto 0;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 30px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 30px 80px rgba(15, 23, 42, .28);
        }

        .modern-login-visual {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 58px;
            overflow: hidden;
            color: #fff;
            background-image:
                linear-gradient(145deg, rgba(7, 31, 74, .97) 0%, rgba(11, 63, 136, .87) 55%, rgba(21, 87, 176, .74) 100%),
                url("{{ asset('img/pharmacy-hero.png') }}");
            background-position: center, center;
            background-repeat: no-repeat;
            background-size: cover, cover;
        }

        .modern-login-visual::before,
        .modern-login-visual::after {
            content: '';
            position: absolute;
            z-index: -1;
            border-radius: 999px;
            filter: blur(2px);
        }

        .modern-login-visual::before {
            width: 330px;
            height: 330px;
            top: -105px;
            right: -120px;
            background: rgba(250, 204, 21, .3);
        }

        .modern-login-visual::after {
            width: 410px;
            height: 410px;
            bottom: -230px;
            left: -150px;
            border: 70px solid rgba(255, 255, 255, .08);
        }

        .modern-login-visual .modern-login-copy,
        .modern-login-visual .modern-login-brand,
        .modern-login-visual .modern-login-footnote {
            position: relative;
            z-index: 2;
        }

        .modern-login-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            width: fit-content;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .modern-login-brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .32);
            border-radius: 13px;
            background: rgba(255, 255, 255, .14);
            backdrop-filter: blur(12px);
        }

        .modern-login-copy {
            max-width: 470px;
            margin: auto 0;
        }

        .modern-login-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            padding: 7px 12px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            color: #dbeafe;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .modern-login-copy h1 {
            margin: 0 0 20px;
            color: #fff;
            font-size: clamp(34px, 4vw, 52px);
            font-weight: 750;
            line-height: 1.08;
            letter-spacing: -.045em;
        }

        .modern-login-copy p {
            max-width: 420px;
            margin: 0;
            color: #dbe7ff;
            font-size: 16px;
            line-height: 1.75;
        }

        .modern-login-points {
            display: flex;
            gap: 22px;
            margin-top: 36px;
            color: #eaf1ff;
            font-size: 13px;
            font-weight: 600;
        }

        .modern-login-points span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modern-login-points i {
            color: #fde047;
        }

        .modern-login-footnote {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
        }

        .modern-login-footnote a {
            color: #fff;
            font-weight: 700;
        }

        .modern-login-footnote a:hover {
            color: #fde047;
            text-decoration: none;
        }

        .mart-preview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 420px;
            margin-top: 34px;
        }

        .mart-preview-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 11px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 13px;
            background: rgba(255, 255, 255, .11);
            backdrop-filter: blur(8px);
        }

        .mart-preview-icon {
            display: grid;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            place-items: center;
            border-radius: 9px;
            background: #fff;
            color: #1557b0;
        }

        .mart-preview-item strong,
        .mart-preview-item small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mart-preview-item strong {
            color: #fff;
            font-size: 11px;
        }

        .mart-preview-item small {
            margin-top: 2px;
            color: #d1fae5;
            font-size: 9px;
        }

        .modern-login-form-panel {
            display: flex;
            align-items: center;
            padding: 58px 62px;
            background: #fff;
        }

        .modern-login-form-wrap {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .modern-login-heading {
            margin-bottom: 34px;
        }

        .modern-login-heading h2 {
            margin: 0 0 9px;
            color: #101828;
            font-size: 31px;
            font-weight: 750;
            letter-spacing: -.035em;
        }

        .modern-login-heading p {
            margin: 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.6;
        }

        .modern-field {
            margin-bottom: 21px;
        }

        .modern-field-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 9px;
        }

        .modern-field label {
            margin: 0;
            color: #344054;
            font-size: 13px;
            font-weight: 700;
        }

        .modern-field-control {
            position: relative;
        }

        .modern-field-control > i {
            position: absolute;
            top: 50%;
            left: 16px;
            z-index: 1;
            color: #98a2b3;
            font-size: 15px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .modern-field-input {
            width: 100%;
            height: 52px;
            padding: 0 48px 0 46px;
            border: 1px solid #d0d5dd;
            border-radius: 13px;
            outline: none;
            background: #fff;
            color: #101828;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .03);
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .modern-field-input::placeholder {
            color: #98a2b3;
        }

        .modern-field-input:focus {
            border-color: #1557b0;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(21, 87, 176, .12);
        }

        .modern-field.has-error .modern-field-input {
            border-color: #f04438;
        }

        .modern-field .help-block {
            margin: 7px 2px 0;
            color: #d92d20;
            font-size: 12px;
        }

        .modern-forgot-link,
        .modern-register-link {
            color: #1557b0;
            font-size: 12px;
            font-weight: 700;
            transition: color .2s ease;
        }

        .modern-forgot-link:hover,
        .modern-register-link:hover {
            color: #0b2f6b;
            text-decoration: none;
        }

        .modern-password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            display: grid;
            width: 34px;
            height: 34px;
            padding: 0;
            place-items: center;
            border: 0;
            border-radius: 9px;
            outline: none;
            background: transparent;
            color: #667085;
            transform: translateY(-50%);
        }

        .modern-password-toggle:hover,
        .modern-password-toggle:focus {
            background: #f2f4f7;
            color: #344054;
        }

        .modern-login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 2px 0 24px;
        }

        .modern-remember {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: #475467;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .modern-remember input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: #1557b0;
        }

        .modern-login-submit {
            display: flex;
            width: 100%;
            height: 52px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 13px;
            outline: none;
            background: linear-gradient(135deg, #0b2f6b, #1557b0);
            color: #fff;
            font-size: 14px;
            font-weight: 750;
            box-shadow: 0 10px 24px rgba(21, 87, 176, .25);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .modern-login-submit:hover,
        .modern-login-submit:focus {
            color: #fff;
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(21, 87, 176, .3);
        }

        .modern-login-submit:active {
            transform: translateY(0);
        }

        .modern-login-register {
            margin: 25px 0 0;
            color: #667085;
            font-size: 13px;
            text-align: center;
        }

        .modern-demo-panel {
            grid-column: 1 / -1;
            padding: 24px 30px;
            border-top: 1px solid #eaecf0;
            background: #f8fafc;
        }

        .modern-demo-panel h3 {
            margin: 0 0 14px;
            color: #344054;
            font-size: 14px;
            font-weight: 700;
        }

        .modern-demo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .modern-demo-grid a {
            padding: 8px 12px;
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            background: #fff;
            color: #344054;
            font-size: 12px;
            font-weight: 600;
        }

        .modern-demo-grid a:hover {
            border-color: #8098e8;
            color: #3659c9;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .modern-login-shell {
                max-width: 560px;
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .modern-login-visual {
                min-height: 270px;
                padding: 38px;
            }

            .modern-login-copy {
                margin: 48px 0 12px;
            }

            .modern-login-copy h1 {
                font-size: 34px;
            }

            .modern-login-copy p,
            .modern-login-points,
            .mart-preview {
                display: none;
            }

            .modern-login-footnote {
                margin-top: 22px;
            }

            .modern-login-form-panel {
                padding: 45px 38px 48px;
            }
        }

        @media (max-width: 520px) {
            .modern-login-shell {
                width: calc(100% - 20px);
                margin-top: 4px;
                border-radius: 22px;
            }

            .modern-login-visual {
                min-height: 210px;
                padding: 28px 24px;
            }

            .modern-login-copy {
                margin-top: 32px;
            }

            .modern-login-copy h1 {
                margin-bottom: 8px;
                font-size: 28px;
            }

            .modern-login-footnote {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
                margin-top: 18px;
            }

            .modern-login-eyebrow {
                margin-bottom: 14px;
            }

            .modern-login-form-panel {
                padding: 36px 24px 40px;
            }

            .modern-login-heading h2 {
                font-size: 27px;
            }
        }
    </style>

    <main class="modern-login-shell">
        <section class="modern-login-visual" aria-label="{{ config('app.name', 'ultimatePOS') }}">
            <div class="modern-login-brand">
                <span class="modern-login-brand-mark"><i class="fas fa-prescription-bottle-alt" aria-hidden="true"></i></span>
                <span>{{ config('app.name', 'ultimatePOS') }}</span>
            </div>

            <div class="modern-login-copy">
                <span class="modern-login-eyebrow"><i class="fas fa-clinic-medical" aria-hidden="true"></i> Pharmacy operations platform</span>
                <h1>Better pharmacy control. Better patient service.</h1>
                <p>Dispense faster, protect medicine availability and keep every batch, expiry and transaction visible from one dependable workspace.</p>
                <div class="modern-login-points">
                    <span><i class="fas fa-shield-alt"></i> Secure access</span>
                    <span><i class="fas fa-check-circle"></i> FEFO inventory</span>
                </div>
                <div class="mart-preview" aria-hidden="true">
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-prescription"></i></span>
                        <span><strong>Fast dispensing</strong><small>Search, check &amp; sell</small></span>
                    </div>
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-hourglass-half"></i></span>
                        <span><strong>Expiry control</strong><small>FEFO-ready batches</small></span>
                    </div>
                    <div class="mart-preview-item">
                        <span class="mart-preview-icon"><i class="fas fa-capsules"></i></span>
                        <span><strong>Medicine stock</strong><small>Live availability</small></span>
                    </div>
                </div>
            </div>

            <div class="modern-login-footnote">
                <span>Developed by MensWeb Solutions</span>
                <a href="https://wa.me/233545644749" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-phone-alt" aria-hidden="true"></i> 0545644749
                </a>
            </div>
        </section>

        <section class="modern-login-form-panel">
            <div class="modern-login-form-wrap">
                <header class="modern-login-heading">
                    <h2>@lang('lang_v1.welcome_back')</h2>
                    <p>@lang('lang_v1.login_to_your') {{ config('app.name', 'ultimatePOS') }}</p>
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
                                <a href="{{ route('password.request') }}" class="modern-forgot-link" tabindex="-1">
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
