@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Available Terms</h1>
    <div class="list-group">
        @foreach($terms as $term)
            <a href="{{ route('terms.show', $term->id) }}" class="list-group-item list-group-item-action">
                {{ $term->term_descr }} ({{ $term->term_code }})
            </a>
        @endforeach
    </div>
</div>
@endsection
