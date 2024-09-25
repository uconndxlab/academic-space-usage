<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Academic Space Dashboard </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href={{ asset('css/bootstrap-superhero.min.css') }} rel="stylesheet">
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
 
    </style>

    <script src="https://unpkg.com/htmx.org@2.0.2" integrity="sha384-Y7hw+L/jvKeWIRRkqWYfPcvVxHzVzn5REgzbawhxAuQGwX1XWe70vji+VSeHOThJ" crossorigin="anonymous"></script>

</head>
<body>
    <!-- bootstrap nav for rooms, buildings, and courses -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('terms.index') }}">Space Usage Dashboard</a>
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

    <script>

    
</body>
</html>
