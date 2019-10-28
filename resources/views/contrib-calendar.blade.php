<span class="text-gray-600">Earliest Date: {{ $gitData->earliest_date->toDateString() }}</span>
<span class="text-gray-600 float-right">Latest Date: {{ $gitData->latest_date->toDateString() }}</span>
<table class="calendar-heatmap">
    <tbody>
        @for ($dayOfWeek = 1; $dayOfWeek <= 7; $dayOfWeek++)
            <tr>
                @for ($weekNumber = 0; $weekNumber <= 54; $weekNumber++)
                    @if ($weekNumber > 0)
                    @php
                    $curDay = Carbon\Carbon::parse($gitData->earliest_date->copy()->startOfWeek()->format('Y-m-d'))
                        ->addDays($dayOfWeek-2)->addWeeks($weekNumber-1);
                    $diff = $curDay->diffInDays($gitData->earliest_date);

                    $daysEvents = optional($gitData->data[$dayOfWeek])[$diff] ?? [
                        'date' => $curDay,
                        'count' => 'No',
                        'heatmap_class' => 'bg-gray-300'
                    ];
                    @endphp
                    <td class="bg-gray-100 border-solid border-gray-100 border-2" title="{{ $daysEvents['count'] }} contributions on {{ $daysEvents['date']->toDateString() }}">
                        @if($daysEvents['date']->lt($gitData->earliest_date) || $daysEvents['date']->gt($gitData->latest_date))
                        <svg width=15 height=15>
                            <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                        </svg>
                        @else
                        <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour($daysEvents['count']) }} rounded-full">
                            <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                        </svg>
                        @endif
                        <span style="display: none" name="debug">
                            <span>{{ $daysEvents['count'] }} contributions</span><br>
                            <span>{{ $daysEvents['date']->toDateString() }}</span><br>
                            <span>Diff: {{ $diff }}; AddedDays: {{ $gitData->earliest_date->copy()->addDays($diff)->toDateString() }}</span><br>
                            <span>CurDate: {{ $curDay }}</span><br>
                            <span>Earliest: {{ $gitData->earliest_date->toDateString() }}</span>
                        </span>
                    </td>
                    @else
                        <td class="bg-gray-100 text-gray-600 text-xs text-center">
                            @if ($dayOfWeek == 2) M @elseif ($dayOfWeek == 4) W @elseif ($dayOfWeek == 6) F @else &nbsp; @endif
                        </td>
                    @endif
                @endfor
            </tr>
        @endfor
        <tr>
            <td>
                <svg width=15 height=15>
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
        </tr>
        <tr>
            <td class="bg-gray-100 text-gray-400 text-xs text-center">
                &nbsp;
            </td>
            <td class="bg-gray-100 border-solid border-gray-100 border-2" title="No contributions">
                <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour(0) }} rounded-full">
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
            <td class="bg-gray-100 border-solid border-gray-100 border-2" title="1-9 contributions">
                <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour(3) }} rounded-full">
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
            <td class="bg-gray-100 border-solid border-gray-100 border-2" title="10-19 contributions">
                <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour(13) }} rounded-full">
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
            <td class="bg-gray-100 border-solid border-gray-100 border-2" title="20-29 contributions">
                <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour(23) }} rounded-full">
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
            <td class="bg-gray-100 border-solid border-gray-100 border-2" title="30+ contributions">
                <svg width=15 height=15 class="{{ m50\GitCalendar\GitData::determineHeatmapColour(33) }} rounded-full">
                    <rect x=0 y=0 width=15 height=15 fill="none"></rect>
                </svg>
            </td>
        </tr>
    </tbody>
</table>