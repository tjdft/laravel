<?php

namespace Workbench\App\Actions;

use Workbench\App\Models\User;

class AtualizarPermissionsLoginAction
{
    public function __construct(private User $user)
    {
    }

    public function execute(): void
    {
    }
}

