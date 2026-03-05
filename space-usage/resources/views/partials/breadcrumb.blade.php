<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach ($items as $item)
            <li class="breadcrumb-item{{ isset($item['url']) ? '' : ' active' }}"{{ !isset($item['url']) ? ' aria-current="page"' : '' }}>
                @if (isset($item['url']))
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@if (!$loop->last) &raquo; @endif
                @else
                    {{ $item['label'] }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
