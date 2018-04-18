<?php

namespace Lichee\Database;

/**
 * Class Grammar
 * @package Lichee\Database
 */
class Grammar
{
    /**
     * 构造查询需要的Query $query参数
     * @var array
     */
    protected $selectComponents = [
        'select',
        'aggregate',
        'from',
        'join',
        'where',
        'group',
        'having',
        'orderBy',
        'limit',
        'offset',
        'union',
    ];

    /**
     * where的type和对应的处理函数
     * @var array
     */
    protected $whereTypeFunction = [
        'Basic' => 'getWhereByBasic',
        'In' => 'getWhereByIn',
        'NotIn' => 'getWhereByNotIn',
        'InSub' => 'getWhereByInSub',
        'Nest' => 'getWhereByNest',
        'Sub'=>'getWhereBySub',
    ];
    protected $params = [];

    /**
     * @param Query $query
     * @return array
     */
    public function compileComponent(Query $query)
    {
        $sqlClauses = [];
        foreach ($this->selectComponents as $component) {
            if (!is_null($query->$component)) {
                $method = 'compile' . ucfirst($component);
                $sqlClauses[$component] = $this->$method($query, $query->$component);
                if (isset($query->bindings[$component])) {
                    $this->compileParams($query->bindings[$component]);
                }
            } else {
                $sqlClauses[$component] = '';
            }
        }
        return [implode(' ', array_filter($sqlClauses)),$this->clearParamsAlsoReturn()];
    }

    /**
     * @return array
     */
    protected function clearParamsAlsoReturn(){
        $param=$this->params;
        $this->params=[];
        return $param;
    }


    /**
     * @param Query $query
     * @param $values
     * @return string
     */
    public function compileInsert(Query $query, $values)
    {
        $table = $query->from[0];
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        $columns = $this->implodeArray(array_keys(reset($values)));
        $parameters = [];
        foreach ($values as $record) {
            $record = $this->implodeArray($this->replaceValToPlaceholder($record));
            $parameters[] = sprintf("(%s)", $record);
        }
        return "insert into $table (" . $columns . ") values " . $this->implodeArray($parameters);
    }


    /**
     * @param Query $query
     * @param $values
     * @return string
     */
    public function compileReplace(Query $query, $values)
    {
        $table = $query->from[0];
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        $columns = $this->implodeArray(array_keys(reset($values)));
        $parameters = [];
        foreach ($values as $record) {
            $record = $this->implodeArray($this->replaceValToPlaceholder($record));
            $parameters[] = sprintf("(%s)", $record);
        }
        return "replace into $table (" . $columns . ") values " . $this->implodeArray($parameters);
    }


    /**
     * @param Query $query
     * @return array|string
     */
    public function compileDelete(Query $query)
    {
        $clause[] = 'delete';
        $clause[] = $this->compileFrom($query, $query->from);
        $clause[] = $this->compileWhere($query, $query->where);
        return $this->implodeArray($clause, ' ');
    }


    /**
     * @param Query $query
     * @param $values
     * @param array $incrementClause
     * @return array|string
     */
    public function compileUpdate(Query $query, $values,$incrementClause=array())
    {
        $clause['update'] = 'Update';
        $clause['from'] = $this->compileKeyAndValue($query->from, ' ');
        $clause['set'] = 'Set';
        $incrementSql=!empty($incrementClause)?implode($incrementClause,' '):'';
        $clause['columns'] = $this->compileKeyAndValue($this->replaceValToPlaceholder($values), ' = ');
        $clause['where'] = $this->compileWhere($query, $query->where);
        //合并自增语句和修改语句
        $clause['columns']=$this->implodeArray(array_filter([$incrementSql,$clause['columns']]),',');
        return $this->implodeArray($clause, ' ');
    }


    /**
     * @param Query $query
     * @param array $columns
     * @param array $otherColumn
     * @return array|string
     */
    public function compileIncrementUpdate(Query $query,array $columns,array $otherColumn)
    {
        //自增语句
        $incrementClause=array();
        array_walk($columns,function($value,$column)use(&$incrementClause){
            $incrementClause[]="{$column} = {$column} + $value";
        });
        return $this->compileUpdate($query,$otherColumn,$incrementClause);
    }

    /**
     * @param Query $query
     * @param $select
     * @return string
     */
    protected function compileSelect(Query $query, $select)
    {
        return 'select ' . $this->compileKeyAndValue($select, ' as ');
    }

    /**
     * @param Query $query
     * @param $aggregate
     * @return string
     */
    protected function compileAggregate(Query $query, $aggregate)
    {
        return 'select ' . $aggregate;
    }

    /**
     * @param Query $query
     * @param $from
     * @return string
     */
    protected function compileFrom(Query $query, $from)
    {
        return 'from ' . $this->compileKeyAndValue($from, ' as ');
    }

    /**
     * @param Query $query
     * @param $groups
     * @return string
     */
    protected function compileGroup(Query $query, $groups)
    {
        return 'group by ' . implode(',', $groups);
    }

    /**
     * @param Query $query
     * @param $joins
     * @return string
     */
    protected function compileJoin(Query $query, $joins)
    {
        return implode('', array_map(function ($group) use (&$clause) {
            return $clause .= vsprintf('%s %s %s %s %s %s', $group);
        }, $joins));
    }

    /**
     * @param Query $query
     * @param $having
     * @return string
     */
    protected function compileHaving(Query $query, $having)
    {
        $clause = [];
        foreach ($having as $number => $where) {
            //无视第一条的where运算符
            if ($number == 0 && $where['type'] == 'Basic') {
                $clause[] = "{$where['column']} {$where['operator']} {$where['value']}";
                continue;
            }
            //where子条件
            if ($where['type'] == 'Sub') {
                $clause[] = ' where子条件  ';
                continue;
            }
            $clause[] = "{$where['boolean']} {$where['column']} {$where['operator']} {$where['value']}";

        }
        return 'Having ' . $this->implodeArray($clause, ' ');

    }

    protected function compileUnion(Query $query, $union)
    {
        echo __FUNCTION__ . '<br>';
    }

    /**
     * 编译条件子句
     * @param Query $query
     * @param array $wheres
     * @return string
     */
    public function compileWhere(Query $query,array $wheres)
    {
        $clause = [];
        foreach ($wheres as $number => $where) {
            //删除第一条where的逻辑运算符
            if ($number == 0) {
                $where['boolean'] = '';
            }
            $clause[] = $this->{$this->whereTypeFunction[$where['type']]}($where);
        }
        return 'where ' . $this->implodeArray($clause, ' ');
    }

    /**
     * 编译条件嵌套语句
     * @param $where
     * @return string
     */
    protected function getWhereByNest($where)
    {
        $nestClause=$this->compileWhere($where['query'],$where['query']->where);
        return $where['boolean'].' ('.substr($nestClause,6).')';
    }

    /**
     * 编译子查询语句
     * @param $where
     * @return string
     */
    protected function getWhereBySub($where)
    {
        $sql = $where['query']->getSql();
        $whereClause[] = "{$where['column']} {$where['operator']} ({$sql})";
        return $this->implodeArray($whereClause, ' ');
    }

    /**
     * 编译普通的条件
     * @param $where
     * @return string
     */
    protected function getWhereByBasic($where)
    {
        return "{$where['boolean']} {$where['column']} {$where['operator']} {$where['value']}";
    }

    /**
     * 编译In条件
     * @param $where
     * @return string
     */
    protected function getWhereByIn($where)
    {
        $subClause = $where['type'] == 'In' ? 'In' : 'Not In';
        $values = '(' . $this->compileKeyAndValue($where['values']) . ')';
        return "{$where['boolean']} {$where['column']} {$subClause} {$values}";
    }

    /**
     * 编译NotIn条件
     * @param $where
     * @return string
     */
    protected function getWhereByNotIn($where)
    {
        return $this->getWhereByIn($where);
    }

    /**
     * 编译In子查询条件
     * @param $where
     * @return array|string
     */
    protected function getWhereByInSub($where)
    {
        $sql = $where['query']->getSql();
        $whereClause[] = "{$where['column']} In ({$sql})";
        return $this->implodeArray($whereClause, ' ');
    }


    /**
     * @param Query $query
     * @param $orderBys
     * @return string
     */
    protected function compileOrderBy(Query $query, $orderBys)
    {
        return 'order by ' . $this->compileKeyAndValue($orderBys, ' ');
    }

    /**
     * @param Query $query
     * @param $limit
     * @return string
     */
    protected function compileLimit(Query $query, $limit)
    {
        return 'limit ' . $limit;
    }

    /**
     * @param Query $query
     * @param $offset
     * @return string
     */
    protected function compileOffset(Query $query, $offset)
    {
        return 'offset ' . $offset;
    }


    /**
     * @param $columns
     * @param string $operator
     * @return string
     */
    protected function compileKeyAndValue($columns, $operator = '')
    {
        $columnSql = [];
        foreach ($columns as $column => $as) {
            if (is_string($column)) {
                $columnSql[] = vsprintf('%s%s%s', [$column, $operator, $as]);
            } else {
                $columnSql[] = vsprintf('%s', [$as]);
            }
        }
        return implode(',', $columnSql);
    }

    /**
     * @param $where
     * @return array
     */
    public function compileParams($where)
    {
        return $this->params = array_merge($this->params, $where);
    }

    /**
     * 将数组内对应的值替换成占位符 ['id'=>'？','name'=>'?']
     * @param array $column
     * @return array
     */
    protected function replaceValToPlaceholder(array $column)
    {
        foreach ($column as &$value) {
            if (is_array($value)) {
                $value = $this->replaceValToPlaceholder($value);
            } else {
                $value = '?';
            }
        }
        return $column;
    }

    /**
     * 将数组用符号分割成字符串
     * @param array $columns
     * @param string $operator
     * @return array|string
     */
    public function implodeArray(array $columns, $operator = ',')
    {
        if (empty($columns)) {
            return [];
        }
        return implode($operator, $columns);
    }


}
