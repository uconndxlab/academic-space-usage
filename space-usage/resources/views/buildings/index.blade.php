@extends("layouts.app")
@section("title", "Buildings")
@section("content")
<!-- all buildings -->

<div class="container mt-5">
    <h1 class="mb-4">Available Buildings</h1>
    <div class="list-group">
        @foreach($buildings as $building)
            <a href="{{ route('buildings.show', $building->id) }}" class="list-group-item list-group-item-action">
                {{ $building->description }} ({{ $building->building_code }})
            </a>
        @endforeach
    </div>
</div>
@endsection