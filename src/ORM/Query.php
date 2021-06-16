<?php
/** @noinspection PhpUnused */

namespace SoftDelete\ORM;

use Cake\ORM\Query as CakeQuery;

class Query extends CakeQuery
{
  /**
   * Cake\ORM\Query::triggerBeforeFind overwritten to add the condition `deleted =0` to every find request
   * in order not to return soft deleted records.
   * If the query contains the option `withDeleted` the condition `deleted =0` is not applied.
   */
  public function triggerBeforeFind(): void
  {
    if (!$this->_beforeFindFired && $this->_type === 'select') {
      parent::triggerBeforeFind();
      if (!is_array($this->getOptions()) || !in_array('withDeleted', $this->getOptions())) {
        $this->andWhere('deleted=0');
      }
    }
  }
}
