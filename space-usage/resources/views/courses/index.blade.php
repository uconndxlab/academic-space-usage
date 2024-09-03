<!-- resources/views/courses/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Section List</h1>

    <!-- Table for courses -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Sections</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Subject Code</th>
                        <th scope="col">Catalog Number</th>
                        <th scope="col">Description</th>

                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        <tr>
                            <td>
                               
                                    {{ $course->subject_code }}
                                
                            </td>
                            <td>
                                    {{ $course->catalog_number }}
                               
                                

                            </td>

                            <td>{{ $course->class_descr }}</td>


                            <td>
                                <a href="{{ route('courses.show', $course->id) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
