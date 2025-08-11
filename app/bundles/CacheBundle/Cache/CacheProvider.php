<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Psr\Cache\InvalidArgumentException as Psr6CacheInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CacheProvider extends AbstractCacheProvider
{
    public function getCacheAdapter(): TagAwareAdapterInterface|TagAwareCacheInterface
    {
        $selectedAdapter = $this->coreParametersHelper->get('cache_adapter');
        if (!$selectedAdapter || !$this->container->has($selectedAdapter)) {
            throw new InvalidArgumentException('Requested cache adapter "'.$selectedAdapter.'" is not available');
        }

        $adaptor = $this->container->get($selectedAdapter);
        if (!$adaptor instanceof TagAwareAdapterInterface) {
            throw new InvalidArgumentException(sprintf('Requested cache adapter "%s" is not a %s', $selectedAdapter, TagAwareAdapterInterface::class));
        }

        return $adaptor;
    }

    public function clear(string $prefix = ''): bool
    {
        return $this->getCacheAdapter()->clear($prefix);
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws Psr6CacheInterface When $tags is not valid
     */
    public function invalidateTags(array $tags): bool
    {
        return $this->getCacheAdapter()->invalidateTags($tags);
    }
}
