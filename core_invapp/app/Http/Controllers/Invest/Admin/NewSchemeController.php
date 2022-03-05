<?php

namespace App\Http\Controllers\Invest\Admin;

use App\Enums\Boolean;
use App\Enums\SchemeStatus;
use App\Enums\InterestRateType;

use App\Models\IvScheme;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class NewSchemeController extends Controller
{

    public function saveScheme(Request $request)
    {
        $request->validate([
            "name" => 'required|string',
            "short" => 'required|string',
            "desc" => 'nullable|string',
            "amount" => 'required|numeric|gte:0.01',
            "maximum" => 'nullable|numeric',
            "term" => 'required|integer|not_in:0',
            "rate" => 'required|numeric|not_in:0',
            "duration" => 'required|string',
            "types" => 'required|string',
            "period" => 'required|string',
            "payout" => "required|string"
        ], [
            "amount.numeric" => __("The investment amount should be valid number."),
            "maximum.numeric" => __("The maximum amount should be valid number."),
            "rate.numeric" => __("Enter a valid amount of interest rate."),
            "term.integer" => __("Term duration should be valid number."),
            "term.not_in" => __("Term duration should be not be zero."),
            "rate.not_in" => __("Interest rate should be not be zero."),
        ]);

        if($this->existNameSlug($request->get('name'))==true) {
            throw ValidationException::withMessages([ 'name' => __('The investment scheme (:name) already exist. Please try with different name.', ['name' => $request->get('name')]) ]);
        }

        if( !($request->get('fixed')) && $request->get('maximum') > 0 && $request->get('amount') >= $request->get('maximum') ) {
            throw ValidationException::withMessages(['maximum' => __('The maximum amount should be zero or more than minimum amount of investment.')]);
        }

        $data = [
            "name" => $request->get("name"),
            "slug" => Str::slug($request->get("name")),
            "short" => $request->get('short'),
            "desc" => $request->get('desc'),
            "amount" => $request->get('amount'),
            "maximum" => $request->get('maximum'),
            "is_fixed" => $request->get('fixed') ? Boolean::YES : Boolean::NO,
            "term" => $request->get("term"),
            "term_type" => $request->get("duration"),
            "rate" => $request->get("rate"),
            "rate_type" => $request->get("types"),
            "calc_period" => $request->get("period"),
            "days_only" => $request->get("daysonly") ? Boolean::YES : Boolean::NO,
            "capital" => $request->get("capital") ? Boolean::YES : Boolean::NO,
            "payout" => $request->get("payout"),
            "featured" => $request->get('featured') ? Boolean::YES : Boolean::NO,
            "status" => $request->get('status') ? SchemeStatus::ACTIVE : SchemeStatus::INACTIVE
        ];

        try {
            $ivScheme = new IvScheme();
            $ivScheme->fill($data);
            $ivScheme->save();

            return response()->json([ 'title' => 'Scheme Added', 'msg' => __('The new investment scheme has been added.'), 'reload' => true ]);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['failed' => __('Unable to add new investment scheme, please try again.')]);
        }
    }

    private function existNameSlug($name, $old=null) {
        $slug = Str::slug($name);
        $scheme = IvScheme::where('slug', $slug)->first();

        if ($slug==$old || blank($scheme)) return false;

        return true;
    }
}
