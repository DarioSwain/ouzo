<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Db\Dialect;

use InvalidArgumentException;
use Ouzo\Db\QueryType;
use Ouzo\Utilities\Arrays;

class MySqlDialect extends Dialect
{
    public function table()
    {
        $alias = $this->_query->aliasTable;
        $table = $this->tableOrSubQuery();
        if ($alias) {
            $aliasOperator = $this->_query->type == QueryType::$DELETE ? '' : ' AS ';
            return $table . $aliasOperator . $alias;
        }
        return $table;
    }

    public function getConnectionErrorCodes()
    {
        return [2003, 2006];
    }

    public function getErrorCode($errorInfo)
    {
        return Arrays::getValue($errorInfo, 1);
    }

    public function using()
    {
        return $this->_using($this->_query->usingClauses, ' INNER JOIN ', $this->_query->table, $this->_query->aliasTable);
    }

    public function batchInsert($table, $primaryKey, $columns, $batchSize)
    {
        throw new InvalidArgumentException("Batch insert not supported in mysql");
    }

    protected function insertEmptyRow()
    {
        return "INSERT INTO {$this->_query->table} VALUES ()";
    }

    public function regexpMatcher()
    {
        return 'REGEXP';
    }

    protected function quote($word)
    {
        return '`' . $word . '`';
    }
}
