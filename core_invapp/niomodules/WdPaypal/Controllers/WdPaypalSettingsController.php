<?php

namespace NioModules\WdPaypal\Controllers;

use NioModules\WdPaypal\WdPaypalModule;
use App\Enums\WithdrawMethodStatus;
use App\Models\WithdrawMethod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WdPaypalSettingsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @version 1.0.0
     * @since 1.0
     */
    public function settingsView()
    {
        $config = config('modules.'.WdPaypalModule::SLUG);
        $currencies = array_filter(config('currencies'), function ($key) use ($config) {
            return in_array($key, data_get($config, 'supported_currency'));
        }, ARRAY_FILTER_USE_KEY);
        $settings = WithdrawMethod::where('slug', WdPaypalModule::SLUG)->first();
        return view("WdPaypal::settings", compact('config', 'settings', 'currencies'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @version 1.0.0
     * @since 1.0
     */
    public function savePaypalSettings(Request $request)
    {
        if(empty($request->slug) || $request->slug !== WdPaypalModule::SLUG) {
            return response()->json([ 'type' => 'error', 'msg' => __('Sorry, something wrong with withdraw method.') ]);
        }

        $input = $request->validate([
            'slug' => 'required',
            'name' => 'required|string|max:190',
            'desc' => 'required|string|max:190',
            'currencies' => 'required|array|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable',
            'config' => 'array',
            'config.meta.min' => 'nullable|numeric|min:0',
        ], [
            'slug.required' => __('Sorry, your withdraw method is invalid.'),
            'name.required' => __('Withdraw method title is required.'),
            'desc.required' => __('Withdraw method short description is required.'),
            'currencies.*' => __('Select at-least one currency from supported currencies.'),
            'config.meta.min.numeric' => __('The fixed minimum amount must be numeric.'),
            'config.meta.min.min' => __('The fixed minimum amount must be at least 0.'),
        ]);

        $input['min_amount'] = abs($input['min_amount']);
        $input['fees'] = array('flat' => 0, 'percent' => 0);
        $input['countries'] = array();

        if(isset($input['config']['meta']['min']) && $input['config']['meta']['min']) {
            $input['config']['meta']['min'] = abs($input['config']['meta']['min']);
        }
        if(isset($input['config']['meta']['currency']) && $input['config']['meta']['currency']) {
            $input['currencies'] = array_unique(array_merge($input['currencies'], [$input['config']['meta']['currency']]));
        }

        $input['config']['api'] = array();
        $input['status'] = ($input['status'] == 'active') ? WithdrawMethodStatus::ACTIVE : WithdrawMethodStatus::INACTIVE;
        $input['config']['meta'] = array_map('strip_tags_map', $input['config']['meta']);
        
        WithdrawMethod::updateOrCreate(['slug' => $input['slug']], array_map('strip_tags_map', $input));

        return response()->json([ 'msg' => __('Withdraw method successfully updated.') ]);
    }

}
