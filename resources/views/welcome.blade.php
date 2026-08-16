@extends('layouts.auth2')
@section('title', config('app.name', 'ultimatePOS'))
@inject('request', 'Illuminate\Http\Request')

@section('content')
    <style>
        body {
            overflow-x: hidden;
            background: #f4f7fc !important;
        }

        body > .container-fluid,
        body > .container-fluid > .row,
        body > .container-fluid > .row > .right-col {
            min-height: 100vh;
        }

        body > .container-fluid > .row > .right-col {
            padding-bottom: 0 !important;
            background:
                radial-gradient(circle at 8% 8%, rgba(21, 87, 176, .11), transparent 24%),
                radial-gradient(circle at 92% 28%, rgba(214, 40, 57, .08), transparent 22%),
                #f4f7fc;
        }

        .mart-welcome {
            width: min(1180px, calc(100% - 30px));
            margin: 10px auto 38px;
            color: #17243b;
            font-family: inherit;
        }

        .mart-hero {
            position: relative;
            display: grid;
            min-height: 650px;
            grid-template-columns: minmax(0, 1.03fr) minmax(440px, .97fr);
            overflow: hidden;
            border: 1px solid rgba(21, 87, 176, .14);
            border-radius: 34px;
            background: #fff;
            box-shadow: 0 28px 75px rgba(7, 31, 74, .14);
        }

        .mart-hero-copy {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 70px 66px;
        }

        .mart-brand {
            position: absolute;
            top: 38px;
            left: 66px;
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: #0b2f6b;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .mart-brand-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(145deg, #0b2f6b, #1557b0);
            color: #fff;
            box-shadow: 0 8px 20px rgba(21, 87, 176, .24);
        }

        .mart-kicker {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 9px;
            margin-bottom: 22px;
            padding: 8px 13px;
            border: 1px solid #c9daf2;
            border-radius: 999px;
            background: #eaf2ff;
            color: #1557b0;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .mart-kicker-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #d62839;
            box-shadow: 0 0 0 5px rgba(214, 40, 57, .12);
            animation: martPulse 2.2s ease-in-out infinite;
        }

        .mart-hero h1 {
            max-width: 580px;
            margin: 0;
            color: #17243b;
            font-size: clamp(43px, 5vw, 68px);
            font-weight: 800;
            line-height: 1.03;
            letter-spacing: -.055em;
        }

        .mart-hero h1 span {
            position: relative;
            z-index: 1;
            display: inline-block;
            color: #1557b0;
        }

        .mart-hero h1 span::after {
            content: '';
            position: absolute;
            z-index: -1;
            right: -3px;
            bottom: 5px;
            left: -3px;
            height: 12px;
            border-radius: 999px;
            background: rgba(214, 40, 57, .18);
            transform: rotate(-1deg);
        }

        .mart-hero-description {
            max-width: 520px;
            margin: 25px 0 0;
            color: #5c6f64;
            font-size: 17px;
            line-height: 1.75;
        }

        .mart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }

        .mart-btn {
            display: inline-flex;
            min-height: 52px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 22px;
            border: 1px solid transparent;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .mart-btn:hover,
        .mart-btn:focus {
            text-decoration: none;
            transform: translateY(-2px);
        }

        .mart-btn-primary {
            background: linear-gradient(135deg, #0b2f6b, #1557b0);
            color: #fff;
            box-shadow: 0 13px 26px rgba(21, 87, 176, .24);
        }

        .mart-btn-primary:hover,
        .mart-btn-primary:focus {
            color: #fff;
            box-shadow: 0 16px 32px rgba(21, 87, 176, .3);
        }

        .mart-btn-secondary {
            border-color: #d8e3f2;
            background: #fff;
            color: #263955;
        }

        .mart-btn-secondary:hover,
        .mart-btn-secondary:focus {
            border-color: #9cbce8;
            background: #f5f8ff;
            color: #1557b0;
        }

        .mart-assurances {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 34px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .mart-assurances span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .mart-assurances i {
            color: #d62839;
        }

        .mart-hero-visual {
            position: relative;
            isolation: isolate;
            min-height: 650px;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(7,31,74,.04), rgba(7,31,74,.38)),
                url("{{ asset('img/pharmacy-hero.png') }}") center / cover no-repeat;
        }

        .mart-hero-visual::before {
            content: '';
            position: absolute;
            z-index: -1;
            inset: 0;
            background: linear-gradient(90deg, #fff 0%, rgba(255, 255, 255, .18) 13%, transparent 38%);
        }

        .mart-hero-visual::after {
            content: '+';
            position: absolute;
            top: 18%;
            left: 12%;
            color: rgba(255,255,255,.62);
            font-size: 110px;
            font-weight: 900;
            line-height: 1;
            transform: rotate(8deg);
        }

        .mart-status-card {
            position: absolute;
            top: 38px;
            right: 30px;
            width: 190px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, .6);
            border-radius: 17px;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 16px 35px rgba(7, 31, 74, .18);
            backdrop-filter: blur(14px);
            animation: martFloat 5s ease-in-out infinite;
        }

        .mart-status-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #61718a;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .mart-status-live {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d62839;
            box-shadow: 0 0 0 4px rgba(214, 40, 57, .14);
        }

        .mart-status-value {
            display: block;
            margin-top: 10px;
            color: #0b2f6b;
            font-size: 17px;
            font-weight: 800;
        }

        .mart-status-note {
            display: block;
            margin-top: 4px;
            color: #738198;
            font-size: 10px;
        }

        .mart-mini-card {
            position: absolute;
            right: 30px;
            bottom: 36px;
            left: 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 9px;
            padding: 13px;
            border: 1px solid rgba(255, 255, 255, .54);
            border-radius: 19px;
            background: rgba(7, 31, 74, .86);
            box-shadow: 0 18px 40px rgba(7, 31, 74, .27);
            backdrop-filter: blur(12px);
        }

        .mart-mini-item {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            padding: 9px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .09);
            color: #fff;
        }

        .mart-mini-item i {
            display: grid;
            width: 29px;
            height: 29px;
            flex: 0 0 29px;
            place-items: center;
            border-radius: 9px;
            background: #d62839;
            color: #fff;
            font-size: 12px;
        }

        .mart-mini-item strong,
        .mart-mini-item small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mart-mini-item strong {
            font-size: 10px;
        }

        .mart-mini-item small {
            margin-top: 2px;
            color: #cfe0fb;
            font-size: 8px;
        }

        .mart-feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }

        .mart-feature {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border: 1px solid #d8e3f2;
            border-radius: 22px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 8px 30px rgba(7, 31, 74, .07);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .mart-feature:hover {
            border-color: #a9c6ec;
            transform: translateY(-5px);
            box-shadow: 0 16px 38px rgba(7, 31, 74, .12);
        }

        .mart-feature-number {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #edf3fc;
            font-size: 44px;
            font-weight: 900;
            line-height: 1;
        }

        .mart-feature-icon {
            display: grid;
            width: 48px;
            height: 48px;
            margin-bottom: 20px;
            place-items: center;
            border-radius: 15px;
            background: #eaf2ff;
            color: #1557b0;
            font-size: 18px;
        }

        .mart-feature:nth-child(2) .mart-feature-icon {
            background: #fff0f2;
            color: #d62839;
        }

        .mart-feature:nth-child(3) .mart-feature-icon {
            background: #eef3ff;
            color: #4568cf;
        }

        .mart-feature h2 {
            position: relative;
            margin: 0 0 9px;
            color: #17243b;
            font-size: 17px;
            font-weight: 800;
        }

        .mart-feature p {
            position: relative;
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .mart-welcome-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 26px 8px 0;
            color: #738198;
            font-size: 12px;
        }

        .mart-welcome-footer a {
            color: #1557b0;
            font-weight: 800;
        }

        .mart-welcome-footer a:hover {
            color: #d62839;
            text-decoration: none;
        }

        @keyframes martPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(.82); opacity: .72; }
        }

        @keyframes martFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @media (max-width: 960px) {
            .mart-hero {
                grid-template-columns: 1fr;
            }

            .mart-hero-copy {
                min-height: 600px;
                padding: 110px 52px 58px;
            }

            .mart-brand {
                left: 52px;
            }

            .mart-hero-visual {
                min-height: 500px;
            }

            .mart-feature-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .mart-welcome {
                width: calc(100% - 18px);
                margin-top: 0;
            }

            .mart-hero {
                border-radius: 24px;
            }

            .mart-hero-copy {
                min-height: 570px;
                padding: 100px 27px 44px;
            }

            .mart-brand {
                top: 28px;
                left: 27px;
            }

            .mart-hero h1 {
                font-size: 43px;
            }

            .mart-hero-description {
                font-size: 15px;
            }

            .mart-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .mart-btn {
                width: 100%;
            }

            .mart-assurances {
                align-items: flex-start;
                flex-direction: column;
                gap: 9px;
            }

            .mart-hero-visual {
                min-height: 430px;
                background-position: 61% center;
            }

            .mart-status-card {
                top: 22px;
                right: 20px;
            }

            .mart-mini-card {
                right: 16px;
                bottom: 18px;
                left: 16px;
            }

            .mart-mini-item {
                align-items: center;
                flex-direction: column;
                text-align: center;
            }

            .mart-feature {
                padding: 24px;
            }

            .mart-welcome-footer {
                align-items: flex-start;
                flex-direction: column;
                gap: 6px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .mart-kicker-dot,
            .mart-status-card {
                animation: none;
            }

            .mart-btn,
            .mart-feature {
                transition: none;
            }
        }
    </style>

    <main class="mart-welcome">
        <section class="mart-hero">
            <div class="mart-hero-copy">
                <div class="mart-brand">
                    <span class="mart-brand-icon"><i class="fas fa-prescription-bottle-alt" aria-hidden="true"></i></span>
                    <span>{{ config('app.name', 'UltimatePOS') }}</span>
                </div>

                <div>
                    <span class="mart-kicker"><span class="mart-kicker-dot"></span> Modern pharmacy management</span>
                    <h1>Care for patients. Control your <span>pharmacy.</span></h1>
                    <p class="mart-hero-description">
                        {{ env('APP_TITLE', '') ?: 'A dependable pharmacy workspace for dispensing, batch and expiry control, purchasing, payments and clear business reporting.' }}
                    </p>

                    <div class="mart-actions">
                        @auth
                            <a href="{{ route('home') }}" class="mart-btn mart-btn-primary">
                                <span>Open dashboard</span><i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="mart-btn mart-btn-primary">
                                <span>{{ __('business.sign_in') }}</span><i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>

                            @if (config('constants.allow_registration'))
                                <a href="{{ route('business.getRegister') }}@if (!empty(request()->lang)){{ '?lang=' . request()->lang }}@endif"
                                    class="mart-btn mart-btn-secondary">
                                    <i class="fas fa-clinic-medical" aria-hidden="true"></i><span>{{ __('business.register') }}</span>
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="mart-assurances">
                        <span><i class="fas fa-check-circle" aria-hidden="true"></i> Faster dispensing workflow</span>
                        <span><i class="fas fa-check-circle" aria-hidden="true"></i> Batch and expiry visibility</span>
                    </div>
                </div>
            </div>

            <div class="mart-hero-visual" role="img" aria-label="Pharmacy medicines and inventory management">
                <div class="mart-status-card">
                    <div class="mart-status-top"><span>Pharmacy status</span><span class="mart-status-live"></span></div>
                    <span class="mart-status-value">Ready to dispense</span>
                    <span class="mart-status-note">Stock, batches and sales connected.</span>
                </div>

                <div class="mart-mini-card" aria-hidden="true">
                    <div class="mart-mini-item">
                        <i class="fas fa-prescription"></i>
                        <span><strong>Dispense faster</strong><small>Keep patients moving</small></span>
                    </div>
                    <div class="mart-mini-item">
                        <i class="fas fa-hourglass-half"></i>
                        <span><strong>Expiry control</strong><small>Follow FEFO batches</small></span>
                    </div>
                    <div class="mart-mini-item">
                        <i class="fas fa-capsules"></i>
                        <span><strong>Drug availability</strong><small>Know what is in stock</small></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="mart-feature-grid" aria-label="System highlights">
            <article class="mart-feature">
                <span class="mart-feature-number">01</span>
                <span class="mart-feature-icon"><i class="fas fa-prescription" aria-hidden="true"></i></span>
                <h2>Dispense with confidence</h2>
                <p>A focused selling workflow combines product search, availability, lot selection and payment in one clear process.</p>
            </article>

            <article class="mart-feature">
                <span class="mart-feature-number">02</span>
                <span class="mart-feature-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                <h2>Protect every batch</h2>
                <p>Track lots and expiry dates with FEFO guidance, helping staff sell the right medicine stock first.</p>
            </article>

            <article class="mart-feature">
                <span class="mart-feature-number">03</span>
                <span class="mart-feature-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                <h2>Run a healthier business</h2>
                <p>Understand sales, purchasing, margins, expiring stock and payment performance through practical reports.</p>
            </article>
        </section>

        <footer class="mart-welcome-footer">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'UltimatePOS') }}. </span>
            <span>Developed by MensWeb Solutions &nbsp;&middot;&nbsp; <a href="tel:0545644749"><i class="fas fa-phone-alt" aria-hidden="true"></i> 0545644749</a></span>
        </footer>
    </main>
@endsection
