<?php
/**
 * @author XJ.
 * Date: 2022/10/18 0018
 */

namespace Fatbit\ModelFilter\Traits;

use Fatbit\ModelFilter\Interfaces\ConvertEnumInterface;

/**
 * @implements ConvertEnumInterface
 */
trait ConvertEnum
{
    /**
     * 获取name
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 转换
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     *
     * @param $name
     *
     * @return static|null
     */
    public static function convert($name): ?static
    {
        try {
            return constant(static::class . '::' . $name);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
