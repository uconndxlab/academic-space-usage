<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Academic Space Dashboard </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- css/app.css --}}
    <link href={{ asset('css/app.css') }} rel="stylesheet">


    {{-- <link href={{ asset('css/bootstrap-superhero.min.css') }} rel="stylesheet"> --}}
    <style>
        .container {
            margin-top: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }

        tr.table-secondary {
            font-size: 0.9em;
        }

        tr.table-course {
            cursor: pointer;
        }

        tr.table-course:hover {
            background-color: #f8f9fa;
        }

        .room-enrollment {
            cursor: pointer;
        }

        .room-enrollment:hover {
            text-decoration: underline;
        }

        .card-title {
            font-size: 1.2em;
        }

        .card-text {
            font-size: 1em;
        }

        .card {
            height: 100%;
        }

        tr {
    cursor: unset!important;
}

body {
    background-color: #f8f9fa;
    color: #002855; /* UConn Blue */
}

.navbar {
    background-color: #002855; /* UConn Blue */
}

.navbar a {
    color: #ffffff;
}

.btn-primary {
    background-color: #002855; /* UConn Blue */
    border-color: #002855; /* UConn Blue */
}

.btn-primary:hover {
    background-color: #001f3f; /* Darker shade of UConn Blue */
    border-color: #001a35; /* Darker shade of UConn Blue */
}

.table {
    background-color: #ffffff;
    color: #002855; /* UConn Blue */
}

.table thead th {
    background-color: #002855; /* UConn Blue */
    color: #ffffff;
}

.table tbody tr:nth-child(odd) {
    background-color: #f2f2f2;
}

.table tbody tr:hover {
    background-color: #e9ecef;
}
 
    </style>

    <script src="https://unpkg.com/htmx.org@2.0.2" integrity="sha384-Y7hw+L/jvKeWIRRkqWYfPcvVxHzVzn5REgzbawhxAuQGwX1XWe70vji+VSeHOThJ" crossorigin="anonymous"></script>

</head>
<body>
    <!-- bootstrap nav for rooms, buildings, and courses -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('terms.index') }}">Academic Space Usage Tools</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('rooms.index') }}">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('buildings.index') }}">Buildings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('courses.index') }}">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('terms.index') }}">Terms</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @yield('content')



    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    

    
</body>
</html>
