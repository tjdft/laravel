<?php

namespace TJDFT\Laravel\Traits;

trait CleanStringsBeforeValidation
{
    protected function prepareForValidation($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            $value = $value === '' ? null : $value;

            $attributes[$key] = $value;
        }

        return $attributes;
    }
}
