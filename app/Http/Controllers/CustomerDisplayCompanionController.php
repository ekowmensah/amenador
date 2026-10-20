<?php

namespace App\Http\Controllers;

use App\Business;
use App\Services\CustomerDisplayCompanion;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDisplayCompanionController extends Controller
{
    private function register(Request $request): array
    {
        $business = (int) $request->session()->get('user.business_id');
        abort_unless($business && (int) $request->user()->business_id === $business, 403);
        abort_unless($request->user()->can('superadmin') || $request->user()->can('sell.create') ||
            (app(ModuleUtil::class)->hasThePermissionInSubscription($business, 'repair_module') && $request->user()->can('repair.create')), 403);
        $request->validate(['register' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']]);

        return [$business, (int) $request->user()->id, $request->input('register')];
    }

    public function status(Request $request, CustomerDisplayCompanion $companion)
    {
        $scope = $this->register($request);

        return response()->json(['devices' => $companion->devices(...$scope)
            ->get(['id', 'name', 'last_seen_at'])])->header('Cache-Control', 'no-store');
    }

    public function code(Request $request, CustomerDisplayCompanion $companion)
    {
        return response()->json(['code' => $companion->code(...$this->register($request)), 'expires_in' => 300])
            ->header('Cache-Control', 'no-store');
    }

    public function publish(Request $request, CustomerDisplayCompanion $companion)
    {
        $scope = $this->register($request);
        $data = $request->validate([
            'owner' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'active' => 'required|boolean', 'order' => 'required|array',
            'order.items' => 'present|array|max:250',
            'order.items.*.name' => 'required|string|max:500',
            'order.items.*.quantity' => 'required|string|max:80',
            'order.items.*.amount' => 'required|string|max:80',
            'order.location' => 'nullable|string|max:200',
            'order.subtotal' => 'required|string|max:80', 'order.total' => 'required|string|max:80',
            'order.discount' => 'required|string|max:80', 'order.tax' => 'required|string|max:80',
            'order.adjustments' => 'required|string|max:80',
            'order.has_discount' => 'required|boolean', 'order.has_tax' => 'required|boolean',
            'order.has_adjustments' => 'required|boolean',
        ]);
        $companion->publish(...array_merge($scope, [$data['order'], (bool) $data['active'], $data['owner']]));

        return response()->noContent();
    }

    public function revoke(Request $request, CustomerDisplayCompanion $companion)
    {
        $scope = $this->register($request);
        $request->validate(['device_id' => 'required|integer']);
        $companion->devices(...$scope)->where('id', $request->input('device_id'))->delete();

        return response()->noContent();
    }

    public function pair(Request $request, CustomerDisplayCompanion $companion)
    {
        $data = $request->validate(['code' => 'required|digits:8', 'name' => 'required|string|max:80']);
        $result = $companion->pair($data['code'], $data['name']);
        abort_unless($result, 422, 'Pairing code expired or already used. Generate a new code in the POS.');

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    private function device(Request $request, CustomerDisplayCompanion $companion): object
    {
        $device = $companion->authenticate($request->bearerToken());
        abort_unless($device, 401, 'Display is not paired.');
        // A deleted cashier or business must also invalidate the display.
        abort_unless(DB::table('users')->where('id', $device->user_id)->where('business_id', $device->business_id)->whereNull('deleted_at')->exists(), 401);

        return $device;
    }

    public function state(Request $request, CustomerDisplayCompanion $companion)
    {
        return response()->json($companion->state($this->device($request, $companion)))
            ->header('Cache-Control', 'no-store');
    }

    public function screen(Request $request, CustomerDisplayCompanion $companion)
    {
        $device = $this->device($request, $companion);
        $business = Business::findOrFail($device->business_id);
        $pos_layout_key = $business->common_settings['pos_layout'] ?? 'restaurant';
        if (!array_key_exists($pos_layout_key, config('pos_layouts'))) {
            $pos_layout_key = 'restaurant';
        }
        $display_preset = config('pos_layouts.'.$pos_layout_key.'.display');

        return response()->view('sale_pos.customer_display', compact('business', 'pos_layout_key', 'display_preset'))
            ->header('Cache-Control', 'no-store')
            ->header('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");
    }
}
