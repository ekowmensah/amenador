<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Charts\CommonChart;
use App\Currency;
use App\Media;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    protected $commonUtil;

    protected $restUtil;
    protected $productUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        Util $commonUtil,
        RestaurantUtil $restUtil,
        ProductUtil $productUtil,
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();
        if ($user->user_type == 'user_customer') {
            return redirect()->action([\Modules\Crm\Http\Controllers\DashboardController::class, 'index']);
        }

        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->businessUtil->is_admin(auth()->user());

        $dashboard_context = $this->getDashboardContext($user, $is_admin);

        if (! auth()->user()->can('dashboard.data')) {
            return view('home.index', compact('dashboard_context', 'is_admin'));
        }

        $fy = $this->businessUtil->getCurrentFinancialYear($business_id);

        $currency = Currency::where('id', request()->session()->get('business.currency_id'))->first();
        //ensure start date starts from at least 30 days before to get sells last 30 days
        $least_30_days = \Carbon::parse($fy['start'])->subDays(30)->format('Y-m-d');

        $all_locations = BusinessLocation::forDropdown($business_id)->toArray();
        $permitted_locations = $user->permitted_locations();
        $location_scope = $permitted_locations === 'all'
            ? 'all'
            : implode(',', array_map('intval', $permitted_locations));
        $sales_chart_cache_key = implode(':', [
            'dashboard-sales-chart-v1',
            $business_id,
            $least_30_days,
            $fy['end'],
            md5($location_scope),
        ]);

        // This aggregation scans a large portion of the transactions table.
        // Cache briefly per business, financial year and location permission
        // scope so normal dashboard refreshes do not repeat the same work.
        $sells_this_fy = Cache::remember($sales_chart_cache_key, now()->addMinutes(5), function () use ($business_id, $least_30_days, $fy) {
            return $this->transactionUtil->getSellsCurrentFy($business_id, $least_30_days, $fy['end']);
        });

        //Chart for sells last 30 days
        $labels = [];
        $all_sell_values = [];
        $dates = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = \Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;

            $labels[] = date('j M Y', strtotime($date));

            $total_sell_on_date = $sells_this_fy->where('date', $date)->sum('total_sells');

            if (! empty($total_sell_on_date)) {
                $all_sell_values[] = (float) $total_sell_on_date;
            } else {
                $all_sell_values[] = 0;
            }
        }

        //Group sells by location
        $location_sells = [];
        foreach ($all_locations as $loc_id => $loc_name) {
            $values = [];
            foreach ($dates as $date) {
                $total_sell_on_date_location = $sells_this_fy->where('date', $date)->where('location_id', $loc_id)->sum('total_sells');

                if (! empty($total_sell_on_date_location)) {
                    $values[] = (float) $total_sell_on_date_location;
                } else {
                    $values[] = 0;
                }
            }
            $location_sells[$loc_id]['loc_label'] = $loc_name;
            $location_sells[$loc_id]['values'] = $values;
        }

        $sells_chart_1 = new CommonChart;

        $sells_chart_1->labels($labels)
                        ->options($this->__chartOptions(__(
                            'home.total_sells',
                            ['currency' => $currency->code]
                            )));

        if (! empty($location_sells)) {
            foreach ($location_sells as $location_sell) {
                $sells_chart_1->dataset($location_sell['loc_label'], 'line', $location_sell['values']);
            }
        }

        if (count($all_locations) > 1) {
            $sells_chart_1->dataset(__('report.all_locations'), 'line', $all_sell_values);
        }

        $labels = [];
        $values = [];
        $date = strtotime($fy['start']);
        $last = date('m-Y', strtotime($fy['end']));
        $fy_months = [];
        do {
            $month_year = date('m-Y', $date);
            $fy_months[] = $month_year;

            $labels[] = \Carbon::createFromFormat('m-Y', $month_year)
                            ->format('M-Y');
            $date = strtotime('+1 month', $date);

            $total_sell_in_month_year = $sells_this_fy->where('yearmonth', $month_year)->sum('total_sells');

            if (! empty($total_sell_in_month_year)) {
                $values[] = (float) $total_sell_in_month_year;
            } else {
                $values[] = 0;
            }
        } while ($month_year != $last);

        $fy_sells_by_location_data = [];

        foreach ($all_locations as $loc_id => $loc_name) {
            $values_data = [];
            foreach ($fy_months as $month) {
                $total_sell_in_month_year_location = $sells_this_fy->where('yearmonth', $month)->where('location_id', $loc_id)->sum('total_sells');

                if (! empty($total_sell_in_month_year_location)) {
                    $values_data[] = (float) $total_sell_in_month_year_location;
                } else {
                    $values_data[] = 0;
                }
            }
            $fy_sells_by_location_data[$loc_id]['loc_label'] = $loc_name;
            $fy_sells_by_location_data[$loc_id]['values'] = $values_data;
        }

        $sells_chart_2 = new CommonChart;
        $sells_chart_2->labels($labels)
                    ->options($this->__chartOptions(__(
                        'home.total_sells',
                        ['currency' => $currency->code]
                            )));
        if (! empty($fy_sells_by_location_data)) {
            foreach ($fy_sells_by_location_data as $location_sell) {
                $sells_chart_2->dataset($location_sell['loc_label'], 'line', $location_sell['values']);
            }
        }
        if (count($all_locations) > 1) {
            $sells_chart_2->dataset(__('report.all_locations'), 'line', $values);
        }

        //Get Dashboard widgets from module
        $module_widgets = $this->moduleUtil->getModuleData('dashboard_widget');

        $widgets = [];

        foreach ($module_widgets as $widget_array) {
            if (! empty($widget_array['position'])) {
                $widgets[$widget_array['position']][] = $widget_array['widget'];
            }
        }

        $common_settings = ! empty(session('business.common_settings')) ? session('business.common_settings') : [];


        return view('home.index', compact('sells_chart_1', 'sells_chart_2', 'widgets', 'all_locations', 'common_settings', 'is_admin', 'dashboard_context'));
    }

    /**
     * Retrieves purchase and sell details for a given time period.
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotals()
    {
        abort_unless(auth()->user()->can('dashboard.data'), 403, 'Unauthorized action.');

        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;
            $location_id = $this->getPermittedDashboardLocation(request()->location_id);
            $business_id = request()->session()->get('user.business_id');

            // get user id parameter
            $created_by = request()->user_id;
            if (! empty($created_by) && ! $this->businessUtil->is_admin(auth()->user()) && (int) $created_by !== (int) auth()->id()) {
                abort(403, 'Unauthorized user scope.');
            }

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start, $end, $location_id, $created_by);

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start, $end, $location_id, $created_by);

            $total_ledger_discount = $this->transactionUtil->getTotalLedgerDiscount($business_id, $start, $end);

            $purchase_details['purchase_due'] = $purchase_details['purchase_due'] - $total_ledger_discount['total_purchase_discount'];

            $transaction_types = [
                'purchase_return', 'sell_return', 'expense',
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start,
                $end,
                $location_id,
                $created_by
            );

            $total_purchase_inc_tax = ! empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];

            $output = $purchase_details;
            $output['total_purchase'] = $total_purchase_inc_tax;
            $output['total_purchase_return'] = $total_purchase_return_inc_tax;
            $output['total_purchase_return_paid'] = $this->transactionUtil->getTotalPurchaseReturnPaid($business_id, $start, $end, $location_id);

            $total_sell_inc_tax = ! empty($sell_details['total_sell_inc_tax']) ? $sell_details['total_sell_inc_tax'] : 0;
            $total_sell_return_inc_tax = ! empty($transaction_totals['total_sell_return_inc_tax']) ? $transaction_totals['total_sell_return_inc_tax'] : 0;
            $output['total_sell_return_paid'] = $this->transactionUtil->getTotalSellReturnPaid($business_id, $start, $end, $location_id);

            $output['total_sell'] = $total_sell_inc_tax;
            $output['total_sell_return'] = $total_sell_return_inc_tax;

            $output['invoice_due'] = $sell_details['invoice_due'] - $total_ledger_discount['total_sell_discount'];
            $output['total_expense'] = $transaction_totals['total_expense'];

            //NET = TOTAL SALES - INVOICE DUE - EXPENSE
            $output['net'] = $output['total_sell'] - $output['invoice_due'] - $output['total_expense'];

            return $output;
        }
    }

    /**
     * Business executive analytics for the business Admin role.
     * Results are aggregated, scoped, and briefly cached to protect busy POS databases.
     */
    public function getAdminAnalytics(Request $request)
    {
        $user = auth()->user();
        $business_id = (int) $request->session()->get('user.business_id');
        abort_unless($this->businessUtil->is_admin($user, $business_id), 403, 'Unauthorized action.');
        abort_unless($user->can('dashboard.data'), 403, 'Unauthorized action.');

        $location_id = $this->getPermittedDashboardLocation($request->input('location_id'));
        $start = \Carbon::parse($request->input('start', now()->toDateString()))->startOfDay();
        $end = \Carbon::parse($request->input('end', now()->toDateString()))->endOfDay();
        abort_if($end->lt($start) || $start->diffInDays($end) > 730, 422, 'Invalid dashboard date range.');

        $days = $start->diffInDays($end) + 1;
        $previous_end = $start->copy()->subSecond();
        $previous_start = $previous_end->copy()->subDays($days)->addSecond();
        $cache_key = implode(':', ['admin-dashboard-v1', $business_id, $location_id ?: 'all', $start->format('Ymd'), $end->format('Ymd')]);

        $analytics = Cache::remember($cache_key, now()->addMinutes(2), function () use ($business_id, $location_id, $start, $end, $previous_start, $previous_end, $days) {
            $scope = function ($query, $date_start = null, $date_end = null) use ($business_id, $location_id) {
                $query->where('transactions.business_id', $business_id);
                if ($location_id) {
                    $query->where('transactions.location_id', $location_id);
                }
                if ($date_start && $date_end) {
                    $query->whereBetween('transactions.transaction_date', [$date_start, $date_end]);
                }
                return $query;
            };

            $period = $scope(DB::table('transactions'), $start, $end)
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN final_total ELSE 0 END) sales")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN final_total ELSE 0 END) expenses")
                ->selectRaw("SUM(CASE WHEN type = 'sell_return' THEN final_total ELSE 0 END) returns")
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN discount_amount ELSE 0 END) discounts")
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN 1 ELSE 0 END) transactions_count")
                ->first();
            $previous = $scope(DB::table('transactions'), $previous_start, $previous_end)
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN final_total ELSE 0 END) sales")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN final_total ELSE 0 END) expenses")
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN 1 ELSE 0 END) transactions_count")
                ->first();

            $sales = (float) ($period->sales ?: 0);
            $expenses = (float) ($period->expenses ?: 0);
            $count = (int) ($period->transactions_count ?: 0);
            $change = function ($current, $old) {
                return (float) $old === 0.0 ? ($current > 0 ? 100 : 0) : round((($current - $old) / abs($old)) * 100, 1);
            };

            $group_format = $days <= 45 ? '%Y-%m-%d' : '%Y-%m';
            $trend_rows = $scope(DB::table('transactions'), $start, $end)
                ->whereIn('transactions.type', ['sell', 'expense'])
                ->selectRaw("DATE_FORMAT(transaction_date, '{$group_format}') period")
                ->selectRaw("SUM(CASE WHEN type = 'sell' AND status = 'final' THEN final_total ELSE 0 END) sales")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN final_total ELSE 0 END) expenses")
                ->groupBy(DB::raw("DATE_FORMAT(transaction_date, '{$group_format}')"))->orderBy('period')->get();

            $locations = $scope(DB::table('transactions')->join('business_locations as bl', 'transactions.location_id', '=', 'bl.id'), $start, $end)
                ->select('bl.id', 'bl.name')
                ->selectRaw("SUM(CASE WHEN transactions.type = 'sell' AND transactions.status = 'final' THEN transactions.final_total ELSE 0 END) sales")
                ->selectRaw("SUM(CASE WHEN transactions.type = 'expense' THEN transactions.final_total ELSE 0 END) expenses")
                ->selectRaw("SUM(CASE WHEN transactions.type = 'sell' AND transactions.status = 'final' THEN 1 ELSE 0 END) transaction_count")
                ->groupBy('bl.id', 'bl.name')->orderByDesc('sales')->limit(10)->get();

            $payments = DB::table('transaction_payments as tp')
                ->join('transactions', 'tp.transaction_id', '=', 'transactions.id')
                ->where('transactions.business_id', $business_id)->where('transactions.type', 'sell')->where('transactions.status', 'final')
                ->whereBetween('tp.paid_on', [$start, $end])
                ->when($location_id, function ($q) use ($location_id) { return $q->where('transactions.location_id', $location_id); })
                ->select('tp.method')->selectRaw('SUM(IF(tp.is_return = 1, -tp.amount, tp.amount)) amount')
                ->groupBy('tp.method')->orderByDesc('amount')->get();

            $receivables = $scope(DB::table('transactions'), null, null)
                ->where('transactions.type', 'sell')->where('transactions.status', 'final')->whereIn('transactions.payment_status', ['due', 'partial'])
                ->selectRaw("SUM(CASE WHEN DATEDIFF(?, transaction_date) <= 30 THEN final_total ELSE 0 END) bucket_30", [$end])
                ->selectRaw("SUM(CASE WHEN DATEDIFF(?, transaction_date) BETWEEN 31 AND 60 THEN final_total ELSE 0 END) bucket_60", [$end])
                ->selectRaw("SUM(CASE WHEN DATEDIFF(?, transaction_date) BETWEEN 61 AND 90 THEN final_total ELSE 0 END) bucket_90", [$end])
                ->selectRaw("SUM(CASE WHEN DATEDIFF(?, transaction_date) > 90 THEN final_total ELSE 0 END) bucket_older", [$end])->first();

            $inventory = DB::table('variation_location_details as vld')
                ->join('variations as v', 'vld.variation_id', '=', 'v.id')->join('products as p', 'v.product_id', '=', 'p.id')
                ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->where('p.business_id', $business_id)->where('p.is_inactive', 0)
                ->when($location_id, function ($q) use ($location_id) { return $q->where('vld.location_id', $location_id); })
                ->selectRaw('SUM(vld.qty_available * COALESCE(v.default_purchase_price, 0)) cost_value')
                ->selectRaw('SUM(vld.qty_available * COALESCE(v.sell_price_inc_tax, 0)) retail_value')
                ->selectRaw('SUM(CASE WHEN vld.qty_available < 0 THEN 1 ELSE 0 END) negative_stock')
                ->selectRaw('SUM(CASE WHEN vld.qty_available = 0 THEN 1 ELSE 0 END) out_of_stock')->first();

            $staff = $scope(DB::table('transactions')->join('users as u', 'transactions.created_by', '=', 'u.id'), $start, $end)
                ->where('transactions.type', 'sell')->where('transactions.status', 'final')
                ->select('u.id')->selectRaw("TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) name")
                ->selectRaw('SUM(transactions.final_total) sales')->selectRaw('COUNT(transactions.id) transaction_count')
                ->groupBy('u.id', 'u.first_name', 'u.last_name')->orderByDesc('sales')->limit(6)->get();

            $products = DB::table('transaction_sell_lines as tsl')->join('transactions', 'tsl.transaction_id', '=', 'transactions.id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id')->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')->where('transactions.status', 'final')->whereBetween('transactions.transaction_date', [$start, $end])
                ->when($location_id, function ($q) use ($location_id) { return $q->where('transactions.location_id', $location_id); })
                ->whereNull('tsl.parent_sell_line_id')->select('p.id', 'p.name')
                ->selectRaw('SUM(tsl.quantity - tsl.quantity_returned) quantity')
                ->selectRaw('SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax) sales')
                ->groupBy('p.id', 'p.name')->orderByDesc('sales')->limit(6)->get();

            $risk = [
                ['level' => ((int) ($inventory->negative_stock ?: 0)) > 0 ? 'critical' : 'ok', 'label' => 'Negative stock records', 'value' => (int) ($inventory->negative_stock ?: 0), 'icon' => 'fa-exclamation-triangle'],
                ['level' => ((int) ($inventory->out_of_stock ?: 0)) > 0 ? 'warning' : 'ok', 'label' => 'Out-of-stock variations', 'value' => (int) ($inventory->out_of_stock ?: 0), 'icon' => 'fa-box-open'],
                ['level' => ((float) ($receivables->bucket_older ?: 0)) > 0 ? 'critical' : 'ok', 'label' => 'Receivables older than 90 days', 'value' => (float) ($receivables->bucket_older ?: 0), 'currency' => true, 'icon' => 'fa-file-invoice-dollar'],
                ['level' => ((float) ($period->returns ?: 0)) > 0 ? 'warning' : 'ok', 'label' => 'Sales returns in selected period', 'value' => (float) ($period->returns ?: 0), 'currency' => true, 'icon' => 'fa-undo-alt'],
            ];

            return [
                'kpis' => [
                    ['key' => 'sales', 'label' => 'Revenue', 'value' => $sales, 'currency' => true, 'change' => $change($sales, (float) ($previous->sales ?: 0))],
                    ['key' => 'operating', 'label' => 'Operating result', 'value' => $sales - $expenses, 'currency' => true, 'change' => $change($sales - $expenses, (float) ($previous->sales ?: 0) - (float) ($previous->expenses ?: 0))],
                    ['key' => 'average', 'label' => 'Average transaction', 'value' => $count ? $sales / $count : 0, 'currency' => true, 'change' => 0],
                    ['key' => 'transactions', 'label' => 'Completed sales', 'value' => $count, 'currency' => false, 'change' => $change($count, (int) ($previous->transactions_count ?: 0))],
                    ['key' => 'expenses', 'label' => 'Expenses', 'value' => $expenses, 'currency' => true, 'change' => $change($expenses, (float) ($previous->expenses ?: 0)), 'inverse' => true],
                    ['key' => 'discounts', 'label' => 'Discounts given', 'value' => (float) ($period->discounts ?: 0), 'currency' => true, 'change' => 0],
                ],
                'trend' => $trend_rows,
                'locations' => $locations,
                'payments' => $payments,
                'receivables' => [(float) ($receivables->bucket_30 ?: 0), (float) ($receivables->bucket_60 ?: 0), (float) ($receivables->bucket_90 ?: 0), (float) ($receivables->bucket_older ?: 0)],
                'inventory' => ['cost_value' => (float) ($inventory->cost_value ?: 0), 'retail_value' => (float) ($inventory->retail_value ?: 0), 'negative_stock' => (int) ($inventory->negative_stock ?: 0), 'out_of_stock' => (int) ($inventory->out_of_stock ?: 0)],
                'staff' => $staff,
                'products' => $products,
                'risk' => $risk,
                'meta' => ['generated_at' => now()->format('H:i:s'), 'grouping' => $days <= 45 ? 'day' : 'month'],
            ];
        });

        return response()->json($analytics);
    }

    /**
     * Retrieves sell products whose available quntity is less than alert quntity.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProductStockAlert()
    {
        abort_unless(auth()->user()->can('stock_report.view'), 403, 'Unauthorized action.');

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $permitted_locations = auth()->user()->permitted_locations();
            $location_id = $this->getPermittedDashboardLocation(request()->input('location_id'));
            if (! empty($location_id)) {
                $permitted_locations = [$location_id];
            }
            $products = $this->productUtil->getProductAlert($business_id, $permitted_locations);

            return Datatables::of($products)
                ->editColumn('product', function ($row) {
                    if ($row->type == 'single') {
                        return $row->product.' ('.$row->sku.')';
                    } else {
                        return $row->product.' - '.$row->product_variation.' - '.$row->variation.' ('.$row->sub_sku.')';
                    }
                })
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;

                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>'.(float) $stock.'</span> '.$row->unit;
                })
                ->removeColumn('sku')
                ->removeColumn('sub_sku')
                ->removeColumn('unit')
                ->removeColumn('type')
                ->removeColumn('product_variation')
                ->removeColumn('variation')
                ->rawColumns([2])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasePaymentDues()
    {
        abort_unless(auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase'), 403, 'Unauthorized action.');

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today = \Carbon::now()->format('Y-m-d H:i:s');

            $query = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                    ->leftJoin(
                        'transaction_payments as tp',
                        'transactions.id',
                        '=',
                        'tp.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'purchase')
                    ->where('transactions.payment_status', '!=', 'paid')
                    ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                $query->where('transactions.created_by', auth()->id());
            }

            $location_id = $this->getPermittedDashboardLocation(request()->input('location_id'));
            if (! empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $dues = $query->select(
                'transactions.id as id',
                'c.name as supplier',
                'c.supplier_business_name',
                'ref_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                        ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;

                    return '<span class="display_currency" data-currency_symbol="true">'.
                    $due.'</span>';
                })
                ->addColumn('action', '@can("purchase.create") <a href="{{action([\App\Http\Controllers\TransactionPaymentController::class, \'addPayment\'], [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a> @endcan')
                ->removeColumn('supplier_business_name')
                ->editColumn('supplier', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$supplier}}')
                ->editColumn('ref_no', function ($row) {
                    if (auth()->user()->can('purchase.view')) {
                        return  '<a href="#" data-href="'.action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->id]).'"
                                    class="btn-modal" data-container=".view_modal">'.$row->ref_no.'</a>';
                    }

                    return $row->ref_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([0, 1, 2, 3])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesPaymentDues()
    {
        $user = auth()->user();

        abort_unless(
            $user->can('sell.view') ||
            $user->can('direct_sell.view') ||
            $user->can('view_own_sell_only'),
            403,
            'Unauthorized action.'
        );

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today = \Carbon::now()->format('Y-m-d H:i:s');

            $query = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                    ->leftJoin(
                        'transaction_payments as tp',
                        'transactions.id',
                        '=',
                        'tp.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell')
                    ->where('transactions.payment_status', '!=', 'paid')
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! auth()->user()->can('sell.view') && auth()->user()->can('view_own_sell_only')) {
                $query->where('transactions.created_by', auth()->id());
            }

            $location_id = $this->getPermittedDashboardLocation(request()->input('location_id'));
            if (! empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $dues = $query->select(
                'transactions.id as id',
                'c.name as customer',
                'c.supplier_business_name',
                'transactions.invoice_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                        ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;

                    return '<span class="display_currency" data-currency_symbol="true">'.
                    $due.'</span>';
                })
                ->editColumn('invoice_no', function ($row) {
                    if (auth()->user()->can('sell.view')) {
                        return  '<a href="#" data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]).'"
                                    class="btn-modal" data-container=".view_modal">'.$row->invoice_no.'</a>';
                    }

                    return $row->invoice_no;
                })
                ->addColumn('action', '@if(auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) <a href="{{action([\App\Http\Controllers\TransactionPaymentController::class, \'addPayment\'], [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a> @endif')
                ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$customer}}')
                ->removeColumn('supplier_business_name')
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([0, 1, 2, 3])
                ->make(false);
        }
    }

    public function loadMoreNotifications()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->paginate(10);

        if (request()->input('page') == 1) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        $notifications_data = $this->commonUtil->parseNotifications($notifications);

        return view('layouts.partials.notification_list', compact('notifications_data'));
    }

    /**
     * Function to count total number of unread notifications
     *
     * @return json
     */
    public function getTotalUnreadNotifications()
    {
        $unread_notifications = auth()->user()->unreadNotifications;
        $total_unread = $unread_notifications->count();

        $notification_html = '';
        $modal_notifications = [];
        foreach ($unread_notifications as $unread_notification) {
            if (isset($data['show_popup'])) {
                $modal_notifications[] = $unread_notification;
                $unread_notification->markAsRead();
            }
        }
        if (! empty($modal_notifications)) {
            $notification_html = view('home.notification_modal')->with(['notifications' => $modal_notifications])->render();
        }

        return [
            'total_unread' => $total_unread,
            'notification_html' => $notification_html,
        ];
    }

    private function __chartOptions($title)
    {
        return [
            'yAxis' => [
                'title' => [
                    'text' => $title,
                ],
            ],
            'legend' => [
                'align' => 'right',
                'verticalAlign' => 'top',
                'floating' => true,
                'layout' => 'vertical',
                'padding' => 20,
            ],
        ];
    }

    public function getCalendar()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->restUtil->is_admin(auth()->user(), $business_id);
        $is_superadmin = auth()->user()->can('superadmin');
        if (request()->ajax()) {
            $data = [
                'start_date' => request()->start,
                'end_date' => request()->end,
                'user_id' => ($is_admin || $is_superadmin) && ! empty(request()->user_id) ? request()->user_id : auth()->user()->id,
                'location_id' => ! empty(request()->location_id) ? request()->location_id : null,
                'business_id' => $business_id,
                'events' => request()->events ?? [],
                'color' => '#007FFF',
            ];
            $events = [];

            if (in_array('bookings', $data['events'])) {
                $events = $this->restUtil->getBookingsForCalendar($data);
            }

            $module_events = $this->moduleUtil->getModuleData('calendarEvents', $data);

            foreach ($module_events as $module_event) {
                $events = array_merge($events, $module_event);
            }

            return $events;
        }

        $all_locations = BusinessLocation::forDropdown($business_id)->toArray();
        $users = [];
        if ($is_admin) {
            $users = User::forDropdown($business_id, false);
        }

        $event_types = [
            'bookings' => [
                'label' => __('restaurant.bookings'),
                'color' => '#007FFF',
            ],
        ];
        $module_event_types = $this->moduleUtil->getModuleData('eventTypes');
        foreach ($module_event_types as $module_event_type) {
            $event_types = array_merge($event_types, $module_event_type);
        }

        return view('home.calendar')->with(compact('all_locations', 'users', 'event_types'));
    }

    public function showNotification($id)
    {
        $notification = DatabaseNotification::find($id);

        $data = $notification->data;

        $notification->markAsRead();

        return view('home.notification_modal')->with([
            'notifications' => [$notification],
        ]);
    }

    public function attachMediasToGivenModel(Request $request)
    {
        if ($request->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $model_id = $request->input('model_id');
                $model = $request->input('model_type');
                $model_media_type = $request->input('model_media_type');

                DB::beginTransaction();

                //find model to which medias are to be attached
                $model_to_be_attached = $model::where('business_id', $business_id)
                                        ->findOrFail($model_id);

                Media::uploadMedia($business_id, $model_to_be_attached, $request, 'file', false, $model_media_type);

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (Exception $e) {
                DB::rollBack();

                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function getUserLocation($latlng)
    {
        $latlng_array = explode(',', $latlng);

        $response = $this->moduleUtil->getLocationFromCoordinates($latlng_array[0], $latlng_array[1]);

        return ['address' => $response];
    }

    /**
     * Builds a backward-compatible capability map for the dashboard UI.
     * It uses permissions already present in Ultimate POS roles.
     */
    private function getDashboardContext(User $user, bool $is_admin): array
    {
        $permitted_locations = $user->permitted_locations();
        $role_names = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];

        return [
            'roles' => $role_names,
            'scope' => $permitted_locations === 'all' ? 'all_locations' : 'restricted_locations',
            'scope_label' => $permitted_locations === 'all' ? __('report.all_locations') : count($permitted_locations).' permitted location(s)',
            'refreshed_at' => now()->format('H:i:s'),
            'capabilities' => [
                'management' => $user->can('dashboard.data'),
                'sales' => $user->hasAnyPermission(['sell.view', 'direct_sell.view', 'view_own_sell_only']),
                'purchases' => $user->hasAnyPermission(['purchase.view', 'view_own_purchase']),
                'inventory' => $user->can('stock_report.view'),
                'finance' => $user->can('account.access'),
                'shipping' => $user->hasAnyPermission(['access_pending_shipments_only', 'access_shipping', 'access_own_shipping']),
                'administration' => $is_admin || $user->hasAnyPermission(['business_settings.access', 'user.view', 'roles.view']),
            ],
        ];
    }

    /**
     * Rejects location IDs outside the authenticated user's assigned scope.
     */
    private function getPermittedDashboardLocation($location_id)
    {
        if (empty($location_id)) {
            return null;
        }

        $location_id = (int) $location_id;
        $permitted_locations = auth()->user()->permitted_locations();

        if ($permitted_locations !== 'all' && ! in_array($location_id, array_map('intval', $permitted_locations), true)) {
            abort(403, 'Unauthorized location.');
        }

        $business_id = request()->session()->get('user.business_id');
        abort_unless(BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->exists(), 403, 'Unauthorized location.');

        return $location_id;
    }
}
