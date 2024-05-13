<p align="center"><h1> Laravel Knight </h1></p>

<p align="center">
    <a href="https://github.com/hughcube-php/laravel-knight/actions?query=workflow%3ATest">
        <img src="https://github.com/hughcube-php/laravel-knight/workflows/Test/badge.svg" alt="Test Actions status">
    </a>
    <a href="https://github.com/hughcube-php/laravel-knight/actions?query=workflow%3ALint">
        <img src="https://github.com/hughcube-php/laravel-knight/workflows/Lint/badge.svg" alt="Lint Actions status">
    </a>
    <a href="https://styleci.io/repos/373508253">
        <img src="https://github.styleci.io/repos/373508253/shield?branch=master" alt="StyleCI">
    </a>
</p>

## Introduction
Laravel Knight is a helper extension for the [Laravel PHP framework](https://github.com/laravel/framework), designed to provide a more powerful and efficient development experience. The project aims to streamline the development process, optimize code structure, and offer a range of feature-rich tools to enhance the performance and maintainability of Laravel applications. With Laravel Knight, developers can build feature-rich web applications faster and reduce redundant work, making the development process more efficient.

## Installing
```shell
$ composer require hughcube/laravel-knight -vvv
```

## Usage
```php

# model
class User extends \HughCube\Laravel\Knight\Model{
    public function getCache() {
        return null;
    }
}

$user = User::findById(1);      // model of User
$user = User::findById(null);      // null
$users = User::noCacheQuery()->findByPks([1]); // collection of User

# action
class Action extends \HughCube\Laravel\Knight\Routing\Controller{
    public function action() {
        return $this->getOrSet(__METHOD__, function (){
            return 'HELLO WORLD';
        });
    }
}
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/hughcube-php/laravel-knight/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/hughcube-php/laravel-knight/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

Laravel Knight is open-sourced software licensed under the [MIT license](LICENSE).