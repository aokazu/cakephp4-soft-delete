<?php

/** @noinspection PhpUnused */

namespace SoftDelete\Model\Table;

use ArrayObject;
use Cake\Core\Exception\CakeException;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use Datetime;
use InvalidArgumentException;
use SoftDelete\Error\MissingColumnException;
use SoftDelete\ORM\Query;

trait SoftDeleteTrait
{
  /**
   * Get the configured deletion field
   *
   * @return string
   */
  public function getSoftDeleteField(): string
  {
    if (isset($this->softDeleteField)) {
      $field = $this->softDeleteField;
    } else {
      $field = 'deleted';
    }

    if ($this->getSchema()->getColumn($field) === null) {
      throw new MissingColumnException(
        __('Configured field `{0}` is missing from the table `{1}`.',
          $field,
          $this->getAlias()
        )
      );
    }

    return $field;
  }

  /** @noinspection PhpParamsInspection */
  public function query(): Query
  {
    return new \Cake\ORM\Query($this->getConnection(), $this);
  }

  /**
   * Perform the delete operation.
   *
   * Will soft delete the entity provided. Will remove rows from any
   * dependent associations, and clear out join tables for BelongsToMany associations.
   *
   * @param \Cake\DataSource\EntityInterface $entity The entity to soft delete.
   * @param ArrayObject $options The options for the delete.
   * @return bool success
   * @throws InvalidArgumentException if there are no primary key values of the
   * passed entity
   */
  protected function _processDelete(EntityInterface $entity, ArrayObject $options): bool
  {
    if ($entity->isNew()) {
      return false;
    }

    $primaryKey = (array)$this->getPrimaryKey();
    if (!$entity->has($primaryKey)) {
      $msg = 'Deleting requires all primary key values.';
      throw new InvalidArgumentException($msg);
    }

    if ($options['checkRules'] && !$this->checkRules($entity, RulesChecker::DELETE, $options)) {
      return false;
    }
    /** @var Event $event */
    $event = $this->dispatchEvent(
      'Model.beforeDelete',
      [
        'entity' => $entity,
        'options' => $options
      ]
    );

    if ($event->isStopped()) {
      return $event->getResult();
    }

    $this->_associations->cascadeDelete(
      $entity,
      ['_primary' => false] + $options->getArrayCopy()
    );

    $query = $this->query();
    $conditions = $entity->extract($primaryKey);
    $statement = $query->update()
      ->set(['deleted_date' => date('Y-m-d H:i:s'), 'deleted' => 1])
      ->where($conditions)
      ->execute();

    $success = $statement->rowCount() > 0;
    if ($success) {
      $this->dispatchEvent(
        'Model.afterDelete',
        [
          'entity' => $entity,
          'options' => $options
        ]
      );
    }
    return $success;
  }

  /**
   * Soft deletes all records matching `$conditions`.
   * @return int number of affected rows.
   */
  public function deleteAll($conditions): int
  {
    $query = $this->query()
      ->update()
      ->set(['deleted_date' => date('Y-m-d H:i:s'), 'deleted' => 1])
      ->where($conditions);
    $statement = $query->execute();
    $statement->closeCursor();
    return $statement->rowCount();
  }

  /**
   * Hard deletes the given $entity.
   * @return bool true in case of success, false otherwise.
   */
  public function hardDelete(EntityInterface $entity): bool
  {
    if (!$this->delete($entity)) {
      return false;
    }
    $primaryKey = (array)$this->getPrimaryKey();
    $query = $this->query();
    $conditions = $entity->extract($primaryKey);
    $statement = $query->delete()
      ->where($conditions)
      ->execute();

    return $statement->rowCount() > 0;
  }

  /**
   * Hard deletes all records that were soft deleted before a given date.
   * @param DateTime $until Date until witch soft deleted records must be hard deleted.
   * @return int number of affected rows.
   */
  public function hardDeleteAll(Datetime $until): int
  {
    $query = $this->query()
      ->delete()
      ->where(
        [
          "deleted<>0",
          'deleted_date <=' => $until->format('Y-m-d H:i:s')
        ]
      );
    $statement = $query->execute();
    $statement->closeCursor();
    return $statement->rowCount();
  }

  /**
   * Restore a soft deleted entity into an active state.
   * @param EntityInterface $entity Entity to be restored.
   * @return bool true in case of success, false otherwise.
   */
  public function restore(EntityInterface $entity): bool
  {
    $entity->set('deleted', 0);
    $entity->set('deleted_date', '0');
    return $this->save($entity);
  }
}
