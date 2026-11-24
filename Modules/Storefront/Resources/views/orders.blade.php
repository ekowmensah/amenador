@extends('layouts.app')
@section('title', 'Customer orders')
@section('content')
<section class="content-header"><h1>Customer orders <small>Storefront</small></h1></section>
<section class="content">
    <p><a class="btn btn-default" href="{{ route('storefront.settings') }}">Storefront settings</a> <a class="btn btn-primary" href="{{ route('storefront.index', $store) }}" target="_blank" rel="noopener">Open storefront</a></p>
    <p><a class="btn {{ $deletedSales ? 'btn-default' : 'btn-primary' }}" href="{{ route('storefront.orders') }}">Active orders</a> <a class="btn {{ $deletedSales ? 'btn-primary' : 'btn-default' }}" href="{{ route('storefront.orders', ['view' => 'deleted']) }}">Deleted sales</a></p>
    @if($deletedSales)<p>These orders are cancelled because their linked POS sale was deleted. Original order and payment information is retained for reference.</p>@endif
    <div class="box box-primary"><div class="box-body table-responsive"><table class="table table-striped">
        <thead><tr><th>Reference / date</th><th>Customer</th><th>Items / fulfilment</th><th>Total / payment</th><th>Progress</th></tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td><a href="{{ route('storefront.order', [$store, $order->token]) }}" target="_blank" rel="noopener">{{ $order->tracking_code ?: $order->reference }}</a><br><small>{{ $order->reference }}</small><br><small>{{ $order->created_at }}</small>
                    @if($order->sale_deleted_at)<br><strong class="text-danger">POS sale #{{ $order->transaction_id }} deleted</strong><br><small>{{ $order->sale_deleted_at }}</small>
                    @elseif($order->transaction_id)<br>POS sale #{{ $order->transaction_id }}@endif
                </td>
                <td>{{ $order->customer['name'] }}<br>{{ $order->customer['phone'] }}<br>{{ $order->customer['email'] }}
                    @if($order->fulfilment === 'delivery')<br>{{ $order->customer['address'] }}<br>{{ $order->customer['city'] }}@endif
                </td>
                <td>@foreach($order->items as $item){{ $item['quantity'] }} &times; {{ $item['name'] }}<br>@endforeach<strong>{{ ucfirst($order->fulfilment) }}</strong>@if($order->notes)<p>{{ $order->notes }}</p>@endif</td>
                <td>{{ $order->currency }} {{ number_format($order->total_minor / 100, 2) }}<br>{{ $order->payment_method === 'cash' ? 'Pay on pickup / delivery' : 'Paystack' }}<br><strong>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</strong>
                    @if($order->status === 'paid_review' && !$order->sale_deleted_at)
                        <p class="text-danger">{{ $order->review_reason ?: 'Payment verified. Sale needs staff review.' }}</p>
                        <form method="post" action="{{ route('storefront.orders.update', $order->id) }}">@csrf<input type="hidden" name="action" value="retry"><button class="btn btn-warning btn-sm">Retry POS sale creation</button></form>
                    @endif
                </td>
                <td>
                    @if($order->sale_deleted_at)<strong class="text-danger">Cancelled — sale deleted</strong>
                    @elseif($order->transaction_id)
                        <form method="post" action="{{ route('storefront.orders.update', $order->id) }}">@csrf
                            <select class="form-control" name="fulfilment_status">
                                @foreach(['received' => 'Received', 'preparing' => 'Preparing', 'ready' => ($order->fulfilment === 'delivery' ? 'Ready for dispatch' : 'Ready for collection'), 'out_for_delivery' => 'Out for delivery', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    @continue($order->fulfilment === 'pickup' && $value === 'out_for_delivery')
                                    <option value="{{ $value }}" @selected($order->fulfilment_status === $value)>{{ $label }}</option>
                                @endforeach
                            </select><button class="btn btn-default btn-sm" style="margin-top:8px">Update</button>
                        </form>
                    @else{{ ucfirst(str_replace('_', ' ', $order->fulfilment_status)) }}@endif
                </td>
            </tr>
        @empty<tr><td colspan="5">{{ $deletedSales ? 'No deleted sales.' : 'No active customer orders.' }}</td></tr>@endforelse
        </tbody>
    </table>{{ $orders->links() }}</div></div>
    <p>Collect outstanding cash payments through the normal POS payment screen. Refunds, voids and returns must use the POS and gateway's existing workflows; changing order progress does not collect or refund money.</p>
</section>
@endsection
