@extends('layouts.app')

@section('content')

    <!-- list all the rooms -->
    <div class="container mt-5">
        <h1 class="mb-4">Available Rooms</h1>
        @foreach ($rooms as $building => $buildingRooms)
            <h2>
                {{-- get building description for the current building --}}
                {{ $buildingRooms->first()->building->description }}
            </h2>

            <ul class="list-group mb-4">
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
        @endforeach
    </div>
</div>
@endsection
