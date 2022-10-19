<?php
/**
 * @author XJ.
 * Date: 2022/10/18 0018
 */

namespace Fatbit\ModelFilter\Demo;

use Fatbit\ModelFilter\Annotations\ModelColumn;
use Fatbit\ModelFilter\Interfaces\ModelColumnFilterInterface;
use Fatbit\ModelFilter\Traits\GetModelColumn;

enum TestModelColumn implements ModelColumnFilterInterface
{
    use GetModelColumn;

    #[ModelColumn('名称', rules: 'string|max:50', fieldMapper: 'admin.name')]
    case name;

    #[ModelColumn('用户名', rules: 'string|max:50', fieldMapper: 'admin.username')]
    case username;

    #[ModelColumn('状态', rules: ['integer', 'in:1,2,3'])]
    case status;
}