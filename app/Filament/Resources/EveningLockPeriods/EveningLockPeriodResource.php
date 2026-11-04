<?php

namespace App\Filament\Resources\EveningLockPeriods;

use App\Filament\Resources\EveningLockPeriods\Pages\ListEveningLockPeriods;
use App\Filament\Resources\EveningLockPeriods\Schemas\EveningLockPeriodForm;
use App\Filament\Resources\EveningLockPeriods\Tables\EveningLockPeriodsTable;
use App\Filament\Resources\Resource;
use App\Models\EveningLockPeriod;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EveningLockPeriodResource extends Resource
{
    protected static ?string $model = EveningLockPeriod::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $navigationLabel = 'Закрытые периоды';

    protected static ?string $modelLabel = 'Закрытый период';

    protected static ?string $pluralModelLabel = 'Закрытые периоды';

    protected static UnitEnum|string|null $navigationGroup = 'Клуб';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return FilamentAccess::isSuperAdmin() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return EveningLockPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EveningLockPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEveningLockPeriods::route('/'),
        ];
    }
}
