<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Db;

use Ouzo\Db;
use Ouzo\Relations;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Strings;
use ReflectionClass;

class ModelDefinition
{
    /** @var Db */
    public $db;

    public $table;
    public $sequence;
    public $primaryKey;
    public $fields;

    /** @var Relations */
    public $relations;

    public $afterSaveCallbacks = [];
    public $beforeSaveCallbacks = [];
    public $defaults = [];

    private static $cache = [];

    public function __construct(Db $db, $table, $sequence, $primaryKey, $fields, $relations, array $afterSaveCallbacks, array $beforeSaveCallbacks, $defaults)
    {
        $this->db = $db;
        $this->table = $table;
        $this->sequence = $sequence;
        $this->primaryKey = $primaryKey;
        $this->fields = $fields;
        $this->relations = $relations;
        $this->afterSaveCallbacks = $afterSaveCallbacks;
        $this->beforeSaveCallbacks = $beforeSaveCallbacks;
        $this->defaults = $defaults;
    }

    public static function resetCache()
    {
        self::$cache = [];
    }

    /**
     * @param $class
     * @param $params
     * @return ModelDefinition
     */
    public static function get($class, $params)
    {
        if (!isset(self::$cache[$class])) {
            self::$cache[$class] = self::_createDefinition($class, $params);
        }
        return self::$cache[$class];
    }

    public function mergeWithDefaults($attributes, $fields)
    {
        if (empty($this->defaults)) {
            return $attributes;
        }
        $defaultsToUse = array_diff_key(array_intersect_key($fields, $this->defaults), $attributes);
        foreach ($defaultsToUse as $field => $value) {
            if (is_callable($value)) {
                $attributes[$field] = $value();
            } else {
                $attributes[$field] = $value;
            }
        }
        return $attributes;
    }

    private static function defaultTable($class)
    {
        $reflectionClass = new ReflectionClass($class);
        return Strings::tableize($reflectionClass->getShortName());
    }

    /**
     * @param $class
     * @param $params
     * @return ModelDefinition
     */
    private static function _createDefinition($class, $params)
    {
        $table = Arrays::getValue($params, 'table') ?: self::defaultTable($class);
        $primaryKey = Arrays::getValue($params, 'primaryKey', 'id');
        $sequence = Arrays::getValue($params, 'sequence', "{$table}_{$primaryKey}_seq");

        list($fields, $defaults) = self::_extractFieldsAndDefaults($params['fields']);

        $relations = new Relations($class, $params, $primaryKey);

        $db = empty($params['db']) ? Db::getInstance() : $params['db'];
        if ($primaryKey && !in_array($primaryKey, $fields)) {
            $fields[] = $primaryKey;
        }
        $afterSaveCallbacks = Arrays::toArray(Arrays::getValue($params, 'afterSave'));
        $beforeSaveCallbacks = Arrays::toArray(Arrays::getValue($params, 'beforeSave'));

        return new ModelDefinition($db, $table, $sequence, $primaryKey, $fields, $relations, $afterSaveCallbacks, $beforeSaveCallbacks, $defaults);
    }

    private static function _extractFieldsAndDefaults($fields)
    {
        $newFields = [];
        $defaults = [];
        foreach ($fields as $key => $value) {
            if (is_numeric($key)) {
                $newFields[] = $value;
            } else {
                $newFields[] = $key;
                $defaults[$key] = $value;
            }
        }
        return [$newFields, $defaults];
    }
}
