<?php

namespace App\Http\Controllers\Invest\Admin;

use App\Enums\Boolean;
use App\Enums\InvestmentStatus;
use App\Enums\SchemeStatus;
use App\Models\IvScheme;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\InvestormService;

class InvestmentSchemeController extends Controller
{
    public function schemeList(Request $request, $status = null)
    {
        $schemes = IvScheme::query();

        if ($status) {
            $schemes->withoutGlobalScope('exceptArchived')->where('status', $status);
        } else {
            $schemes->withoutGlobalScope('exceptArchived')->where('status', SchemeStatus::ACTIVE);
        }

        $schemes = $schemes->orderBy('id', 'asc')->get();
        if ($request->isXmlHttpRequest()) {
            return view("investment.admin.schemes.cards", compact('schemes', 'status'))->render();
        }

        return view("investment.admin.schemes.list", compact('schemes', 'status'));
    }

    public function actionScheme(Request $request, $action=null)
    {
        $type = (!empty($action)) ? $action : $request->get('view');

        if(!in_array($type, ['new', 'edit'])) {
            throw ValidationException::withMessages(['action' => __("Sorry, we are unable to proceed your action.")]);
        }

        if ($type=='new' && !has_route('admin.investment.scheme.save')) {
            throw ValidationException::withMessages(['extended' => __("Sorry, the adding feature is not avilable. It may avialble in extended / pro version.")]);
        }

        $scheme = compact([]);
        $schmeID = ($request->get('uid')) ? $request->get('uid') : $request->get('id');

        if ($schmeID) {
            $scheme = IvScheme::find(get_hash($schmeID));
            if (blank($scheme)) {
                throw ValidationException::withMessages(['id' => __("The scheme id invalid or may not avialble.")]);
            }
        }

        return view("investment.admin.schemes.form", compact('scheme', 'type'));
    }

    public function updateSchemeStatus(Request $request)
    {
        $input = $request->validate([
            'uid' => 'required',
            'action' => 'required',
        ]);

        $ivScheme = IvScheme::withoutGlobalScope('exceptArchived')->find(get_hash(Arr::get($input, 'uid')));

        if (blank($ivScheme)) {
            throw ValidationException::withMessages(["id" => __("The investment scheme id is invalid or not found.")]);
        }

        $allowedStatuses = IvScheme::NEXT_STATUSES[$ivScheme->status] ?? [];

        $status = Arr::get($input, 'action');
        if (!in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages(['status' => __("Scheme status cannot be updated to :state", ["state" => $status]) ]);
        }

        $ivlistStatus =  session()->get('ivlistStatus');

        if ($request->action === 'delete') {
            //if ($ivScheme->is_restricted) throw ValidationException::withMessages(['status' => __("The scheme already has a investment.")]);
            //$ivScheme->delete();
        } else {
            $ivScheme->status = $status;
            $ivScheme->save();
        }

        return response()->json([
            'type' => 'success',
            'title' => __("Status Updated"), 
            'msg' => __('The investment scheme (:name) status updated to :state', ['name' => $ivScheme->name, "state" => $status]),
            'embed' => $this->schemeList($request, $ivlistStatus)
        ]);
    }

    public function updateScheme(Request $request, $id=null)
    {
        $schemeID = (!empty($id)) ? get_hash($id) : get_hash($request->get('uid'));

        if($schemeID != $request->get('id')) {
            throw ValidationException::withMessages([ 'invalid' => __('The investment scheme id is invalid or not found.') ]);
        }

        $scheme = IvScheme::find($schemeID);

        if (blank($scheme)) {
            throw ValidationException::withMessages(['failed' => __('Unable to find the scheme, please try again.')]);
        }

        $slug = $scheme->slug ??  '';

        if ($scheme->is_restricted) {

            $rules = [
                'desc' => 'nullable|string',
            ];

            if ($scheme->is_fixed === Boolean::NO) {
                Arr::add($rules, 'maximum', 'nullable|numeric');
                Arr::add($rules, 'amount', 'nullable|numeric');
            }

            $request->validate($rules, [
                "amount.numeric" => __("The investment amount should be valid number."),
                "maximum.numeric" => __("The maximum amount should be valid number."),
            ]);
        } else {
            $request->validate([
                "name" => 'required|string',
                "short" => 'required|string',
                "desc" => 'nullable|string',
                "amount" => 'required|numeric|gte:0.001',
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
        }

        if($this->existNameSlug($request->get('name'), $slug)==true) {
            throw ValidationException::withMessages([ 'name' => __('The investment scheme (:name) already exist. Please try with different name.', ['name' => $request->get('name')]) ]);
        }

        if( !($request->get('fixed')) && $request->get('maximum') > 0 && $request->get('amount') >= $request->get('maximum') ) {
            throw ValidationException::withMessages(['maximum' => __('The maximum amount should be zero or more than minimum amount of investment.')]);
        }

        if ($request->has('period') && !array_key_exists($request->get("period"), InvestormService::TERM_CONVERSION[$request->get('duration')])) {
            throw ValidationException::withMessages(['period' => __('Interest period is not valid for term duration.')]);
        }

        $data = [
            "name" => strip_tags($request->get("name")),
            "slug" => Str::slug(strip_tags($request->get("name"))),
            "short" => strip_tags($request->get('short')),
            "desc" => strip_tags($request->get('desc')),
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

        if ($scheme->is_restricted) {
            $required = ['desc', 'featured', 'status'];
            if ($scheme->is_fixed === Boolean::NO) $required = array_merge($required, ['amount', 'maximum']);
            $data = Arr::only($data, $required);
        }

        $scheme->fill($data);
        $scheme->save();

        return response()->json([
            'msg' => __('The investment scheme has been updated.'), 'title' => 'Scheme Updated', 'title' => 'Scheme Updated',
            'title' => 'Scheme Updated', 'modal' => 'hide',
            'embed' => $this->schemeList($request, $request->route('status')),
        ]);
    }

    private function existNameSlug($name, $old=null) {
        $slug = Str::slug($name);
        $scheme = IvScheme::where('slug', $slug)->first();

        if ($slug==$old || blank($scheme)) return false;

        return true;
    }
}
