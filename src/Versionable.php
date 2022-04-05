<?php

namespace Tal7aouy\Laraversion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Versionable
{
    protected static bool $versioning = true;

    protected bool $forceDeleteVersion = false;

    // You can add these properties to you versionable model
    //protected $versionable = [];
    //protected $dontVersionable = ['*'];

    public static function bootVersionable()
    {
        static::saved(
            function (Model $model) {
                static::createVersionForModel($model);
            }
        );

        static::deleted(
            function (Model $model) {
                /* @var \Tal7aouy\Laraversion\Versionable|Model $model */
                if ($model->forceDeleting) {
                    $model->forceRemoveAllVersions();
                } else {
                    static::createVersionForModel($model);
                }
            }
        );
    }

    private static function createVersionForModel(Model $model): void
    {
        /* @var \Tal7aouy\Laraversion\Versionable|Model $model */
        if (static::$versioning && $model->shouldVersioning()) {
            Version::createForModel($model);
            $model->removeOldVersions($model->getKeepVersionsCount());
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(\config('versionable.version_model'), 'versionable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function lastVersion(): MorphOne
    {
        return $this->latestVersion();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function latestVersion(): MorphOne
    {
        return $this->morphOne(\config('versionable.version_model'), 'versionable')->latest('id');
    }

    public function firstVersion(): ?Model
    {
        return $this->versions()->oldest('id')->first();
    }

    /**
     * @param  int  $id
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getVersion(int $id)
    {
        return $this->versions()->find($id);
    }

    public function getThrashedVersions()
    {
        return $this->versions()->onlyTrashed()->get();
    }

    public function restoreTrashedVersion(int $id)
    {
        return $this->versions()->onlyTrashed()->whereId($id)->restore();
    }

    /**
     * @param  int  $id
     *
     * @return mixed
     */
    public function revertToVersion(int $id)
    {
        return $this->versions()->findOrFail($id)->revert();
    }

    /**
     * @param  int  $keep
     */
    public function removeOldVersions(int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        $this->versions()->skip($keep)->take(PHP_INT_MAX)->get()->each->delete();
    }

    public function removeVersions(array $ids)
    {
        if ($this->forceDeleteVersion) {
            return $this->forceRemoveVersions($ids);
        }

        return $this->versions()->find($ids)->each->delete();
    }

    public function removeVersion(int $id)
    {
        if ($this->forceDeleteVersion) {
            return $this->forceRemoveVersion($id);
        }

        return $this->versions()->findOrFail($id)->delete();
    }

    public function removeAllVersions()
    {
        if ($this->forceDeleteVersion) {
            $this->forceRemoveAllVersions();
        }

        $this->versions->each->delete();
    }

    public function forceRemoveVersion(int $id)
    {
        return $this->versions()->findOrFail($id)->forceDelete();
    }

    public function forceRemoveVersions(array $ids)
    {
        return $this->versions()->find($ids)->each->forceDelete();
    }

    public function forceRemoveAllVersions()
    {
        $this->versions->each->forceDelete();
    }

    /**
     * @return bool
     */
    public function shouldVersioning(): bool
    {
        return !empty($this->getVersionableAttributes());
    }

    /**
     * @return array
     */
    public function getVersionableAttributes(): array
    {
        $changes = $this->getDirty();

        if (empty($changes)) {
            return [];
        }

        $contents = $this->attributesToArray();

        if ($this->getVersionStrategy() == VersionStrategy::DIFF) {
            $contents = $this->only(\array_keys($changes));
        }

        return $this->versionableFromArray($contents);
    }

    /**
     * @param  array  $attributes
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setVersionable(array $attributes)
    {
        if (!\property_exists($this, 'versionable')) {
            throw new \Exception('Property $versionable not exist.');
        }

        $this->versionable = $attributes;

        return $this;
    }

    /**
     * @param  array  $attributes
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setDontVersionable(array $attributes)
    {
        if (!\property_exists($this, 'dontVersionable')) {
            throw new \Exception('Property $dontVersionable not exist.');
        }

        $this->dontVersionable = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getVersionable(): array
    {
        return \property_exists($this, 'versionable') ? $this->versionable : [];
    }

    /**
     * @return array
     */
    public function getDontVersionable(): array
    {
        return \property_exists($this, 'dontVersionable') ? $this->dontVersionable : [];
    }

    /**
     * @return string
     */
    public function getVersionStrategy()
    {
        return \property_exists($this, 'versionStrategy') ? $this->versionStrategy : VersionStrategy::DIFF;
    }

    /**
     * @param  string  $strategy
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setVersionStrategy(string $strategy)
    {
        if (!\property_exists($this, 'versionStrategy')) {
            throw new \Exception('Property $versionStrategy not exist.');
        }

        $this->versionStrategy = $strategy;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersionModel(): string
    {
        return config('versionable.version_model');
    }

    public function getVersionUserId()
    {
        return $this->getAttribute(\config('versionable.user_foreign_key')) ?? auth()->id();
    }

    /**
     * @return string
     */
    public function getKeepVersionsCount(): string
    {
        return config('versionable.keep_versions', 0);
    }

    /**
     * Get the versionable attributes of a given array.
     *
     * @param  array  $attributes
     *
     * @return array
     */
    public function versionableFromArray(array $attributes): array
    {
        if (count($this->getVersionable()) > 0) {
            return \array_intersect_key($attributes, array_flip($this->getVersionable()));
        }

        if (count($this->getDontVersionable()) > 0) {
            return \array_diff_key($attributes, array_flip($this->getDontVersionable()));
        }

        return $attributes;
    }

    public static function getVersioning()
    {
        return static::$versioning;
    }

    /**
     * @param  callable  $callback
     */
    public static function withoutVersion(callable $callback)
    {
        $lastState = static::$versioning;

        static::disableVersioning();

        \call_user_func($callback);

        static::$versioning = $lastState;
    }

    public static function disableVersioning()
    {
        static::$versioning = false;
    }

    public static function enableVersioning()
    {
        static::$versioning = true;
    }
}
