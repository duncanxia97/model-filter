<?php
/**
 * @author XJ.
 * Date: 2022/10/13 0013
 */

namespace Fatbit\ModelFilter\Traits;

use Exception;
use Fatbit\ModelFilter\Interfaces\ModelColumnFilterInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Model
 * @method Builder modelFilter()
 * @property ModelColumnFilterInterface $modelColumn
 * @property bool                       $checkFilterFieldDiff 是否开启检查筛选字段差异
 * @property bool                       $notValidFilterField  是否开启筛选字段验证
 * @method never throwFilterFieldDiffError(array $diffFields) 字段差异报错信息
 */
trait ModelFilter
{
    private $whereVals       = [];

    private $whereFieldIndex = [];


    /**
     *  模型筛选
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeModelFilter($query)
    {
        $filter = request()?->input('__filter');
        if ($filter && is_array($filter)) {
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
        if (class_exists($this->modelColumn, false)) {
            $names      = call_user_func([$this->modelColumn, 'names']);
            $diffFields = array_diff(array_unique($this->whereFieldIndex), $names);
            if (!empty($diffFields) && $this->checkFilterFieldDiff) {
                if (is_callable([$this, 'throwFilterFieldDiffError'])) {
                    call_user_func([$this, 'throwFilterFieldDiffError'], $diffFields);
                }
                throw new Exception('this names(' . implode(',', $diffFields) . ') not exist!');
            }
            if (!$this->notValidFilterField) {
                return;
            }
            $data = [];
            foreach ($this->whereFieldIndex as $index => $field) {
                if (!isset($data[$field])) {
                    $data[$field] = [];
                }
                /** @var ModelColumnFilterInterface $enum */
                $enum = call_user_func([$this->modelColumn, 'convert'], $field);
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
    private function queryFilter($query, array $filter, $boolean = 'and')
    {
        $query->where(
            function ($query) use ($filter) {
                foreach ($filter as $field => $val) {
                    if (is_array($val) && in_array(strtolower($field), ['and', 'or', 'and not', 'or not'])) {
                        $this->queryFilter($query, $val, $field);
                        continue;
                    }
                    $this->whereFieldIndex[] = $field;
                    if (is_callable([$this->modelColumn, 'convert'])) {
                        $field = call_user_func([$this->modelColumn, 'convert'], $field)?->field() ?: $field;
                    }
                    if (is_array($val)) {
                        if (isset($val[0]) && count($val) > 4) {
                            $query->whereIn($field, $val);

                            continue;
                        }
                        if (isset($val[0]) && count($val) < 4) {
                            if (Str::endsWith($val[0], 'like')) {
                                [$val[0], $val[1]] = $this->toWhereCondition($val[0], $val[1]);
                            }
                            $query->where($field, ...$val);
                            continue;
                        }
                        foreach ($val as $k => $v) {
                            $k = strtolower($k);
                            if (Str::endsWith($k, 'like')) {
                                $where = $this->toWhereCondition($k, $v);
                                $query->where($field, $where[0], $where[1]);

                                continue;
                            }
                            if (is_callable([$query, 'where' . Str::studly($k)])) {

                                if (is_array($v) && isset($v[0]) && $k !== 'in') {
                                    $query->{'where' . Str::studly($k)}($field, ...$v);

                                    continue;
                                }

                                $query->{'where' . Str::studly($k)}($field, $v);

                                continue;
                            }
                            if (is_array($v) && isset($v[0])) {
                                $query->where($field, $k, ...$v);

                                continue;
                            }
                            $query->where($field, $k, $v);
                        }
                        continue;
                    }
                    $query->where($field, $val);
                }
            },
            boolean: strtolower($boolean)
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

            $this->whereVals[] = Str::between($where['value'], '%', '%');
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
            'right like' => ['like', $value . '%'],
            'left like'  => ['like', '%' . $value],
            default      => [$type, $value],
        };
    }
}