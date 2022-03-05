<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\PaymentMethodStatus;
use App\Enums\TransactionCalcType;
use App\Enums\WithdrawMethodStatus;

use App\Helpers\MsgState;
use App\Jobs\ProcessEmail;
use App\Filters\TransactionFilter;

use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\UserAccount;
use App\Models\WithdrawMethod;

use App\Services\Transaction\TransactionProcessor;
use App\Services\Transaction\TransactionService;
use App\Services\Withdraw\WithdrawProcessor;

use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    // private $exchangeRateApi;
    private $rounded;
    private $basecur;
    private $altcur;

    public function __construct()
    {
        // $this->exchangeRateApi = new ExchangeRateApi();

        $this->basecur = base_currency();
        $this->altcur = secondary_currency();
        $this->rounded = (object)[
            'fiat' => sys_settings('decimal_fiat_calc', 3),
            'crypto' => sys_settings('decimal_crypto_calc', 6)
        ];
    }

    /**
     * @param Request $request
     * @param TransactionFilter $filter
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \ReflectionException
     * @version 1.0.0
     * @since 1.0
     */
    public function list(Request $request, TransactionFilter $filter)
    {
        $tnxTypes = get_enums(TransactionType::class, false);
        $tnxStates = Arr::except(get_enums(TransactionStatus::class, false), ['NONE', 'ONHOLD', 'CONFIRMED', 'PENDING']);

        $scheduledCount = Transaction::loggedUser()
            ->whereIn('status', [TransactionStatus::PENDING, TransactionStatus::CONFIRMED, TransactionStatus::ONHOLD])
            ->whereNotIn('type', [TransactionType::REFERRAL])
            ->count();
        $scheduled = ($request->has('view') && $request->get('view') == 'scheduled') ? true : false;

        $orderBy = $scheduled ? 'id' : 'completed_at';
        $sortBy = $scheduled ? 'asc' : 'desc';

        $query = Transaction::loggedUser()->orderBy($orderBy, $sortBy)->whereNotIn('status', [TransactionStatus::NONE]);

        if (!$scheduled && blank($request->get('query')) && blank($request->get('filter'))) {
            $query->where('status', TransactionStatus::COMPLETED);
        }

        if ($scheduled || ($request->get('filter') == true && $request->get('type') != TransactionType::REFERRAL)) {
            $query->whereNotIn('type', [TransactionType::REFERRAL]);
        }

        $transactions = $query->filter($filter)
            ->paginate(user_meta('tnx_perpage', 10))
            ->onEachSide(0);

        return view('user.transaction.list', compact('transactions', 'tnxTypes', 'tnxStates', 'scheduledCount'));
    }

    /**
     * @param $name
     * @return mixed|object|models
     * @version 1.0.0
     * @since 1.0
     */
    private function getAccountBalance($name = null, $echo = false)
    {
        $name = (empty($name)) ? AccType('main') : $name;
        $userID = auth()->user()->id;
        return Account::getBalance($name, $userID, $echo);
    }

    /**
     * @param $name
     * @return boolean
     * @version 1.0.0
     * @since 1.0
     */
    private function hasAccountBalance($name = null)
    {
        $name = (empty($name)) ? AccType('main') : $name;
        $userID = auth()->user()->id;
        return Account::hasBalance($name, $userID);
    }

    /**
     * @param $method
     * @return object|models
     * @version 1.0.0
     * @since 1.0
     */
    private function getUserAccounts($method = null)
    {
        if (!blank($method)) {
            $user_id = auth()->user()->id;
            return UserAccount::getAccounts($method, $user_id);
        }
        return false;
    }

    /**
     * @param $method
     * @return boolean
     * @version 1.0.0
     * @since 1.0
     */
    private function hasUserAccounts($method = null)
    {
        $user_id = auth()->user()->id;
        return UserAccount::hasAccounts($method, $user_id);
    }

    /**
     * @param $pm
     * @return mixed|object|models
     * @version 1.0.0
     * @since 1.0
     */
    private function pmDetails($pm)
    {
        return PaymentMethod::where('slug', $pm)
            ->where('status', PaymentMethodStatus::ACTIVE)->first();
    }

    /**
     * @param $wdm
     * @return mixed|object|models
     * @version 1.0.0
     * @since 1.0
     */
    private function wdmDetails($wdm)
    {
        return WithdrawMethod::where('slug', $wdm)
            ->where('status', WithdrawMethodStatus::ACTIVE)->first();
    }

    /**
     * @param $gateway
     * @return mixed|array
     * @version 1.0.0
     * @since 1.0
     */
    private function payInfo($gateway, $currency, $only = true)
    {
        if (is_object($gateway)) {
            $pay_info = PaymentMethod::paymentInfo($gateway, $currency, $only);
        } else {
            $get_gateway = PaymentMethod::where('slug', $gateway)->first();
            if (!blank($get_gateway)) {
                $pay_info = PaymentMethod::paymentInfo($get_gateway, $currency, $only);
            } else {
                $pay_info = false;
            }
        }

        return $pay_info;
    }

    /**
     * @param $account |object
     * @param $method |object
     * @return mixed|array
     * @version 1.0.0
     * @since 1.0
     */
    private function payAccount($account, $method, $currency = null, $only = true)
    {
        return UserAccount::paymentInfo($account, $method, $currency, $only);
    }

    /**
     * @param $name
     * @param $gateway |object
     * @return bool
     * @version 1.0.0
     * @since 1.0
     */
    private function isMethod($gateway, $name = null)
    {
        $method = (isset($gateway->method)) ? $gateway->method : false;

        if (empty($name) || empty($method)) {
            return false;
        }

        return ($method == $name) ? true : false;
    }

    /**
     * @param $gateway |object
     * @param $currency
     * @return mixed
     * @version 1.0.0
     * @since 1.0
     */
    private function specifyMin($gateway, $currency)
    {
        $getMin = ($this->isMethod($gateway, 'crypto')) ? 'wallet.' . $currency . '.min' : 'meta.min';

        return data_get($gateway->config, $getMin, 0);
    }

    /**
     * @param $amount1
     * @param $amount2
     * @return number|string
     * @version 1.0.0
     * @since 1.0
     */
    private function toSum($amount1, $amount2)
    {
        $total = BigDecimal::of($amount1)->plus(BigDecimal::of($amount2));
        return is_object($total) ? (string)$total : $total;
    }

    /**
     * @param $name
     * @return mixed
     * @version 1.0.0
     * @since 1.0
     */
    private function getSupportContext($name = null)
    {
        return MsgState::helps('support');
    }

    /**
     * @param $activeMethods
     * @return array
     * @version 1.0.0
     * @since 1.0
     */
    private function validateDepositDefault($activeMethods)
    {
        if (sys_settings('deposit_disable_request') == 'yes') {
            return MsgState::of('disable', 'deposit');
        }

        if (blank($activeMethods)) {
            return MsgState::of('no-method', 'deposit');
        }

        if (!auth()->user()->is_verified) {
            return MsgState::of('verify-email', 'account');
        }

        if (kyc_required('deposit', true)) {
            return module_msg_of('deposit', 'error', 'BasicKYC');
        }

        if ($limit_queue = sys_settings('deposit_limit_request')) {
            $pending_queue = Transaction::where('user_id', auth()->id())
                ->where('type', TransactionType::DEPOSIT)->where('status', TransactionStatus::PENDING)->count();

            if ($pending_queue >= $limit_queue) {
                return MsgState::of('limit', 'deposit');
            }
        }
    }

    /**
     * @param null $status
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function depositPaymentMethod($status = null)
    {
        $paymentMethods = available_payment_methods();
        $activeMethods = PaymentMethod::whereIn('slug', array_column($paymentMethods, 'slug'))
            ->where('status', PaymentMethodStatus::ACTIVE)
            ->get()->filter(function ($item) {
                return filled($item->is_active);
            })->keyBy('slug');

        $errors = $this->validateDepositDefault($activeMethods);

        return view('user.transaction.deposit-method', compact('activeMethods', 'errors'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function depositAmount(Request $request)
    {
        $pm = $request->get('deposit_method', $request->session()->get('deposit_method'));
        $method = $this->pmDetails($pm);
        $currencies = $this->getCurrenciesData($method, false, 'deposit');

        if (blank($method) || blank($currencies)) {
            $errors = MsgState::of('invalid-method', 'deposit');
            return view('user.transaction.error-state', $errors);
        }

        $rates = $this->getCurrenciesData($method, false, 'deposit', 'rate');
        $default = (in_array($this->basecur, array_keys($currencies))) ? Arr::get($currencies, $this->basecur) : Arr::first($currencies);

        $request->session()->put('deposit_method', $pm);
        $request->session()->put('deposit_payment_method', data_get($method, 'slug'));
        $request->session()->put('deposit_currencies', array_keys($currencies));

        return view('user.transaction.deposit-amount', compact('method', 'default', 'currencies', 'rates'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     * @version 1.0.0
     * @since 1.0
     */
    public function depositPreview(Request $request)
    {
        $this->validate($request, [
            'deposit_amount' => ['required', 'numeric'],
            'deposit_currency' => ['required']
        ], [
            'deposit_amount.required' => __('Please enter your amount to deposit.'),
            'deposit_amount.numeric' => __('Please enter a valid amount for deposit.'),
            'deposit_currency.required' => __('Choose your deposited currency.'),
        ]);

        $base_cur = $this->basecur;
        $currency = strtoupper($request->get('deposit_currency'));
        $amount = (float)$request->get('deposit_amount');
        $pm = $this->pmDetails($request->session()->get('deposit_payment_method'));
        $currencies = $request->session()->get('deposit_currencies');
        $currencies = (!blank($pm)) ? array_intersect($currencies, $pm->currencies) : $currencies;
        $rates = (!blank($pm)) ? $this->getCurrenciesData($pm) : array();

        // recheck error
        if (!in_array($currency, $currencies) || blank($pm) || !isset($rates[$currency])) {
            $msgof = blank($pm) ? 'invalid-method' : 'wrong';
            $errors = MsgState::of($msgof, 'deposit');
            return view('user.transaction.error-state', $errors);
        }

        $has_fiat = (isset($pm->config['meta']['fiat'])) ? $pm->config['meta']['fiat'] : false;
        $alt_cur = ($has_fiat && $has_fiat !== 'alter') ? $has_fiat : secondary_currency();

        $type_base = (is_crypto($base_cur)) ? 'crypto' : 'fiat';
        $fx = $rates[$currency];

        if (BigDecimal::of($amount)->compareTo($fx['min']) == -1) {
            throw ValidationException::withMessages([
                'deposit_amount' => __("Sorry, the minimum amount of :amount is required to deposit funds.", ['amount' => money($fx['min'], $currency)])
            ]);
        }

        if (!empty($fx['max']) && BigDecimal::of($amount)->compareTo($fx['max']) == 1) {
            throw ValidationException::withMessages([
                'deposit_amount' => __("You can deposit funds maximum :amount in a single order.", ['amount' => money($fx['max'], $currency)])
            ]);
        }

        $exchange = BigDecimal::of($fx['rate']);
        if (BigDecimal::of($exchange)->compareTo(0) != 1) {
            $errors = MsgState::of('no-rate', 'deposit');
            return view('user.transaction.error-state', $errors);
        }

        $equal_amount = BigDecimal::of(get_fx_rate($currency, $alt_cur, $amount));
        $amount_fee = 0.0;
        $fee_data = ['type' => '', 'amount' => 0];

        $fx_cur = ($currency == $base_cur) ? $alt_cur : $currency;
        $fx_rate = ($currency == $base_cur) ? get_fx_rate($base_cur, $alt_cur) : $fx['rate'];

        $exchange = is_object($exchange) ? (string)$exchange : $exchange;
        $equal_amount = is_object($equal_amount) ? (string)$equal_amount : $equal_amount;
        $base_amount = round(($amount / $exchange), $this->rounded->$type_base);
        $base_fee = round(($amount_fee / $exchange), $this->rounded->$type_base);

        $payment = [
            'method' => $pm->slug,
            'method_name' => $pm->title,
            'currency' => $currency,
            'currency_name' => get_currency($currency, 'name'),
            'amount' => $amount,
            'amount_fees' => $amount_fee,
            'fees' => $fee_data,
            'total' => $this->toSum($amount, $amount_fee),
            'base_amount' => $base_amount,
            'base_fees' => $base_fee,
            'base_total' => $this->toSum($base_amount, $base_fee),
            'base_currency' => $base_cur,
            'equal_amount' => $equal_amount,
            'equal_currency' => $alt_cur,
            'exchange_rate' => $exchange,
            'fx_rate' => $fx_rate,
            'fx_currency' => $fx_cur,
            'pay_to' => $this->payInfo($pm, $currency, true),
            'pay_meta' => $this->payInfo($pm, $currency, false)
        ];

        $request->session()->put('deposit_details', $payment);

        return view('user.transaction.deposit-preview', compact('amount', 'currency', 'payment', 'pm'));
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     * @version 1.0.0
     * @since 1.0
     */
    public function depositConfirm(Request $request)
    {
        if ($request->get('confirm') && $request->session()->has('deposit_details')) {
            return $this->wrapInTransaction(function ($request) {
                $depositDetails = $request->session()->pull('deposit_details');
                $hasPayInfo = $this->payInfo($depositDetails['method'], $depositDetails['currency'], true);
                if (!empty($hasPayInfo)) {
                    $transactionService = new TransactionService();
                    $transaction = $transactionService->createDepositTransaction($depositDetails);
                    $transactionProcessor = new TransactionProcessor();
                    $response = $transactionProcessor->process($transaction, Arr::get($depositDetails, 'method'));

                    try {
                        ProcessEmail::dispatch('deposit-placed-customer', data_get($transaction, 'customer'), null, $transaction);
                        ProcessEmail::dispatch('deposit-placed-admin', data_get($transaction, 'customer'), null, $transaction);
                    } catch (\Exception $e) {
                        save_mailer_log($e, 'deposit-placed');
                    }

                    $transaction->status = TransactionStatus::PENDING;
                    $transaction->save();

                    return $response ? $response : view('user.transaction.deposit-confirm');
                } else {
                    $errors = MsgState::of('try-method', 'deposit');
                    return view('user.transaction.error-state', $errors);
                }
            }, $request);
        } else {
            throw ValidationException::withMessages(['confirm' => __('Opps! We unable to process your request. Please reload the page and try again.')]);
        }
    }

    /**
     * @param $methods
     * @return array
     * @version 1.0.0
     * @since 1.0
     */
    private function validateWithdrawDefault($methods)
    {
        if (sys_settings('withdraw_disable_request') == 'yes') {
            return MsgState::of('disable', 'withdraw');
        }

        if (blank($methods)) {
            return MsgState::of('no-method', 'withdraw');
        }

        if (!$this->hasUserAccounts()) {
            return MsgState::of('add-method', 'account');
        }

        if (!$this->hasAccountBalance()) {
            return MsgState::of('no-fund', 'account');
        }

        if (kyc_required('withdraw', true)) {
            return module_msg_of('withdraw', 'error', 'BasicKYC');
        }

        if ($limit_queue = sys_settings('withdraw_limit_request')) {
            $pending_queue = Transaction::where('user_id', auth()->id())
                ->where('type', TransactionType::WITHDRAW)->where('status', TransactionStatus::PENDING)->count();

            if ($pending_queue >= $limit_queue) {
                return MsgState::of('limit', 'withdraw');
            }
        }
    }

    /**
     * @param $method
     * @param $currencies
     * @return array
     * @version 1.0.0
     * @since 1.0
     */
    private function validateWithdrawAccount($method, $currencies)
    {
        if (blank($method)) {
            return MsgState::of('no-method', 'withdraw');
        }

        if (blank($currencies)) {
            return MsgState::of('invalid-method', 'withdraw');
        }

        if (!$this->hasAccountBalance()) {
            return MsgState::of('no-fund', 'account');
        }
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function showWithdrawMethod()
    {
        $withdrawMethods = available_withdraw_methods();
        $activeMethods = WithdrawMethod::whereIn('slug', array_column($withdrawMethods, 'slug'))
            ->where('status', WithdrawMethodStatus::ACTIVE)
            ->get()->filter(function ($item) {
                return filled($item->is_active);
            })->keyBy('slug');

        $balance = $this->getAccountBalance(AccType('main'), true);
        $errors = $this->validateWithdrawDefault($activeMethods);

        return view('user.transaction.withdraw-method', compact('activeMethods', 'balance', 'errors'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     * @version 1.0.0
     * @since 1.0
     */
    public function withdrawAmount(Request $request)
    {
        $wdm = $request->get('withdraw_method', $request->session()->get('withdraw_method'));
        $source = $request->get('wd_source', $request->session()->get('wd_source', AccType('main')));

        $method = $this->wdmDetails($wdm);
        $accounts = $this->getUserAccounts($wdm);
        $currencies = $this->getCurrenciesData($method, false, 'withdraw');

        $errors = $this->validateWithdrawAccount($method, $currencies);
        if (!empty($errors)) {
            return view('user.transaction.error-state', $errors);
        }

        $rates = $this->getCurrenciesData($method, false, 'withdraw', 'rate');
        $balance = $this->getAccountBalance($source, true);

        $request->session()->put('withdraw_method', $wdm);
        $request->session()->put('wd_source', $source);
        $request->session()->put('wd_currencies', array_keys($currencies));

        return view('user.transaction.withdraw-amount', compact('method', 'accounts', 'currencies', 'rates', 'balance'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     * @version 1.0.0
     * @since 1.0
     */
    public function withdrawPreview(Request $request)
    {
        $this->validate($request, [
            'wd_account' => ['required'],
            'wd_currency' => ['required'],
            'wd_amount' => ['required', 'numeric'],
            'wd_desc' => ['nullable'],
            'wd_amount_to' => ['required', 'numeric'],
        ], [
            'wd_account.required' => __('Select the account you would like to withdraw.'),
            'wd_currency.required' => __('Enter your currency to withdraw.'),
            'wd_amount.*' => __('Enter a valid amount in withdraw amount.'),
            'wd_amount_to.*' => __('Enter a valid amount in amount to receive.'),
        ]);

        $accountID = get_hash($request->get('wd_account'));
        $wdmAccount = UserAccount::find($accountID);

        $currency = strtoupper($request->get('wd_currency', $this->basecur));
        $amount = (float)$request->get('wd_amount');
        $wddesc = strip_tags($request->get('wd_desc'));

        $amount_wd = (float)$request->get('wd_amount_to');
        $currency_wd = strtoupper($request->get('wd_currency_to'));
        $fee_data = ['type' => '', 'amount' => 0];

        $wdm = (!blank($wdmAccount)) ? $this->wdmDetails($wdmAccount->slug) : null;
        $wdmcur = (!blank($wdmAccount)) ? $wdmAccount->account_currency : false;
        $currencies = $request->session()->get('wd_currencies');
        $currencies = (!blank($wdm)) ? array_intersect($currencies, $wdm->currencies) : $currencies;
        $rates = (!blank($wdm)) ? $this->getCurrenciesData($wdm, false, 'withdraw') : array();

        // recheck error
        if (blank($wdmAccount)) {
            throw ValidationException::withMessages([
                'wd_account' => ['title' => __('Account may not valid or found!'), 'message' => __('Selected account is no longer available. Please choose another account and try again.')]
            ]);
        } elseif (!($currency_wd === $wdmcur)) {
            throw ValidationException::withMessages([
                'wd_account' => ['title' => __('Account currency does not support!'), 'message' => __('Please update currency on your withdraw account from profile and then try again.')]
            ]);
        }
        if (!($currency === $this->basecur) || !in_array($currency_wd, $currencies) || blank($wdm) || !isset($rates[$currency]) || !isset($rates[$currency_wd])) {
            $msgof = (blank($wdm) || !($currency === $this->basecur)) ? 'invalid-method' : 'wrong';
            $errors = MsgState::of($msgof, 'withdraw');
            return view('user.transaction.error-state', $errors);
        }

        $source = $request->session()->get('wd_source', AccType('main'));
        $account = $this->getAccountBalance($source);
        $balance = $this->getAccountBalance($source, true);

        $wfx = $rates[$currency_wd];
        $type = (is_crypto($currency)) ? 'crypto' : 'fiat';
        $amount_to = get_fx_rate($currency, $currency_wd, $amount);
        $currency_to = $currency_wd;

        if (BigDecimal::of($amount_to)->minus($amount_wd)->compareTo('-0.01') === 0) {
            $amount_to = BigDecimal::of($amount_to)->plus('0.01');
        } elseif (BigDecimal::of($amount_to)->minus($amount_wd)->compareTo('0.01') === 0) {
            $amount_to = BigDecimal::of($amount_to)->minus('0.01');
        }

        $amount_to = (is_object($amount_to)) ? (string)$amount_to : $amount_to;
        $amount_min = get_fx_rate($currency_to, $currency, $wfx['min']);
        $amount_max = get_fx_rate($currency_to, $currency, $wfx['max']);

        // amount validation
        if (BigDecimal::of($amount)->compareTo($amount_min) == -1) {
            throw ValidationException::withMessages([
                'wd_amount' => __("The minimum amount of :amount (:from) is required to withdraw.", ['amount' => money($wfx['min'], $currency_to), 'from' => money($amount_min, $currency)])
            ]);
        }

        if (!empty($amount_max) & BigDecimal::of($amount)->compareTo($amount_max) == 1) {
            throw ValidationException::withMessages([
                'wd_amount' => __("You can withdraw maximum :amount (:from) in a single request.", ['amount' => money($wfx['max'], $currency_to), 'from' => money($amount_max, $currency)])
            ]);
        }
        if (BigDecimal::of($amount)->compareTo($balance) > 0) {
            throw ValidationException::withMessages(['wd_amount' => ['title' => __('Insufficient balance!'), 'message' => __('The amount exceeds your current balance.')]]);
        }

        $exchange = BigDecimal::of($wfx['rate']);
        $exchange = is_object($exchange) ? (string)$exchange : $exchange;

        $withdraw = [
            'method' => $wdm->slug,
            'method_name' => ($wdm->slug === 'wd-crypto-wallet') ? get_currency($currency_to, 'name') . ' Wallet' : $wdm->title,
            'currency' => $currency_to,
            'currency_name' => get_currency($currency_to, 'name'),
            'amount' => $amount_to,
            'amount_fees' => 0,
            'fees' => $fee_data,
            'total' => $this->toSum($amount_to, 0),
            'base_amount' => $amount,
            'base_fees' => 0,
            'base_total' => $this->toSum($amount, 0),
            'base_currency' => $currency,
            'exchange_rate' => $exchange,
            'pay_to' => $this->payAccount($wdmAccount, $wdm, $currency_to, true),
            'pay_meta' => $this->payAccount($wdmAccount, $wdm, $currency_to, false),
            'desc' => ($wddesc) ? $wddesc : '',
            'source' => $source,
            'account' => $wdmAccount->account_name,
            'fx_rate' => $exchange,
            'fx_currency' => $currency_to,
        ];

        $request->session()->put('wd_details', $withdraw);
        return view('user.transaction.withdraw-preview', compact('withdraw', 'wdm', 'amount', 'currency'));
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     * @version 1.0.0
     * @since 1.0
     */
    public function withdrawConfirm(Request $request)
    {
        if ($request->get('confirm') && $request->session()->has('wd_details')) {
            return $this->wrapInTransaction(function ($request) {
                $withdrawDetails = $request->session()->pull('wd_details');
                $transactionService = new TransactionService();
                $transaction = $transactionService->createWithdrawTransaction($withdrawDetails);

                $userAccount = get_user_account(auth()->user()->id);
                $userAccount->amount = BigDecimal::of($userAccount->amount)->minus(BigDecimal::of($transaction->total));
                $userAccount->save();

                $transaction->status = TransactionStatus::PENDING;
                $transaction->save();

                try {
                    ProcessEmail::dispatch('withdraw-request-customer', data_get($transaction, 'customer'), null, $transaction);
                    ProcessEmail::dispatch('withdraw-request-admin', data_get($transaction, 'customer'), null, $transaction);
                } catch (\Exception $e) {
                    save_mailer_log($e, 'withdraw-request');
                }

                return view('user.transaction.withdraw-confirm', compact('transaction'));
            }, $request);
        } else {
            throw ValidationException::withMessages(['confirm' => __('Opps! We unable to process your request. Please reload the page and try again.')]);
        }
    }

    /**
     * @param $status
     * @param null $tnx
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function depositComplete(Request $request, $status, $tnx = null)
    {
        $tnx = get_hash($tnx);
        $transaction = Transaction::loggedUser()->find($tnx);
        return $this->finalDepositUpdate($transaction, $status, $request);
    }

    /**
     * @param string $currency
     * @param object $method
     * @param boolean $fm
     * @param string $what
     * @param boolean $ignore
     * @return mixed
     * @version 1.0.0
     * @since 1.0
     */
    private function getCurrencyAmounts($currency, $method, $fm = false, $what = 'deposit', $ignore = false)
    {
        $fx = BigDecimal::of(get_ex_rate($currency));
        $type = (is_crypto($currency)) ? 'crypto' : 'fiat';
        $where = ($what === 'withdraw') ? 'withdraw_' : 'deposit_';
        $isMin = $this->specifyMin($method, $currency);

        $maxF = sys_settings($where . 'fiat_maximum', 0);
        $minF = sys_settings($where . 'fiat_minimum', 0.1);

        $round = sys_settings($type . '_rounded');
        $maxD = sys_settings($where . $type . '_maximum', 0);
        $minD = sys_settings($where . $type . '_minimum', 0.1);

        $maxD = (empty($maxD)) ? $maxF : $maxD;
        $minD = (empty($minD)) ? $minF : $minD;

        $minM = ($method->min_amount) ? $method->min_amount : 0;
        $minS = ($isMin && $ignore !== true) ? $isMin : 0;

        $minDefault = BigDecimal::of($minD)->multipliedBy($fx);
        $minMethod = BigDecimal::of($minM)->multipliedBy($fx);
        $minSpecify = BigDecimal::of($minS);

        $minAmount = (BigDecimal::of($minMethod)->compareTo($minDefault) > 0) ? $minMethod : $minDefault;
        $minAmount = (BigDecimal::of($minSpecify)->compareTo('0') > 0) ? $minSpecify : $minAmount;

        $maxAmount = BigDecimal::of($maxD)->multipliedBy($fx);
        $maxAmount = (BigDecimal::of($maxAmount)->compareTo($minAmount) > 0) ? $maxAmount : 0;

        $fx = is_object($fx) ? (string)$fx : $fx;
        $minAmount = is_object($minAmount) ? (string)$minAmount : $minAmount;
        $maxAmount = is_object($maxAmount) ? (string)$maxAmount : $maxAmount;

        if (in_array($round, ['up', 'down'])) {
            $minAmount = ($round === 'up') ? ceil($minAmount) : floor($minAmount);
            $maxAmount = ($round === 'up') ? ceil($maxAmount) : floor($maxAmount);
        }

        // Override if 0
        $minAmount = (BigDecimal::of($minAmount)->compareTo(0) == 1) ? $minAmount : 0.1;

        $return = [
            'dp' => $this->rounded->$type,
            'fx' => ($fm === true) ? amount($fx, $currency) : round($fx, $this->rounded->$type),
            'minimum' => ($fm === true) ? amount($minAmount, $currency) : round($minAmount, $this->rounded->$type),
            'maximum' => ($fm === true) ? amount($maxAmount, $currency) : round($maxAmount, $this->rounded->$type),
        ];

        return (object)$return;
    }

    /**
     * @param $method
     * @param bool $format
     * @param string $what
     * @return array
     * @version 1.0.0
     * @since 1.0
     */
    private function getCurrenciesData($method, $format = false, $what = 'deposit', $only = null)
    {
        if (!blank($method)) {
            $method_currencies = collect($method->currencies);
            $active_currencies = $method_currencies->intersect(active_currencies('key'));

            $data = $active_currencies->map(function ($currency) use ($method, $format, $what) {
                $amounts = $this->getCurrencyAmounts($currency, $method, $format, $what);
                $rate = ($format === false) ? round($amounts->fx, $amounts->dp) : amount($amounts->fx, $currency);
                $minimum = ($format === false) ? round($amounts->minimum, $amounts->dp) : amount($amounts->minimum, $currency);
                $maximum = ($format === false) ? round($amounts->maximum, $amounts->dp) : amount($amounts->maximum, $currency);

                return ['rate' => $rate, 'min' => $minimum, 'max' => $maximum, 'code' => $currency, 'dp' => $amounts->dp];
            })->keyBy('code');

            $output = ($only == 'rate') ? array_column($data->toArray(), 'rate', 'code') : $data->toArray();

            if (!isset($output[$this->basecur]) && $what != 'deposit') {
                if ($only == 'rate') {
                    $output[$this->basecur] = 1;
                } else {
                    $base = $this->getCurrencyAmounts($this->basecur, $method, $format, $what, true);
                    $output[$this->basecur] = ['rate' => $base->fx, 'min' => $base->minimum, 'max' => $base->maximum, 'code' => $this->basecur, 'dp' => $base->dp];
                }
            }
            return $output;
        }

        return [];
    }

    public function viewTransactionDetails(Request $request)
    {
        $id = get_hash($request->get("id"));
        $transaction = Transaction::loggedUser()->find($id);

        if (blank($transaction)) {
            throw ValidationException::withMessages(["invalid" => __("Invalid transaction id or not found.")]);
        }

        if ($transaction->type == TransactionType::INVESTMENT && $transaction->status == TransactionStatus::PENDING) {
            if ($request->ajax()) {
                return response()->json(view('user.transaction.details', compact('transaction'))->render());
            }
            return abort(404);
        }

        if ($transaction->type == TransactionType::REFERRAL) {
            if ($request->ajax()) {
                return response()->json(view('user.transaction.details-referral', compact('transaction'))->render());
            }
            return abort(404);
        }

        if ($transaction->status != TransactionStatus::PENDING) {
            if ($request->ajax()) {
                return response()->json(view('user.transaction.details', compact('transaction'))->render());
            }
            return abort(404);
        }

        if ($transaction->type == TransactionType::DEPOSIT) {
            $transactionProcessor = new TransactionProcessor();
            return $transactionProcessor->getTransactionDetailsView($transaction);
        } elseif ($transaction->type == TransactionType::WITHDRAW) {
            $withdrawProcessor = new WithdrawProcessor();
            return $withdrawProcessor->getWithdrawDetailsView($transaction);
        }
    }

    private function finalDepositUpdate($transaction, $status, $request, $online = false)
    {
        if (blank($transaction)) {
            return redirect()->route('dashboard')->withErrors(['tnx_error' => __('The transaction may invalid or not found!')]);
        }

        if ($transaction->status == TransactionStatus::FAILED) {
            if ($request->ajax()) {
                $errors = MsgState::of('wrong', 'deposit');
                return view('user.transaction.error-state', $errors);
            } else {
                return view('user.transaction.deposit-final', [
                    'transaction' => $transaction,
                    'contentBlade' => 'deposit-wrong',
                    'status' => $status,
                ]);
            }
        }

        if (in_array($transaction->status, [
            TransactionStatus::COMPLETED,
            TransactionStatus::CONFIRMED,
            TransactionStatus::CANCELLED
        ]) && !$online) {
            $errors = MsgState::of('invalid-action', $transaction->type);
            return view('user.transaction.errors', compact('errors'));
        }

        if (!in_array($status, ['success', 'cancel'])) {
            return redirect()->route('dashboard');
        }

        $etype = false;
        if (data_get($transaction, 'type') == TransactionType::DEPOSIT) {
            $etype = 'deposit';
        } elseif (data_get($transaction, 'type') == TransactionType::WITHDRAW) {
            $etype = 'withdraw';
        }

        if ($status == 'cancel' && !$online) {
            if ($transaction->is_cancellable) {
                $transaction->status = TransactionStatus::CANCELLED;
                $transaction->save();

                if ($transaction->type == TransactionType::WITHDRAW) {
                    $userAccount = get_user_account($transaction->user_id);
                    $userAccount->amount = BigDecimal::of($userAccount->amount)->plus(BigDecimal::of($transaction->total));
                    $userAccount->save();
                }

                try {
                    if ($etype) {
                        ProcessEmail::dispatch($etype . '-cancel-user-customer', data_get($transaction, 'customer'), null, $transaction);
                        ProcessEmail::dispatch($etype . '-cancel-user-admin', data_get($transaction, 'customer'), null, $transaction);
                    }
                } catch (\Exception $e) {
                    save_mailer_log($e, $etype);
                }
            } else {
                $errors = MsgState::of('cancel-timeout', $transaction->type);
                return view('user.transaction.errors', compact('errors'));
            }
        }

        return view('user.transaction.deposit-final', [
            'transaction' => $transaction,
            'contentBlade' => $transaction->type . '-' . $status,
        ]);
    }

    public function onlineDepositComplete(Request $request, $status, $tnx)
    {
        $transaction = Transaction::loggedUser()->where('tnx', $tnx)->first();
        return $this->finalDepositUpdate($transaction, $status, $request, true);
    }
}
