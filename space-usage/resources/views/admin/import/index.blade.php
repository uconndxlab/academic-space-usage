@extends('layouts.app')
@section('title', 'Data Import')
@section('content')
<div class="container mt-5">
    <h1 class="mb-4">
        <i class="bi bi-cloud-upload"></i> Data Import Manager
    </h1>

    <div class="alert alert-info">
        <h5><i class="bi bi-info-circle"></i> Import Instructions</h5>
        <ol class="mb-0">
            <li>Select your CSV file (must match the comprehensive data format with 66+ columns)</li>
            <li>Choose whether to clear existing data before import</li>
            <li>Click "Upload File" to upload the CSV</li>
            <li>Once uploaded, click "Start Import" to begin processing</li>
            <li>Monitor progress and review the import summary</li>
        </ol>
    </div>

    <!-- Current Database Stats -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-database"></i> Current Database Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row" id="statsContainer">
                <div class="col-md-3 mb-3">
                    <div class="stat-box text-center p-3 border rounded">
                        <h2 class="mb-0" id="stat-terms">-</h2>
                        <small class="text-muted">Terms</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-box text-center p-3 border rounded">
                        <h2 class="mb-0" id="stat-courses">-</h2>
                        <small class="text-muted">Courses</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-box text-center p-3 border rounded">
                        <h2 class="mb-0" id="stat-sections">-</h2>
                        <small class="text-muted">Sections</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-box text-center p-3 border rounded">
                        <h2 class="mb-0" id="stat-rooms">-</h2>
                        <small class="text-muted">Rooms</small>
                    </div>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-primary" onclick="loadStats()">
                <i class="bi bi-arrow-clockwise"></i> Refresh Stats
            </button>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-upload"></i> Upload CSV File</h5>
        </div>
        <div class="card-body">
            <form id="uploadForm">
                @csrf
                <div class="mb-3">
                    <label for="csvFile" class="form-label">Select CSV File</label>
                    <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv,.txt" required>
                    <small class="form-text text-muted">
                        Maximum file size: 100MB. File must be in CSV format with headers.
                    </small>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="clearDatabase" name="clear_database">
                    <label class="form-check-label" for="clearDatabase">
                        <strong class="text-danger">Clear existing data before import</strong>
                        <small class="d-block text-muted">Warning: This will delete all current data and run migrations.</small>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="uploadBtn">
                    <i class="bi bi-cloud-upload"></i> Upload File
                </button>
            </form>

            <div id="uploadStatus" class="mt-3" style="display: none;"></div>
        </div>
    </div>

    <!-- Import Control -->
    <div class="card mb-4" id="importCard" style="display: none;">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="bi bi-play-circle"></i> Start Import</h5>
        </div>
        <div class="card-body">
            <p>File uploaded successfully. Click below to start the import process.</p>
            <button class="btn btn-success btn-lg" id="importBtn" onclick="startImport()">
                <i class="bi bi-play-fill"></i> Start Import
            </button>
        </div>
    </div>

    <!-- Progress -->
    <div class="card mb-4" id="progressCard" style="display: none;">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Import Progress</h5>
        </div>
        <div class="card-body">
            <div class="progress mb-3" style="height: 30px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     style="width: 100%"
                     id="progressBar">
                    Processing...
                </div>
            </div>
            <div id="progressStatus">
                <p class="mb-0"><i class="bi bi-gear-fill spin"></i> Import in progress, please wait...</p>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="card" id="resultsCard" style="display: none;">
        <div class="card-header" id="resultsHeader">
            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Import Results</h5>
        </div>
        <div class="card-body">
            <div id="resultsContent"></div>
        </div>
    </div>
</div>

<style>
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .stat-box {
        transition: all 0.3s;
    }
    .stat-box:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>

<script>
    // Load initial stats
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
    });

    function loadStats() {
        fetch('{{ route("admin.import.status") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('stat-terms').textContent = data.stats.terms.toLocaleString();
                    document.getElementById('stat-courses').textContent = data.stats.courses.toLocaleString();
                    document.getElementById('stat-sections').textContent = data.stats.sections.toLocaleString();
                    document.getElementById('stat-rooms').textContent = data.stats.rooms.toLocaleString();
                }
            })
            .catch(error => console.error('Error loading stats:', error));
    }

    // Upload form handler
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadStatus = document.getElementById('uploadStatus');
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="bi bi-hourglass"></i> Uploading...';
        uploadStatus.style.display = 'block';
        uploadStatus.className = 'alert alert-info';
        uploadStatus.textContent = 'Uploading file...';

        fetch('{{ route("admin.import.upload") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadStatus.className = 'alert alert-success';
                uploadStatus.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.message;
                document.getElementById('importCard').style.display = 'block';
            } else {
                uploadStatus.className = 'alert alert-danger';
                uploadStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + data.message;
            }
        })
        .catch(error => {
            uploadStatus.className = 'alert alert-danger';
            uploadStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Upload failed: ' + error.message;
        })
        .finally(() => {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload File';
        });
    });

    function startImport() {
        const importBtn = document.getElementById('importBtn');
        const progressCard = document.getElementById('progressCard');
        const resultsCard = document.getElementById('resultsCard');
        const clearDatabase = document.getElementById('clearDatabase').checked;

        importBtn.disabled = true;
        progressCard.style.display = 'block';
        resultsCard.style.display = 'none';

        fetch('{{ route("admin.import.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                clear_database: clearDatabase
            })
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            
            // Check if response is JSON
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // HTML error response (likely a timeout or PHP error)
                const text = await response.text();
                
                // Try to extract error message from HTML
                let errorMsg = 'Server error occurred';
                if (text.includes('Maximum execution time')) {
                    errorMsg = 'Import timed out after 30 seconds. For large files (>50MB), please use the command line import: ./import-data.sh database/comprehensive_data.csv';
                } else if (text.includes('memory')) {
                    errorMsg = 'Server ran out of memory. Please use the command line import for large files.';
                } else {
                    // Extract error message from HTML if possible
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');
                    const errorElement = doc.querySelector('.exception_message, .exception-message, h1');
                    if (errorElement) {
                        errorMsg = errorElement.textContent.trim();
                    }
                }
                
                throw new Error(errorMsg);
            }
        })
        .then(data => {
            progressCard.style.display = 'none';
            resultsCard.style.display = 'block';

            const resultsHeader = document.getElementById('resultsHeader');
            const resultsContent = document.getElementById('resultsContent');

            if (data.success) {
                resultsHeader.className = 'card-header bg-success text-white';
                resultsHeader.innerHTML = '<h5 class="mb-0"><i class="bi bi-check-circle"></i> Import Successful!</h5>';
                resultsContent.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle"></i> ${data.message}</h5>
                    </div>
                    <h6>Import Output:</h6>
                    <pre class="border p-3 bg-light" style="max-height: 400px; overflow-y: auto;">${data.output}</pre>
                    <button class="btn btn-primary mt-3" onclick="loadStats()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Statistics
                    </button>
                    <a href="{{ route('courses.index') }}" class="btn btn-success mt-3">
                        <i class="bi bi-list"></i> View Courses
                    </a>
                `;
                
                // Auto-refresh stats
                setTimeout(loadStats, 1000);
            } else {
                resultsHeader.className = 'card-header bg-danger text-white';
                resultsHeader.innerHTML = '<h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Import Failed</h5>';
                resultsContent.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle"></i> ${data.message}</h5>
                        ${data.error_type ? `<p class="mb-0"><small>Error type: ${data.error_type}</small></p>` : ''}
                    </div>
                    <p>Check the application logs for more details: <code>storage/logs/laravel.log</code></p>
                `;
            }
        })
        .catch(error => {
            progressCard.style.display = 'none';
            resultsCard.style.display = 'block';
            
            const resultsHeader = document.getElementById('resultsHeader');
            const resultsContent = document.getElementById('resultsContent');
            
            resultsHeader.className = 'card-header bg-danger text-white';
            resultsHeader.innerHTML = '<h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Import Error</h5>';
            
            const isTimeout = error.message.includes('timed out') || error.message.includes('Maximum execution time');
            
            resultsContent.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> ${error.message}</h5>
                </div>
                ${isTimeout ? `
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-terminal"></i> Recommended Solution: Use Command Line Import</h6>
                        <p>For large CSV files, the command line import is much faster and has no timeout limits:</p>
                        <ol>
                            <li>Open a terminal in your project directory</li>
                            <li>Run: <code>./import-data.sh database/comprehensive_data.csv</code></li>
                            <li>The import will complete in about 40-60 seconds</li>
                        </ol>
                        <p class="mb-0"><strong>Note:</strong> You can also increase PHP's max_execution_time in php.ini if you prefer to use the web interface.</p>
                    </div>
                ` : `
                    <p>Check the application logs for more details: <code>storage/logs/laravel.log</code></p>
                `}
            `;
        })
        .finally(() => {
            importBtn.disabled = false;
        });
    }
</script>
@endsection
