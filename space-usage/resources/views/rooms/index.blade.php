@extends('layouts.app')

@section('content')

<!-- list all the rooms -->
<div class="container mt-5">
    <h1 class="mb-4">Available Rooms</h1>
    <div class="list-group">
        @foreach($rooms as $room)
            <a href="{{ route('rooms.show', $room->id) }}" class="list-group-item list-group-item-action">
                {{ $room->room_description }} ({{ $room->room_number }})
            </a>
        @endforeach
    </div>
</div>
@endsection

