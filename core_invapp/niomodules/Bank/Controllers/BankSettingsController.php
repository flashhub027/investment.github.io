<?php

namespace NioModules\Bank\Controllers;

use NioModules\Bank\BankModule;
use App\Enums\PaymentMethodStatus;
use App\Models\PaymentMethod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BankSettingsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function settingsView()
    {
        $config = config('modules.'.BankModule::SLUG);
        $supportedCurrencies = array_filter(config('currencies'), function ($key) use ($config) {
            return ( in_array($key, data_get($config, 'supported_currency')) );
        }, ARRAY_FILTER_USE_KEY);
        $settings = PaymentMethod::where('slug', BankModule::SLUG)->first();
        return view("Bank::settings", compact('config', 'settings', 'supportedCurrencies'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @version 1.0.0
     * @since 1.0
     */
    public function saveBankSettings(Request $request)
    {
        if(empty($request->slug) || $request->slug !== BankModule::SLUG) {
            return response()->json([ 'type' => 'error', 'msg' => __('Sorry, something wrong with payment method.') ]);
        }

        $input = $request->validate([
            'slug' => 'required',
            'name' => 'required|string|max:190',
            'desc' => 'required|string|max:190',
            'currencies' => 'required',
            'status' => 'nullable',
            'min_amount' => 'nullable|numeric|min:0',
            'config' => 'array',
            'config.ac.account_name' => 'required|string',
            'config.ac.account_number' => 'required|string',
            'config.ac.bank_name' => 'required',
            'config.ac.bank_short' => 'required',
            'config.meta.min' => 'nullable|numeric|min:0'
        ], [
            'slug.required' => __('Sorry, your payment method is invalid.'),
            'name.required' => __('Payment method title is required.'),
            'desc.required' => __('Payment method short description is required.'),
            'currencies.required' => __('You must select your local currency.'),
            'config.ac.account_name.required' => __('Account name is required on bank details.'),
            'config.ac.account_number.required' => __('Account number is required on bank details.'),
            'config.ac.bank_name.required' => __('The bank name is required.'),
            'config.ac.bank_short.required' => __('The bank short name is required.'),
            'config.meta.min.numeric' => __('The fixed minimum amount must be numeric.'),
            'config.meta.min.min' => __('The fixed minimum amount must be at least 0.'),
        ]);

        $input['min_amount'] = abs($input['min_amount']);
        $input['fees'] = array('flat' => 0, 'percent' => 0);
        $input['countries'] = array();
        $input['status'] = ($input['status'] == 'active') ? PaymentMethodStatus::ACTIVE : PaymentMethodStatus::INACTIVE;
        $input['config']['meta'] = array_map('strip_tags_map', $input['config']['meta']);
        $input['config']['ac'] = array_map('strip_tags_map', $input['config']['ac']);

        PaymentMethod::updateOrCreate(['slug' => $input['slug']], array_map('strip_tags_map', $input));

        return response()->json([ 'msg' => __('Payment method successfully updated.') ]);
    }

}
