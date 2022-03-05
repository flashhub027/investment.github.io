@extends('admin.layouts.modules')
@section('title', __('PayPal - Payment Method'))

@section('content')
    <div class="nk-content-body">
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">{{ __('Payment Methods') }}</h3>
                    <p>{{ __('Manage payment methods to receive payment from user.') }}</p>
                </div>
                <div class="nk-block-head-content">
                    <ul class="nk-block-tools gx-1">
                        <li class="d-lg-none">
                            <a href="#" class="btn btn-icon btn-trigger toggle" data-target="pageSidebar"><em class="icon ni ni-menu-right"></em></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="nk-block card card-bordered nk-block-mh">
            <div class="card-inner">
                <div class="justify-between">
                    <h5 class="title">{{ __('PayPal') }} <span class="meta ml-1"><span class="badge badge-pill badge-xs badge-light">{{ __('Core') }}</span></span></h5>
                    <div class="go-back"><a class="back-to" href="{{ route('admin.settings.gateway.payment.list') }}"><em class="icon ni ni-arrow-left"> </em> {{ __('Back') }}</a></div>
                </div>
                <p>{{ __('Accept payment via PayPal payment gateway.') }}</p>
                <div class="divider"></div>
                <div class="row">
                    <div class="col-12">
                        <form action="{{ route('admin.settings.gateway.payment.paypal.save') }}" class="form-settings" method="POST">
                            <div class="row g-5">
                                <div class="col-lg-12 col-xxl-6">
                                    <h6 class="title mb-3">{{ __('Method Setting') }}</h6>
                                    <div class="row gy-3">
                                        <div class="col-12 col-xxl-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Method Title') }}</label>
                                                <div class="form-control-wrap">
                                                    <input type="text" name="name" class="form-control" value="{{ data_get($settings, 'name', __('PayPal')) }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-xxl-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Description') }}</label>
                                                <div class="form-control-group">
                                                    <input type="text" name="desc" value="{{ data_get($settings, 'desc', __('Pay securely with your PayPal account.')) }}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-xxl-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Minimum Amount') }}</span></label>
                                                <div class="form-control-wrap">
                                                    <div class="form-text-hint"><span>{{ base_currency() }}</span></div>
                                                    <input type="number" class="form-control" name="min_amount" value="{{ data_get($settings, 'min_amount', '1') }}" min="1">
                                                </div>
                                                <div class="form-note">{{ __('Amount will be convert') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-8 col-xxl-6">
                                            <div class="form-group opt-disable" title="{{ __('Upcoming future update for extended license.') }}" data-toggle="tooltip">
                                                <label class="form-label" for="gateway-fee">{{ __('Fees') }} <span>({{ __('per transaction') }})</span></label>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <div class="form-control-wrap">
                                                            <div class="form-text-hint">
                                                                <span class="overline-title">{{ 'P' }}</span>
                                                            </div>
                                                            <input type="text" disabled="" class="form-control" id="gateway-fee-percent" placeholder="0">
                                                        </div>
                                                        <div class="form-note">{{ __('Percent Fee') }}</div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-control-wrap">
                                                            <div class="form-text-hint">
                                                                <span class="overline-title">{{ 'F' }}</span>
                                                            </div>
                                                            <input type="text" disabled="" class="form-control" id="gateway-fee-flat" placeholder="0">
                                                        </div>
                                                        <div class="form-note">{{ __('Flat Fee') }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-8">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Method Name') }} <span class="small">{{ __('Alternet') }}</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" name="config[meta][title]" class="form-control" value="{{ data_get($settings, 'config.meta.title') }}">
                                                </div>
                                                <div class="form-note">{{ __('Method title will use if leave blank.') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Fixed Minimum') }} <small><sup>2</sup></small></label>
                                                <div class="form-control-wrap">
                                                    <input type="number" class="form-control" name="config[meta][min]" value="{{ data_get($settings, 'config.meta.min', '0') }}" min="0">
                                                </div>
                                                <div class="form-note">{{ __('Fixed Amount') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="note mt-2 pl-2 border-left border-primary">
                                                <p><strong>{{ __('Please Note:') }}</strong><br>
                                                    <small><sup>1</sup></small> 
                                                    {{ __("The amount will apply only if its more than the base minimum deposit amount.") }}<br>
                                                    <small><sup>2</sup></small> 
                                                    {{ __("The fixed minimum amount will be set same for each currency & override others.") }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="currency-supported">{{ __('Supported Currency') }}</label>
                                            <div class="form-control-group">
                                                <ul class="custom-control-group g-2 align-center flex-wrap li-w225">
                                                    @foreach($supportedCurrencies as $currency)
                                                        <li>
                                                            <div class="custom-control custom-control-sm custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" name="currencies[]" value="{{ data_get($currency, 'code') }}" id="cur-{{data_get($currency, 'code')}}"{{ !is_active_currency(data_get($currency, 'code')) ? ' disabled' : '' }} @if(in_array(data_get($currency, 'code'), data_get($settings, 'currencies', []))) checked @endif>
                                                                <label class="custom-control-label" for="cur-{{data_get($currency, 'code')}}">{{ __(':name (:code)', ["name" => data_get($currency, 'name'), "code" => data_get($currency, 'code')]) }}</label>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xxl-6">
                                    <h6 class="title mb-3">{{ __('API Credentials') }}</h6>
                                    <div class="row gy-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('API Client ID') }}</label>
                                                <div class="form-control-group">
                                                    <input type="text" name="config[api][client_id]" value="{{ data_get($settings, 'config.api.client_id') }}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('API Client Secret') }}</label>
                                                <div class="form-control-group">
                                                    <input type="text" name="config[api][client_secret]" value="{{ data_get($settings, 'config.api.client_secret') }}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('Account Name/ID') }} <span class="small">({{ __('Reference') }})</span></label>
                                                <div class="form-control-group">
                                                    <input type="text" name="config[api][account]" value="{{ data_get($settings, 'config.api.account') }}" class="form-control">
                                                </div>
                                                <div class="form-note">{{ __('System use only for record.') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="form-label">{{ __('PayPal Sandbox') }}</label>
                                                <div class="form-control-wrap">
                                                    <div class="custom-control custom-switch custom-control-labeled">
                                                        <input class="switch-option-value" type="hidden" name="config[api][sandbox]" value="{{ data_get($settings, 'config.api.sandbox') ?? 'inactive' }}">
                                                        <input type="checkbox" class="custom-control-input switch-option" data-switch="active"{{ (data_get($settings, 'config.api.sandbox', 'inactive') == 'active') ? ' checked=""' : ''}} id="paypalSandbox">
                                                        <label class="custom-control-label" for="paypalSandbox"><span class="over"></span><span>{{ __('Enable') }}</span></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="divider"></div>
                            <div class="d-flex justify-between">
                                @csrf
                                <input type="hidden" name="slug" value="{{ data_get($config, 'slug') }}">
                                <div class="custom-control custom-switch">
                                    <input class="switch-option-value" type="hidden" name="status" value="{{ data_get($settings, 'status') ?? 'inactive' }}">
                                    <input type="checkbox" class="custom-control-input switch-option" data-switch="active"{{ (data_get($settings, 'status', 'inactive') == 'active') ? ' checked=""' : ''}}  id="enable-method">
                                    <label class="custom-control-label" for="enable-method"><span class="over"></span><span>{{ __('Enable Method') }}</span></label>
                                </div>
                                <button type="button" class="btn btn-primary submit-settings" disabled="">
                                    <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
                                    <span>{{ __('Update') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
