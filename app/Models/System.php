<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class System extends Model
{
    use SoftDeletes;

    public const STACKS = [
        'laravel' => 'Laravel',
        'php' => 'PHP',
        'dotnet' => '.NET',
        'node' => 'Node.js',
        'python' => 'Python',
        'flutter' => 'Flutter',
        'other' => 'Other',
    ];

    public const CATEGORY_LABELS = [
        'hosting' => 'Hosting',
        'domain' => 'Domain',
        'setup_fee' => 'Setup Fee',
        'development' => 'Development',
        'design' => 'Design',
        'other' => 'Other',
    ];

    public const ENVIRONMENTS = [
        'development' => 'Development',
        'staging' => 'Staging',
        'production' => 'Production',
    ];

    public const STATUSES = [
        'planning' => 'Planning',
        'active' => 'Active',
        'maintenance' => 'Maintenance',
        'archived' => 'Archived',
    ];

    public const CURRENCIES = ['USD', 'KHR'];

    public const MAINTENANCE_CYCLES = [
        'monthly' => 'Monthly',
        'semester' => 'Semester (6 months)',
        'yearly' => 'Yearly',
        'one_time' => 'One Time',
    ];

    public const MAINTENANCE_INTERVAL_MONTHS = [
        'monthly' => 1,
        'semester' => 6,
        'yearly' => 12,
        'one_time' => null,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stack',
        'folder_path',
        'repo_url',
        'deploy_url',
        'environment',
        'status',
        'icon',
        'notes',
        'owner_id',
        'client_name',
        'client_phone',
        'client_telegram',
        'client_email',
        'client_address',
        'price',
        'price_currency',
        'paid_amount',
        'maintenance_amount',
        'maintenance_cycle',
        'maintenance_starts_at',
        'payment_notes',
    ];

    protected $attributes = [
        'price_currency' => 'USD',
        'paid_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'maintenance_amount' => 'decimal:2',
            'maintenance_starts_at' => 'date',
            'maintenance_due_date' => 'date',
            'maintenance_collected_at' => 'datetime',
        ];
    }

    public function getMaintenanceStatusAttribute(): string
    {
        if ($this->maintenance_collected_at) {
            return 'paid';
        }

        if ($this->maintenance_due_date && $this->maintenance_due_date->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions()
    {
        return $this->hasMany(Version::class)->orderByDesc('release_date')->orderByDesc('id');
    }

    public function builds()
    {
        return $this->hasMany(Build::class)->latest('created_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function operationLogs()
    {
        return $this->hasMany(OperationLog::class)->orderByDesc('performed_at');
    }

    public function financeTransactions()
    {
        return $this->hasMany(FinanceTransaction::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }

    public function paymentMilestones()
    {
        return $this->hasMany(PaymentMilestone::class)->orderBy('sort_order')->orderBy('id');
    }

    public function maintenancePayments()
    {
        return $this->hasMany(MaintenancePayment::class)->orderBy('due_date');
    }

    public function hasMaintenancePlan(): bool
    {
        return $this->maintenance_starts_at !== null
            && $this->maintenance_amount !== null
            && (float) $this->maintenance_amount > 0;
    }

    public function maintenanceIntervalMonths(): ?int
    {
        return self::MAINTENANCE_INTERVAL_MONTHS[$this->maintenance_cycle] ?? 1;
    }

    /**
     * All maintenance due dates from the start date through the given date.
     */
    public function maintenancePeriods(?CarbonInterface $through = null): Collection
    {
        if (! $this->hasMaintenancePlan()) {
            return collect();
        }

        $start = $this->maintenance_starts_at->copy()->startOfDay();
        $through = $through ? Carbon::parse($through)->startOfDay() : Carbon::today();

        if ($start->gt($through)) {
            return collect();
        }

        $months = $this->maintenanceIntervalMonths();

        if ($months === null) {
            return collect([$start]);
        }

        $periods = collect();

        for ($i = 0; $i < 240; $i++) {
            $date = $start->copy()->addMonthsNoOverflow($months * $i);

            if ($date->gt($through)) {
                break;
            }

            $periods->push($date);
        }

        return $periods;
    }

    public function paidMaintenanceDates(): array
    {
        return $this->maintenancePayments()
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    /**
     * Due dates that have arrived (<= today) and are not paid yet.
     */
    public function maintenanceDuePeriods(?CarbonInterface $through = null): Collection
    {
        $paid = $this->paidMaintenanceDates();

        return $this->maintenancePeriods($through)
            ->reject(fn (Carbon $date) => in_array($date->toDateString(), $paid, true))
            ->values();
    }

    /**
     * The first unpaid due date, even if it is in the future.
     */
    public function nextUnpaidMaintenanceDate(): ?Carbon
    {
        if (! $this->hasMaintenancePlan()) {
            return null;
        }

        $start = $this->maintenance_starts_at->copy()->startOfDay();
        $months = $this->maintenanceIntervalMonths();
        $paid = $this->paidMaintenanceDates();

        for ($i = 0; $i < 240; $i++) {
            $date = $months === null
                ? $start->copy()
                : $start->copy()->addMonthsNoOverflow($months * $i);

            if (! in_array($date->toDateString(), $paid, true)) {
                return $date;
            }

            if ($months === null) {
                return null;
            }
        }

        return null;
    }

    public function latestVersion()
    {
        return $this->hasOne(Version::class)->orderByDesc('release_date')->orderByDesc('id');
    }

    public function stackLabel(): string
    {
        return self::STACKS[$this->stack] ?? 'Other';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Active';
    }

    public function environmentLabel(): string
    {
        return self::ENVIRONMENTS[$this->environment] ?? 'Development';
    }

    public function maintenanceCycleLabel(): string
    {
        return self::MAINTENANCE_CYCLES[$this->maintenance_cycle] ?? ucfirst((string) $this->maintenance_cycle);
    }

    public static function formatMoney(float $amount, string $currency = 'USD'): string
    {
        $formatted = number_format($amount, $currency === 'KHR' ? 0 : 2);

        return match ($currency) {
            'KHR' => '៛'.$formatted,
            'USD' => '$'.$formatted,
            default => $currency.' '.$formatted,
        };
    }

    public function formatPrice(): string
    {
        return $this->price === null ? '—' : self::formatMoney((float) $this->price, $this->price_currency);
    }

    public function balanceDue(): ?float
    {
        if ($this->price === null) {
            return null;
        }

        return max(0, (float) $this->price - (float) $this->paid_amount);
    }

    public function isFullyPaid(): bool
    {
        return $this->balanceDue() !== null && $this->balanceDue() <= 0;
    }
}
