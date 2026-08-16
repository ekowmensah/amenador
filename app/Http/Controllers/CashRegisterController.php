<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CashRegister;
use App\CashRegisterTransaction;
use App\Utils\CashRegisterUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $cashRegisterUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  CashRegisterUtil  $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Enforce business, location and own-report scope for a register record.
     */
    private function authorizeRegisterScope($register): void
    {
        $user = auth()->user();
        $business_id = (int) request()->session()->get('user.business_id');

        if ((int) $register->business_id !== $business_id) {
            abort(403, 'Unauthorized register business.');
        }

        $permitted_locations = $user->permitted_locations();
        if ($permitted_locations !== 'all'
            && ! in_array((int) $register->location_id, array_map('intval', (array) $permitted_locations))) {
            abort(403, 'Unauthorized register location.');
        }

        $is_admin = $user->hasRole('Admin#'.$business_id);
        if ($user->can('reports.view_own') && ! $is_admin && (int) $register->user_id !== (int) $user->id) {
            abort(403, 'You may only access your own register.');
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if yes then redirect to POS screen.
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action([\App\Http\Controllers\SellPosController::class, 'create'], ['sub_type' => $sub_type]);
        }
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $pos_settings = json_decode(request()->session()->get('business.pos_settings', '{}'), true) ?: [];
        $cash_denominations = \App\Utils\CashDenominationUtil::fromSettings($pos_settings);
        $payment_types_by_location = [];
        foreach ($business_locations as $location_id => $location_name) {
            $payment_types_by_location[$location_id] = $this->cashRegisterUtil->payment_types($location_id, false, $business_id);
        }

        return view('cash_register.create')->with(compact('business_locations', 'sub_type', 'cash_denominations', 'payment_types_by_location'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        $request->validate([
            'amount' => 'required',
            'location_id' => 'required|integer',
            'sub_type' => 'nullable|string|max:50',
            'opening_denominations' => 'nullable|array',
            'opening_denominations.*' => 'nullable|integer|min:0|max:1000000',
        ]);

        $user_id = (int) $request->session()->get('user.id');
        $business_id = (int) $request->session()->get('user.business_id');
        $allowed_locations = BusinessLocation::forDropdown($business_id);
        $location_id = (int) $request->input('location_id');
        $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
        $pos_settings = json_decode($request->session()->get('business.pos_settings', '{}'), true) ?: [];
        $configured_denominations = \App\Utils\CashDenominationUtil::byCode($pos_settings);
        $opening_denominations = collect(\App\Utils\CashDenominationUtil::submittedRecords($pos_settings, $request->input('opening_denominations', [])))
            ->mapWithKeys(fn ($item) => [$item['denomination_code'] => [
                'code' => $item['denomination_code'], 'label' => $item['denomination_label'], 'value' => (float) $item['amount'],
                'type' => $item['denomination_type'], 'count' => (int) $item['total_count'],
            ]])->all();
        if (! empty($configured_denominations)) {
            $initial_amount = collect($opening_denominations)->sum(fn ($item) => $item['value'] * $item['count']);
        }

        if (! array_key_exists($location_id, $allowed_locations->toArray())) {
            return redirect()->back()->withInput()->with('status', ['success' => 0, 'msg' => 'You do not have access to the selected business location.']);
        }
        $active_payment_types = $this->cashRegisterUtil->payment_types($location_id, false, $business_id);
        $has_opening_cash = $request->boolean('has_opening_cash') && array_key_exists('cash', $active_payment_types);
        if (! $has_opening_cash) {
            $opening_denominations = [];
            $initial_amount = 0;
        }
        if (! is_numeric($initial_amount) || $initial_amount < 0) {
            return redirect()->back()->withInput()->with('status', ['success' => 0, 'msg' => 'Opening cash must be a valid amount of zero or more.']);
        }

        try {
            DB::transaction(function () use ($business_id, $user_id, $location_id, $initial_amount, $opening_denominations) {
                $open_register = CashRegister::where('business_id', $business_id)
                    ->where('user_id', $user_id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if ($open_register) {
                    throw new \RuntimeException('You already have an open register.');
                }

                $register = CashRegister::create([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'status' => 'open',
                    'location_id' => $location_id,
                    'opening_denominations' => empty($opening_denominations) ? null : $opening_denominations,
                    'created_at' => \Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                $register->cash_register_transactions()->create([
                    'amount' => (float) $initial_amount,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('Unable to open cash register: '.$e->getMessage(), ['user_id' => $user_id, 'location_id' => $location_id]);

            return redirect()->back()->withInput()->with('status', ['success' => 0, 'msg' => $e instanceof \RuntimeException ? $e->getMessage() : __('messages.something_went_wrong')]);
        }

        return redirect()->action([\App\Http\Controllers\SellPosController::class, 'create'], ['sub_type' => $sub_type])
            ->with('status', ['success' => 1, 'msg' => 'Register opened successfully.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        request()->merge(['register_id' => $id]);

        return $this->getRegisterDetails();
    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        if (! auth()->user()->can('view_cash_register') && ! auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details = $this->cashRegisterUtil->getRegisterDetails(request()->input('register_id'));

        if (empty($register_details) || empty($register_details->open_time)
            || (int) $register_details->business_id !== (int) $business_id) {
            return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><h4>No open register found</h4><p>Please open a register before viewing its details.</p><button class="btn btn-default" data-dismiss="modal">Close</button></div></div></div>');
        }
        $this->authorizeRegisterScope($register_details);

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = ! empty($register_details['closed_at']) ? $register_details['closed_at'] : \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled, $register_details->location_id);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, false, $business_id);

        $payment_counts = CashRegisterTransaction::where('cash_register_id', $register_details->register_id)
            ->where('transaction_type', 'sell')
            ->select('pay_method', DB::raw('COUNT(DISTINCT transaction_id) as transaction_count'))
            ->groupBy('pay_method')
            ->pluck('transaction_count', 'pay_method');

        $payment_fields = [
            'cash' => ['total_cash', 'total_cash_refund', 'total_cash_expense'],
            'cheque' => ['total_cheque', 'total_cheque_refund', 'total_cheque_expense'],
            'card' => ['total_card', 'total_card_refund', 'total_card_expense'],
            'bank_transfer' => ['total_bank_transfer', 'total_bank_transfer_refund', 'total_bank_transfer_expense'],
            'advance' => ['total_advance', 'total_advance_refund', 'total_advance_expense'],
            'other' => ['total_other', 'total_other_refund', 'total_other_expense'],
        ];
        foreach (range(1, 7) as $index) {
            $payment_fields['custom_pay_'.$index] = [
                'total_custom_pay_'.$index,
                'total_custom_pay_'.$index.'_refund',
                'total_custom_pay_'.$index.'_expense',
            ];
        }

        $payment_breakdown = [];
        foreach ($payment_types as $method => $label) {
            if (! isset($payment_fields[$method])) {
                continue;
            }
            [$sale_field, $refund_field, $expense_field] = $payment_fields[$method];
            $sales = (float) ($register_details->{$sale_field} ?? 0);
            $refunds = (float) ($register_details->{$refund_field} ?? 0);
            $expenses = (float) ($register_details->{$expense_field} ?? 0);
            $payment_breakdown[] = compact('method', 'label', 'sales', 'refunds', 'expenses') + [
                'net' => $sales - $refunds - $expenses,
                'count' => (int) ($payment_counts[$method] ?? 0),
            ];
        }

        $opening_cash = (float) $register_details->cash_in_hand;
        $expected_cash = $opening_cash + (float) $register_details->total_cash
            - (float) $register_details->total_cash_refund
            - (float) $register_details->total_cash_expense;
        $gross_sales = (float) ($details['transaction_details']->total_sales ?? 0);
        $credit_sales = max(0, $gross_sales - (float) $register_details->total_sale);
        $can_view_reconciliation = auth()->user()->can('close_cash_register')
            || auth()->user()->can('admin')
            || auth()->user()->can('superadmin');

        $register_kpis = [
            'transactions' => (int) ($details['transaction_details']->transaction_count ?? 0),
            'average_sale' => (float) ($details['transaction_details']->average_sale ?? 0),
            'items_sold' => (float) $details['product_details']->sum('total_quantity'),
            'refunds' => CashRegisterTransaction::where('cash_register_id', $register_details->register_id)->where('transaction_type', 'refund')->distinct()->count('transaction_id'),
            'expenses' => CashRegisterTransaction::where('cash_register_id', $register_details->register_id)->where('transaction_type', 'expense')->distinct()->count('transaction_id'),
        ];

        $recent_activity = CashRegisterTransaction::from('cash_register_transactions as crt')
            ->leftJoin('transactions as t', 't.id', '=', 'crt.transaction_id')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('crt.cash_register_id', $register_details->register_id)
            ->orderByDesc('crt.id')
            ->limit(12)
            ->get([
                'crt.id', 'crt.created_at', 'crt.transaction_type', 'crt.pay_method', 'crt.type', 'crt.amount',
                't.invoice_no', 't.ref_no', 'c.name as customer_name',
            ]);

        $top_products_by_revenue = $details['product_details']->sortByDesc('total_amount')->take(5);
        $top_products_by_quantity = $details['product_details']->sortByDesc('total_quantity')->take(5);

        $register_warnings = [];
        if ($expected_cash < 0) {
            $register_warnings[] = ['level' => 'danger', 'message' => 'Expected cash is negative. Review refunds and cash expenses.'];
        }
        if ($gross_sales > 0 && (float) $register_details->total_refund > ($gross_sales * 0.15)) {
            $register_warnings[] = ['level' => 'warning', 'message' => 'Refunds exceed 15% of gross sales.'];
        }
        if ($gross_sales > 0 && (float) $register_details->total_expense > ($gross_sales * 0.20)) {
            $register_warnings[] = ['level' => 'warning', 'message' => 'Register expenses exceed 20% of gross sales.'];
        }
        foreach ($payment_breakdown as $payment) {
            if ($payment['net'] < 0) {
                $register_warnings[] = ['level' => 'danger', 'message' => $payment['label'].' has a negative net collection.'];
            }
        }

        return view('cash_register.register_details')
                ->with(compact(
                    'register_details', 'details', 'payment_types', 'payment_breakdown',
                    'opening_cash', 'expected_cash', 'gross_sales', 'credit_sales',
                    'can_view_reconciliation', 'register_kpis', 'recent_activity',
                    'top_products_by_revenue', 'top_products_by_quantity', 'register_warnings', 'close_time'
                ));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister($id = null)
    {
        if (! auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details = $this->cashRegisterUtil->getRegisterDetails($id);

        if (empty($register_details) || empty($register_details->register_id)
            || (int) $register_details->business_id !== (int) $business_id
            || $register_details->register_status !== 'open') {
            return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><h4>No open register found</h4><p>This register is no longer available for closing.</p><button class="btn btn-default" data-dismiss="modal">Close</button></div></div></div>');
        }

        $this->authorizeRegisterScope($register_details);

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled, $register_details->location_id);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, false, $business_id);

        $pos_settings = ! empty(request()->session()->get('business.pos_settings')) ? json_decode(request()->session()->get('business.pos_settings'), true) : [];

        $expected_cash = (float) $register_details->cash_in_hand
            + (float) $register_details->total_cash
            - (float) $register_details->total_cash_refund
            - (float) $register_details->total_cash_expense;
        $gross_sales = (float) ($details['transaction_details']->total_sales ?? 0);
        $credit_sales = max(0, $gross_sales - (float) $register_details->total_sale);
        $payment_fields = [
            'cash' => ['total_cash', 'total_cash_refund', 'total_cash_expense'],
            'cheque' => ['total_cheque', 'total_cheque_refund', 'total_cheque_expense'],
            'card' => ['total_card', 'total_card_refund', 'total_card_expense'],
            'bank_transfer' => ['total_bank_transfer', 'total_bank_transfer_refund', 'total_bank_transfer_expense'],
            'advance' => ['total_advance', 'total_advance_refund', 'total_advance_expense'],
            'other' => ['total_other', 'total_other_refund', 'total_other_expense'],
        ];
        foreach (range(1, 7) as $index) {
            $payment_fields['custom_pay_'.$index] = ['total_custom_pay_'.$index, 'total_custom_pay_'.$index.'_refund', 'total_custom_pay_'.$index.'_expense'];
        }
        $payment_breakdown = [];
        foreach ($payment_types as $method => $label) {
            if (! isset($payment_fields[$method])) {
                continue;
            }
            [$sale_field, $refund_field, $expense_field] = $payment_fields[$method];
            $sales = (float) ($register_details->{$sale_field} ?? 0);
            $refunds = (float) ($register_details->{$refund_field} ?? 0);
            $expenses = (float) ($register_details->{$expense_field} ?? 0);
            // Cash is physically counted by denomination. Every other active
            // payment mode must be manually checked before closing.
            $requires_confirmation = $method !== 'cash';
            $payment_breakdown[] = compact('method', 'label', 'sales', 'refunds', 'expenses', 'requires_confirmation') + ['net' => $sales - $refunds - $expenses];
        }

        return view('cash_register.close_register_modal')
                    ->with(compact('register_details', 'details', 'payment_types', 'pos_settings', 'expected_cash', 'gross_sales', 'credit_sales', 'payment_breakdown'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        if (! auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                    'msg' => 'Feature disabled in demo!!',
                ];

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json($output);
                }

                return redirect()->action([\App\Http\Controllers\HomeController::class, 'index'])->with('status', $output);
            }

            $request->validate([
                'register_id' => 'required|integer',
                'closing_amount' => 'required',
                'total_card_slips' => 'required|integer|min:0',
                'total_cheques' => 'required|integer|min:0',
                'closing_note' => 'nullable|string|max:2000',
                'denominations' => 'required|array',
                'denominations.*' => 'required|integer|min:0|max:1000000',
                'payment_confirmations' => 'nullable|array',
                'payment_confirmations.*' => 'required',
            ]);

            $closing_amount = $this->cashRegisterUtil->num_uf($request->input('closing_amount'));
            if (! is_numeric($closing_amount) || $closing_amount < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'closing_amount' => 'Please enter a valid closing cash amount.',
                ]);
            }

            $business_id = (int) $request->session()->get('user.business_id');
            $target_register = CashRegister::where('id', $request->input('register_id'))
                ->where('business_id', $business_id)
                ->where('status', 'open')
                ->firstOrFail();
            $this->authorizeRegisterScope($target_register);
            $pos_settings = json_decode($request->session()->get('business.pos_settings', '{}'), true) ?: [];
            $allowed_denominations = collect(\App\Utils\CashDenominationUtil::byCode($pos_settings));

            if ($allowed_denominations->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'denominations' => 'Cash denominations must be configured in Business Settings before a register can be closed.',
                ]);
            }

            foreach ($allowed_denominations->keys() as $code) {
                $count = $request->input('denominations.'.$code);
                if ($count === null || $count === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'denominations.'.$code => 'Enter a count for every cash denomination. Use 0 when none is present.',
                    ]);
                }
            }

            $submitted_denominations = \App\Utils\CashDenominationUtil::submittedRecords($pos_settings, $request->input('denominations', []));
            if ($allowed_denominations->isNotEmpty()) {
                $closing_amount = collect($submitted_denominations)->sum(fn ($item) => (float) $item['amount'] * (int) $item['total_count']);
            }

            DB::transaction(function () use ($request, $closing_amount, $business_id, $allowed_denominations) {
                $register = CashRegister::where('id', $request->input('register_id'))
                    ->where('business_id', $business_id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->firstOrFail();

                $active_payment_types = $this->cashRegisterUtil->payment_types($register->location_id, false, $business_id);
                $payment_confirmations = [];
                foreach ($active_payment_types as $method => $label) {
                    if ($method === 'cash') {
                        continue;
                    }
                    $submitted = $request->input('payment_confirmations.'.$method);
                    if ($submitted === null || $submitted === '') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'payment_confirmations.'.$method => 'Confirm the '.$label.' total before closing the register.',
                        ]);
                    }
                    $confirmed_total = $this->cashRegisterUtil->num_uf($submitted);
                    if (! is_numeric($confirmed_total) || $confirmed_total < 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'payment_confirmations.'.$method => 'Enter a valid confirmed '.$label.' amount.',
                        ]);
                    }
                    $totals = CashRegisterTransaction::where('cash_register_id', $register->id)
                        ->where('pay_method', $method)
                        ->selectRaw("SUM(IF(transaction_type='sell', amount, 0)) as sales, SUM(IF(transaction_type='refund', amount, 0)) as refunds, SUM(IF(transaction_type='expense', amount, 0)) as expenses")
                        ->first();
                    $system_total = (float) ($totals->sales ?? 0) - (float) ($totals->refunds ?? 0) - (float) ($totals->expenses ?? 0);
                    $payment_confirmations[$method] = [
                        'label' => $label, 'system_total' => $system_total, 'confirmed_total' => (float) $confirmed_total,
                        'variance' => (float) $confirmed_total - $system_total, 'confirmed_by' => auth()->id(),
                        'confirmed_at' => \Carbon::now()->format('Y-m-d H:i:s'),
                    ];
                }

                $denominations = collect($request->input('denominations', []))
                    ->filter(fn ($count, $code) => $allowed_denominations->has($code))
                    ->map(function ($count, $code) use ($allowed_denominations) {
                        $item = $allowed_denominations->get($code);
                        return $item + ['count' => max(0, (int) $count)];
                    })->filter(fn ($item) => $item['count'] > 0)->all();

                $register->update([
                    'closing_amount' => $closing_amount,
                    'total_card_slips' => (int) $request->input('total_card_slips'),
                    'total_cheques' => (int) $request->input('total_cheques'),
                    'closing_note' => $request->input('closing_note'),
                    'closed_at' => \Carbon::now()->format('Y-m-d H:i:s'),
                    'status' => 'close',
                    'denominations' => empty($denominations) ? null : $denominations,
                    'payment_confirmations' => empty($payment_confirmations) ? null : $payment_confirmations,
                ]);
            });
            $output = ['success' => 1,
                'msg' => __('cash_register.close_success'),
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }
}
