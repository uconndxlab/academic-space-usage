@extends('layouts.app')
@section('title', 'Course Details')
@section('content')
    <div class="container">
        @php
            $breadcrumbParams = [];
            if (isset($selectedCampus) && $selectedCampus) {
                $breadcrumbParams['campus'] = $selectedCampus->id;
            }
            if (isset($selectedFacilityType) && $selectedFacilityType) {
                $breadcrumbParams['sa_facility_type'] = $selectedFacilityType;
            }
            if ($course->subject_code) {
                $breadcrumbParams['department'] = $course->subject_code;
            }
            if ($course->term_id) {
                $breadcrumbParams['term'] = $course->term_id;
            }
            if (isset($dayType) && $dayType !== 'all') {
                $breadcrumbParams['day_type'] = $dayType;
            }
            $breadcrumbQuery = !empty($breadcrumbParams) ? '?' . http_build_query($breadcrumbParams) : '';
            $breadcrumbItems = [
                ['label' => 'Courses', 'url' => route('courses.index') . $breadcrumbQuery],
                ['label' => $course->catalog_number],
            ];
        @endphp
        @include('partials.breadcrumb', ['items' => $breadcrumbItems])

        {{-- course details --}}

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">Course: {{ $course->subject_code }} - {{ $course->catalog_number }}
                    @if($course->term)
                        <span class="text-muted">({{ $course->term->term_code }} - {{ $course->term->term_descr }})</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('courses.show', $course->id) }}" method="get"
                    hx-get="{{ route('courses.show', $course->id) }}" hx-target="#courseInfo" hx-select="#courseInfo">
                    <dl class="row">
                        @php
                            $course_duration_nearest_hour = ceil($course->duration_minutes / 60);
                        @endphp

                        <dt class="col-sm-3">Term</dt>
                        <dd class="col-sm-9">
                            @if($course->term)
                                {{ $course->term->term_code }} - {{ $course->term->term_descr }}
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Class Description</dt>
                        <dd class="col-sm-9">{{ $course->class_descr }}</dd>

                        <dt class="col-sm-3">Campus</dt>
                        
                        <dd class="col-sm-9">
                            <select 
                                hx-target="#courseInfo"
                                hx-select="#courseInfo" name="campus" id="campus" class="form-select"
                                onchange="this.form.submit()">
                                <option value="">Select a Campus</option>
                                @foreach ($campuses as $campus)
                                    <option value="{{ $campus->id }}" @if (isset($selectedCampus) && $campus->id == $selectedCampus->id) selected @endif>
                                        {{ $campus->name }}
                                    </option>
                                @endforeach
                            </select>
                        </dd>

                        <dt class="col-sm-3">Facility Type</dt>
                        <dd class="col-sm-9">
                            <select name="sa_facility_type" id="facility_type" class="form-select"
                                onchange="this.form.submit()">
                                <option value="">Select a Facility Type</option>
                                @foreach ($facilityTypes as $facilityType)
                                    <option value="{{ $facilityType }}" @if (isset($selectedFacilityType) && $facilityType == $selectedFacilityType) selected @endif>
                                        {{ $facilityType }}
                                    </option>
                                @endforeach
                            </select>
                        </dd>

                    </dl>
                </form>
            </div>
        </div>

        <div id="courseInfo">

            @if ($course->sections->count() > 0)
  
            <div class="card mb-3">
                <div class="card-body">
                    <dl>
                        <dt class="col-sm-3">Rooms used{{ $selectedCampus ? ' at ' . $selectedCampus->name : '' }}</dt>
                        <dd class="col-sm-9">{{ $course->sections->unique('room_id')->count() }}</dd>

                        <dt class="col-sm-3">Total WSCH{{ $selectedCampus ? ' at ' . $selectedCampus->name : '' }}</dt>
                        <dd class="col-sm-9">
                            {{ ceil($course->sections->sum('day10_enrol') * $course_duration_nearest_hour) }}
                        </dd>

                        <dt class="col-sm-3">Class Duration Weekly</dt>
                        <dd class="col-sm-9">{{ $course->class_duration_weekly }}</dd>

                        <dt class="col-sm-3">% Full</dt>
                        <dd class="col-sm-9">
                            @php
                                $totalEnrollment = $course->sections->sum('day10_enrol');
                                $totalCapacity = $course->sections->sum(function($section) {
                                    return $section->room ? $section->room->capacity : 0;
                                });
                                $percentFull = $totalCapacity > 0 ? ($totalEnrollment / $totalCapacity) * 100 : 0;
                            @endphp
                            {{ number_format($percentFull, 2) }}%
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Days of Week Tabs -->
            <ul class="nav nav-tabs mb-3" id="dayTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    @php
                        $allDaysParams = request()->all();
                        unset($allDaysParams['day_type']);
                        $allDaysUrl = route('courses.show', ['id' => $course->id]) . (!empty($allDaysParams) ? '?' . http_build_query($allDaysParams) : '');
                    @endphp
                    <a class="nav-link {{ (!isset($dayType) || $dayType == 'all') ? 'active' : '' }}" 
                       href="{{ $allDaysUrl }}" 
                       role="tab">All Days</a>
                </li>
                <li class="nav-item" role="presentation">
                    @php
                        $mwfParams = request()->all();
                        $mwfParams['day_type'] = 'mwf';
                        $mwfUrl = route('courses.show', ['id' => $course->id]) . '?' . http_build_query($mwfParams);
                    @endphp
                    <a class="nav-link {{ (isset($dayType) && $dayType == 'mwf') ? 'active' : '' }}" 
                       href="{{ $mwfUrl }}" 
                       role="tab">MWF (Mon/Wed/Fri)</a>
                </li>
                <li class="nav-item" role="presentation">
                    @php
                        $tuthParams = request()->all();
                        $tuthParams['day_type'] = 'tuth';
                        $tuthUrl = route('courses.show', ['id' => $course->id]) . '?' . http_build_query($tuthParams);
                    @endphp
                    <a class="nav-link {{ (isset($dayType) && $dayType == 'tuth') ? 'active' : '' }}" 
                       href="{{ $tuthUrl }}" 
                       role="tab">TuTh (Tue/Thu)</a>
                </li>
            </ul>

            <h2>Sections</h2>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="enrollmentIncrease" class="form-label">Enrollment Increase (%)</label>
                            <input type="number" id="enrollmentIncrease" class="form-control" value="0" min="0"
                                max="100" step="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="percentageIncrease" class="form-label">Seat Utilization (%)</label>
                            <input type="number" id="percentageIncrease" class="form-control" value="{{ $selectedFacilityType && stripos($selectedFacilityType, 'LAB') !== false ? 80 : 75 }}" min="0"
                                max="100" step="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="blockPerDay" class="form-label"># of blocks per day</label>
                            <input 
                                type="number" 
                                id="blockPerDay" 
                                class="form-control" 
                                value="{{ (isset($dayType) && $dayType == 'tuth') ? 6 : 9 }}" 
                                data-lecture-default="{{ (isset($dayType) && $dayType == 'tuth') ? 6 : 9 }}"
                                min="0.1"
                                step="0.1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                @foreach ($componentCodes as $componentCode)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if ($loop->first) active @endif" id="{{ $componentCode }}-tab"
                            data-bs-toggle="tab" href="#{{ $componentCode }}" role="tab"
                            aria-controls="{{ $componentCode }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $componentCode }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-3" id="myTabContent">
                @foreach ($componentCodes as $componentCode)
                    <div class="tab-pane fade @if ($loop->first) show active @endif"
                        id="{{ $componentCode }}" role="tabpanel" aria-labelledby="{{ $componentCode }}-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Section</th>
                                        <th>Room</th>
                                        <th>Capacity</th>
                                        <th>Enroll</th>
                                        <th>Duration<br>(minutes)</th>
                                        <th>Days/<br>Week</th>
                                        <th>Blocks per<br>Week</th>
                                        <th>Enroll<br>Growth</th>
                                        <th class="seat-or-wsch-header">Seat<br>%</th>
                                        <th>Rooms<br>Needed</th>
                                        <th>Seat<br>Range</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($course->sections->where('component_code', $componentCode) as $section)
                                        @php
                                            $durationMinutes = $section->course->duration_minutes ?? 0;
                                            $totalClassDays = $section->total_class_days ?? 0;
                                            
                                            // Determine block length based on meeting days
                                            // MWF = 50 min blocks, TuTh = 75 min blocks
                                            $isTuTh = ($section->tuesday || $section->thursday) && !($section->monday || $section->wednesday || $section->friday);
                                            $blockLengthMinutes = $isTuTh ? 75 : 50;
                                            $blocksPerWeek = $totalClassDays * ceil($durationMinutes / $blockLengthMinutes);
                                            
                                            $facilityType = $section->room ? $section->room->sa_facility_type : ($selectedFacilityType ?? '');
                                            
                                            // Contact hours per class meeting, rounded up to nearest half hour
                                            $contactHours = $durationMinutes / 60;
                                            $contactHours = (int) ceil($contactHours * 2) / 2;
                                        @endphp
                                        <tr class="section-row" 
                                            data-original-enrollment="{{ $section->day10_enrol }}"
                                            data-duration-minutes="{{ $durationMinutes }}"
                                            data-capacity="{{ $section->room ? $section->room->capacity : 0 }}"
                                            data-total-class-days="{{ $totalClassDays }}"
                                            data-facility-type="{{ $facilityType }}"
                                            data-contact-hours="{{ $contactHours }}"
                                            data-block-length="{{ $blockLengthMinutes }}">
                                            <td>{{ $section->component_code }}</td>
                                            <td>{{ $section->section_number }}</td>
                                            <td>
                                                @if($section->room)
                                                    <a href="{{ route('rooms.show', $section->room->id) }}">
                                                        {{ $section->room->room_description }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">No room</span>
                                                @endif
                                            </td>
                                            <td class="forecast-capacity">{{ $section->room ? number_format($section->room->capacity) : '-' }}</td>
                                            <td class="forecast-enrollment">{{ number_format($section->day10_enrol) }}</td>
                                            <td class="forecast-duration">{{ $durationMinutes }}</td>
                                            <td class="forecast-days-per-week">{{ $totalClassDays }}</td>
                                            <td class="forecast-blocks-per-week">{{ $blocksPerWeek }}</td>
                                            <td class="forecast-enroll-growth">{{ number_format($section->day10_enrol) }}</td>
                                            <td class="forecast-seating-75"></td>
                                            <td class="forecast-rooms-needed"></td>
                                            <td class="forecast-seating-range"></td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
            @else
            <div class="alert alert-info" role="alert">
                No sections found for this course 
                {{-- if campus is set, point that out --}}
                @if (isset($selectedCampus))
                    at {{ $selectedCampus->name }}
                @endif
            </div>
            @endif
        </div>
    </div>

    <script>
        (function() {
            const selectedFacilityType = @json($selectedFacilityType ?? '');
            let isLabFilter = false;
            const defaultHoursPerWeek = 28;

            const enrollmentInput = document.querySelector('#enrollmentIncrease');
            const seatUtilInput = document.querySelector('#percentageIncrease');
            const blockPerDayInput = document.querySelector('#blockPerDay');

            function getLectureDefault() {
                if (blockPerDayInput && blockPerDayInput.dataset.lectureDefault) {
                    return parseFloat(blockPerDayInput.dataset.lectureDefault) || 9;
                }
                return 9;
            }

            function isLabTabActive() {
                const activePane = document.querySelector('#myTabContent .tab-pane.active');
                const componentCode = activePane ? (activePane.id || '') : '';
                return componentCode.toLowerCase().includes('lab');
            }

            function syncLabelAndMode() {
                isLabFilter = isLabTabActive();
                const blockPerDayLabel = document.querySelector('label[for="blockPerDay"]');
                if (blockPerDayLabel) {
                    blockPerDayLabel.textContent = isLabFilter ? 'Hours per week' : '# of blocks per day';
                }
                document.querySelectorAll('.seat-or-wsch-header').forEach(function(th) {
                    th.innerHTML = isLabFilter ? 'WSCH<br>Benchmark' : 'Seat<br>%';
                });
                if (blockPerDayInput) {
                    const lectureDefault = getLectureDefault();
                    const v = parseFloat(blockPerDayInput.value);
                    if (isLabFilter && (v === 6 || v === 9 || v === lectureDefault)) {
                        blockPerDayInput.value = defaultHoursPerWeek;
                    } else if (!isLabFilter && v === defaultHoursPerWeek) {
                        blockPerDayInput.value = lectureDefault;
                    }
                }
            }

            document.querySelectorAll('#myTab [data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function() {
                    syncLabelAndMode();
                    updateAllTables();
                });
            });
            syncLabelAndMode();

            function getBlockPerDay() {
                const value = parseFloat(blockPerDayInput?.value);
                if (!isNaN(value) && value > 0) {
                    return value;
                }
                return isLabFilter ? defaultHoursPerWeek : getLectureDefault();
            }
            
            function getSeatingRange(seating75Util) {
                if (seating75Util <= 0) return 'N/A';
                if (seating75Util <= 25) return '0-25';
                if (seating75Util <= 49) return '26-49';
                if (seating75Util <= 74) return '50-74';
                if (seating75Util <= 124) return '75-124';
                if (seating75Util <= 174) return '125-174';
                if (seating75Util <= 224) return '175-224';
                if (seating75Util <= 249) return '225-249';
                if (seating75Util <= 299) return '250-299';
                if (seating75Util <= 349) return '300-349';
                if (seating75Util <= 399) return '350-399';
                return '400+';
            }
            
            function formatNumber(value, decimals) {
                if (value === null || value === undefined || isNaN(value)) return '0';
                const num = parseFloat(value);
                if (!decimals) return Math.round(num).toLocaleString('en-US');
                return num.toLocaleString('en-US', { 
                    minimumFractionDigits: decimals, 
                    maximumFractionDigits: decimals 
                });
            }

            function updateForecastGrowth(row, growthPercentage, inputs) {
                const originalEnrollment = parseFloat(row.getAttribute('data-original-enrollment')) || 0;
                const totalClassDays = parseFloat(row.getAttribute('data-total-class-days')) || 0;
                const durationMinutes = parseFloat(row.getAttribute('data-duration-minutes')) || 0;
                const blockLengthMinutes = parseFloat(row.getAttribute('data-block-length')) || 50;
                const contactHours = parseFloat(row.getAttribute('data-contact-hours')) || 0;
                const capacity = parseFloat(row.getAttribute('data-capacity')) || 0;

                const seatUtilDecimal = inputs.seatUtilPercent / 100;
                const growthEnrollment = Math.round(originalEnrollment * (1 + growthPercentage / 100));

                if (isLabFilter) {
                    const wschBenchmark = capacity > 0 && inputs.blockPerDay > 0 && seatUtilDecimal > 0
                        ? Math.ceil(capacity * inputs.blockPerDay * seatUtilDecimal)
                        : 0;
                    const wschScheduled = growthEnrollment * contactHours * totalClassDays;
                    const roomsNeeded = wschBenchmark > 0 ? wschScheduled / wschBenchmark : 0;

                    row.querySelector('.forecast-enroll-growth').textContent = formatNumber(growthEnrollment);
                    row.querySelector('.forecast-seating-75').textContent = formatNumber(wschBenchmark);
                    row.querySelector('.forecast-rooms-needed').textContent = formatNumber(roomsNeeded, 2);
                    row.querySelector('.forecast-seating-range').textContent = getSeatingRange(capacity);
                    return;
                }

                const seating75Util = seatUtilDecimal > 0 ? Math.round(growthEnrollment / seatUtilDecimal) : 0;
                const blocksPerWeek = totalClassDays * Math.ceil(durationMinutes / blockLengthMinutes);
                const totalBlocksAvailable = inputs.blockPerDay * totalClassDays;
                const roomsNeeded = totalBlocksAvailable > 0 ? blocksPerWeek / totalBlocksAvailable : 0;

                row.querySelector('.forecast-enroll-growth').textContent = formatNumber(growthEnrollment);
                row.querySelector('.forecast-seating-75').textContent = formatNumber(seating75Util);
                row.querySelector('.forecast-rooms-needed').textContent = formatNumber(roomsNeeded, 2);
                row.querySelector('.forecast-seating-range').textContent = getSeatingRange(seating75Util);
            }

            function updateAllTables() {
                const enrollmentIncrease = parseFloat(enrollmentInput?.value) || 0;
                const seatUtilPercent = parseFloat(seatUtilInput?.value) || 75;
                const blockPerDay = getBlockPerDay();
                const inputs = { seatUtilPercent, blockPerDay };
                const rows = document.querySelectorAll('.section-row');
                for (let i = 0; i < rows.length; i++) {
                    updateForecastGrowth(rows[i], enrollmentIncrease, inputs);
                }
            }

            if (enrollmentInput) enrollmentInput.addEventListener('input', updateAllTables);
            if (seatUtilInput) seatUtilInput.addEventListener('input', updateAllTables);
            if (blockPerDayInput) blockPerDayInput.addEventListener('input', updateAllTables);

            updateAllTables();
        })();
    </script>
@endsection
