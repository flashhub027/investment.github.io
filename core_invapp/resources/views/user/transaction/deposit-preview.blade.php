@php
    $nameSpace = ucfirst(data_get($pm, 'slug')).'::deposit-preview';
@endphp

@if(view()->exists($nameSpace))
    @include($nameSpace)
@else
<div class="nk-pps-apps">
    <div class="nk-pps-steps">
        <span class="step"></span>
        <span class="step"></span>
        <span class="step active"></span>
        <span class="step"></span>
    </div>
    <div class="nk-pps-title text-center">
        <h3 class="title">{{ __('Confirm Your Deposit') }}</h3>
        <p class="caption-text">{!! __('You are about to deposit :amount in your account.', [ 'amount' => '<strong class="text-dark">'.money($amount, $currency, ['dp' => 'calc']).'</strong>' ]) !!}</p>
        <p class="sub-text-sm">{{ __('Please review the information and confirm.') }}</p>
    </div>
    <div class="nk-pps-data">
        <ul class="nk-olist">
            <li class="nk-olist-item">
                <div class="label lead-text">{{ (data_get($pm, 'module_config.is_online')) ? __('Deposit from') : __('Payment method') }}</div>
                <div class="data"><span class="method"><em class="icon ni {{ data_get($pm, 'module_config.icon') }}"></em> <span>{{ $pm->title }}</span></span></div>
            </li>

            <li class="nk-olist-item is-grouped">
                @if(data_get($payment, 'amount_fees'))
                <div class="label lead-text">{{ __('Amount to deposit') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($payment, 'amount'), $currency, ['dp' => 'calc']) }}</span></div>
                @else
                <div class="label lead-text">{{ (data_get($pm, 'module_config.is_online')) ? __('Amount to deposit') : __('You will send') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($payment, 'total'), $currency, ['dp' => 'calc']) }}</span></div>
                @endif
            </li>
            <li class="nk-olist-item small{{ (data_get($payment, 'equal_currency') != base_currency() && data_get($payment, 'equal_currency') != $currency) ? ' is-grouped' : '' }}">
                <div class="label">{{ __('Exchange rate') }}</em></div>
                <div class="data fw-normal text-soft">
                    <span class="amount">{{ __(':amount = :rate', ['amount' => '1'.' '.data_get($payment, 'base_currency'), 'rate' => money(data_get($payment, 'fx_rate'), data_get($payment, 'fx_currency'), ['dp' => 'calc'])]) }}</span>
                </div>
            </li>

            @if(data_get($payment, 'equal_currency') != base_currency() && data_get($payment, 'equal_currency') != $currency)
            <li class="nk-olist-item small">
                <div class="label">{{ __('Equivalent to') }}</div>
                <div class="data fw-normal text-soft">
                    <span class="amount">{{ money(data_get($payment, 'equal_amount'), data_get($payment, 'equal_currency'), ['dp' => 'calc'])  }}</span>
                </div>
            </li>
            @endif

            @if(data_get($payment, 'amount_fees'))
            <li class="nk-olist-item">
                <div class="label lead-text">{{ __('Processing fee :amount', ['amount' => (data_get($payment, 'fees.type') == 'percentage') ? '('. data_get($payment, 'fees.amount') .'%)' : '']) }}</div>
                <div class="data"><span class="amount">{{ money(data_get($payment, 'amount_fees'), $currency, ['dp' => 'calc']) }}</span></div>
            </li>
            <li class="nk-olist-item nk-olist-item-final">
                <div class="label lead-text">{{ (data_get($pm, 'module_config.is_online')) ? __('Total charge') : __('You will send (Total)') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($payment, 'total'), $currency, ['dp' => 'calc']) }}</span></div>
            </li>
            @endif
        </ul>
        <ul class="nk-olist">
            <li class="nk-olist-item nk-olist-item-final">
                <div class="label lead-text">{{ (data_get($pm, 'module_config.is_online')) ? __('Amount to credit') : __('Amount to deposit') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($payment, 'base_amount'), data_get($payment, 'base_currency'), ['dp' => 'calc']) }}</span></div>
            </li>
        </ul>

        @if(data_get($pm, 'module_config.is_online'))
            <div class="sub-text-sm">{!! __('* You will be redirect to :gateway website once you confirm.', ['gateway' => '<strong class="text-dark">' . $pm->title . '</strong>']) !!}</div>
        @endif

        @if($pm->method=='crypto')
            <div class="sub-text-sm">{{ __('* Payment info (:currency wallet) will available once you proceed.', ['currency' => data_get($payment, 'currency_name')]) }}</div>
        @endif
    </div>
    <div class="nk-pps-field form-action text-center">
        <div class="nk-pps-action">
            <a href="#" class="btn btn-lg btn-block btn-primary" id="confirm-deposit" data-url="{{ route('deposit.confirm') }}">
                <span>{{ __('Confirm & Pay') }}</span>
                <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
            </a>
        </div>
        <div class="nk-pps-action pt-3">
            <a href="{{ route('deposit') }}" class="btn btn-outline-danger btn-trans">{{ __('Cancel Order') }}</a>
        </div>
    </div>
</div>
@endif
