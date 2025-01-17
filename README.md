# CakeSoftDelete plugin for Cakephp4.* (Upgrade for CakePHP2's SoftDeletableBehavior)

## Fork

This repo is forked from not maintained [salines/cakephp4-soft-delete](https://github.com/salines/cakephp4-soft-delete) and updated to avoid deprecated code in CakePHP >=4.0

## Purpose

This Cakephp plugin enables you to make your models soft deletable.
When soft deleting an entity, it is not actually removed from your database. 
Instead, a `deleted` int2 is set 1 on the record.
AND a `deleted_date` timestamp as string is set on the record.(`deleted_date` Default ='0')

## Requirements

This Trait has been developed for cakephp >=4.0 and PHP >=8.0

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

Update your composer file to include this plugin:

```
composer require aokazu/cakephp4-soft-delete "2.*"
```

## Configuration

### Make a model soft deleteable:

Use the SoftDelete trait on your model Table class:

```
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;

class UsersTable extends Table
{
    use SoftDeleteTrait;
    ...
```


## Use

### Soft deleting records

`delete` and `deleteAll` functions will now soft delete records by populating `deleted` field with the date of the deletion.

```php
// in src/Model/Table/UsersTable.php
$this->delete($user); // $user entity is now soft deleted if UsersTable uses SoftDeleteTrait.
```

### Restoring Soft deleted records

To restore a soft deleted entity into an active state, use the `restore` method:

```php
// in src/Model/Table/UsersTable.php
// Let's suppose $user #1 is soft deleted.
$user = $this->Users->find('all', ['withDeleted'])->where('id', 1)->first();
$this->restore($user); // $user #1 is now restored.
```

### Finding records

`find`, `get` or dynamic finders (such as `findById`) will only return non soft deleted records.
To also return soft deleted records, `$options` must contain `'withDeleted'`. Example:

```php
// in src/Model/Table/UsersTable.php
$nonSoftDeletedRecords = $this->find('all');
$allRecords            = $this->find('all', ['withDeleted']);
```

### Hard deleting records

To hard delete a single entity:
```php
// in src/Model/Table/UsersTable.php
$user = $this->get($userId);
$success = $this->hardDelete($user);
```

To mass hard delete records that were soft deleted before a given date, you can use hardDeleteAll($date):

```
// in src/Model/Table/UsersTable.php
$date = new \DateTime('some date');
$affectedRowsCount = $this->hardDeleteAll($date);
```

## Soft deleting & associations

Associations are correctly handled by SoftDelete plugin.

1. Soft deletion will be cascaded to related models as usual. If related models also use SoftDelete Trait, they will be soft deleted.
2. Soft deletes records will be excluded from counter caches.
