<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Db;

use Iterator;
use Ouzo\Db;
use Ouzo\DbException;
use Ouzo\Model;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Iterator\BatchingIterator;
use Ouzo\Utilities\Iterator\TransformingIterator;
use Ouzo\Utilities\Iterator\UnbatchingIterator;
use PDO;

class ModelQueryBuilder
{
    CONST MODEL_QUERY_MARKER_COMMENT = 'orm:model';

    /** @var Db */
    private $_db;

    /** @var Model */
    private $_model;

    /** @var ModelJoin[] */
    private $_joinedModels = [];

    /** @var RelationToFetch[] */
    private $_relationsToFetch = [];

    /** @var Query */
    private $_query;

    /** @var bool */
    private $_selectModel = true;

    public function __construct(Model $model, Db $db = null, $alias = null)
    {
        $this->_db = $db ? $db : Db::getInstance();
        $this->_model = $model;

        $this->_query = new Query();
        $this->_query->table = $model->getTableName();
        $this->_query->aliasTable = $alias;
        $this->_query->selectType = PDO::FETCH_NUM;
        $this->_query->selectColumns = [];
        $this->selectModelColumns($model, $this->getModelAliasOrTable());
    }

    private function getModelAliasOrTable()
    {
        return $this->_query->aliasTable ?: $this->_model->getTableName();
    }

    private function selectModelColumns(Model $metaInstance, $alias)
    {
        if ($this->_selectModel) {
            $this->_query->selectColumns = array_merge($this->_query->selectColumns, ColumnAliasHandler::createSelectColumnsWithAliases($metaInstance->_getFields(), $alias));
        }
    }

    /**
     * @param string $where
     * @param array $values
     * @return ModelQueryBuilder
     */
    public function where($where = '', $values = [])
    {
        $this->_query->where($where, $values);
        return $this;
    }

    /**
     * @param $columns
     * @return ModelQueryBuilder
     */
    public function order($columns)
    {
        $this->_query->order = $columns;
        return $this;
    }

    /**
     * @param $offset
     * @return ModelQueryBuilder
     */
    public function offset($offset)
    {
        $this->_query->offset = $offset;
        return $this;
    }

    /**
     * @param $limit
     * @return ModelQueryBuilder
     */
    public function limit($limit)
    {
        $this->_query->limit = $limit;
        return $this;
    }

    /**
     * @return ModelQueryBuilder
     */
    public function lockForUpdate()
    {
        $this->_query->lockForUpdate = true;
        return $this;
    }

    public function count()
    {
        $this->_query->type = QueryType::$COUNT;
        $value = QueryExecutor::prepare($this->_db, $this->_query)->fetch();
        return intval(Arrays::firstOrNull(Arrays::toArray($value)));
    }


    private function beforeSelect()
    {
        if ($this->_selectModel) {
            $this->_query->comment(ModelQueryBuilder::MODEL_QUERY_MARKER_COMMENT);
        }
    }

    /**
     * @return Model|array
     */
    public function fetch()
    {
        $this->beforeSelect();
        $result = QueryExecutor::prepare($this->_db, $this->_query)->fetch();
        if (!$result) {
            return null;
        }
        return !$this->_selectModel ? $result : Arrays::firstOrNull($this->_processResults([$result]));
    }

    /**
     * @return Model[]|array[]
     */
    public function fetchAll()
    {
        $this->beforeSelect();
        $result = QueryExecutor::prepare($this->_db, $this->_query)->fetchAll();
        return !$this->_selectModel ? $result : $this->_processResults($result);
    }

    /**
     * @param int $batchSize
     * @return Iterator
     */
    public function fetchIterator($batchSize = 500)
    {
        $this->beforeSelect();
        $iterator = QueryExecutor::prepare($this->_db, $this->_query)->fetchIterator();
        $iterator->rewind();
        return !$this->_selectModel ? $iterator : new UnbatchingIterator(new TransformingIterator(new BatchingIterator($iterator, $batchSize), [$this, '_processResults']));
    }

    function _processResults($results)
    {
        $resultSetConverter = new ModelResultSetConverter($this->_model, $this->getModelAliasOrTable(), $this->_joinedModels, $this->_relationsToFetch);
        $converted = $resultSetConverter->convert($results);
        BatchLoadingSession::attach($converted);
        return $converted;
    }

    /**
     * Issues "delete from ... where ..." sql command.
     * Note that overridden Model::delete is not called.
     */
    public function deleteAll()
    {
        $this->_query->type = QueryType::$DELETE;
        return QueryExecutor::prepare($this->_db, $this->_query)->execute();
    }

    /**
     * Calls Model::delete method for each matching object
     */
    public function deleteEach()
    {
        $objects = $this->fetchAll();
        return Arrays::map($objects, function (Model $object) {
            return !$object->delete();
        });
    }

    /**
     * Runs an update query against a set of models
     * @param array $attributes
     * @return int
     */
    public function update(array $attributes)
    {
        $this->_query->type = QueryType::$UPDATE;
        $this->_query->updateAttributes = $attributes;
        return QueryExecutor::prepare($this->_db, $this->_query)->execute();
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @param null $aliases - alias of the first joined table or array of aliases for nested joins
     * @param string $type - join type, defaults to LEFT
     * @param array $on
     * @return ModelQueryBuilder
     */
    public function join($relationSelector, $aliases = null, $type = 'LEFT', $on = [])
    {
        $modelJoins = $this->createModelJoins($relationSelector, $aliases, $type, $on);
        foreach ($modelJoins as $modelJoin) {
            $this->addJoin($modelJoin);
        }
        return $this;
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @param null $aliases - alias of the first joined table or array of aliases for nested joins
     * @param array $on
     * @return ModelQueryBuilder
     */
    public function innerJoin($relationSelector, $aliases = null, $on = [])
    {
        return $this->join($relationSelector, $aliases, 'INNER', $on);
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @param null $aliases - alias of the first joined table or array of aliases for nested joins
     * @param array $on
     * @return ModelQueryBuilder
     */
    public function rightJoin($relationSelector, $aliases = null, $on = [])
    {
        return $this->join($relationSelector, $aliases, 'RIGHT', $on);
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @param null $aliases - alias of the first joined table or array of aliases for nested joins
     * @param array $on
     * @return ModelQueryBuilder
     */
    public function leftJoin($relationSelector, $aliases = null, $on = [])
    {
        return $this->join($relationSelector, $aliases, 'LEFT', $on);
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @param null $aliases - alias of the first joined table or array of aliases for nested joins
     * @return ModelQueryBuilder
     */
    public function using($relationSelector, $aliases)
    {
        $modelJoins = $this->createModelJoins($relationSelector, $aliases, 'USING', []);
        foreach ($modelJoins as $modelJoin) {
            $this->_query->addUsing($modelJoin->asJoinClause());
        }
        return $this;
    }

    private function addJoin(ModelJoin $modelJoin)
    {
        if (!$this->isAlreadyJoined($modelJoin)) {
            $this->_query->addJoin($modelJoin->asJoinClause());
            $this->_joinedModels[] = $modelJoin;
            if ($modelJoin->storeField()) {
                $this->selectModelColumns($modelJoin->getModelObject(), $modelJoin->alias());
            }
        }
    }

    private function isAlreadyJoined(ModelJoin $modelJoin)
    {
        return Arrays::any($this->_joinedModels, ModelJoin::equalsPredicate($modelJoin));
    }

    /**
     * @param $relationSelector - Relation object, relation name or nested relations 'rel1->rel2'
     * @return ModelQueryBuilder
     */
    public function with($relationSelector)
    {
        if (!BatchLoadingSession::isAllocated()) {
            $relations = ModelQueryBuilderHelper::extractRelations($this->_model, $relationSelector);
            $field = '';

            foreach ($relations as $relation) {
                $nestedField = $field ? $field . '->' . $relation->getName() : $relation->getName();
                $this->_addRelationToFetch(new RelationToFetch($field, $relation, $nestedField));
                $field = $nestedField;
            }
        }
        return $this;
    }

    private function _addRelationToFetch($relationToFetch)
    {
        if (!$this->isAlreadyAddedToFetch($relationToFetch)) {
            $this->_relationsToFetch[] = $relationToFetch;
        }
    }

    private function isAlreadyAddedToFetch(RelationToFetch $relationToFetch)
    {
        return Arrays::any($this->_relationsToFetch, RelationToFetch::equalsPredicate($relationToFetch));
    }

    /**
     * @param $columns
     * @param int $type
     * @return ModelQueryBuilder
     */
    public function select($columns, $type = PDO::FETCH_NUM)
    {
        $this->_selectModel = false;
        $this->_query->selectColumns = Arrays::toArray($columns);
        $this->_query->selectType = $type;
        return $this;
    }

    /**
     * @param $columns
     * @param int $type
     * @return ModelQueryBuilder
     */
    public function selectDistinct($columns, $type = PDO::FETCH_NUM)
    {
        $this->_query->distinct = true;
        return $this->select($columns, $type);
    }

    public function __clone()
    {
        $this->_query = clone $this->_query;
    }

    public function copy()
    {
        return clone $this;
    }

    public function options(array $options)
    {
        $this->_query->options = $options;
        return $this;
    }

    public function groupBy($groupBy)
    {
        if ($this->_selectModel) {
            throw new DbException("Cannot use group by without specifying columns.\n"
                . "e.g. Model::select('column, count(*)')->groupBy('column')->fetchAll();");
        }

        $this->_query->groupBy = $groupBy;
        return $this;
    }

    public function getQuery()
    {
        return $this->_query;
    }

    private function createModelJoins($relationSelector, $aliases, $type, $on)
    {
        $relations = ModelQueryBuilderHelper::extractRelations($this->_model, $relationSelector);
        $relationWithAliases = ModelQueryBuilderHelper::associateRelationsWithAliases($relations, $aliases);
        return ModelQueryBuilderHelper::createModelJoins($this->getModelAliasOrTable(), $relationWithAliases, $type, $on);
    }
}
