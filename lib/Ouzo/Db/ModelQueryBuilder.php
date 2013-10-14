<?php
namespace Ouzo\Db;

use Ouzo\Db;
use Ouzo\Model;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\FluentArray;
use Ouzo\Utilities\Objects;
use Ouzo\Utilities\Strings;
use PDO;

class ModelQueryBuilder
{
    private $_db;
    private $_model;
    /**
     * @var Relation[]
     */
    private $_joinedRelations = array();
    private $_transformers;
    private $_query;
    private $_selectModel = true;

    public function __construct(Model $model, $db = null)
    {
        $this->_db = $db ? $db : Db::getInstance();
        $this->_model = $model;
        $this->_transformers = array();

        $tableName = $model->getTableName();
        $this->_query = new Query();
        $this->_query->table = $tableName;

        $this->_query->selectColumns = ColumnAliasHandler::createSelectColumnsWithAliases("{$tableName}_", $model->_getFields(), $tableName);
    }

    /**
     * @return ModelQueryBuilder
     */
    public function where($where = '', $values = array())
    {
        $this->_query->whereClauses[] = new WhereClause($where, $values);
        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function order($columns)
    {
        $this->_query->order = $columns;
        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function offset($offset)
    {
        $this->_query->offset = $offset;
        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function limit($limit)
    {
        $this->_query->limit = $limit;
        return $this;
    }

    public function count()
    {
        return QueryExecutor::prepare($this->_db, $this->_query)->count();
    }

    /**
     * @return Model
     */
    public function fetch()
    {
        $result = QueryExecutor::prepare($this->_db, $this->_query)->fetch();
        if (!$result) {
            return null;
        }
        return !$this->_selectModel ? $result : Arrays::firstOrNull($this->_processResults(array($result)));
    }

    /**
     * @return Model[]
     */
    public function fetchAll()
    {
        $result = QueryExecutor::prepare($this->_db, $this->_query)->fetchAll();
        return !$this->_selectModel ? $result : $this->_processResults($result);
    }

    private function _transform($results)
    {
        foreach ($this->_transformers as $transformer) {
            $transformer->transform($results);
        }
        return $results;
    }

    private function _processResults($results)
    {
        $tableName = $this->_model->getTableName();

        $models = array();
        foreach ($results as $row) {
            $mainAttributes = ColumnAliasHandler::extractAttributesForPrefix($row, "{$tableName}_");
            $model = $this->_model->newInstance($mainAttributes);
            $models[] = $model;

            foreach ($this->_joinedRelations as $joinedRelation) {
                $joinDestinationField = $joinedRelation->getName();
                if ($joinDestinationField && !$joinedRelation->isCollection()) {
                    $joinedModel = $joinedRelation->getRelationModelObject();
                    $joinedTableName = $joinedModel->getTableName();
                    $joinedAttributes = ColumnAliasHandler::extractAttributesForPrefix($row, "{$joinedTableName}_");
                    if ($joinedAttributes[$joinedModel->getIdName()]) {
                        Objects::setValueRecursively($model, $joinDestinationField, $joinedModel->newInstance($joinedAttributes));
                    }
                }
            }
        }
        return $this->_transform($models);
    }

    public function deleteAll()
    {
        return QueryExecutor::prepare($this->_db, $this->_query)->delete();
    }

    public function deleteEach()
    {
        $objects = $this->fetchAll();
        return array_map(function ($object) {
            return !$object->delete();
        }, $objects);
    }

    /**
     * @return ModelQueryBuilder
     */
    public function join($relationName)
    {
        $relations = array();
        if ($relationName instanceof Relation) {
            $relations[] = $relationName;
        } else {
            $relationNames = explode('->', $relationName);
            $model = $this->_model;
            foreach ($relationNames as $name) {
                $relation = $model->getRelation($name);
                $relations[] = $relation;
                $model = $relation->getRelationModelObject();
            }
        }

        $field = '';
        $model = $this->_model;
        foreach ($relations as $relation) {
            $field = $field ? $field . '->' . $relation->getName() : $relation->getName();

            $relation = $relation->withName($field);

            $this->_joinedRelations[] = $relation;

            $joinedModel = $relation->getRelationModelObject();
            $joinTable = $joinedModel->getTableName();
            $joinKey = $relation->getForeignKey();
            $idName = $relation->getLocalKey();

            $this->_query->addJoin(new JoinClause($joinTable, $joinKey, $idName, $model->getTableName()));

            $this->_query->selectColumns = $this->_query->selectColumns + ColumnAliasHandler::createSelectColumnsWithAliases("{$joinTable}_", $joinedModel->_getFields(), $joinTable);

            $model = $relation->getRelationModelObject();
        }

        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function with($relationName)
    {
        $field = '';
        $model = $this->_model;

        $relationNames = explode('->', $relationName);
        foreach ($relationNames as $relationName) {
            $relation = $model->getRelation($relationName);
            $relationFetcher = new RelationFetcher($relation);
            $fieldTransformer = new FieldTransformer($field, $relationFetcher);

            $this->_transformers[] = $fieldTransformer;

            $model = $relation->getRelationModelObject();
            $field = $field ? $field . '->' . $relation->getName() : $relation->getName();
        }

        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function select($columns)
    {
        $this->_selectModel = false;
        $this->_query->selectColumns = is_array($columns) ? $columns : array($columns);
        $this->_query->selectType = PDO::FETCH_NUM;
        return $this;
    }

    function __clone()
    {
        $this->_query = clone $this->_query;
    }

    function copy()
    {
        return clone $this;
    }
}