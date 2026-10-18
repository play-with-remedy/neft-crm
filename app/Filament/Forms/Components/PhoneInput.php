<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class PhoneInput
{
    public static function make(): Grid
    {
        return Grid::make([
            'default' => 12,
        ])
            ->schema([
                Select::make('phone_country_code')
                    ->label('Код страны')
                    ->options([
                        '375' => '+375',
                        '7' => '+7',
                    ])
                    ->default('375')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live()
                    ->columnSpan(2),

                TextInput::make('phone')
                    ->key(fn (Get $get): string => 'phone-' . self::countryCode($get))
                    ->label('Телефон')
                    ->placeholder(fn (Get $get): string => self::countryCode($get) === '7' ? 'XXX XXXXXXX' : 'XX XXXXXXX')
                    ->mask(fn (Get $get): string => self::countryCode($get) === '7' ? '999 9999999' : '99 9999999')
                    ->afterStateHydrated(function (TextInput $component, ?string $state, Set $set): void {
                        $digits = self::digits($state);

                        if ($digits === '') {
                            return;
                        }

                        if (str_starts_with($digits, '375') && strlen($digits) === 12) {
                            $set('phone_country_code', '375');
                            $component->state(self::formatLocalNumber(substr($digits, 3), '375'));

                            return;
                        }

                        if (str_starts_with($digits, '7') && strlen($digits) === 11) {
                            $set('phone_country_code', '7');
                            $component->state(self::formatLocalNumber(substr($digits, 1), '7'));

                            return;
                        }

                        $component->state(self::formatLocalNumber($digits, '375'));
                    })
                    ->dehydrateStateUsing(function (?string $state, Get $get): ?string {
                        $digits = self::digits($state);

                        return $digits === '' ? null : '+' . self::countryCode($get) . $digits;
                    })
                    ->rules(fn (Get $get): array => [
                        self::countryCode($get) === '7'
                            ? 'regex:/^(?:\d{10}|\d{3} \d{7})$/'
                            : 'regex:/^(?:\d{9}|\d{2} \d{7})$/',
                    ])
                    ->validationMessages([
                        'regex' => 'Введите корректный номер телефона.',
                    ])
                    ->columnSpan(10),
            ])
            ->columnSpanFull();
    }

    private static function countryCode(Get $get): string
    {
        return (string) $get('phone_country_code') === '7' ? '7' : '375';
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    private static function formatLocalNumber(string $digits, string $countryCode): string
    {
        $prefixLength = $countryCode === '7' ? 3 : 2;

        if (strlen($digits) <= $prefixLength) {
            return $digits;
        }

        return substr($digits, 0, $prefixLength) . ' ' . substr($digits, $prefixLength);
    }
}
