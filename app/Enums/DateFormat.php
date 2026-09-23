<?php

declare(strict_types=1);

namespace App\Enums;

enum DateFormat: string
{
    /* Day-Month-Year Formats */
    case DMY_SLASH = 'd/m/Y'; /* 31/12/2021 */
    case DMY_DASH = 'd-m-Y'; /* 31-12-2021 */
    case DMY_DOT = 'd.m.Y'; /* 31.12.2021 */
    case DMY_SPACE = 'd m Y'; /* 31 12 2021 */
    case DMY_LONG = 'd F Y'; /* 31 December 2021 */
    case DMY_SHORT = 'd M Y'; /* 31 Dec 2021 */

    /* Year-Month-Day Formats */
    case YMD_SLASH = 'Y/m/d'; /* 2021/12/31 */
    case YMD_DASH = 'Y-m-d'; /* 2021-12-31 */
    case YMD_DOT = 'Y.m.d'; /* 2021.12.31 */
    case YMD_SPACE = 'Y m d'; /* 2021 12 31 */
    case YMD_LONG = 'Y F d'; /* 2021 December 31 */
    case YMD_SHORT = 'Y M d'; /* 2021 Dec 31 */

    public const string DEFAULT = self::DMY_LONG->value;

    public const string DEFAULT_WITH_TIME = self::DEFAULT.' '.TimeFormat::DEFAULT;
}
