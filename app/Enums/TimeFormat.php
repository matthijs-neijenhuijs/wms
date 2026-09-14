<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeFormat: string
{
    case G24 = 'G:i'; /* 5:30 */
    case H24 = 'H:i'; /* 05:30 */

    public const string DEFAULT = self::H24->value;
}
