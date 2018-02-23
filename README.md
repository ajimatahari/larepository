# Larepository



[![Build Status](https://travis-ci.org/BobMali/larepository.svg?branch=master)](https://travis-ci.org/BobMali/larepository)
[![Coverage Status](https://coveralls.io/repos/github/BobMali/larepository/badge.svg?branch=master)](https://coveralls.io/github/BobMali/larepository?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/3ea3fba8c85848c6960afbfbc815d2a6)](https://www.codacy.com/app/BobMali/larepository?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=BobMali/larepository&amp;utm_campaign=Badge_Grade)
[![Latest Stable Version](https://img.shields.io/packagist/v/mola/larepository.svg)](https://packagist.org/packages/mola/larepository)
[![MIT licensed](https://img.shields.io/crates/l/hyper.svg)](LICENSE.md)



A larvavel command for generating eloquent-repositories and their corresponding interface. I find the reposiory-pattern to be quite nice
to abstract the data-layer from the application itself,  so this artisan-command comes in handy.

---

## Install

The package can be installed via composer.
```bash
composer require mola/larepository
```

The service provider should be automatically added.
If not please add
```php 
Mola\Larepository\LarepositoryServiceProvider::class,
```
to the providers-array in your `config.php`.

If you want to change the default-paths publish the configuration-file.
``` bash
php artisan vendor:publish --provider "Mola\Larepository\LarepositoryServiceProvider"
```

## Compatibility

| Laravel  | Larepository  |
|---|---|
| 5.5.*  | v1.0.0  |


## Usage

The `make:repository`-command will create a new repository with an associated interface.
The command will create an abstract base-repository with a corresponding interface as well. This base-repository contains the basic crud-methods and will be extended by all following created repositories.

## Commands

There are two commands provided within this package.

### Make repository

A command to create a repository-interface and an implementing class.

#### Repository-command parameters

The command requires a `name`-parameter which will be the name of the repository (the suffix 'Repository' will be appended automatically).
A `Repositories`-directory will be created in the app-directory per default. The repositories will be placed there.
The default path can be overriden via the published configuration file.

The associated interface will be placed in the `Contracts`-directory in the app-directory per default. The default path can be overriden in the configuration file as well.

The `name`-parameter might be a Namespace as well, i.e.
```bash
php artisan make:repository Foo\\Bar
```
will create a `Foo`-directory within the default/configured repository-directory. Within this directory a `BarRepository.php` will be created.
The interface mirror that behaviour simply within the default/configured contracts-directory.


The command requires a `model`-parameter as the second input-parameter. Per default the command expects the models to be in the app-directory.
The path can be modified in the configuration-file.
The `model`-parameter can be a namespace as well. The command will look for it in the default/configured models-path.
To provide the User-model the command would be:
```bash
php artisan make:repository Foo\\Bar User
```

If your models reside in another directory-structure simply add the namespace to the parameter like so:
```bash
php artisan make:repository Foo\\Bar Bla\\Blub\\User
```

If you have a models-directory where all models reside in you should state the path in the published configuration-file.
If your models-directory is `Models` for example and the directory was added in the configuration-file, all following commands will search for provided Models within this directory.

### Make interface

The package comes with a `make:interface`-command. This command automatically creates an interface.

#### Interface-command parameters

The command requires a `name`-parameter which will be the name of the created interface (the suffix `Interface` will be appended automatically).
The created interface will be placed in the `Contracts`-directory in the app-directory per default. The default path can be overriden in the configuration file as well.

Interfaces can be created like this:
```bash
php artisan make:interface Foo\\Bar
```

The provided name may be a namespace as well. The above command would create the directory `Foo` and place a `BarInterface.php` within that directory.

## Methods

The following methods are contained within every repository:

#### Create
The create method accepts an array with data to map on an eloquent-model. It returns a boolean.

```php
$isCreated = $this->repository->create(['foo' => 'bar']);
```

#### Retrieve all entries
The `findAll`-method retrieves all records from the corresponding table. It returns the records within a [Laravel collection](https://laravel.com/docs/5.5/eloquent-collections) ([API-documentation here](https://laravel.com/api/5.5/Illuminate/Database/Eloquent/Collection.html)).

```php
$allEntries = $this->repository->findAll();
```

#### Retrieve single record
The `findOne`-method retrieves a single record via a provided id. It returns an eloquent model.

```php
$singleEntries = $this->repository->findOne(123);
```

#### Update single record
The `update`-method retrieves a single record via a provided id and updates it via the provided update data. It returns a boolean on success or failure.
By finding the record first and then updating it the [update-events](https://laravel.com/docs/5.6/eloquent#events) are preserved.
The method will throw a [`ModelNotFoundException`](https://laravel.com/api/5.5/Illuminate/Database/Eloquent/ModelNotFoundException.html) if no record with the provided id is found.

```php
$isUpdated = $this->repository->update(123, ['foo' => 'bar']);
```
#### Delete single record
The `delete`-method deletes a single record via the provided id. It returns a boolean on success or failure.
By finding the record first and then deleting it the [delete-events](https://laravel.com/docs/5.6/eloquent#events) are preserved.

```php
$isDeleted = $this->repository->delete(123);
```

