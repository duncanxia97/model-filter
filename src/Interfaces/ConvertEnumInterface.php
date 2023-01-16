<?php
/**
 * @author XJ.
 * Date: 2022/10/18 0018
 */

namespace Fatbit\ModelFilter\Interfaces;

use BackedEnum;

interface ConvertEnumInterface extends BackedEnum
{
    public static function names(): array;

    public static function convert($name): ?static;
}