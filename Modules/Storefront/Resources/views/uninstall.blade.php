@extends('layouts.app')
@section('title', 'Uninstall Storefront')
@section('content')
<section class="content-header"><h1>Uninstall Storefront</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
    <p>This disables Storefront for all businesses on this system. The public shop, checkout and tracking pages will become unavailable.</p>
    <p>Orders, payment records, products and POS sales will be retained. You can reinstall the module later.</p>
    <p>Finish outstanding online payments before uninstalling: Paystack callbacks and payment verification will be unavailable while the module is disabled.</p>
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('storefront.uninstall.run') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Uninstall Storefront</button>
        <a class="btn btn-default" href="{{ action([\App\Http\Controllers\Install\ModulesController::class, 'index']) }}">Cancel</a>
    </form>
</div></div></section>
@endsection
