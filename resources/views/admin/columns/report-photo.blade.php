{{-- regular object attribute --}}
@php
    $column['value'] = $column['value'] ?? data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 32;
    $column['prefix'] = $column['prefix'] ?? '';
    $column['suffix'] = $column['suffix'] ?? '';
    $column['text'] = $column['default'] ?? '-';

    if($column['value'] instanceof \Closure) {
        $column['value'] = $column['value']($entry);
    }

    $itemValue = $column['value'];
@endphp

<span>
    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')

    @if(!empty($itemValue) && is_array($itemValue))
        <div>
            @foreach($itemValue as $item)
                <div>
                    <a href="{{env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $item}}" target="_blank">
                        <img src="{{env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $item}}" style="max-width: 100px; object-fit: contain" alt="">
                    </a>
                </div>
            @endforeach
        </div>
    @else
        -
    @endif

    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
</span>

