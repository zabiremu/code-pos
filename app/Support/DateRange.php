<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** A report period from ?range=this_month (or ?from=…&to=… for custom). */
class DateRange
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'custom' => 'Custom',
    ];

    public function __construct(public Carbon $from, public Carbon $to, public string $preset) {}

    public static function fromRequest(Request $request, string $default = 'this_month'): self
    {
        $preset = array_key_exists((string) $request->query('range'), self::PRESETS) ? $request->query('range') : $default;

        if ($preset === 'custom' || ($request->filled('from') && ! $request->filled('range'))) {
            try {
                $from = Carbon::parse($request->query('from', today()->toDateString()))->startOfDay();
                $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();
            } catch (\Throwable) {
                $from = today()->startOfDay();
                $to = today()->endOfDay();
            }
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return new self($from, $to, 'custom');
        }

        [$from, $to] = match ($preset) {
            'today' => [today(), today()],
            'yesterday' => [today()->subDay(), today()->subDay()],
            'this_week' => [today()->startOfWeek(), today()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [today()->startOfYear(), today()],
            default => [today()->startOfMonth(), today()],
        };

        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay(), $preset);
    }

    public function label(): string
    {
        return $this->from->isSameDay($this->to)
            ? $this->from->format('d M Y')
            : $this->from->format('d M Y').' – '.$this->to->format('d M Y');
    }

    /** Query-string params that reproduce this range (for links like pagination and exports). */
    public function query(): array
    {
        return $this->preset === 'custom'
            ? ['range' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];
    }

    /** @return array{0: Carbon, 1: Carbon} for whereBetween on datetime columns */
    public function between(): array
    {
        return [$this->from, $this->to];
    }

    /** @return array{0: string, 1: string} for whereBetween on date columns */
    public function betweenDates(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }
}
