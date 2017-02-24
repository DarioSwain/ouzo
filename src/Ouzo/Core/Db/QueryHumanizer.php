<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Db;

use Ouzo\Utilities\Strings;

class SelectColumnCallback
{
    private $prev_table;

    public function __invoke($matches)
    {
        $table = $matches[1];

        if ($table != $this->prev_table) {
            $first = !$this->prev_table;
            $this->prev_table = $table;
            $result = "$table.*";
            return $first ? $result : ", $result";
        }
        return "";
    }
}

class QueryHumanizer
{
    public static function humanize($sql)
    {
        if (Strings::endsWith($sql, ModelQueryBuilder::MODEL_QUERY_MARKER_COMMENT . ' */')) {
            $sql = Strings::removeSuffix($sql, ' /* ' . ModelQueryBuilder::MODEL_QUERY_MARKER_COMMENT . ' */');
            return preg_replace_callback('/SELECT .*? FROM/', function ($matches) {
                return preg_replace_callback('/(\w+)\.(\w+)( AS \w+)?(, )?/', new SelectColumnCallback(), $matches[0]);
            }, $sql);
        }
        return $sql;
    }
}
