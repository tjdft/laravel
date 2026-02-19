<?php

namespace TJDFT\Laravel\Traits;

trait HasSpinnerPlaceholder
{
    public function placeholder()
    {
        return <<<'HTML'
        <div><x-loading /></div>
        HTML;
    }
}
