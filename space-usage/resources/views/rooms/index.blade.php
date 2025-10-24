@extends('layouts.app')

@section('content')

    <!-- list all the rooms -->
    <div class="container mt-5">
        <h1 class="mb-4">Available Rooms</h1>
        @foreach ($rooms as $building => $buildingRooms)
            <div class="mb-3">
                <button class="btn btn-outline-primary w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-building-{{ $loop->index }}" aria-expanded="false">
                    <h2 class="mb-0">
                        {{-- get building description for the current building --}}
                        {{ $buildingRooms->first()->building->description }}
                    </h2>
                </button>
                
                <div id="collapse-building-{{ $loop->index }}" class="collapse">
                    <ul class="list-group mt-2">
                        @foreach ($buildingRooms as $room)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h3>{{ $room->room_description }}                             <span class="badge badge-primary bg-primary">
                                        {{ $room->sa_facility_type ?? 'N/A' }}
                                    </span></h3>
                                    <strong>Capacity:</strong> {{ $room->capacity }}<br>
                                </div>
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-primary">View</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
@endsection
