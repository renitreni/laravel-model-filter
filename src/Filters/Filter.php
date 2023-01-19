<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Exceptions\FilterValueException;

abstract class Filter
{
    protected MessageBag $errorBag;

    protected FilterMode $mode = FilterMode::EQUAL;

    protected array $options;

    public function queryName(string|int $key): string
    {
        return is_int($key) ? Str::snake(class_basename(static::class)) : $key;
    }

    abstract public function apply(Builder $query, string|array $values): Builder;

    public function options(): array
    {
        return $this->options ?? [];
    }

    public function rules(): array
    {
        return [];
    }

    protected function validate($data, $rules = null, $messages = [], $attributes = []): array
    {
        [$rules, $messages, $attributes] = $this->getValidationData($rules, $messages, $attributes);

        $validator = Validator::make($data, $rules, $messages, $attributes);
        $validatedData = $validator->validate();

        $this->errorBag = new MessageBag;

        return $validatedData;
    }

    protected function getValidationData($rules, $messages, $attributes): array
    {
        $rules = is_null($rules) ? $this->rules() : $rules;
        $messages = empty($messages) ? $this->getMessages() : $messages;
        $attributes = empty($attributes) ? $this->getValidationAttributes() : $attributes;

        return [$rules, $messages, $attributes];
    }

    protected function getMessages()
    {
        return match (true) {
            method_exists($this, 'messages') => $this->messages(),
            property_exists($this, 'messages') => $this->messages,
            default => [],
        };
    }

    protected function getValidationAttributes()
    {
        return match (true) {
            method_exists($this, 'validationAttributes') => $this->validationAttributes(),
            property_exists($this, 'validationAttributes') => $this->validationAttributes,
            default => [],
        };
    }
}
