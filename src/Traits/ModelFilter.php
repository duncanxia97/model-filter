<?php
/**
 * @author XJ.
 * Date: 2022/10/13 0013
 */

namespace Fatbit\ModelFilter\Traits;

use Exception;
use Fatbit\ModelFilter\Interfaces\ModelColumnFilterInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * @mixin Model
 * @method Builder|static modelFilter(ModelColumnFilterInterface|string|array|null $modelColumn = null)
 * @property ModelColumnFilterInterface $modelColumn
 * @property bool                       $checkFilterFieldDiff 是否开启检查筛选字段差异
 * @property bool                       $notValidFilterField  是否开启筛选字段验证
 * @method never throwFilterFieldDiffError(array $diffFields) 字段差异报错信息
 */
trait ModelFilter
{
    private       $whereVals             = [];

    private       $whereFieldIndex       = [];

    private array $whereConditionFormula = [
        'eq',
        '=',
        'neq',
        '<>',
        'gt',
        '>',
        'egt',
        '>=',
        'lt',
        '<',
        'elt',
        '<='
    ];

    /**
     * @var string|null|ModelColumnFilterInterface
     */
    private string|array|null $__modelColumn = null;


    /**
     *  模型筛选
     *
     * @param Builder                                $query
     * @param string|null|ModelColumnFilterInterface $modelColumn
     *
     * @return Builder
     * @throws ValidationException
     */
    public function scopeModelFilter($query, string|array|null $modelColumn = null)
    {
        $filter = request()?->input('__filter');
        if ($filter && is_array($filter)) {
            $this->__modelColumn = $modelColumn;
            if (property_exists($this, 'modelColumn')) {
                $this->__modelColumn = $modelColumn ?: $this->modelColumn;
            }
            $this->getQueryWhereValues($query->getQuery()->wheres);
            $index = count($this->whereVals) - 1;
            $index = $index < 0 ? 0 : $index;
            $this->queryFilter($query, $filter);
            $this->getQueryWhereValues($query->getQuery()->wheres);
            $this->validateFilterField(array_slice($this->whereVals, $index, preserve_keys: true));
        }

        return $query;
    }

    /**
     * 检查字段
     *
     * @author XJ.
     * Date: 2022/10/18 0018
     *
     * @param $whereKVs
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateFilterField($whereKVs)
    {

        if ($this->__modelColumn && !is_array($this->__modelColumn) && class_exists($this->__modelColumn, false)) {
            $names      = call_user_func([$this->__modelColumn, 'names']);
            $diffFields = array_diff(array_unique($this->whereFieldIndex), $names);
            if (!empty($diffFields) && property_exists($this, 'checkFilterFieldDiff') && $this->checkFilterFieldDiff) {
                if (is_callable([$this, 'throwFilterFieldDiffError'])) {
                    call_user_func([$this, 'throwFilterFieldDiffError'], $diffFields);
                }
                throw new Exception('this names(' . implode(',', $diffFields) . ') not exist!');
            }
            if (property_exists($this, 'notValidFilterField') && !$this->notValidFilterField) {
                return;
            }
            $data = [];
            foreach ($this->whereFieldIndex as $index => $field) {
                if (!isset($data[$field])) {
                    $data[$field] = [];
                }
                /** @var ModelColumnFilterInterface $enum */
                $enum = call_user_func([$this->__modelColumn, 'convert'], $field);
                if (!empty($enum?->rules())) {
                    validator(
                                          [$field => $whereKVs[$index]],
                                          $enum->rules(),
                        customAttributes: [$field => $enum->attribute()]
                    )->validate();
                }
            }
        }
    }

    /**
     * 查询筛选
     *
     * @author XJ.
     * Date: 2022/10/17 0017
     *
     * @param Builder                               $query
     * @param array                                 $filter
     * @param                                       $boolean
     */
    private function queryFilter($query, array $filter, $boolean = 'and', $lastBoolean = 'and')
    {
        $query->where(
            function ($query) use ($filter, $boolean) {
                /** @var Illuminate\Database\Query\Builder $query * */
                $whereHas = [];
                foreach ($filter as $field => $val) {
                    if (is_array($val) && in_array(strtolower($field), ['and', 'or', 'and not', 'or not'])) {
                        $this->queryFilter($query, $val, $field, lastBoolean: $boolean);
                        continue;
                    }
                    if (strpos($field, '.')) {
                        [$with, $field] = explode('.', $field);
                        // 判断with是否存在
                        if (method_exists($this, $with)) {
                            $whereHas[$with][$field] = $val;
                            continue;
                        }
                        $field = $this->fieldConvert($field, $with);
                        $field = $with . '.' . $field;
                    }
                    $this->whereFieldIndex[] = $field;
                    $field                   = $this->fieldConvert($field);
                    if (is_array($val)) {
                        if (isset($val[0])) {
                            if (count($val) > 4) {
                                $query->whereIn($field, $val, boolean: $boolean);

                                continue;
                            }
                            if (Str::endsWith($val[0], 'like') && count($val) < 4) {
                                [$val[0], $val[1]] = $this->toWhereCondition($val[0], $val[1]);
                                $val['boolean'] = $boolean;
                                $query->where($field, ...$val);
                                continue;
                            }
                            if (!is_numeric($val[0])
                                && in_array($val[0], $query->getQuery()->operators ?? $this->whereConditionFormula)) {
                                $query->where($field, $val[0], $val[1], boolean: $boolean);
                                continue;
                            }
                            if (
                                !is_numeric($val[0])
                                && is_callable([$query, 'where' . Str::studly($val[0])])
                            ) {
                                $query->{'where' . Str::studly($val[0])}($field, $val[1], boolean: $boolean);
                                continue;
                            }
                        }
                        foreach ($val as $k => $v) {
                            $k = strtolower($k);
                            if (Str::endsWith($k, 'like')) {
                                $where = $this->toWhereCondition($k, $v);
                                $query->where($field, $where[0], $where[1], boolean: $boolean);

                                continue;
                            }
                            if (!is_numeric($k) && is_callable([$query, 'where' . Str::studly($k)])) {
                                var_dump($k);
                                $query->{'where' . Str::studly($k)}($field, $v, boolean: $boolean);

                                continue;
                            }
                            if (is_array($v) && isset($v[0])) {
                                if (is_callable([$query, 'where' . Str::studly($v[0])])) {
                                    $v['boolean'] = $boolean;
                                    $query->{'where' . Str::studly($v[0])}($field, $v[1]);

                                    continue;
                                }
                                $v['boolean'] = $boolean;
                                $query->where($field, $k, ...$v);

                                continue;
                            }
                            $query->where($field, $k, $v, boolean: $boolean);
                        }
                        continue;
                    }
                    $query->where($field, $val, boolean: $boolean);
                }
                foreach ($whereHas as $with => $filter) {
                    $query->whereHas($with, fn($q) => $this->queryFilter($q, $filter, $boolean, lastBoolean: $boolean));
                }
            },
            boolean: strtolower($lastBoolean)
        );
    }

    /**
     * 获取查询where 数据
     *
     * @author XJ.
     * Date: 2022/10/17 0017
     *
     * @param $wheres
     */
    private function getQueryWhereValues($wheres)
    {
        foreach ($wheres as $where) {
            if ($where['type'] === 'Nested') {
                $this->getQueryWhereValues($where['query']->wheres);
                continue;
            }
            if (isset($where['values'])) {
                $this->whereVals[] = $where['values'];
                continue;
            }
            if ($where['type'] === 'Null') {
                $this->whereVals[] = null;
                continue;
            }
            $this->whereVals[] = Str::between($where['value'] ?? null, '%', '%');
        }
    }

    /**
     * @author XJ.
     * Date: 2022/10/18 0018
     *
     * @param $type
     * @param $value
     *
     * @return array|string[]
     */
    private function toWhereCondition($type, $value): array
    {
        return match ($type) {
            'like'       => ['like', '%' . $value . '%'],
            'right like' => ['like', '%' . $value],
            'left like'  => ['like', $value . '%'],
            default      => [$type, $value],
        };
    }

    /**
     * 字段转换
     *
     * @author XJ.
     * Date: 2023/1/13 0013
     *
     * @param string       $field 查询字段
     * @param false|string $with  with字符串
     *
     * @return mixed|string
     */
    protected function fieldConvert(string $field, false|string $with = false)
    {
        $lastField = $field;
        if (is_string($with)) {
            $field = is_array($this->__modelColumn) ? $with . ':' . $field : $with . '__' . $field;
        }
        $lastConvertField = $field;
        $fieldSnake       = Str::snake($field);
        if (is_array($this->__modelColumn) && (isset($this->__modelColumn[$field]) || isset($this->__modelColumn[$fieldSnake]))) {
            // 快速匹配
            $field = value($this->__modelColumn[$field] ?? $this->__modelColumn[$fieldSnake]) ?: $field;
        } else if (isset($this->__modelColumn) && is_callable([$this->__modelColumn, 'convert'])) {
            // model column 匹配
            $modelColumn = call_user_func([$this->__modelColumn, 'convert'], $field);
            if (!is_null($modelColumn)) {
                $field = $modelColumn->field() ?: $field;
            } else {
                $field = call_user_func([$this->__modelColumn, 'convert'], $fieldSnake)?->field() ?: $field;
            }
        }
        if ($field == $lastConvertField) {
            // 如果没有匹配字段还原字段
            $field = $lastField;
        }

        return Str::snake($field);
    }
}