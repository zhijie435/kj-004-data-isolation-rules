<?php

namespace App\Services\DataIsolation;

use App\Contracts\DataIsolationStrategyInterface;
use App\Models\User;
use App\Services\DataIsolation\Strategies\GlobalAdminIsolationStrategy;
use App\Services\DataIsolation\Strategies\GuestIsolationStrategy;
use App\Services\DataIsolation\Strategies\StudentIsolationStrategy;
use App\Services\DataIsolation\Strategies\TeacherIsolationStrategy;
use App\Services\DataIsolation\Strategies\TenantIsolationStrategy;

class StrategyFactory
{
    public function make(?User $user): DataIsolationStrategyInterface
    {
        if (! $user) {
            $strategy = new GuestIsolationStrategy();
            return $strategy;
        }

        if ($user->tenant_id === null && $user->type === 'admin') {
            $strategy = new GlobalAdminIsolationStrategy();
            $strategy->setUser($user);
            return $strategy;
        }

        switch ($user->type) {
            case 'teacher':
                $strategy = new TeacherIsolationStrategy();
                break;
            case 'student':
                $strategy = new StudentIsolationStrategy();
                break;
            case 'admin':
            default:
                $strategy = new TenantIsolationStrategy();
                break;
        }

        $strategy->setUser($user);
        return $strategy;
    }
}
