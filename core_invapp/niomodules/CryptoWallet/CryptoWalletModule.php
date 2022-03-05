<?php


namespace NioModules\CryptoWallet;

use App\Interfaces\Payable;
use App\Models\PaymentMethod;
use App\Enums\PaymentMethodStatus;

class CryptoWalletModule implements Payable
{
    const VERSION = '1.0.0';
    const LAST_UPDATE = '04102021';
    const MIN_APP_VER = '1.0.0';
    const MOD_TYPES = 'core';
    const SLUG = 'crypto-wallet';
    const METHOD = 'crypto';

    private $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = PaymentMethod::where('slug', self::SLUG)->first();
    }

    /**
     * @return string
     * @version 1.0.0
     * @since 1.0
     */
    public function getRequiredMinBaseAppVersion()
    {
        return self::MIN_APP_VER;
    }

    /**
     * @return string
     * @version 1.0.0
     * @since 1.0
     */
    public function getSlug()
    {
        return self::SLUG;
    }

    /**
     * @return string
     * @version 1.0.0
     * @since 1.0
     */
    public function getTitle()
    {
        return data_get($this->paymentMethod->config, 'meta.title', $this->paymentMethod->name);
    }

    /**
     * @param $transaction
     * @param $paymentMethodDetails
     * @version 1.0.0
     * @since 1.0
     */
    public function makePayment($transaction, $paymentMethodDetails)
    {
        $amount = data_get($transaction, 'tnx_total');
        $currency = data_get($transaction, 'tnx_currency');
        $currencyName = get_currency(data_get($transaction, 'tnx_currency'), 'name');
        $contextQR = str_replace(" ", "-", strtolower($currencyName)) . ":" . data_get($transaction, 'meta.pay_meta.address') . "?amount=" . amount($amount, $currency, ['dp'=>'calc', 'zero' => true]);
        $has_fiat = data_get($paymentMethodDetails->config, 'meta.fiat');
        $currency = data_get($transaction, 'tnx_currency');
        $currencyName = get_currency($currency, 'name');
        $amount = data_get($transaction, 'tnx_total');

        $contextQR = str_replace(" ", "-", strtolower($currencyName)) . ":" . data_get($transaction, 'pay_to') . "?amount=" . amount($amount, $currency, ['dp' => 'calc', 'zero' => true]);

        $depositDetails = [
            'amount' => $amount,
            'currency' => $currency,
            'currency_name' => $currencyName,
            'payment' => [
                'address' => data_get($transaction, 'pay_to'),
                'meta' => data_get($transaction, 'meta.pay_meta'),
                'fiat' =>  ($has_fiat && $has_fiat!=='alter') ? $has_fiat : secondary_currency()
            ],
            'qrcode' => $contextQR,
            'tranx' => $transaction,
            'method' => $paymentMethodDetails,
            'qrcode' => $contextQR,
        ];

        request()->session()->put('deposit_details', $depositDetails);

        return [
            'redirect' => true,
            'approve_url' => [
                'href' => route('user.crypto.wallet.deposit.complete')
            ],
        ];
    }

    /**
     * @param $transaction
     * @param $paymentMethodDetails
     * @version 1.0.0
     * @since 1.0
     */
    public function verifyPayment($transaction, $paymentMethodDetails)
    {
        return false;
    }

    /*
     * Check if this active
     */
    public function isActive()
    {
        if (blank($this->paymentMethod)) {
            return false;
        }

        if (!$this->hasConfig()) {
            return false;
        }
        return true;
    }

    /*
     * Check if this enable
     */
    public function isEnable()
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->paymentMethod->status==PaymentMethodStatus::ACTIVE) {
            return true;
        }

        return false;
    }

    /*
     * Check it has minimal configuration
     */
    public function hasConfig()
    {
        $enableCurrency = $this->paymentMethod->currencies;
        $config = $this->paymentMethod->config;
        $wallets = $config['wallet'] ?? false;

        if (empty($enableCurrency) || empty($config) || empty($wallets)) {
            return false;
        }

        return true;
    }

    /*
     * Provide payment info
     */
    public function getPayInfo($currency=null, $only=false)
    {
        if (!$this->hasConfig()) return false;

        $config = $this->paymentMethod->config;
        $wallets = $config['wallet'] ?? false;

        if(!empty($currency) && !empty($wallets)) {
            if(isset($wallets[$currency])) {
                if($only===false) {
                    $payinfo = $wallets[$currency];
                    unset($payinfo['min']);
                    if(isset($config['meta']['timeout'])) {
                        $timeout = ['timeout' => $config['meta']['timeout']];
                        $payinfo = array_merge($payinfo, $timeout);
                    }
                    return $payinfo;
                }
                if(isset($wallets[$currency]['address'])) {
                    return ($wallets[$currency]['address']) ? $wallets[$currency]['address'] : false;
                }
                return false;
            }
            return false;
       }

        return (!empty($wallets)) ? $wallets : false;
    }

    public static function getShortcutInfo($transaction)
    {
        return "\nIf you have not made the payment yet, please send the amount of " . money($transaction->tnx_total, $transaction->tnx_currency, ['dp'=>'calc']) . " to the following " . get_currency($transaction->tnx_currency, 'name') . " address:\n" . data_get($transaction, 'meta.pay_meta.address');
    }
    
    public function getDetailsView($transaction, $paymentMethod)
    {
        $config = data_get($paymentMethod, 'config');
        $fiat = data_get($config, 'meta.fiat');
        $timeout = data_get($config, 'meta.timeout');
        $reference = data_get($config, 'wallet.'.data_get($transaction, 'tnx_currency').'.ref');
        $currency = data_get($transaction, 'tnx_currency');
        $currencyName = get_currency(data_get($transaction, 'tnx_currency'), 'name');
        $amount = data_get($transaction, 'tnx_amount');
        $contextQR = str_replace(" ", "-", strtolower($currencyName)) . ":" . data_get($transaction, 'meta.pay_meta.address') . "?amount=" . amount($amount, $currency, ['dp'=>'calc', 'zero' => true]);

        $currency = data_get($transaction, 'tnx_currency');
        $currencyName = get_currency($currency, 'name');
        $amount = data_get($transaction, 'tnx_amount');

        $contextQR = str_replace(" ", "-", strtolower($currencyName)) . ":" . data_get($transaction, 'pay_to') . "?amount=" . amount($amount, $currency, ['dp' => 'calc', 'zero' => true]);

        return view('CryptoWallet::details', [
            'amount' => $amount,
            'currency' => $currency,
            'currency_name' => $currencyName,
            'payment' => [
                "address" => data_get($transaction, 'pay_to'),
                'meta' => data_get($transaction, 'meta.pay_meta'),
                'fiat' => ($fiat=='alter') ? secondary_currency() : $fiat,
                'timeout' => $timeout,
                'reference' => $reference
            ],
            'qrcode' => $contextQR,
            'tranx' => $transaction,
            'method' => $paymentMethod,
            'qrcode' => $contextQR,
        ]);
    }
}
