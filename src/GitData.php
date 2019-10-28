<?php

namespace m50\GitCalendar;

use ArrayAccess;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Iterator;

/**
 * GitData
 * Stores the Count data from the GitApi countables.
 *
 * @method void __construct(Collection $data, Carbon $earliest_date = null, Carbon $latest_date = null)
 * @method self merge(GitData $gd)
 * @method array implode()
 * @method array toArray()
 * @method string toJson()
 * @method mixed __get()
 * @method bool offsetExists($offset)
 * @method string|Carbon\Carbon offsetGet($offset)
 * @method void offsetSet($offset, $value)
 * @method void offsetUnset($offset)
 * @method void rewind()
 * @method array current()
 * @method int key()
 * @method void next()
 * @method bool valid()
 * @static string determineHeatmapColour($count)
 */
class GitData implements Arrayable, Jsonable, ArrayAccess, Iterator
{
    use Macroable;

    /**
     * The position of the iterator.
     *
     * @var int
     */
    private $position;

    /**
     * The data store for the countables.
     *
     * @var Illuminate\Support\Collection
     */
    protected $data;

    /**
     * The earliest date that the GitData goes to.
     *
     * @var Carbon\Carbon
     */
    protected $earliest_date;

    /**
     * The latest date that the GitData goes to.
     *
     * @var Carbon\Carbon
     */
    protected $latest_date;

    /**
     * Construct a new GitData instance.
     *
     * @param Illuminate\Support\Collection $data
     * @param Carbon\Carbon|null $earliest_date
     * @param Carbon\Carbon|null $latest_date
     * @return void
     */
    public function __construct(
        Collection $data = null,
        Carbon $earliest_date = null,
        Carbon $latest_date = null
    ) {
        $this->data = $data ?? collect([1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => []]);
        $this->latest_date = $latest_date ?? now();
        $this->earliest_date = $earliest_date ?? now()->subMonths(12);
        $this->rewind();
    }

    /**
     * Merges another GitData set into this one.
     *
     * @param self $gd
     * @return self
     */
    public function merge(self $gd): self
    {
        $events = collect();
        for ($day = 1; $day <= 7; $day++) {
            $k = collect();
            foreach ($this->data[$day] as $date => $obj) {
                if (isset($gd->data[$day][$date])) {
                    $obj['count'] += $gd->data[$day][$date]['count'];
                }
                $k[$date] = $obj;
            }
            foreach ($gd->data[$day] as $date => $obj) {
                if (isset($k[$date])) {
                    continue;
                }
                $k[$date] = $obj;
            }

            $events[$day] = $k;
        }
        $this->data = $events;
        if ($gd->earliest_date->lt($this->earliest_date)) {
            $this->earliest_date = $gd->earliest_date;
        }
        if ($gd->latest_date->gt($this->latest_date)) {
            $this->latest_date = $gd->latest_date;
        }

        return $this;
    }

    /**
     * Alias for toArray().
     *
     * @return array
     */
    public function implode(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->data,
            'earliest_date' => $this->earliest_date,
            'latest_date' => $this->latest_date,
        ];
    }

    /**
     * Accessor for the data.
     *
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Determine colour class based on the count.
     *
     * @param int $count
     * @return string
     */
    public static function determineHeatmapColour($count): string
    {
        $heatmapClass = config('contrib-calendar.heatmap-class.zero', 'bg-gray-300');

        if ($count >= 1 && $count < 9) {
            $heatmapClass = config('contrib-calendar.heatmap-class.low', 'bg-blue-200');
        } elseif ($count >= 10 && $count < 19) {
            $heatmapClass = config('contrib-calendar.heatmap-class.medium', 'bg-blue-400');
        } elseif ($count >= 20 && $count < 29) {
            $heatmapClass = config('contrib-calendar.heatmap-class.high', 'bg-blue-600');
        } elseif ($count >= 30) {
            $heatmapClass = config('contrib-calendar.heatmap-class.very-high', 'bg-blue-800');
        }

        return $heatmapClass;
    }

    /**
     * Check if offset exists for array access.
     *
     * @param int|string $offset
     * @return void
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]) || $offset == 'earliest_date' || $offset == 'latest_date';
    }

    /**
     * Get the setting from array access.
     *
     * @param int|string $offset
     * @return array|Carbon\Carbon
     */
    public function offsetGet($offset)
    {
        if ($offset == 'earliest_date' || $offset == 'latest_date') {
            return $this->$offset;
        }

        return $this->data[$offset];
    }

    /**
     * Set a value using array access.
     *
     * @param int|string $offset
     * @param array|Carbon\Carbon $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset == 'earliest_date' || $offset == 'latest_date') {
            $this->$offset = $value;
        }

        $this->data[$offset] = $value;
    }

    /**
     * [Not Implemented] Unset an offset using array access.
     *
     * @param int|string $offset
     * @return void
     *
     * @throws \Exception Unable to unset a GitData date/time.
     */
    public function offsetUnset($offset)
    {
        throw new \Exception('Unable to unset a GitData date/time.');
    }

    /**
     * Rewing the iterable.
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 1;
    }

    /**
     * Get the value of the current position using the iterable.
     *
     * @return array
     */
    public function current()
    {
        return $this->data[$this->position];
    }

    /**
     * Get the key for the current position of the iterable.
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Go to the next step of the iterable.
     *
     * @return void
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Check if entry in iterable is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->data[$this->position]) && $this->position >= 1 && $this->position <= 7;
    }
}
