<div class="nk-pps-apps">
    <div class="nk-pps-steps">
        <span class="step"></span>
        <span class="step"></span>
        <span class="step active"></span>
        <span class="step"></span>
    </div>
    <div class="nk-pps-title text-center">
        <h3 class="title">{{ __('Confirm Your Withdrawal') }}</h3>
        <p class="caption-text">{{ __('You are about to withdraw :amount via :account.', [
                        'amount' => money(data_get($withdraw, 'total'), data_get($withdraw, 'currency')),
                        'account' => __($wdm->title).' ('.data_get($withdraw, 'account').')'
                        ]) }}
        </p>
        <p class="sub-text-sm">{{ __('Please review the information and confirm.') }}</p>
    </div>
    <div class="nk-pps-data">
        <ul class="nk-olist">
            <li class="nk-olist-item">
                <div class="label lead-text">{{ __('Withdraw Account (:method)', ['method' => data_get($withdraw, 'method_name') ]) }}</div>
                <div class="data"><span class="method"><em class="icon ni {{ data_get($wdm, 'module_config.icon') }}"></em> <span class="ellipsis w-max-225px">{{ data_get($withdraw, 'account') }}</span></span></div>
            </li>
            <li class="nk-olist-item">
                <div class="label lead-text">{{ __('Withdraw amount') }}</div>
                <div class="data"><span class="amount">{{ money($amount, $currency) }}</span></div>
            </li>
            @if(base_currency() != data_get($withdraw, 'currency'))
            <li class="nk-olist-item{{ ($currency != data_get($withdraw, 'currency')) ? ' is-grouped' : '' }}">
                <div class="label lead-text">{{ __('Equivalent to') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($withdraw, 'total'), data_get($withdraw, 'currency')) }}</span></div>
            </li>
            @if($currency != data_get($withdraw, 'currency'))
            <li class="nk-olist-item small">
                <div class="label">{{ __('Exchange rate') }}</em></div>
                <div class="data fw-normal text-soft">
                    <span class="amount">{{ __(':amount = :rate', ['amount' => '1'.' '.$currency, 'rate' => money(data_get($withdraw, 'fx_rate'), data_get($withdraw, 'fx_currency'))]) }}</span>
                </div>
            </li>
            @endif
            <li class="nk-olist-item nk-olist-item-final">
                <div class="label lead-text">{{ __('Total amount to debit') }}</div>
                <div class="data"><span class="amount">{{ money($amount, $currency) }}</span></div>
            </li>
            @endif
        </ul>
        
        @if(data_get($withdraw, 'desc'))
        <ul class="nk-olist">
            <li class="nk-olist-item">
                <div class="label">{{ __('Description') }}</div>
                <div class="data note">{{ data_get($withdraw, 'desc') }}</div>
            </li>
        </ul>
        @endif

        <ul class="nk-olist">
            <li class="nk-olist-item nk-olist-item-final">
                <div class="label lead-text">{{ __('Amount transferred to Account') }}</div>
                <div class="data"><span class="amount">{{ money(data_get($withdraw, 'total'), data_get($withdraw, 'currency')) }}</span></div>
            </li>
        </ul>
        {{-- <div class="sub-text-sm">{{ __('* Additional fees, network fees or intermediary fees may be deducted from the Amount Transferred by your payment provider.') }}</div> --}}
    </div>
    <div class="nk-pps-field form-action text-center">
        <div class="nk-pps-action">
            <a href="javascript:void(0)" class="btn btn-lg btn-block btn-primary" data-url="{{ route('withdraw.confirm') }}" id="wd-confirm">
                <span>{{ __('Confirm & Withdraw') }}</span>
                <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
            </a>
        </div>
        <div class="nk-pps-action pt-3">
            <a href="{{ route('withdraw') }}" class="btn btn-outline-danger btn-trans">{{ __('Cancel Order') }}</a>
        </div>
    </div>
</div>
