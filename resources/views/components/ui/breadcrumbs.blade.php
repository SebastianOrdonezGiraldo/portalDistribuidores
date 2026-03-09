@props(['items' => []])

@if(count($items) > 0)
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            @foreach($items as $index => $item)
                <li class="inline-flex items-center gap-2">
                    @if(!empty($item['href']) && $index < count($items) - 1)
                        <a href="{{ $item['href'] }}" class="rounded px-1 py-0.5 hover:bg-slate-100 hover:text-slate-700">{{ $item['label'] }}</a>
                    @else
                        <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                    @endif

                    @if($index < count($items) - 1)
                        <span aria-hidden="true">/</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
