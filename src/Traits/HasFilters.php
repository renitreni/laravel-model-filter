<?php

namespace Lacodix\LaravelModelFilter\Traits;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\Filter;

trait HasFilters
{
    /** @var array<Collection> $filterInstances  */
    protected array $filterInstances = [];

    public function scopeFilter(Builder $query, array $values, string $group = '__default'): Builder
    {
        $values = $this->getOnlyFilterUsableValues($values, $group);

        $this->filters($group)
            ->filter(
                static fn (Filter $filter)
                    => $values->has($filter->getQueryName()) && $filter->applicable()
            )
            ->each(
                static fn (Filter $filter) => $filter
                    ->values($values->get($filter->getQueryName()))
                    ->when(
                        $filter->validationMode === ValidationMode::THROW,
                        fn (Filter $filter) => $filter->validate()
                    )
                    ->when(
                        ! $filter->fails(),
                        fn (Filter $filter) => $filter->apply($query)
                    )
            );

        return $query;
    }

    public function scopeFilterByQueryString(Builder $query, string $group = '__default'): Builder
    {
        $request = Container::getInstance()->make(Request::class);

        return $this->scopeFilter($query, $request->all(), $group);
    }

    public function filters(string $group = '__default'): Collection
    {
        return $this->filterInstances[$group] ??= $this->getGroupedFilters($group)->map(
            static fn ($filterOrName) => $filterOrName instanceof Filter ? $filterOrName : new $filterOrName()
        );
    }

    protected function getGroupedFilters($group): Collection
    {
        if (! Arr::isAssoc($this->filters)) {
            $this->filters = ['__default' => $this->filters];
        }

        return collect($this->filters[$group] ?? []);
    }

    protected function getAllFilterQueryNames(string $group)
    {
        return $this
            ->filters($group)
            ->map(static fn (Filter $filter) => $filter->getQueryName())->values()->all();
    }

    protected function getOnlyFilterUsableValues(array $values, string $group): Collection
    {
        return collect($values)
            ->only($this->getAllFilterQueryNames($group))
            ->filter();
    }
}
