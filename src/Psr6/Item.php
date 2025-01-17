<?php

namespace MatthiasMullie\Scrapbook\Psr6;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Representation of a cache item, both existing & non-existing (to be created).
 *
 * @author Matthias Mullie <scrapbook@mullie.eu>
 * @copyright Copyright (c) 2014, Matthias Mullie. All rights reserved
 * @license LICENSE MIT
 */
class Item implements CacheItemInterface
{
    /**
     * @var string
     */
    protected string $hash;

    /**
     * @var string
     */
    protected string $key;

    /**
     * @var Repository
     */
    protected Repository $repository;

    /**
     * @var mixed
     */
    protected mixed $value;

    /**
     * @var int
     */
    protected int $expire = 0;

    /**
     * @var bool
     */
    protected ?bool $isHit = null;

    /**
     * @var bool
     */
    protected bool $changed = false;

    /**
     * @param string $key
     */
    public function __construct(string $key, Repository $repository)
    {
        $this->key = $key;

        /*
         * Register this key (tied to this particular object) to the value
         * repository.
         *
         * If 1 key is requested multiple times, the value could be an object
         * that could be altered (by reference) and if all objects-for-same-key
         * reference that same value, all would've been changed (because all
         * would be that exact same value.)
         *
         * I'm using spl_object_hash to get a unique identifier linking $key to
         * this particular object, without using this object itself (I could use
         * SplObjectStorage.) If I stored this object, it wouldn't be destructed
         * when it's no longer needed, and I want it to destruct so I can free
         * up this value in the repository when it's no longer needed.
         */
        $this->repository = $repository;
        $this->hash = spl_object_hash($this);
        $this->repository->add($this->hash, $this->key);
    }

    /**
     * When this item is being killed, we should no longer keep its value around
     * in the repository. Free up some memory!
     */
    public function __destruct()
    {
        $this->repository->remove($this->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey() : string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get() : mixed
    {
        // value was already set on this object, return that one!
        if (null !== $this->value) {
            return $this->value;
        }

        // sanity check
        if (!$this->isHit()) {
            return null;
        }

        return $this->repository->get($this->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value) : CacheItemInterface
    {
        $this->value = $value;
        $this->changed = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit() : bool
    {
        if (null !== $this->isHit) {
            return $this->isHit;
        }

        return $this->repository->exists($this->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration) : CacheItemInterface
    {
        // DateTimeInterface only exists since PHP>=5.5, also accept DateTime
        if ($expiration instanceof DateTimeInterface || $expiration instanceof DateTime) {
            // convert datetime to unix timestamp
            $this->expire = (int) $expiration->format('U');
            $this->changed = true;
        } elseif (null === $expiration) {
            $this->expire = 0;
            $this->changed = true;
        } else {
            $class = get_class($this);
            $type = gettype($expiration);
            $error = "Argument 1 passed to $class::expiresAt()  must be an ".
                "instance of DateTime or DateTimeImmutable, $type given";

            if (class_exists('\TypeError')) {
                throw new \TypeError($error);
            }
            trigger_error($error, E_USER_ERROR);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time) : CacheItemInterface
    {
        if ($time instanceof DateInterval) {
            $expire = new DateTime();
            $expire->add($time);
            // convert datetime to unix timestamp
            $this->expire = (int) $expire->format('U');
        } elseif (is_int($time)) {
            $this->expire = time() + $time;
        } elseif (is_null($time)) {
            // this is allowed, but just defaults to infinite
            $this->expire = 0;
        } else {
            throw new InvalidArgumentException('Invalid time: '.serialize($time).'. Must be integer or '.'instance of DateInterval.');
        }
        $this->changed = true;

        return $this;
    }

    /**
     * Returns the set expiration time in integer form (as it's what
     * KeyValueStore expects).
     *
     * @return int
     */
    public function getExpiration(): int
    {
        return $this->expire;
    }

    /**
     * Returns true if the item is already expired, false otherwise.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expire = $this->getExpiration();

        return 0 !== $expire && $expire < time();
    }

    /**
     * We'll want to know if this Item was altered (value or expiration date)
     * once we'll want to store it.
     *
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->changed;
    }

    /**
     * Allow isHit to be override, in case it's a value that is returned from
     * memory, when a value is being saved deferred.
     *
     * @param bool $isHit
     */
    public function overrideIsHit(bool $isHit)
    {
        $this->isHit = $isHit;
    }
}
