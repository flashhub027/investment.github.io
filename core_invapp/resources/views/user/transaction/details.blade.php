@php

use \App\Enums\TransactionType;
use \App\Enums\TransactionStatus;
use \App\Enums\TransactionCalcType;

$details = data_get($transaction, 'details');
$type  = data_get($transaction, 'type');
$calc  = data_get($transaction, 'calc');
$status  = data_get($transaction, 'status');
$ledger = data_get($transaction,'ledger');

@endphp
<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <a href="#" class="close" data-dismiss="modal"><em class="icon ni ni-cross-sm"></em></a>
        <div class="modal-body modal-body-md">
            <div class="nk-modal-head mb-2 mb-sm-4">
                <h4 class="nk-modal-title title">
                    {!! __('Order ID #:orderid', ['orderid' => '<small class="text-primary">'.data_get($details, 'order_id').'</small>' ]) !!}
                </h4>
            </div>
            <div class="nk-block">
                <div class="nk-block-between flex-wrap g-3">
                    <div class="nk-tnx">
                        {!! tnx_type_icon($transaction, 'tnx-icon') !!}
                        <div class="nk-tnx-text">
                            <h5 class="title">{{ data_get($details, 'amount') }}</h5>
                            <span class="sub-text mt-n1">{{ data_get($details, 'order_date') }}</span>
                        </div>
                    </div>
                    <ul class="align-center flex-wrap gx-3">
                        <li>
                            <span class="badge badge-sm{{ css_state_tnx($transaction->status, 'badge') }}">
                                {{ __(data_get($details, 'status')) }}
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="divider md stretched"></div>
                <h5 class="overline-title">{{ __(':Type Details', ['type' => __(ucfirst($type))]) }}</h5>
                <div class="row gy-3">
                    <div class="col-md-6">
                        <span class="sub-text">{{ ($type == TransactionType::WITHDRAW) ? __('Withdraw Amount') : (($type == TransactionType::DEPOSIT) ? __('Payment Amount') : __('Amount') ) }}</span>
                        <span class="caption-text">{{ data_get($details, 'tnx_amount') }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="sub-text">{{ ($calc == TransactionCalcType::DEBIT) ? __('Debited in Account') : __('Credited in Account') }}</span>
                        <span class="caption-text">{{ data_get($details, 'amount') }}</span>
                    </div>
                    <div class="col-lg-6">
                        <span class="sub-text">{{ __('Exchage Rate') }}</span>
                        <span class="caption-text">
                            {{ __(':amount = :rate', ['amount' => '1'.' '.data_get($details, 'base_currency'), 'rate' => data_get($details, 'exchange_rate') ]) }} 
                        </span>
                    </div>
                    <div class="col-lg-6">
                        <span class="sub-text">{{ __('Details') }}</span>
                        <span class="caption-text">{{ data_get($details, 'details') }}</span>
                    </div>
                </div>

                <div class="divider md stretched"></div>
                <h5 class="overline-title">{{ __('Additional') }}</h5>
                <div class="row gy-3">
                    <div class="col-lg-6">
                        <span class="sub-text">
                            {{ ($type == TransactionType::WITHDRAW) ? __('Withdraw Method') : (($type == TransactionType::DEPOSIT) ? __('Payment Method') : __('Gateway') ) }}
                        </span>
                        <span class="caption-text">{{ __(data_get($details, 'gateway')) }}</span>
                    </div>

                    @if(data_get($details, 'pay_to') && $type != TransactionType::INVESTMENT)
                    <div class="col-lg-6">
                        <span class="sub-text">
                            {{ ($type == TransactionType::WITHDRAW) ? __('Withdraw To') : (($type == TransactionType::DEPOSIT) ? __('Payment To') : __('Pay To') ) }}
                        </span>
                        <span class="caption-text text-break"><span class="small">
                            {{ ($type == TransactionType::WITHDRAW) ? data_get($transaction, 'meta.account') : data_get($details, 'pay_to') }}
                        </span></span>
                        @if (data_get($transaction, 'tnx_method') === 'wd-bank-transfer')
                        <a href="#see-details" class="link link-btn link-primary popup-open ml-2">{{ __('Show Details') }}</a>
                        @endif
                    </div>
                    @endif

                    @if($type == TransactionType::INVESTMENT && data_get($details, 'pay_to'))
                    <div class="col-lg-6">
                        <span class="sub-text">
                            {{ __('Received From') }}
                        </span>
                        <span class="caption-text text-break"><span class="small">{{ from_to_case(data_get($details, 'pay_to')) }}</span></span>
                    </div>
                    @endif

                    @if($type == TransactionType::INVESTMENT && data_get($details, 'pay_from'))
                    <div class="col-lg-6">
                        <span class="sub-text">
                            {{ __('Transfered From') }}
                        </span>
                        <span class="caption-text text-break"><span class="small">{{ from_to_case(data_get($details, 'pay_from')) }}</span></span>
                    </div>
                    @endif

                    <div class="col-lg-6">
                        <span class="sub-text">{{ __('Reference') }}</span>
                        <span class="caption-text text-break">{{ data_get($details, 'reference') ?? __("N/A") }}</span>
                    </div>

                    @if(data_get($ledger, 'balance'))
                    <div class="col-lg-6">
                        <span class="sub-text">{{ __('Updated Balance') }}</span>
                        <span class="caption-text">{{ money(data_get($ledger, 'balance'), base_currency()) }}</span>
                    </div>
                    @endif

                    @if(data_get($details, 'notes'))
                    <div class="col-lg-12">
                        <span class="sub-text">{{ __('Notes') }}</span>
                        <span class="caption-text">{{ data_get($details, 'notes') }}</span>
                    </div>
                    @endif
                </div>
                <div class="divider md stretched"></div>
                <div class="notes">
                    <ul>
                        @if($status == TransactionStatus::PENDING || $status == TransactionStatus::ONHOLD)
                        <li class="alert-note is-plain text-primary">
                            <em class="icon ni ni-info"></em>
                            <p>{{ __("The transaction is currently under review. We will send you an email once our review is complete.") }}</p>
                        </li>
                        @endif
                        @if($status == TransactionStatus::COMPLETED)
                        <li class="alert-note is-plain text-primary">
                            <em class="icon ni ni-info"></em>
                            <p>{{ __('The transaction has been completed at :time.', ['time'=> show_date(data_get($transaction, 'completed_at'), true)]) }}</p>
                        </li>
                        @endif
                        @if($status == TransactionStatus::CANCELLED)
                        <li class="alert-note is-plain text-danger">
                            <em class="icon ni ni-info"></em>
                            <p>{{ __('The transaction was cancelled at :time.', ['time'=> show_date(data_get($transaction, 'updated_at'), true)]) }}</p>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            @if (data_get($transaction, 'tnx_method') === 'wd-bank-transfer')
            <div id="see-details" class="popup">
                <div class="popup-content px-3 py-2 px-md-4 py-md-3 mx-auto w-85">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h5>{{ __('Bank Account Details') }}</h5>
                        </div>
                        <div class="card-tools">
                            <a href="#" class="link link-btn link-danger popup-close">{{ __('Close') }}</a>
                        </div>
                    </div>
                    <table class="table table-plain table-borderless table-xs mb-0">
                        @if (data_get($transaction, 'meta.pay_meta.payment.acc_type'))
                        <tr>
                            <td><span class="sub-text">{{ __('Account Type') }}</span></td>
                            <td><span class="lead-text">{{ ucfirst(data_get($transaction, 'meta.pay_meta.payment.acc_type')) }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'metapay_meta.payment.acc_name'))
                        <tr>
                            <td><span class="sub-text">{{ __('Account Name') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'metapay_meta.payment.acc_name') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.acc_no'))
                        <tr>
                            <td><span class="sub-text">{{ __('Account Number') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.acc_no') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.currency'))
                        <tr>
                            <td><span class="sub-text">{{ __('Account Currency') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.currency') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.bank_name'))
                        <tr>
                            <td><span class="sub-text">{{ __('Bank Name') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.bank_name') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.bank_branch'))
                        <tr>
                            <td><span class="sub-text">{{ __('Branch') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.bank_branch') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.bank_address'))
                        <tr>
                            <td><span class="sub-text">{{ __('Bank Address') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.bank_address') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.country'))
                        <tr>
                            <td><span class="sub-text">{{ __('Country') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.country') }}</span></td>
                        </tr>
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.sortcode'))
                        <tr>
                            <td><span class="sub-text">{{ __('Sort Code') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.sortcode') }}</span></td>
                        </tr>    
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.routing'))
                        <tr>
                            <td><span class="sub-text">{{ __('Routing Number') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.routing') }}</span></td>
                        </tr>    
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.swift'))
                        <tr>
                            <td><span class="sub-text">{{ __('Swift Code / BIC') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.swift') }}</span></td>
                        </tr>    
                        @endif
                        @if (data_get($transaction, 'meta.pay_meta.payment.iban'))
                        <tr>
                            <td><span class="sub-text">{{ __('IBAN Number') }}</span></td>
                            <td><span class="lead-text">{{ data_get($transaction, 'meta.pay_meta.payment.iban') }}</span></td>
                        </tr>    
                        @endif
                    </table>
                </div>
                <div class="popup-overlay"></div>
            </div>
            @endif
        </div>
    </div>
</div>
<script type="text/javascript">
    !(function (App) { App.Popup(); })(NioApp);
</script>
