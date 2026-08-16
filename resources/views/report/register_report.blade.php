@extends('layouts.app')
@section('title', __('report.register_report'))
@section('css')<link rel="stylesheet" href="{{ asset('css/pos-redesign.css?v=' . filemtime(public_path('css/pos-redesign.css'))) }}">@endsection

@section('content')
<section class="content register-report-page">
    <header class="register-report-hero">
        <div><span><i class="fas fa-cash-register"></i> Register governance</span><h1>Register Report</h1><p>Monitor shift activity, physical cash accountability and payment reconciliation across permitted locations.</p></div>
        <aside><i class="fas fa-shield-alt"></i><div><strong>Permission controlled</strong><small>Only registers within your assigned locations are shown.</small></div></aside>
    </header>

    <div class="register-report-filters">
        {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getRegisterReport']), 'method' => 'get', 'id' => 'register_report_filter_form']) !!}
        @if(!auth()->user()->can('reports.view_own') || auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
        <label><span>Cashier</span>{!! Form::select('register_user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]) !!}</label>
        @else
        <label><span>Report scope</span><strong class="form-control">My registers only</strong></label>
        @endif
        <label><span>Location</span>{!! Form::select('register_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]) !!}</label>
        <label><span>Status</span>{!! Form::select('register_status', ['open' => __('cash_register.open'), 'close' => __('cash_register.close')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]) !!}</label>
        <label><span>Opened date range</span><div class="register-report-date"><i class="fas fa-calendar-alt"></i>{!! Form::text('register_report_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'register_report_date_range', 'readonly']) !!}</div></label>
        {!! Form::close() !!}
    </div>

    <div class="register-report-summary">
        <article><span>Sessions shown</span><strong id="register_summary_sessions">0</strong><small>All filtered sessions</small></article>
        <article><span>Opening cash</span><strong id="register_summary_opening">0.00</strong><small>Combined starting floats</small></article>
        <article><span>Expected cash</span><strong id="register_summary_expected">0.00</strong><small>Calculated drawer balance</small></article>
        <article><span>Closing cash</span><strong id="register_summary_closing">0.00</strong><small>Physical closed counts</small></article>
        <article><span>Net variance</span><strong id="register_summary_variance">0.00</strong><small>Over less shortages</small></article>
        <article><span>Non-cash collected</span><strong id="register_summary_non_cash">0.00</strong><small>Generated from payments</small></article>
    </div>

    <div class="register-report-table-card">
        <header><div><span>Shift register</span><h3>Register sessions</h3></div><p>Click View for the full opening, payment, product, cash-control and audit record.</p></header>
        <div class="table-responsive"><table class="table" id="register_report_table"><thead><tr>
            <th>Opened</th><th>Closed</th><th>Location / Cashier</th><th>Status</th><th>Duration</th><th>Opening Cash</th><th>Cash Movement</th><th>Expected Cash</th><th>Closing Cash</th><th>Variance</th><th>Non-cash</th><th>MoMo Check</th><th>Action</th>
        </tr></thead></table></div>
    </div>
</section>
<div class="modal fade view_register register_details_modal" tabindex="-1" role="dialog"></div>
@endsection

@section('javascript')
<script src="{{ asset('js/report.js?v=' . filemtime(public_path('js/report.js'))) }}"></script>
@endsection
