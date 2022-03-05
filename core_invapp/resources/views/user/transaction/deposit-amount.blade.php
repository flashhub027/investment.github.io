@php 
$cur_count = count($currencies);
$cls_dd = ($cur_count >= 6) ? '' : (($cur_count > 3) ? ' dropdown-menu-xs' : ' dropdown-menu-xxs');
$cls_ul = ($cur_count >= 6) ? ' li-col3x' : (($cur_count > 3) ? ' li-col2x' : '');
@endphp

<div class="nk-pps-apps">
    <div class="nk-pps-steps">
        <span class="step"></span>
        <span class="step active"></span>
        <span class="step"></span>
        <span class="step"></span>
    </div>
    <div class="nk-pps-title text-center">
        <h3 class="title">{{ __('Deposit Funds') }}</h3>
        <p class="caption-text">{{ __('via') }} <strong>{{ __(data_get($method, 'name')) }}</strong></p>
        <p class="sub-text-sm">{{ __(data_get($method, 'desc')) }}</p>
    </div>
    <form class="nk-pps-form" action="{{ route('deposit.preview.form') }}" id="deposit-amount-form">
        <div class="nk-pps-field form-group">
            <div class="form-label-group">
                <label class="form-label" for="deposit-amount">{{ __('Amount to Deposit') }}</label>
            </div>
            <div class="form-control-group">
                @if($cur_count > 1)
                <div class="form-dropdown">
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-indicator-caret currency" data-toggle="dropdown" data-offset="0,2" id="deposit-currency-name">{{ $default['code'] }}</a>
                        <div class="dropdown-menu dropdown-menu-right text-center{{ $cls_dd }}">
                            <ul class="link-list-plain{{ $cls_ul }}" id="currency-list">
                                @foreach($currencies as $code => $item)
                                    <li><a class="switch-currency" href="javascript:void(0)" data-switch="deposit" data-currency="{{ $code }}">{{ $code }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                @else
                <div class="form-text-hint form-text-hint-lg">
                    <span class="currency">{{ $default['code'] }}</span>
                </div>
                @endif
                <input type="text" class="form-control form-control-lg form-control-number" id="deposit-amount" name="deposit_amount" placeholder="0.00">
                <input type="hidden" id="deposit-currency" name="deposit_currency" value="{{ $default['code'] }}">
            </div>

            <div class="form-note-group">
                <span class="nk-pps-min form-note-alt">{!! __('Minimum :amount', ['amount' => '<span id="deposit-min">'.money( $default['min'], $default['code'], ['dp' => 'calc']).'</span>']) !!}</span>
                <span id="deposit-rate" class="nk-pps-rate form-note-alt{{ (base_currency()==$default['code']) ? ' hide' : ''  }}">
                    {!! __(':base = :rate', ['base' => '1 '.base_currency(), 'rate' => '<span class="fxrate">'.money($default['rate'], $default['code'], ['dp' => 'calc']).'</span>']) !!}
                </span>
            </div>
        </div>
        <div class="nk-pps-field form-action text-center">
            <div class="nk-pps-action">
                <a href="javascript:void(0)" class="btn btn-lg btn-block btn-primary" id="proceed-btn">
                    <span>{{ __('Continue to Deposit') }}</span>
                    <span class="spinner-border spinner-border-sm hide" role="status" aria-hidden="true"></span>
                </a>
            </div>
            <div class="nk-pps-action pt-3">
                <a href="{{ route('deposit') }}" class="btn btn-outline-secondary btn-trans">{{ __('Back to previous') }}</a>
            </div>
        </div>
    </form>
    <script type="text/javascript">
        var fxCur = { base: "{{ base_currency() }}", alter: "{{ secondary_currency() }}", data: @json($currencies) };
    </script>
</div>