<?php
/**
 * @author XJ.
 * Date: 2022/10/18 0018
 */

namespace Fatbit\ModelFilter\Interfaces;

interface ModelColumnFilterInterface
{
    /**
     * 获取属性
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return string|null
     */
    public function attribute(): ?string;

    /**
     * 获取所有的字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allFields(): array;

    /**
     * 获取所有的rules
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allRules(): array;

    /**
     * 获取所有的列
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allColumns(): array;

    /**
     * 获取字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return string|null
     */
    public function field();

    /**
     * 获取验证规则
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return string|array|null
     */
    public function rules(): null|string|array;

    /**
     *  获取列
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public function column(): array;

    /**
     * 是否存在字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     *
     * @param $field
     *
     * @return bool
     */
    public function issetFiled($field): bool;
}