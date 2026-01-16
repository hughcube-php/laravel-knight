<h1><p align="center"> Laravel Knight </p></h1>

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
Laravel Knight is a powerful helper extension for the [Laravel PHP framework](https://github.com/laravel/framework), designed to provide a more robust and efficient development experience. It aims to streamline the development process, optimize code structure, and offer a range of feature-rich tools to enhance the performance, maintainability, and reliability of Laravel applications. With Laravel Knight, developers can build feature-rich web applications faster, reduce redundant work, and handle common challenges like data concurrency more effectively.

## Features

*   **Enhanced Eloquent Models**: Provides advanced caching mechanisms, optimistic locking, and convenient data manipulation methods.
*   **Optimized Query Builder**: Extends Laravel's query builder with powerful search and filtering capabilities.
*   **Controller Utilities**: Offers helpful methods for caching controller actions and managing request parameters.
*   **Robust Caching**: Implements intelligent caching strategies to reduce database load and improve response times.
*   **Optimistic Locking**: A built-in solution to prevent data conflicts in concurrent update scenarios.
*   **Code Quality Tools Integration**: Integrates with PHPUnit, PHPStan, and PHP_CodeSniffer for robust testing and code analysis.

## Installing
```shell
$ composer require hughcube/laravel-knight -vvv
```

## Usage

Laravel Knight provides several powerful Traits and classes to enhance your Laravel application.

### 1. Model Enhancements (`HughCube\Laravel\Knight\Database\Eloquent\Model` & Traits)

You can extend `HughCube\Laravel\Knight\Database\Eloquent\Model` or `use` specific traits in your Eloquent Models to gain additional functionalities.

#### Basic Model Usage (with Caching)

Extend `HughCube\Laravel\Knight\Database\Eloquent\Model` to get caching and other utilities. You need to implement `getCache()` method to provide a cache store.

```php
<?php

namespace App\Models;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Psr\SimpleCache\CacheInterface;
use Illuminate\Support\Facades\Cache;

class User extends Model
{
    // Implement this method to return your desired cache store
    public function getCache(): ?CacheInterface
    {
        return Cache::store('redis'); // Example: using redis cache
    }

    // Example: find a user by ID, will use cache if available
    public static function findUserById(int $id): ?self
    {
        return static::findById($id);
    }

    // Example: find multiple users by IDs, will use cache if available
    public static function findUsersByIds(array $ids): \HughCube\Laravel\Knight\Database\Eloquent\Collection
    {
        return static::findByIds($ids);
    }

    // Example: get a query builder that bypasses cache
    public static function getUsersWithoutCache()
    {
        return static::noCacheQuery();
    }
}

// Usage
$user = User::findUserById(1); // Fetches from cache or database
$users = User::findUsersByIds([1, 2, 3]); // Fetches from cache or database
$userFromDb = User::getUsersWithoutCache()->find(1); // Always fetches from database
```

#### Optimistic Locking

To add optimistic locking to any Eloquent Model, simply `use` the `OptimisticLock` trait and ensure your database table has a `data_version` column (unsigned big integer with a default value, e.g., `1`).

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;
use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;

class Product extends Model
{
    use OptimisticLock;

    protected $fillable = ['name', 'price', 'data_version'];

    // ...
}

// In your migration:
// $table->unsignedBigInteger('data_version')->default(1);

// Usage:
$product = Product::find(1);
if ($product) {
    $product->price = 100.50;

    try {
        $product->save(); // Automatically handles optimistic locking and increments data_version
        echo "Product updated successfully!\n";
    } catch (OptimisticLockException $e) {
        echo "Failed to update: " . $e->getMessage() . " (The record was modified by another process).\n";
        // Handle concurrency conflict, e.g., reload data and ask user to retry
    } catch (\Throwable $e) {
        echo "An unexpected error occurred: " . $e->getMessage() . "\n";
    }
}

// You can temporarily disable optimistic lock for a specific save operation:
$product = Product::find(2);
if ($product) {
    $product->name = 'New Name (No Lock)';
    $product->disableOptimisticLock()->save(); // This save will bypass optimistic locking
    echo "Product updated without optimistic lock.\n";
}
```

### 2. Query Builder Enhancements (`HughCube\Laravel\Knight\Database\Eloquent\Traits\Builder`)

This trait extends Laravel's Eloquent Query Builder with additional helpful methods. These methods are available when you use `HughCube\Laravel\Knight\Database\Eloquent\Model` or when you explicitly use the `Builder` trait in your custom query builders.

```php
<?php

namespace App\Models;

use HughCube\Laravel\Knight\Database\Eloquent\Model; // This model already uses the Builder trait implicitly

class Order extends Model
{
    // ...
}

// Usage:
// Find orders with LIKE pattern (no auto wildcards)
$orders = Order::query()->whereLike('name', '%keyword%')->get();

// Find orders with escaped "contains" match
$orders = Order::query()->whereEscapeLike('name', 'keyword')->get();

// Find orders with escaped prefix match
$orders = Order::query()->whereEscapeLeftLike('name', 'prefix')->get();

// Find orders created within a date range
$startDate = '2023-01-01';
$endDate = '2023-12-31';
$orders = Order::query()->whereRange('created_at', [$startDate, $endDate])->get();

// Find available (not soft-deleted) orders
$availableOrders = Order::availableQuery()->get();
```

### 3. Controller Utilities (`HughCube\Laravel\Knight\Routing\Controller` & `HughCube\Laravel\Knight\Knight\Routing\Action`)

Extend `HughCube\Laravel\Knight\Routing\Controller` or `use` the `Action` trait in your controllers to leverage caching and other request-related utilities.

```php
<?php

namespace App\Http\Controllers;

use HughCube\Laravel\Knight\Routing\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, int $id)
    {
        // Cache the result of this action for 60 seconds
        $productData = $this->getOrSet(
            $this->getActionCacheKey(__METHOD__), // Unique cache key for this action and parameters
            function () use ($id) {
                // Simulate fetching product data from database
                return ['id' => $id, 'name' => 'Cached Product', 'description' => 'This data is cached!'];
            },
            60 // Cache TTL in seconds
        );

        return response()->json($productData);
    }

    public function update(Request $request, int $id)
    {
        // After updating a product, you might want to clear its cache
        $this->forget($this->getActionCacheKey('ProductController@show', ['id' => $id]));

        return response()->json(['message' => 'Product updated and cache cleared.']);
    }
}
```

## Contributing

You can contribute in one of three ways:

1.  File bug reports using the [issue tracker](https://github.com/com/hughcube-php/laravel-knight/issues).
2.  Answer questions or fix bugs on the [issue tracker](https://github.com/com/hughcube-php/laravel-knight/issues).
3.  Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

Laravel Knight is open-sourced software licensed under the [MIT license](LICENSE).
