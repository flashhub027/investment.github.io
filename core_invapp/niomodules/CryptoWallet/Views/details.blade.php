<div class="modal-dialog modal-dialog-centered modal-md" role="document">
    <div class="modal-content">
        <a href="#" class="close" data-dismiss="modal"><em class="icon ni ni-cross-sm"></em></a>
        <div class="modal-body modal-body-md">
            <div class="nk-pps-apps">
                <div class="nk-pps-title text-center">
                    <h3 class="title">{{ __('Make Your Payment') }}</h3>
                    <p class="caption-text">{!! __('Your order :orderid has been placed successfully. To complete, please send the exact amount of :amount to the address below.', ['orderid' => '<strong class="text-dark">'.the_tnx(data_get($tranx, 'tnx')).'</strong>', 'amount' => '<strong class="text-dark">'.money($amount, $currency).'</strong>']) !!}</p>
                </div>
                <div class="nk-pps-card card card-bordered popup-inside">
                    <div class="card-inner-group">
                        <div class="card-inner card-inner-sm">
                            <div class="card-head mb-0">
                                <h6 class="title mb-0{{ (remaining_timeout($tranx->created_at, data_get($payment, 'timeout'))) ? '' : ' text-center' }}">{{ __('Pay :wallet', ['wallet' => $currency_name]) }}</h6>
                                @if(remaining_timeout($tranx->created_at, data_get($payment, 'timeout')))
                                    <div class="card-opt"><span class="counter" data-countdown-second="{{ ((remaining_timeout($tranx->created_at, data_get($payment, 'timeout')) * 60) - 1) }}" data-countdown-text="{{ __('Expire in') }}">-</span></div>
                                @elseif(data_get($payment, 'timeout')!=0)
                                    <div class="card-opt"><span>{{ __('Expired') }}</span></div>
                                @endif
                            </div>
                        </div>
                        <div class="card-inner">
                            <div class="qr-media mx-auto mb-3 w-max-100px">
                                {!! NioQR::generate($qrcode, 100) !!}
                            </div>
                            <div class="pay-info text-center">
                                <h5 class="title text-dark mb-0 clipboard-init" data-clipboard-text="{{ amount($amount, $currency, ['zero' => true]) }}">
                                    {{ money($amount, $currency) }} <em class="click-to-copy icon ni ni-copy-fill nk-tooltip" title="{{ __('Click to Copy') }}"></em>
                                </h5>
                                @if(data_get($payment, 'fiat')!=$currency)
                                    <p class="text-soft">{{ money(get_fx_rate($currency, data_get($payment, 'fiat'), $amount), data_get($payment, 'fiat')) }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                <div class="form-label overline-title-alt lg text-center">{{ __(':wallet Address', ['wallet' => $currency_name]) }}</div>
                                <div class="form-control-wrap">
                                    <div class="form-clip clipboard-init nk-tooltip" data-clipboard-target="#wallet-address" title="{{ __('Copy') }}">
                                        <em class="click-to-copy icon ni ni-copy"></em>
                                    </div>
                                    <div class="form-icon"><em class="icon ni ni-sign-btc-alt"></em></div>
                                    <input readonly type="text" class="form-control form-control-lg" id="wallet-address" value="{{ data_get($payment, 'address') }}">
                                </div>
                                @if(data_get($payment, 'meta.limit') || data_get($payment, 'meta.price'))
                                    <ul class="pay-info-meta row mt-1 justify-center text-center">
                                        @if(data_get($payment, 'meta.limit'))
                                            <li class="col-sm-6"><span class="meta-title">{{ __('Set Gas Limit:') }}</span> {{ data_get($payment, 'meta.limit') }}</li>
                                        @endif
                                        @if(data_get($payment, 'meta.price'))
                                            <li class="col-sm-6"><span class="meta-title">{{ __('Set Gas Price:') }}</span> {{ data_get($payment, 'meta.price') }}</li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                            @if(data_get($payment, 'reference')=='yes')
                                @if(data_get($payment, 'timeout')==0 || remaining_timeout($tranx->created_at, data_get($payment, 'timeout')))
                                <div class="nk-pps-action">
                                    <a href="#crypto-paid" class="btn btn-block btn-primary popup-open"><span>{{ __('Paid :coin', ['coin' => $currency_name]) }}</span></a>
                                </div>
                                <div class="nk-pps-action pt-2 text-center">
                                    <a href="{{ route('transaction.list') }}" class="link link-btn link-primary">{{ __('Pay Later') }}</a>
                                </div>
                                @endif
                                <div id="crypto-paid" class="popup">
                                    <div class="popup-content">
                                        <h6 class="mb-2">{{ __('Confirm your payment') }}</h6>
                                        <p>{{ __('If you already paid, please provide us your payment reference to speed up verification procces.') }}</p>
                                        <form class="form" action="{{ route('user.crypto.wallet.deposit.reference') }}" method="POST" id="crypto-pay-reference">
                                            <div class="form-group">
                                                <div class="form-label">{{ ('Payment Reference') }} <span class="text-danger">*</span></div>
                                                <div class="form-control-wrap">
                                                    <input name="reference" type="text" class="form-control " value="" placeholder="{{ __('Enter your reference id / hash') }}">
                                                </div>
                                            </div>
                                            <ul class="btn-group justify-between align-center gx-4">
                                                <li><button type="submit" class="btn btn-primary btn-block">{{ __('Confirm Payment') }}</button></li>
                                                <li><a href="#" class="link link-btn link-secondary popup-close">{{ __('Close') }}</a></li>
                                            </ul>
                                            <input type="hidden" name="tnx" value="{{ the_hash($tranx->tnx) }}">
                                            @csrf
                                        </form>
                                        <div class="alert-note is-plain mt-4">
                                            <em class="icon ni ni-alert-circle"></em>
                                            <p>{{ __('Account will credited once we confirm that payment has been received.') }}</p>
                                        </div>
                                    </div>
                                    <div class="popup-overlay"></div>
                                </div>
                            @endif
                        </div>
                        <div class="card-inner bg-lighter">
                            <ul>
                                <li class="alert-note is-plain text-danger">
                                    <em class="icon ni ni-alert-circle"></em>
                                    <p>{{ __('Be aware of that this order will be cancelled, if you send any other :currency amount.', ['currency' => $currency]) }}</p>
                                </li>
                                <li class="alert-note is-plain">
                                    <em class="icon ni ni-info"></em>
                                    <p>{{ __('Account will credited once we received your payment.') }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="nk-pps-action mt-n2">
                    @if($tranx->is_cancellable)
                    <ul class="btn-group justify-center align-center gy-3">
                        <li><a href="{{ route('deposit.complete', ['status' => 'cancel', 'tnx' => the_hash($tranx->id)]) }}" class="link link-danger">{{ __('Cancel Order') }}</a></li>
                    </ul>
                    @endif
                </div>
                <script>
                    !(function (NioApp) {
                        var data = @json($payment);
                        NioApp.BS.tooltip('.nk-tooltip');
                        NioApp.Timer.init();
                        NioApp.Popup();
                    })(NioApp);
                </script>
            </div>
        </div>
    </div>
</div>
