<?php

namespace NioModules\WdBank\Controllers;

use NioModules\WdBank\WdBankModule;
use App\Enums\WithdrawMethodStatus;
use App\Models\WithdrawMethod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BankSettingsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function settingsView()
    {
        $config = config('modules.'.WdBankModule::SLUG);
        $currencies = array_filter(config('currencies'), function ($key) use ($config) {
            return in_array($key, data_get($config, 'supported_currency'));
        }, ARRAY_FILTER_USE_KEY);
        $settings = WithdrawMethod::where('slug', WdBankModule::SLUG)->first();
        return view("WdBank::settings", compact('config', 'settings','currencies'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @version 1.0.0
     * @since 1.0
     */
    public function saveBankSettings(Request $request)
    {
        if(empty($request->slug) || $request->slug !== WdBankModule::SLUG) {
            return response()->json([ 'type' => 'error', 'msg' => __('Sorry, something wrong with withdraw method.') ]);
        }

        $input = $request->validate([
            'slug' => 'required',
            'name' => 'required|string|max:190',
            'desc' => 'required|string|max:190',
            'currencies' => 'required|array|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'form-fields' => 'required|array',
            'status' => 'nullable',
            'config' => 'array',
            'config.country_restriction_type' => 'required|string|in:include,exclude,disable,global',
            'countries' => 'array',
            'config.meta.min' => 'nullable|numeric|min:0'
        ], [
            'slug.required' => __('Sorry, your withdraw method is invalid.'),
            'name.required' => __('Withdraw method title is required.'),
            'desc.required' => __('Withdraw method short description is required.'),
            'currencies.*' => __('Select at-least one currency from supported currencies.'),
            'bank-fields' => __('Invalid display form field value.'),
            'config.meta.min.numeric' => __('The fixed minimum amount must be numeric.'),
            'config.meta.min.min' => __('The fixed minimum amount must be at least 0.'),
        ]);
        
        if (empty($input['countries'])) {
            $input['countries'] = [];
        }

        Cache::forget($input['slug'] . '_filtered_countries');

        $input['min_amount'] = abs($input['min_amount']);
        $input['fees'] = array('flat' => 0, 'percent' => 0);

        if(isset($input['config']['meta']['min']) && $input['config']['meta']['min']) {
            $input['config']['meta']['min'] = abs($input['config']['meta']['min']);
        }
        if(isset($input['config']['meta']['currency']) && $input['config']['meta']['currency']) {
            $input['currencies'] = array_unique(array_merge($input['currencies'], [$input['config']['meta']['currency']]));
        }
        $input['config']['form'] = $input['form-fields'];
        $input['status'] = ($input['status'] == 'active') ? WithdrawMethodStatus::ACTIVE : WithdrawMethodStatus::INACTIVE;
        $input['config']['meta'] = array_map('strip_tags_map', $input['config']['meta']);

        WithdrawMethod::updateOrCreate(['slug' => $input['slug']], array_map('strip_tags_map', $input));

        return response()->json([ 'msg' => __('Withdraw method successfully updated.') ]);
    }

}
