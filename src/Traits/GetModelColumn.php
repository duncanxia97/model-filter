<?php
/**
 * @author XJ.
 * Date: 2022/10/18 0018
 */

namespace Fatbit\ModelFilter\Traits;

use Fatbit\ModelFilter\Annotations\ModelColumn;
use Fatbit\ModelFilter\Interfaces\ConvertEnumInterface;
use Fatbit\ModelFilter\Interfaces\ModelColumnFilterInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use ReflectionEnumUnitCase;

/**
 * @implements ConvertEnumInterface|ModelColumnFilterInterface
 */
trait GetModelColumn
{
    use ConvertEnum;

    protected function getModelColumn($get = 0): ?ModelColumn
    {
        return (new ReflectionEnumUnitCase($this, $this->name))
                   ->getAttributes(ModelColumn::class)[$get]
                   ?->newInstance() ?? null;
    }

    /**
     * 获取属性
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return string|null
     */
    public function attribute(): ?string
    {
        return $this->getModelColumn()?->attribute;
    }

    /**
     * 获取字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return string|null|Builder|Model
     */
    public function field()
    {
        $modelColumn = $this->getModelColumn();
        if ($modelColumn?->fieldMapper) {
            if (method_exists($this, $modelColumn?->fieldMapper)) {
                return $this->{$modelColumn?->fieldMapper}() ?: $this->name;
            }

            return $modelColumn?->fieldMapper;
        }
        if (method_exists($this, $this->name)) {
            return $this->{$this->name}() ?: $this->name;
        }

        return $this->name;
    }

    /**
     * 获取所有的字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allFields(): array
    {
        $fields = [];
        /** @var static $case */
        foreach (self::cases() as $case) {
            $fields[$case->name] = $case->field();
        }

        return $fields;
    }

    /**
     * 获取所有的rules
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allRules(): array
    {
        $rules = [];
        /** @var static $case */
        foreach (self::cases() as $case) {
            $tmpRules = $case->rules() ?? [];
            $rules    += $tmpRules;
        }

        return $rules;
    }

    /**
     * 获取验证规则
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array|null
     */
    public function rules(): ?array
    {
        $modelColumn = $this->getModelColumn();
        if ($modelColumn?->rules) {
            return $this->toRules($modelColumn?->rules);
        }

        $method = 'get' . Str::studly($this->name) . 'Rules';
        if (method_exists($this, $method)) {
            return $this->toRules($this->{$method}() ?: $modelColumn?->rules);
        }

        return null;
    }

    /**
     * 转换为rules
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     *
     * @param $rules
     *
     * @return string[]
     */
    private function toRules($rules): ?array
    {
        if (
            is_string($rules)
            || (is_array($rules) && isset($rules[0]))
        ) {
            return [$this->name => $rules];
        }
        if (is_array($rules)) {
            return $rules;
        }

        return null;
    }

    /**
     * 获取列
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array{field:string|Builder|Model, rules:string|array, attribute:string, name:string}
     */
    public function column(): array
    {
        $modelColumn = $this->getModelColumn();

        return [
            'field'     => $this->field(),
            'rules'     => $this->rules(),
            'attribute' => $modelColumn?->attribute,
            'name'      => $this->name,
        ];
    }

    /**
     * 获取所有的列
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     * @return array
     */
    public static function allColumns(): array
    {
        $columns = [];
        /** @var static $case */
        foreach (self::cases() as $case) {
            $columns[$case->name] = $case->column();
        }

        return $columns;
    }

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
    public function issetFiled($field): bool
    {
        return self::convert($field) !== null;
    }


}