@extends('layouts.app')
@section('title', 'Term Details')
@section('content')
<div class="container">
    <h1>{{ $term->term_descr }} ({{ $term->term_code }})</h1>

    <div class="row my-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Courses</h5>
                    <p class="card-text">{{ $totalCourses }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Sections</h5>
                    <p class="card-text">{{ $totalSections }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Enrollment</h5>
                    <p class="card-text">{{ $totalEnrollment }}</p>
                </div>
            </div>
        </div>
    </div>

    <h2>Buildings</h2>
    <!-- search for a room -->
    <form action="{{ route('terms.show', $term->id) }}" method="GET" class="form-inline my-4">
        @csrf
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search building..." name="building_search"
                value="{{ request('building_search') }}">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>Building</th>
                <th># Rooms</th>
            </tr>
        </thead>
        <tbody>
            @foreach($buildings as $building)
            <tr>
                <td>
                    <a href="{{ route('buildings.show', $building->id) }}">
                        {{ $building->description }} ({{ $building->building_code }})
                    </a>
                </td>
                <td>{{ $building->rooms->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection