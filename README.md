# laraversion

<p align="center">  Make Laravel model versionable.</p>

<p align="center">
<a href="https://packagist.org/packages/tal7aouy/laraversion"><img src="https://poser.pugx.org/tal7aouy/laraversion/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://scrutinizer-ci.com/g/tal7aouy/laraversion/?branch=master"><img src="https://scrutinizer-ci.com/g/tal7aouy/laraversion/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>
<a href="https://packagist.org/packages/tal7aouy/laraversion"><img src="https://poser.pugx.org/tal7aouy/laraversion/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tal7aouy/laraversion"><img src="https://poser.pugx.org/tal7aouy/laraversion/license" alt="License"></a>
</p>

It's a minimalist way to make your model support version history, and it's very simple to roll back to the specified version.

⚡️ Installing

```shell
composer require tal7aouy/laraversion -vvv
```

Optional, you can publish the config file:

```bash
php artisan vendor:publish --provider="Tal7aouy\Laraversion\ServiceProvider" --tag=config
```
Run the migrations.
```sh
php artisan migrate --path=vendor/mpociot/versionable/src/migrations
```
Alternatively, publish the migrations.

```bash
php artisan vendor:publish --provider="Tal7aouy\Laraversion\ServiceProvider" --tag=migrations
```

> After you published the migration files, please update `'migrations' => false` in the config file `config/versionable.php` to disable load the package migrations.

Then customize and run them.

```bash
php artisan migrate
```

⚡️ Requirement ⚡️

1. PHP >= 8.0.2
2. laravel/framework >= 9.0

⚡️ Features

-   Keep the specified number of versions.
-   Whitelist and blacklist for versionable attributes.
-   Easily roll back to the specified version.
-   Record only changed attributes.
-   Easy to customize.


⚡️ Usage

Add `Tal7aouy\Laraversion\Laraversion` trait to the model and set versionable attributes:

```php
use Tal7aouy\Laraversion\Versionable;

class Post extends Model
{
    use Versionable;

    /**
     * Versionable attributes
     *
     * @var array
     */
    protected $versionable = ['title', 'content'];

    <...>
}
```

Versions will be created on vensionable model saved.

```php
$post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
$post->update(['title' => 'version2']);
```

✅ versions

Get all versions

```php
$post->versions;
```

Get latest version

```php
$post->latestVersion;
// or
$post->lastVersion;
```

✅ Reversion

Reversion a model instance to the specified version:

```php
$post->getVersion(3)->revert();

// or

$post->revertToVersion(3);
```

🤓 Reversion without saving

```php
$version = $post->versions()->first();

$post = $version->revertWithoutSaving();
```

✅ Remove versions

```php
// soft delete
$post->removeVersion($versionId = 1);
$post->removeVersions($versionIds = [1, 2, 3]);
$post->removeAllVersions();

// force delete
$post->forceRemoveVersion($versionId = 1);
$post->forceRemoveVersions($versionIds = [1, 2, 3]);
$post->forceRemoveAllVersions();
```

✅ Restore deleted version by id

```php
$post->restoreTrashedVersion($id);
```

✅ Temporarily disable versioning

```php
// create
Post::withoutVersion(function () use (&$post) {
    Post::create(['title' => 'version1', 'content' => 'version1 content']);
});

// update
Post::withoutVersion(function () use ($post) {
    $post->update(['title' => 'updated']);
});
```

✅ Custom Version Store strategy

You can set the following different version policies through property `protected $versionStrategy`:

-   `Tal7aouy\Laraversion::DIFF` - Version content will only contain changed attributes (Default Strategy).
-   `Tal7aouy\Laraversion::SNAPSHOT` - Version content will contain all versionable attributes values.

🚀 Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/tal7aouy/laraversion/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/tal7aouy/laraversion/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

🚀 License

**Laraversion** was created by **[tal7aouy](https://github.com/tal7aouy)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
