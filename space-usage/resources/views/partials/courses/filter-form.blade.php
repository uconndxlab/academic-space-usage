<form method="GET" action="{{ $filterAction }}" id="filterForm">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="termFilter" class="form-label">Filter by Term <span class="text-danger">*</span></label>
            <select name="term" id="termFilter" class="form-select" required>
                <option value="">-- Select Term --</option>
                @foreach ($terms as $term)
                    <option @selected($term->id == request('term')) value="{{ $term->id }}">{{ $term->term_code }} - {{ $term->term_descr }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label for="departmentFilter" class="form-label">Filter by Department <span class="text-danger">*</span></label>
            <div class="dropdown" id="departmentDropdown">
                <button class="form-select text-start" type="button" id="departmentFilterButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="departmentFilterText">Select Departments</span>
                </button>
                <ul class="dropdown-menu w-100 p-2" id="departmentDropdownMenu" style="max-height: 300px; overflow-y: auto;" onclick="event.stopPropagation();">
                    <li class="px-2 py-2 sticky-top bg-white" style="z-index: 1;">
                        <input type="text" class="form-control form-control-sm" id="departmentSearch" placeholder="Search departments..." autocomplete="off">
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li class="px-2 py-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllDepartments">
                            <label class="form-check-label fw-bold" for="selectAllDepartments">
                                Select All
                            </label>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach ($departments as $department)
                    <li class="px-2 py-1 department-item" data-department="{{ strtolower($department) }}">
                        <div class="form-check">
                            <input class="form-check-input department-checkbox" type="checkbox"
                                name="department[]"
                                value="{{ $department }}"
                                id="dept_{{ $loop->index }}"
                                @checked(in_array($department, $selectedDepartments))>
                            <label class="form-check-label" for="dept_{{ $loop->index }}">
                                {{ $department }}
                            </label>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="campusFilter" class="form-label">Filter by Campus <span class="text-danger">*</span></label>
            <select name="campus" id="campusFilter" class="form-select" required>
                <option value="">-- Select Campus --</option>
                @foreach ($campuses as $campus)
                    <option @selected($campus->id == request('campus')) value="{{ $campus->id }}">{{ $campus->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-3">
        <input type="submit" value="Filter" class="btn btn-primary" id="filterSubmit">
    </div>
</form>

<script>
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const selectedDepts = Array.from(document.querySelectorAll('.department-checkbox:checked')).map(cb => cb.value);
    if (selectedDepts.length === 0) {
        e.preventDefault();
        alert('Please select at least one department.');
        return false;
    }
});
</script>
