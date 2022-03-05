<?php

namespace NioModules\CryptoWallet\Controllers;

use NioModules\CryptoWallet\CryptoWalletModule;
use App\Enums\PaymentMethodStatus;
use App\Models\PaymentMethod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletSettingsController extends Controller
{

    /**
     * @return array
     * @version 1.0.0
     * @since 1.0
     */
    public function support_currencies()
    {
        $config = config('modules.'.CryptoWalletModule::SLUG);

        $currencies = array_filter(config('currencies'), function ($key) use ($config) {
            return ( in_array($key, data_get($config, 'supported_currency')) );
        }, ARRAY_FILTER_USE_KEY);

        return $currencies;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function settingsView()
    {
        $config = config('modules.'.CryptoWalletModule::SLUG);
        $currencies = $this->support_currencies();
        $fiat_currencies = get_currencies('list', 'fiat');

        $settings = PaymentMethod::where('slug', CryptoWalletModule::SLUG)->first();
        return view("CryptoWallet::settings", compact('config', 'settings', 'currencies', 'fiat_currencies'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @version 1.0.0
     * @since 1.0
     */
    public function saveWalletSettings(Request $request)
    {
        if(empty($request->slug) || $request->slug !== CryptoWalletModule::SLUG) {
            return response()->json([ 'type' => 'error', 'msg' => __('Sorry, something wrong with payment method.') ]);
        }

        $currencies = $this->support_currencies();

        $input = $request->validate([
            'slug' => 'required',
            'name' => 'required|string|max:190',
            'desc' => 'required|string|max:190',
            'currencies' => 'required|array|min:1',
            'status' => 'nullable',
            'min_amount' => 'nullable|numeric|min:0',
            'config' => 'array',
            'config.wallet.*.min' => 'nullable|numeric|min:0',
        ], [
            'slug.required' => __('Sorry, your payment method is invalid.'),
            'name.required' => __('Payment method title is required.'),
            'desc.required' => __('Payment method short description is required.'),
            'currencies.*' => __('Select at-least one wallet (currency) from supported wallet.'),
            'config.wallet.*.min.numeric' => __('The fixed minimum amount must be numeric.'),
            'config.wallet.*.min.min' => __('The fixed minimum amount must be at least 0.'),
        ]);

        foreach ($currencies as $currency) {
            if(in_array($currency['code'], $input['currencies'])) {
                $input['config']['wallet'][$currency['code']] = array_map('strip_tags_map', $input['config']['wallet'][$currency['code']]);
                if($input['config']['wallet'][$currency['code']]['min']){
                    $input['config']['wallet'][$currency['code']]['min'] = abs($input['config']['wallet'][$currency['code']]['min']);
                }
                if(empty($input['config']['wallet'][$currency['code']]['address'])){
                    return response()->json([ 'type' => 'warning', 'msg' => __('The address is required as you have enable :currency wallet.', ['currency' => $currency['code']]) ]);
                }
            }
        }

        $input['min_amount'] = abs($input['min_amount']);
        $input['fees'] = array('flat' => 0, 'percent' => 0);
        $input['countries'] = array();
        $input['status'] = ($input['status'] == 'active') ? PaymentMethodStatus::ACTIVE : PaymentMethodStatus::INACTIVE;
        $input['config']['meta'] = array_map('strip_tags_map', $input['config']['meta']);
        
        if(empty($input['currencies'])) {
            return response()->json([ 'type' => 'warning', 'msg' => __('Select at-least one wallet (currency) from supported wallet.') ]);
        }

        PaymentMethod::updateOrCreate(['slug' => $input['slug']], array_map('strip_tags_map', $input));

        return response()->json([ 'msg' => __('Payment method successfully updated.') ]);
    }

}
