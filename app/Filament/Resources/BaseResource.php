<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class BaseResource extends Resource
{
    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function canWrite(): bool
    {
        return static::currentUser()?->canManageRecords() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::currentUser()?->is_active ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::currentUser()?->is_active ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canWrite();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWrite();
    }

    public static function canDelete(Model $record): bool
    {
        return static::currentUser()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return static::currentUser()?->isAdmin() ?? false;
    }

    public static function canReplicate(Model $record): bool
    {
        return static::canWrite();
    }
}