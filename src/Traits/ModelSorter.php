<?php
/**
 * @author XJ.
 * Date: 2022/10/25 0025
 */

namespace Fatbit\ModelFilter\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Model
 * @method Builder|static modelSort(array $canSortField = [])
 * @property array $canSortField 可排序字段
 */
trait ModelSorter
{

    /**
     *  模型筛选
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeModelSort($query, array $canSortField = [])
    {
        $canSortField = $canSortField ?: $this->canSortField;
        $sorts        = request()?->input('__sort');
        if ($sorts && is_array($sorts)) {
            foreach ($sorts as $field => $sort) {
                if (empty($canSortField) || isset($canSortField[$field]) || in_array($field, $canSortField)) {
                    $query->orderBy($this->getSortField($field, $canSortField), $sort);
                }
            }
        }

        return $query;
    }

    /**
     * 获取排序字段
     *
     * @author XJ.
     * Date: 2022/10/25 0025
     *
     * @param string $field
     *
     * @return mixed|string
     */
    private function getSortField(string $field, $canSortField)
    {
        $method = 'sort' . Str::studly($field);
        if (method_exists($this, $method)) {
            $field = call_user_func([$this, $method], $field);
        }
        if (isset($canSortField[$field])) {
            $field = $canSortField[$field];
        }

        return Str::snake($field);
    }

}