<?php
/**
 * @author XJ.
 * Date: 2022/10/14 0014
 */

namespace Fatbit\ModelFilter\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class ModelColumn
{
    /**
     * @param string               $attribute    属性
     * @param string|callable|null $fieldMapping 字段映射
     * @param string|array|null    $rules        校验规则
     */
    public function __construct(
        public readonly string            $attribute,
        public readonly ?string           $fieldMapper = null,
        public readonly null|string|array $rules = null,
    )
    {
    }
}