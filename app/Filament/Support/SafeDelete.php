<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Delete with a confirmation (REQ-CMS-41) that refuses, with a plain reason, when the record
 * is still in use, for example a category that still has products.
 */
final class SafeDelete
{
    /** @param  Closure(Model): bool  $inUse */
    public static function make(Closure $inUse, string $reason): DeleteAction
    {
        return DeleteAction::make()->before(function (Model $record, DeleteAction $action) use ($inUse, $reason) {
            if ($inUse($record)) {
                Notification::make()->danger()->title(__('This cannot be deleted'))->body($reason)->send();
                $action->cancel();
            }
        });
    }
}
