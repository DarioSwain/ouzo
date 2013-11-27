<?php
namespace Ouzo\Db\Dialect;

use Ouzo\Db\Query;
use Ouzo\Db\QueryType;
use Ouzo\Utilities\Joiner;

class Dialect
{
    /**
     * @var Query
     */
    protected $_query;

    public function select()
    {
        if ($this->_query->type == QueryType::$SELECT) {
            $sql = ' ' . (empty($this->_query->selectColumns) ? '*' : Joiner::on(', ')->map(DialectUtil::_addAliases())->join($this->_query->selectColumns));
        } else if ($this->_query->type == QueryType::$COUNT) {
            $sql = ' count(*)';
        } else {
            $sql = '';
        }
        return $sql;
    }

    public function update()
    {
        if ($this->_query->type == QueryType::$UPDATE) {
            $attributes = DialectUtil::buildAttributesPartForUpdate($this->_query->updateAttributes);
            return " {$this->_query->table} set $attributes";
        }
        return '';
    }

    public function join()
    {
        $join = DialectUtil::buildJoinQuery($this->_query->joinClauses);
        if ($join) {
            return ' ' . $join;
        }
        return '';
    }

    public function where()
    {
        $where = DialectUtil::buildWhereQuery($this->_query->whereClauses);
        if ($where) {
            return ' WHERE ' . $where;
        }
        return '';
    }

    public function order()
    {
        if ($this->_query->order) {
            return ' ORDER BY ' . (is_array($this->_query->order) ? implode(', ', $this->_query->order) : $this->_query->order);
        }
        return '';
    }

    public function limit()
    {
        if ($this->_query->limit) {
            return ' LIMIT ?';
        }
        return '';
    }

    public function offset()
    {
        if ($this->_query->offset) {
            return ' OFFSET ?';
        }
        return '';
    }

    public function from()
    {
        return ' FROM ' . $this->_query->table;
    }

    public function buildQuery(Query $query)
    {
        $this->_query = $query;
        $sql = DialectUtil::buildQueryPrefix($query->type);

        if ($query->type == QueryType::$UPDATE) {
            $sql .= $this->update();
            $sql .= $this->where();
        } else {
            $sql .= $this->select();
            $sql .= $this->from();
            $sql .= $this->join();
            $sql .= $this->where();
            $sql .= $this->order();
            $sql .= $this->limit();
            $sql .= $this->offset();
        }
        return rtrim($sql);
    }
}