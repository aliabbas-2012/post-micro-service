<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use League\Fractal\Manager;

abstract class AbstractEloquentRepository implements BaseRepository {

    /**
     * Name of the Model with absolute namespace
     *
     * @var string
     */
    protected $modelName;

    /**
     * Instance that extends Illuminate\Database\Eloquent\Model
     *
     * @var Model
     */
    protected $model;

    /**
     * get logged in user
     *
     * @var User $loggedInUser
     */
    protected $loggedInUser;

    /**
     * Constructor
     */
    public function __construct(Model $model) {
        $this->model = $model;
        $this->loggedInUser = $this->getLoggedInUser();
    }

    /**
     * Get Model instance
     *
     * @return Model
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * @inheritdoc
     */
    public function findOne($id) {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function findOneBy(array $criteria) {
        return $this->model->where($criteria)->first();
    }

    /**
     * @inheritdoc
     */
    public function findBy(array $searchCriteria = []) {
        $limit = !empty($searchCriteria['per_page']) ? (int) $searchCriteria['per_page'] : 40; // it's needed for pagination

        $queryBuilder = $this->model->where(function ($query) use ($searchCriteria) {

            $this->applySearchCriteriaInQueryBuilder($query, $searchCriteria);
        }
        );
        return $queryBuilder->paginate($limit);
    }

    /**
     * Apply condition on query builder based on search criteria
     *
     * @param Object $queryBuilder
     * @param array $searchCriteria
     * @return mixed
     */
    protected function applySearchCriteriaInQueryBuilder($queryBuilder, array $searchCriteria = []) {

        foreach ($searchCriteria as $key => $value) {

            //skip pagination related query params
            if (in_array($key, ['page', 'per_page'])) {
                continue;
            }

            //we can pass multiple params for a filter with commas
            $allValues = explode(',', $value);

            if (count($allValues) > 1) {
                $queryBuilder->whereIn($key, $allValues);
            } else {
                $operator = '=';
                $queryBuilder->where($key, $operator, $value);
            }
        }

        return $queryBuilder;
    }

    /**
     * @inheritdoc
     */
    public function save(array $data) {
        return $this->model->create($data);
    }

    /**
     * @inheritdoc
     */
    public function update(Model $model, array $data) {
        $fillAbleProperties = $this->model->getFillable();
        foreach ($data as $key => $value) {
            // update only fillAble properties
            if (in_array($key, $fillAbleProperties)) {
                $model->$key = $value;
            }
        }
        // update the model
        $model->save();

        // get updated model from database

        $model = $this->findOneBy(['id' => $model->id]);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function findIn($key, array $values) {
        return $this->model->whereIn($key, $values)->get();
    }

    /**
     * @inheritdoc
     */
    public function delete(Model $model) {
        return $model->delete();
    }

    /**
     * This will delete row from DB table with respect to conditions defined
     * @param type $conditions
     * @return type
     */
    public function deleteByAtrributes($conditions) {
        return $this->model->where($conditions)->delete();
    }

    /**
     * get loggedIn user
     *
     * @return User
     */
    protected function getLoggedInUser() {
        $user = \Auth::user();

        if ($user instanceof User) {
            return $user;
        } else {
            return new User();
        }
    }

    /**
     * @inheritdoc
     */
    public function findOneWithRelations(array $criteria, array $relations) {

        $record = $this->model->where($criteria);
        if (!empty($relations)) {
            $record->with($relations);
        }
        return $record->first();
    }

    /**
     * Get data by certain colum condition
     * @param type $colum
     * @param type $value
     * @return boolean
     */
    public function findByColumn($colum, $value) {
        if ($user = self::findOneWithRelations([$colum => $value], ['notificationSetting'])) {
            return $user;
        }
        return false;
    }

    /**
     * get users with ids with selected column
     * @param type $key
     * @param array $values
     * @return type
     */
    public function findInBySelectedColumns($key, array $values, array $colums) {
        return $this->model->select($colums)->whereIn($key, $values)->get();
    }

    public function intiRepoObject($class, $model) {
        return new $class($model);
    }

}
