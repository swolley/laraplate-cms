<p class="px-4 py-3 bg-gray-100 rounded-lg">
    @foreach ($getRecord()->components as $key => $value)
        <div>
            <span class="font-medium">{{ $key }}:</span>
            <span>{!! json_encode($value) !!}</span>
        </div>
    @endforeach
</p>