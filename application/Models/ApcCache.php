<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Core\Cache\ApcStore;
use Application\Core\Foundation\Model;
use Application\Core\Text\Translator;
use Application\Core\Type\Map;
use Application\Core\Type\Table;

/**
 * Class ApcCache
 * @package Application\Providers
 */
class ApcCache extends Model
{
    /**
     * @var Translator
     */
    protected $text;

    /**
     * Initializes the class properties.
     *
     * @param Translator $text
     */
    public function __construct(Translator $text)
    {
        $this->text = $text;
    }

    /**
     * @return array
     */
    public function getStatistics(): array
    {
        $smaInfo = apc_sma_info() ?: [] ;
        $cacheInfo = apc_cache_info('user') ?: [];

        if (empty($smaInfo) || empty($cacheInfo)) {
            return [];
        }

        $currentTime = time();
        $phpVersion = phpversion();
        $apcVersion = phpversion('apc');

        $serverName = $_SERVER['SERVER_NAME'];
        $serverAddress = $_SERVER['SERVER_ADDR'];
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'];

        $segmentCount = $smaInfo['num_seg'];
        $segmentSize = $smaInfo['seg_size'];
        $sharedMemorySize = $segmentCount * $segmentSize;
        $freeSharedMemory = $smaInfo['avail_mem'];
        $usedSharedMemory = $sharedMemorySize - $freeSharedMemory;

        $startTime = $cacheInfo['start_time'];
        $uptime = $currentTime - $startTime;
        $timeToLive = $cacheInfo['ttl'];
        $usedMemory = $cacheInfo['mem_size'];
        $slotCount = $cacheInfo['num_slots'];
        $itemCount = $cacheInfo['num_entries'];

        $hitCount = $cacheInfo['num_hits'];
        $missCount = $cacheInfo['num_misses'];
        $requestCount = $hitCount + $missCount;
        $hitRate = ($uptime > 0) ? $hitCount / $uptime : 0;
        $missRate = ($uptime > 0) ? $missCount / $uptime : 0;
        $requestRate = ($uptime > 0) ? $requestCount / $uptime : 0;

        return [
            'php_version'           => $phpVersion,
            'apc_version'           => $apcVersion,
            'server_name'           => $serverName,
            'server_address'        => $serverAddress,
            'server_software'       => $serverSoftware,
            'segment_count'         => $segmentCount,
            'segment_size'          => $segmentSize,
            'shared_memory_size'    => $sharedMemorySize,
            'free_shared_memory'    => $freeSharedMemory,
            'used_shared_memory'    => $usedSharedMemory,
            'start_time'            => $startTime,
            'uptime'                => $uptime,
            'time_to_live'          => $timeToLive,
            'used_memory'           => $usedMemory,
            'slot_count'            => $slotCount,
            'item_count'            => $itemCount,
            'hit_count'             => $hitCount,
            'hit_rate'              => $hitRate,
            'miss_count'            => $missCount,
            'miss_rate'             => $missRate,
            'request_count'         => $requestCount,
            'request_rate'          => $requestRate,
        ];
    }

    /**
     * @return Table
     */
    public function getGeneralInfo(): Table
    {
        $info = $this->getStatistics();

        if (empty($info)) {
            return new Table();
        }

        $memorySize = $this->text->getByteCount(
            intval($info['shared_memory_size'])
        );
        $freeMemory = $this->text->getByteCount(
            intval($info['free_shared_memory'])
        );
        $usedMemory = $this->text->getByteCount(
            intval($info['used_shared_memory'])
        );

        $generalInfo = new Map([
            $this->text->get('cache.server_name') => $info['server_name'],
            $this->text->get('cache.server_address') => $info['server_address'],
            $this->text->get('cache.server_software') => $info['server_software'],
            $this->text->get('cache.php_version') => $info['php_version'],
            $this->text->get('cache.apc_version') => $info['apc_version'],
            $this->text->get('cache.shared_memory_size') => $memorySize,
            $this->text->get('cache.free_shared_memory') => $freeMemory,
            $this->text->get('cache.used_shared_memory') => $usedMemory,
        ]);

        return $generalInfo->toTable();
    }

    /**
     * @return Table
     */
    public function getCacheInfo(): Table
    {
        $info = $this->getStatistics();

        if (empty($info)) {
            return new Table();
        }

        $startTime = $this->text->getTime(
            'date_time.timestamp.long',
            $info['start_time']
        );
        $uptime = $this->text->getDuration($info['uptime']);

        $itemCount = $info['item_count'];
        if ($itemCount > 0) {
            $usedMemory = intval($info['used_memory']);
            $itemCount .= ' (' . $this->text->getByteCount($usedMemory) . ')';
        }

        $requestCount = $info['request_count'];
        if ($info['request_rate'] >= 0.01) {
            $requestCount .= sprintf(
                ' (%.2f ' . $this->text->get('cache.requests_per_second') . ')',
                $info['request_rate']
            );
        }

        $hitCount = $info['hit_count'];
        if ($info['hit_rate'] >= 0.01) {
            $hitCount .= sprintf(
                ' (%.2f ' . $this->text->get('cache.requests_per_second') . ')',
                $info['hit_rate']
            );
        }

        $missCount = $info['miss_count'];
        if ($info['miss_rate'] >= 0.01) {
            $missCount .= sprintf(
                ' (%.2f ' . $this->text->get('cache.requests_per_second') . ')',
                $info['miss_rate']
            );
        }

        $cacheInfo = new Map([
            $this->text->get('cache.start_time') => $startTime,
            $this->text->get('cache.uptime') => $uptime,
            $this->text->get('cache.time_to_live') => $info['time_to_live'],
            $this->text->get('cache.slots') => $info['slot_count'],
            $this->text->get('cache.cached_items') => $itemCount,
            $this->text->get('cache.requests') => $requestCount,
            $this->text->get('cache.hits') => $hitCount,
            $this->text->get('cache.misses') => $missCount,
        ]);

        return $cacheInfo->toTable();
    }

    /**
     * @param string $prefix
     * @return Table
     */
    public function getItems(string $prefix = null): Table
    {
        $info = apc_cache_info('user') ?: [];

        if (empty($info) || !isset($info['cache_list'])) {
            return new Table();
        }

        if (!empty($prefix)) {
            $prefix .= ApcStore::PREFIX_SEPARATOR;
        }

        $data = [];

        foreach ($info['cache_list'] as $item) {
            $expirationTime = $item['creation_time'] + $item['ttl'];
            $key = $item['info'];
            if (!empty($prefix)) {
                $prefixLength = strlen($prefix);
                if (substr($key, 0, $prefixLength) === $prefix) {
                    $key = substr($key, $prefixLength);
                } else {
                    continue;
                }
            }
            $data[] = [
                'key' => $key,
                'memory_size' => $this->text->getByteCount(
                    intval($item['mem_size'])
                ),
                'hit_count' => $item['num_hits'],
                'creation_time' => $this->text->getTime(
                    'date_time.timestamp.medium', $item['creation_time']
                ),
                'last_access_time' => $this->text->getTime(
                    'date_time.timestamp.medium', $item['access_time']
                ),
                'expiration_time' => $this->text->getTime(
                    'date_time.timestamp.medium', $expirationTime
                ),
            ];
        }
        return new Table($data);
    }
}

// -- End of file
