<?php

namespace Lichee\Database;

use PDO;
use Closure;
use ArrayAccess;
use Lichee\Database\Connection;
use Lichee\Database\Grammar;

/**
 * Class Query
 * @package Lichee\Database
 */
class Query
{
    /**
     * 选择符
     * @var
     */
    protected $select;

    /**
     * 聚合函数
     * @var
     */
    protected $aggregate;

    /**
     * 表名
     * @var
     */
    protected $from;

    /**
     * 连表
     * @var
     */
    protected $join;

    /**
     * 条件语句
     * @var
     */
    protected $where;

    /**
     * 分组
     * @var
     */
    protected $group;

    /**
     * 过滤
     * @var
     */
    protected $having;

    /**
     * 排序
     * @var
     */
    protected $orderBy;

    /**
     * 偏移量
     * @var
     */
    protected $offset;

    /**
     * 条数
     * @var
     */
    protected $limit;

    /**
     * 联合
     * @var
     */
    protected $union;

    /**
     * 参数
     * @var
     */
    protected $params;

    /**
     * 数据库连接
     * @var \Lichee\Database\Connection
     */
    protected $connect;

    /**
     * 语法解析
     * @var Grammar
     */
    protected $grammar;

    /**
     * 比较运算符
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',                //basic
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * 参数绑定
     * @var array
     */
    protected $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
        'union' => [],
    ];

    /**
     * 查询后回调
     * @var array
     */
    protected $selectAfterCallable = [];

    /**
     * @param \Lichee\Database\Connection $connect
     */
    public function setConnection(Connection $connect)
    {
        $this->connect = $connect;
    }

    /**
     * 获取数据库连接
     * @return Connection
     */
    public function getConnection()
    {
        return $this->clear()
            ->connect;
    }

    public function rawSelectAll($sql, $params = [])
    {
        return $this->connect->selectAll($sql, $params);
    }

    public function rawSelectOne($sql, $params = [])
    {
        return $this->connect->selectOne($sql, $params);
    }

    /**
     * 构造查询的Sql语句
     * @return array
     */
    protected function build()
    {
        $grammar = $this->getGrammar();
        list($sql, $params) = $grammar->compileComponent($this);
        return [$sql, $params];
    }

    /**
     * 获取语法解析器
     * @return Grammar
     */
    protected function getGrammar()
    {
        return $this->grammar;

    }

    /**
     * 返回SQL
     * @return array
     */
    public function getSql()
    {
        list($sql,) = $this->build();
        return $sql;
    }

    /**
     * 返回绑定的参数
     * @return mixed
     */
    public function getBindings()
    {
        list($sql, $params) = $this->build();
        return $params;
    }

    /**
     * 执行查询
     * @param Closure $fetchAction
     * @return mixed
     */
    protected function runSelect(Closure $fetchAction)
    {
        list($sql, $params) = $this->build();
        $connect = $this->getConnection()->command($sql, $params);
        $result = $fetchAction($connect);
        //index
        if ($this->selectAfterCallable instanceof Closure) {
            return call_user_func($this->selectAfterCallable, $result);
        }
        return $result;
    }

    /**
     *  add 'select' clauses to the query
     * @param $columns
     * @return $this
     */
    public function select(...$columns)
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->select = array_merge((array)$this->select, $column);
            } else {
                $this->select[] = $column;
            }
        }
        return $this;
    }

    /**
     * set table
     * @example
     *     设置表名 form('user') or form(['user'])
     *     重命表名 form(['user'=>'u'])
     * @param $tables
     * @return $this
     */
    public function table($tables)
    {
        $tableClause = [];
        $tables = (array)$tables;
        foreach ($tables as $table => $as) {
            if (is_int($table)) {
                $tableClause[$table] = $as;
            } else {
                $tableClause[$table] = $as;
            }
        }
        if (empty($this->from) || false == array_intersect_assoc((array)$this->from, $tableClause)) {
            $this->from = array_merge((array)$this->from, $tableClause);
        }
        return $this;
    }

    /**
     * where查询
     * @param mixed $column 列
     * @param null $operator 比较运算符
     * @param null $value 值
     * @param string $boolean 逻辑运算符
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';
        if (is_array($column)) {
            return $this->multiSimpleWhere($column, 'where');
        }
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        if ($column instanceof Closure) {
            return $this->whereNest($column, $boolean);
        }
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }
        $operator = $this->filterOperator($operator);
        $this->addBinding($value, 'where');
        $value = '?';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'boolean');
        return $this;
    }

    /**
     * where嵌套查询
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function whereNest(Closure $callback, $boolean = 'and')
    {
        $type = 'Nest';
        call_user_func($callback, $query = $this->newQuery());
        if (count($query->where)) {
            $this->where[] = compact('type', 'query', 'boolean');
            $this->addBinding($query->getBindings(), 'where');
        }
        return $this;
    }

    /**
     * where子查询
     * @param $column
     * @param $operator
     * @param Closure $callback
     * @param string $boolean
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean = 'and')
    {
        $type = 'Sub';
        call_user_func($callback, $query = $this->newQuery());
        $this->where[] = compact('type', 'column', 'operator', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * SubQuery方式的In查询
     * @param string $column 列
     * @param Closure $callback 嵌套查询
     * @param string $boolean 逻辑控制符
     * @param bool|false $not in还是notIn
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function whereSubIn($column, Closure $callback, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotInSub' : 'InSub';
        $query = $this->newQuery();
        call_user_func($callback, $query);
        $this->where[] = compact('type', 'column', 'query', 'boolean');
        $this->addBinding($query->getBindings(), 'where');
        return $this;
    }

    /**
     * 模糊查询
     * @param $column
     * @param $likeOperator
     * @return $this
     */
    public function whereLike($column, $likeOperator)
    {
        $this->where($column, 'like', $likeOperator);
        return $this;
    }


    /**
     * and逻辑In查询
     * @param mixed $column 条件
     * @param mixed $values In要查询数组
     * @param string $boolean 运算符
     * @param bool|false $not true:notIn false:In
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        if ($values instanceof Closure) {
            return $this->whereSubIn($column, $values, $boolean, $not);
        }
        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }
        $values = $this->getPlaceholderFormat($values);
        $this->where[] = compact('type', 'column', 'values', 'boolean');
        return $this;
    }


    /**
     * @param $columns
     * @param $method
     * @return $this
     */
    protected function multiSimpleWhere($columns, $method)
    {
        foreach ($columns as $column => $value) {
            $this->$method($column, '=', $value);
        }
        return $this;
    }

    /**
     * @param string $operator 运算符
     * @return array
     */
    protected function filterOperator($operator)
    {
        $isOperator = in_array($operator, $this->operators, true);
        if (!$isOperator) {
            throw new \InvalidArgumentException();
        }
        return strtolower($operator);
    }

    /**
     * add 'or where ' clauses to the query
     * @param $column
     * @param null $operator
     * @param null $value
     * @return Query
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * 删除
     * @return mixed
     */
    public function delete()
    {
        if (!empty(func_get_args())) {
            call_user_func_array([$this, 'where'], func_get_args());
        }
        return $this->getConnection()
            ->delete(
                $this->getGrammar()
                    ->compileDelete($this), $this->bindings['where']
            );
    }


    /**
     * @param array $values
     * @return mixed
     */
    public function update(array $values)
    {
        $bindings = array_merge(array_values($values), $this->bindings['where']);
        return $this->getConnection()
            ->update(
                $this->getGrammar()
                    ->compileUpdate($this, $values),
                $bindings
            );
    }

    /**
     * 字段自增
     * @param string $column 自增字段
     * @param int $number 自增数
     * @param array $otherColumn 更新
     * @return mixed
     */
    public function increment($column, $number = 1, $otherColumn = array())
    {
        $columns = [];
        if (!is_array($column)) {
            $columns[$column] = $number;
        }
        $bindings = (array)array_merge(array_values($otherColumn), $this->bindings['where']);
        return $this->getConnection()
            ->update(
                $this->getGrammar()
                    ->compileIncrementUpdate($this, $columns, $otherColumn),
                $bindings
            );
    }

    /**
     * add 'and where ' clauses to the query
     * @param $column
     * @param null $operator
     * @param null $value
     * @return Query
     */
    public function andWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'and');
    }

    /**
     * add 'order by' clauses to the query
     * @param $orderBy
     * @return $this
     */
    public function orderBy(...$orderBy)
    {
        foreach ($orderBy as $column) {
            if (is_array($column)) {
                $this->orderBy = array_merge((array)$this->orderBy, $column);
            } else {
                $this->orderBy[$column] = 'desc';
            }
        }
        return $this;
    }

    /**
     * add 'offset' clauses to the query
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * add 'offset' clauses to the query
     * @param $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }

    /**
     * add 'group by' clauses to the query
     * @param $groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            $this->group = array_merge((array)$this->group, is_array($group) ? $group : [$group]);
        }
        return $this;
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }
        //确保插入的数据是一个规范的二维数组
        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }
        //生成绑定的参数
        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }
        return $this->getConnection()
            ->insert(
                $this->getGrammar()->compileInsert($this, $values)
                , $bindings
            );
    }

    /**
     * @param array $values
     * @return bool|mixed
     */
    public function replace(array $values)
    {

        if (empty($values)) {
            return true;
        }
        //确保插入的数据是一个规范的二维数组
        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }
        //生成绑定的参数
        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }
        $sql = $this->getGrammar()->compileReplace($this, $values);
        return $this->getConnection()->update($sql, $bindings);
    }

    /**
     * @return mixed
     */
    public function one()
    {
        return $this->runSelect(function (Connection $connection) {
            return $connection->fetch();
        });
    }

    /**
     * @param int $number
     * @return mixed
     */
    public function column($number = 0)
    {
        return $this->runSelect(function (Connection $connection) use ($number) {
            return $connection->column($number);
        });
    }

    /**
     * @param string $index
     * @return $this
     */
    public function index(string $index)
    {
        $this->selectAfterCallable = function ($array) use ($index) {
            return array_combine(
                (array)array_column($array, $index)
                , $array);
        };
        return $this;
    }

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->runSelect(function (Connection $connection) {
            return $connection->fetchAll();
        });
    }

    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function count($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function sum($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function average($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function innerJoin($table = null, $one = null, $operator = null, $two = null)
    {
        return $this->join('inner join', $table, $one, $operator, $two);
    }

    public function leftJoin($table = null, $one = null, $operator = null, $two = null)
    {
        return $this->join('left join', $table, $one, $operator, $two);
    }

    public function rightJoin($table = null, $one = null, $operator = null, $two = null)
    {
        return $this->join('right join', $table, $one, $operator, $two);
    }

    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }
        $operator = $this->filterOperator($operator);
        $this->addBinding($value, 'having');
        $value = '?';
        $this->having[] = compact('type', 'column', 'operator', 'value', 'boolean');
        return $this;
    }

    public function andHaving($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->having($column, $operator, $value, $boolean);
    }

    public function orHaving($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->having($column, $operator, $value, $boolean);
    }

    /**
     * @param $function
     * @param $column
     * @return $this
     */
    public function aggregate($function, $column = '*')
    {
        if (is_array($column)) {
            $select = sprintf('%s(%s) as %s', $function, key($column), current($column));
        } else {
            $select = sprintf('%s(%s)', $function, $column);;
        }
        $this->aggregate = $select;
        return $this;
    }

    /**
     * @param string $type
     * @param string $table
     * @param string $one
     * @param string $operator
     * @param string $two
     * @return $this
     */
    public function join($type = 'Inner Join', $table = null, $one = null, $operator = null, $two = null)
    {
        $where = 'on';
        $this->join[] = compact('type', 'table', 'where', 'one', 'operator', 'two');
        return $this;
    }

    /**
     * 添加参数
     * @param $value
     * @param string $type
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function addBinding($value, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new \InvalidArgumentException("Invalid binding type: {$type}.");
        }
        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }
        return $this;
    }

    /**
     * @return static
     */
    protected function newQuery()
    {
        $static = new static();
        $static->setConnection($this->getConnection());;
        $static->setGrammar($this->getGrammar());
        return $static;
    }

    public function setGrammar(Grammar $grammar)
    {
        $this->grammar = $grammar;
    }

    /**
     * @param $var
     * @return mixed
     */
    public function __get($var)
    {
        return $this->{$var};
    }

    /**
     * @param $var
     * @param $value
     * @return mixed
     */
    public function __set($var, $value)
    {
        return $this->{$var} = $value;
    }

    /**
     * @return $this
     */
    protected function clear()
    {
        $this->select = null;
        $this->from = [];
        $this->aggregate = null;
        $this->join = null;
        $this->where = null;
        $this->group = null;
        $this->having = null;
        $this->orderBy = null;
        $this->offset = null;
        $this->limit = null;
        $this->union = null;
        $this->params = null;
        $this->bindings = [
            'select' => [],
            'join' => [],
            'where' => [],
            'having' => [],
            'order' => [],
            'union' => [],
        ];
        return $this;
    }

    /**
     * @return mixed
     */
    public function begin()
    {
        return $this->connect->begin();
    }

    /**
     * @return mixed
     */
    public function commit()
    {
        return $this->connect->commit();
    }

    /**
     * @return mixed
     */
    public function rollback()
    {
        return $this->connect->rollback();
    }

    /**
     * 将数组内对应的值替换成占位符 ['id'=>'？','name'=>'?']
     * @param array $column
     * @return array
     */
    protected function getPlaceholderFormat(array $column)
    {
        foreach ($column as &$value) {
            if (is_array($value)) {
                $value = $this->getPlaceholderFormat($value);
            } else {
                $value = '?';
            }
        }
        return $column;
    }

}




