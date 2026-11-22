<?php

namespace App\Services\Royalties;

use App\Models\Release;
use App\Models\RoyaltyLine;
use App\Models\Track;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The usage reported for a track or a product: how often, where, on which
 * service, on which subscription and when - all of it narrowed by the same
 * filters, so every figure on the page tells about the same slice.
 */
class RoyaltyUsage
{
    public const FILTERS = ['year', 'country', 'portal', 'stream_type', 'kind'];

    /** Streams, downloads and videos, as the distributor reports them */
    public const KINDS = ['stream' => 'Streams', 'download' => 'Downloads', 'video' => 'Videos'];

    public function __construct(private Track|Release $subject, private array $filters = [])
    {
        $this->filters = array_filter(array_intersect_key($filters, array_flip(self::FILTERS)), fn ($v) => $v !== null && $v !== '');
    }

    public function subject(): Track|Release
    {
        return $this->subject;
    }

    public function filters(): array
    {
        return $this->filters;
    }

    /** All usage of the track, or of the product (its tracks and its album downloads) */
    public function base(): Builder
    {
        if ($this->subject instanceof Track) {
            return RoyaltyLine::where('track_id', $this->subject->id);
        }

        $trackIds = $this->subject->tracks()->pluck('tracks.id');

        return RoyaltyLine::where(fn ($q) => $q->where('release_id', $this->subject->id)->orWhereIn('track_id', $trackIds));
    }

    public function query(): Builder
    {
        $query = $this->base();
        $f = $this->filters;

        if (!empty($f['year'])) {
            $query->whereBetween('period', [((int) $f['year']) . '-01-01', ((int) $f['year']) . '-12-31 23:59:59']);
        }
        foreach (['country', 'portal', 'stream_type'] as $column) {
            if (!empty($f[$column])) {
                $f[$column] === '—' ? $query->whereNull($column) : $query->where($column, $f[$column]);
            }
        }
        if (!empty($f['kind'])) {
            match ($f['kind']) {
                'video' => $query->where('sales_category', 'Video'),
                'download' => $query->where('sales_type', 'Download'),
                default => $query->where('sales_type', 'Stream')->where(fn ($q) => $q->whereNull('sales_category')->orWhere('sales_category', '!=', 'Video')),
            };
        }

        return $query;
    }

    /** @return array{stream: int, download: int, video: int, amount: float, lines: int} */
    public function totals(): array
    {
        $rows = $this->query()->selectRaw('sales_type, sales_category, sum(quantity) as quantity, sum(amount) as amount, count(*) as n')
            ->groupBy('sales_type', 'sales_category')->toBase()->get();

        $totals = ['stream' => 0, 'download' => 0, 'video' => 0, 'amount' => 0.0, 'lines' => 0];
        foreach ($rows as $row) {
            $totals[self::kind($row->sales_type, $row->sales_category)] += (int) $row->quantity;
            $totals['amount'] += (float) $row->amount;
            $totals['lines'] += (int) $row->n;
        }

        return $totals;
    }

    /** Per month: usage and amount, without gaps between the first and the last month */
    public function months(): Collection
    {
        $rows = $this->query()->selectRaw('period, sum(quantity) as quantity, sum(amount) as amount')
            ->groupBy('period')->orderBy('period')->toBase()->get()
            ->keyBy(fn ($row) => substr((string) $row->period, 0, 7));
        if ($rows->isEmpty()) return collect();

        $months = collect();
        $month = \Illuminate\Support\Carbon::parse($rows->keys()->first() . '-01');
        $last = \Illuminate\Support\Carbon::parse($rows->keys()->last() . '-01');
        while ($month->lte($last)) {
            $row = $rows->get($month->format('Y-m'));
            $months->push((object) ['month' => $month->copy(), 'quantity' => (int) ($row->quantity ?? 0), 'amount' => (float) ($row->amount ?? 0)]);
            $month->addMonth();
        }

        return $months;
    }

    /**
     * Ranked by usage: the first ones, the rest folded into "Übrige".
     *
     * @return Collection<int, object{name: string, value: ?string, quantity: int, amount: float}>
     */
    public function ranking(string $column, int $limit = 10): Collection
    {
        $rows = $this->query()->selectRaw("{$column} as name, sum(quantity) as quantity, sum(amount) as amount")
            ->groupBy($column)->orderByDesc('quantity')->toBase()->get()
            ->map(fn ($row) => (object) ['name' => self::label($column, $row->name), 'value' => $row->name ?? '—',
                'quantity' => (int) $row->quantity, 'amount' => (float) $row->amount]);

        if ($rows->count() <= $limit + 1) return $rows;

        $rest = $rows->slice($limit);

        return $rows->take($limit)->push((object) ['name' => 'Übrige (' . $rest->count() . ')', 'value' => null,
            'quantity' => $rest->sum('quantity'), 'amount' => $rest->sum('amount')]);
    }

    /** What the filters offer: the values of the whole usage, not only of the current slice */
    public function options(): array
    {
        $base = fn () => $this->base();
        $distinct = fn (string $column) => $base()->select($column)->distinct()->pluck($column)
            ->map(fn ($v) => $v ?? '—')->sortBy(fn ($v) => self::label($column, $v === '—' ? null : $v))
            ->mapWithKeys(fn ($v) => [$v => self::label($column, $v === '—' ? null : $v)])->all();

        return [
            'year' => $base()->select('period')->distinct()->pluck('period')->map(fn ($p) => substr((string) $p, 0, 4))->unique()->sortDesc()->values()->all(),
            'country' => $distinct('country'),
            'portal' => $distinct('portal'),
            'stream_type' => $distinct('stream_type'),
        ];
    }

    public static function kind(?string $salesType, ?string $category): string
    {
        if ($category === 'Video') return 'video';

        return $salesType === 'Download' ? 'download' : 'stream';
    }

    /** "SWITZERLAND" reads "Switzerland"; nothing given reads "ohne Angabe" */
    public static function label(string $column, ?string $value): string
    {
        if ($value === null || trim($value) === '') return 'ohne Angabe';

        return $column === 'country' ? mb_convert_case(mb_strtolower($value), MB_CASE_TITLE) : $value;
    }
}
