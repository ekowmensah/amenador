@extends('layouts.app')
@section('title', 'Install Storefront')
@section('content')
<section class="content-header"><h1>Install Storefront</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body"><h3>Customer ordering, directly in your POS</h3><p>This adds the module’s settings and order tables. It does not alter existing products or sales. Your public store stays disabled until you enable it in settings.</p>@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif<form method="post" action="{{ route('storefront.install.run') }}">@csrf<button class="btn btn-primary">Install / update Storefront</button></form></div></div></section>
@endsection
