@extends("layouts.app")
@section("title", "Admin")
@section("content")
<div class="container mt-5">
    <h1 class="mb-4">Admin</h1>
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header">Add New User</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.addUser') }}">
                @csrf
                <div class="mb-3">
                    <label for="netID" class="form-label">NetID</label>
                    <input type="text" class="form-control" id="netID" name="netID" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="hidden" name="isAdmin" value="0">
                    <input type="checkbox" class="form-check-input" id="isAdmin" name="isAdmin" value="1">
                    <label class="form-check-label" for="isAdmin">Is Admin</label>
                </div>
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>
    <div class = "row">
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>NetID</th>
                <th>Is Admin</th>
                <th>Remove</th>
                <th>Make/Remove Admin</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>
                    {{ $user->netID}}
                    @if(Auth::user()->id === $user->id)<span>(me)</span>@endif
                </td>
                
                <td>{{ $user->isAdmin ? 'Admin' : 'User' }}</td>
                
                <td>
                    @if(Auth::user()->id !== $user->id)
                    <form method="POST" action="{{ route('admin.removeUser', $user->id) }}" class="me-3 mb-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                    @endif
                </td>
                <td>
                    @if(!$user->isAdmin && Auth::user()->id !== $user->id)
                    <form method="POST" action="{{ route('admin.makeAdmin', $user->id) }}" class="me-3 mb-0">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-primary btn-sm">Make Admin</button>
                    </form>
                    @elseif(Auth::user()->id !== $user->id)
                    <form method="POST" action="{{ route('admin.removeAdmin', $user->id) }}" class="me-3 mb-0">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-danger btn-sm">Remove Admin</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
@endsection