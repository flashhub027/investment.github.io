<?php


namespace App\Models;

use App\Enums\InterestPeriod;
use App\Enums\InterestRateType;
use App\Enums\InvestmentStatus;
use App\Enums\SchemeStatus;
use App\Enums\SchemeTermTypes;
use App\Services\InvestormService;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IvScheme extends Model
{
    protected $fillable = [
        "name",
        "slug",
        "short",
        "desc",
        "amount",
        "maximum",
        "is_fixed",
        "term",
        "term_type",
        "rate",
        "rate_type",
        "calc_period",
        "days_only",
        "capital",
        "payout",
        "status",
        "featured",
        "is_locked"
    ];

    const NEXT_STATUSES = [
        SchemeStatus::ACTIVE => [
            SchemeStatus::INACTIVE,
            SchemeStatus::ARCHIVED,
            SchemeStatus::DELETE,
        ],
        SchemeStatus::INACTIVE => [
            SchemeStatus::ACTIVE,
            SchemeStatus::ARCHIVED,
            SchemeStatus::DELETE,
        ],
        SchemeStatus::ARCHIVED => [
            SchemeStatus::ACTIVE,
            SchemeStatus::INACTIVE,
            SchemeStatus::DELETE,
        ]
    ];

    protected static function booted()
    {
        static::addGlobalScope('exceptArchived', function (Builder $builder) {
            $builder->where('status', '<>', SchemeStatus::ARCHIVED);
        });
    }

    public function metas()
    {
        // IO: Will use for future update.
        return $this->hasMany(IvSchemeMeta::class, 'scheme_id');
    }

    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case SchemeStatus::ACTIVE:
                return 'badge-success';
                break;
            case SchemeStatus::INACTIVE:
                return 'badge-danger';
                break;
            case SchemeStatus::ARCHIVED:
                return 'badge-default';
                break;
        }
    }

    public function plans()
    {
        return $this->hasMany(IvInvest::class, 'scheme_id');
    }

    public function getIsRestrictedAttribute()
    {
        return $this->is_locked ?? $this->plans()->where('status', InvestmentStatus::PENDING)->count() > 0;
    }

    public function getPlanNameAttribute()
    {
        $name = $this->name;
        $fixed = ($this->is_fixed) ? ' '.__('(Fixed Invest)') : '';
        return __(':Plan_name', ['plan_name' => $name.$fixed]);
    }

    public function getCodeAttribute()
    {
        return strtoupper(substr($this->short, 0, 2));
    }

    public function getUidCodeAttribute()
    {
        return 'IV'.str_pad($this->id, 3, '0', STR_PAD_LEFT).'S'.$this->code;
    }

    public function getRateTextAttribute()
    {
        $type = ($this->rate_type == InterestRateType::PERCENT) ? '%' : ' '.base_currency();

        return sprintf("%s%s", $this->rate, $type);
    }

    public function getRateTextAlterAttribute()
    {
        $type = ($this->rate_type == InterestRateType::PERCENT) ? '%' : ' '.base_currency() .' '. __("(Fixed)");

        return sprintf("%s%s", $this->rate, $type);
    }

    public function getTermTextAttribute()
    {
        return sprintf("%s %s", $this->term, ucfirst($this->term_type));
    }

    public function getCalcDetailsAttribute()
    {
        return sprintf(
            '%s %s%s for %d %s',
            ucfirst(data_get($this, 'calc_period')),
            data_get($this, 'rate'),
            data_get($this, 'rate_type') == InterestRateType::PERCENT ? '%' : ' '.base_currency(),
            data_get($this, 'term'),
            ucfirst(data_get($this, 'term_type'))
        );
    }

    public function getTotalReturnAttribute()
    {
        $calcUnit = InvestormService::TERM_CONVERSION[data_get($this, 'term_type')][data_get($this, 'calc_period')];
        $rate = BigDecimal::of(data_get($this, 'rate'));
        $amount = BigDecimal::of(data_get($this, 'amount'));
        $term = data_get($this, 'term');
        $profit = $rate->multipliedBy($calcUnit * $term);
        $scale = is_crypto(base_currency()) ? dp_calc('crypto') : dp_calc('fiat');
    
        if(data_get($this, 'rate_type') === InterestRateType::FIXED){
            if(data_get($this, 'is_fixed') == 1){
                if(data_get($this, 'capital') == 1){
                    return $amount->plus($profit)->dividedBy($amount, $scale, RoundingMode::CEILING)->multipliedBy(100);
                }
                return $profit->dividedBy($amount, $scale, RoundingMode::CEILING)->multipliedBy(100);
            }
            $minProfit = $maxProfit = $profit;

            if(data_get($this, 'captial') == 1){
                $maxProfit = BigDecimal::of($amount)->plus($profit);
                $minProfit = BigDecimal::of(data_get($this, 'maximum'))->plus($profit);
            }

            return $minProfit->dividedBy(data_get($this, 'maximum'), $scale, RoundingMode::CEILING)->multipliedBy(100).'% - '.
                    $maxProfit->dividedBy($amount, $scale, RoundingMode::CEILING)->multipliedBy(100);
        } else {
            return data_get($this, 'capital') == 1 ? $profit->plus(100) : $profit; 
        }
    }
}
