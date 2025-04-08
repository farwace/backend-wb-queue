@basset('/assets/animate.compat.css')
@basset('/assets/noty.css')

@basset('/assets/line-awesome.min.css')
@basset('/assets/la-regular-400.woff2')
@basset('/assets/la-solid-900.woff2')
@basset('/assets/la-brands-400.woff2')
@basset('/assets/la-regular-400.woff')
@basset('/assets/la-solid-900.woff')
@basset('/assets/la-brands-400.woff')
@basset('/assets/la-regular-400.ttf')
@basset('/assets/la-solid-900.ttf')
@basset('/assets/la-brands-400.ttf')

@basset(base_path('vendor/backpack/crud/src/resources/assets/css/common.css'))

@if (backpack_theme_config('styles') && count(backpack_theme_config('styles')))
    @foreach (backpack_theme_config('styles') as $path)
        @if(is_array($path))
            @basset(...$path)
        @else
            @basset($path)
        @endif
    @endforeach
@endif

@if (backpack_theme_config('mix_styles') && count(backpack_theme_config('mix_styles')))
    @foreach (backpack_theme_config('mix_styles') as $path => $manifest)
        <link rel="stylesheet" type="text/css" href="{{ mix($path, $manifest) }}">
    @endforeach
@endif

@if (backpack_theme_config('vite_styles') && count(backpack_theme_config('vite_styles')))
    @vite(backpack_theme_config('vite_styles'))
@endif
