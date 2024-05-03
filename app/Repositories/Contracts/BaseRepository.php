<?php

//app/Repositories/Contracts/BaseRepository.php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepository {

    /**
     * Find a resource by id
     *
     * @param $id
     * @return Model|null
     */
    public function findOne($id);

    /**
     * Find a resource by criteria
     *
     * @param array $criteria
     * @return Model|null
     */
    public function findOneBy(array $criteria);

    /**
     * Search All resources by criteria
     *
     * @param array $searchCriteria
     * @return Collection
     */
    public function findBy(array $searchCriteria = []);

    /**
     * Search All resources by any values of a key
     *
     * @param string $key
     * @param array $values
     * @return Collection
     */
    public function findIn($key, array $values);

    /**
     * Save a resource
     *
     * @param array $data
     * @return Model
     */
    public function save(array $data);

    /**
     * Update a resource
     *
     * @param Model $model
     * @param array $data
     * @return Model
     */
    public function update(Model $model, array $data);

    /**
     * Delete a resource
     *
     * @param Model $model
     * @return mixed
     */
    public function delete(Model $model);

    /**
     * Find by column
     * @param type $colum
     * @param type $value
     */
    public function findByColumn($colum, $value);

    /**
     * Get single record with relashions
     * @param array $data
     * @param array $relashions
     */
    public function findOneWithRelations(array $data, array $relashions);

    /**
     * get values with selected columns
     * @param type $key
     * @param array $values
     * @param array $colums
     */
    public function findInBySelectedColumns($key, array $values, array $colums);
}
