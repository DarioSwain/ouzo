<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo;

use InvalidArgumentException;
use Ouzo\Db\Relation;
use Ouzo\Db\RelationFactory;

class Relations
{
    private $_relations;
    private $modelClass;

    private static $relationNames = ['hasOne', 'belongsTo', 'hasMany'];

    public function __construct($modelClass, array $params, $primaryKeyName)
    {
        $this->modelClass = $modelClass;
        $this->_relations = [];

        $this->_addRelations($params, $primaryKeyName);
    }

    /**
     * @param $name
     * @throws InvalidArgumentException
     * @return Relation
     */
    public function getRelation($name)
    {
        if (!isset($this->_relations[$name])) {
            throw new InvalidArgumentException("{$this->modelClass} has no relation: $name");
        }
        return $this->_relations[$name];
    }

    public function hasRelation($name)
    {
        return isset($this->_relations[$name]);
    }

    private function _addRelation(Relation $relation)
    {
        $name = $relation->getName();
        if (isset($this->_relations[$name])) {
            throw new InvalidArgumentException("{$this->modelClass} already has a relation: $name");
        }
        $this->_relations[$name] = $relation;
    }

    private function _addRelations(array $params, $primaryKeyName)
    {
        foreach (self::$relationNames as $relationName) {
            if (isset($params[$relationName])) {
                foreach ($params[$relationName] as $relation => $relationParams) {
                    $this->_addRelation(RelationFactory::create($relationName, $relation, $relationParams, $primaryKeyName, $this->modelClass));
                }
            }
        }
    }
}
