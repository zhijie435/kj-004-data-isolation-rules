<?php

namespace App\Contracts;

use App\DTOs\UserDataScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface DataIsolationStrategyInterface
{
    public function setUser(User $user): self;

    public function getUser(): ?User;

    public function shouldBypass(): bool;

    public function apply(Builder $query, Model $model): void;

    public function canAccess(Model $model): bool;

    public function ensureCanAccess(Model $model): void;

    public function getScopeSummary(): UserDataScope;
}
