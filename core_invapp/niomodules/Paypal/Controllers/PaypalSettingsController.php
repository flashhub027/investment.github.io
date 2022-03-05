<?php

namespace NioModules\Paypal\Controllers;

use NioModules\Paypal\PaypalModule;
use App\Enums\PaymentMethodStatus;
use App\Models\PaymentMethod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaypalSettingsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function settingsView()
    {
        $config = config('modules.paypal');
        $supportedCurrencies = array_filter(config('currencies'), function ($key) use ($config) {
            return ( in_array($key, data_get($config, 'supported_currency')) );
        }, ARRAY_FILTER_USE_KEY);
        $settings = PaymentMethod::where('slug', PaypalModule::SLUG)->first();
        return view("Paypal::settings", compact('config', 'settings', 'supportedCurrencies'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @version 1.0.0
     * @since 1.0
     */
    public function savePaypalSettings(Request $request)
    {
        if(empty($request->slug) || $request->slug !== PaypalModule::SLUG) {
            return response()->json([ 'type' => 'error', 'msg' => __('Sorry, something wrong with payment method.') ]);
        }

        $input = $request->validate([
            'slug' => 'required',
            'name' => 'required|string|max:190',
            'desc' => 'required|string|max:190',
            'currencies' => 'required|array|min:1',
            'status' => 'nullable',
            'min_amount' => 'nullable|numeric|min:0',
            'config' => 'array',
            'config.api.client_id' => 'required|string',
            'config.api.client_secret' => 'required|string',
            'config.api.account' => 'required',
            'config.api.sandbox' => 'required',
            'config.meta.min' => 'nullable|numeric|min:0'
        ],[
            'slug.required' => __('Sorry, your payment method is invalid.'),
            'name.required' => __('Payment method title is required.'),
            'desc.required' => __('Payment method short description is required.'),
            'config.api.client_id.required' => __('API Client ID is required to connect PayPal.'),
            'config.api.client_secret.required' => __('API Client Secret is required to connect PayPal.'),
            'config.api.sandbox.required' => __('Please specify the sandbox status of PayPal account.'),
            'config.api.account.required' => __('Please specify the name of account for reference.'),
            'currencies.*' => __('Select at-least one currency from supported currencies.'),
            'config.meta.min.numeric' => __('The fixed minimum amount must be numeric.'),
            'config.meta.min.min' => __('The fixed minimum amount must be at least 0.'),
        ]);

        $input['min_amount'] = abs($input['min_amount']);
        $input['fees'] = array('flat' => 0, 'percent' => 0);
        $input['countries'] = array();
        $input['status'] = ($input['status'] == 'active') ? PaymentMethodStatus::ACTIVE : PaymentMethodStatus::INACTIVE;
        $input['config']['api'] = array_map('strip_tags_map', $input['config']['api']);
        $input['config']['meta'] = array_map('strip_tags_map', $input['config']['meta']);

        PaymentMethod::updateOrCreate(['slug' => $input['slug']], array_map('strip_tags_map', $input));

        return response()->json([ 'msg' => __('Payment method successfully updated.') ]);
    }
}
