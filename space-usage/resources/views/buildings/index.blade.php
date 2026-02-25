@extends("layouts.app")
@section("title", "Buildings")
@section("content")
<div class="container mt-5">
    <h1 class="mb-4">Available Buildings</h1>
    @foreach($buildingsByCampus as $campusName => $buildings)
        <div class="mb-3">
            <button class="btn btn-outline-secondary w-100 text-start d-flex align-items-center justify-content-between py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-campus-{{ $loop->index }}" aria-expanded="true">
                <span class="fw-semibold">{{ $campusName }}</span>
                <span class="badge bg-secondary rounded-pill">{{ $buildings->count() }}</span>
            </button>
            <div id="collapse-campus-{{ $loop->index }}" class="collapse show">
                <div class="list-group list-group-flush">
                    @foreach($buildings as $building)
                        <a href="{{ route('buildings.show', $building->id) }}" class="list-group-item list-group-item-action">
                            {{ $building->description }} ({{ $building->building_code }})
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection