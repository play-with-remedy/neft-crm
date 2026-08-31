<?php

namespace App\Enums;

enum AutumnCaseStatus: string
{
    case InProgress = 'in_progress';
    case RewardAvailable = 'reward_available';
    case Completed = 'completed';
    case Expired = 'expired';
    case RewardExpired = 'reward_expired';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'В процессе',
            self::RewardAvailable => 'Награда доступна',
            self::Completed => 'Завершено',
            self::Expired => 'Просрочено',
            self::RewardExpired => 'Награда сгорела',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'info',
            self::RewardAvailable => 'success',
            self::Completed => 'gray',
            self::Expired, self::RewardExpired => 'danger',
        };
    }
}
