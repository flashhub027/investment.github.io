@extends('user.layouts.master')

@section('title', __('Deposit Funds'))

@section('content')
    @include('user.transaction.'.$contentBlade, [
        "transaction" => $transaction,
        "status" => $status ?? null,
    ])
@endsection
