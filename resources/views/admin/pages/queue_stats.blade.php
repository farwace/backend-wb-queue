@extends(backpack_view('blank'))
@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <form method="GET" action="{{ route('queue-stats.index') }}" class="form-inline">
                <label for="worker_badge" class="mr-2">Сотрудник (бейдж):</label>
                <select name="worker_badge" id="worker_badge" class="form-control select2 mr-2" style="width:200px;">
                    <option value=""></option>
                    @foreach($workers as $w)
                        <option value="{{ $w->worker_badge }}" {{ (string)$selectedBadge === (string)$w->worker_badge ? 'selected' : '' }}>
                            {{ $w->worker_badge }} — {{ $w->worker_name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary">Показать</button>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach(['All','Worker','Dept','LoginLogout','Warnings'] as $chart)
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5>
                            @switch($chart)
                                @case('All') Всего товаров по дням @break
                                @case('Worker') Товары сотрудника по дням @break
                                @case('Dept') Сравнение направлений по дням @break
                                @case('LoginLogout') Входы/Выходы @break
                                @case('Warnings') Попытки раньше времени @break
                            @endswitch
                        </h5>
                        <canvas id="chart{{ $chart }}"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('after_scripts')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инит Select2 для поиска по бейджу
            $('#worker_badge').select2({
                placeholder: 'Введите бейдж',
                allowClear: true
            });

            // 1. Всего товаров по дням
            new Chart(document.getElementById('chartAll'), { type: 'line', data: {
                    labels: @json($allByDay->pluck('date')),
                    datasets: [{ label: 'Товары', data: @json($allByDay->pluck('total')), fill: false }]
                }});

            // 2. Товары сотрудника по дням
            new Chart(document.getElementById('chartWorker'), { type: 'line', data: {
                    labels: @json($byWorkerByDay->pluck('date')),
                    datasets: [{ label: 'Товары', data: @json($byWorkerByDay->pluck('total')), fill: false }]
                }});

            // 3. Сравнение направлений по дням
            new Chart(document.getElementById('chartDept'), { type: 'bar', data: {
                    labels: @json($deptDates),
                    datasets: @json($deptDataSets)
                }});

            // 4. Входы/Выходы
            const ll = @json($loginLogout);
            const daysLL = [...new Set(ll.map(i=>i.date))];
            const loginCounts = daysLL.map(d=> (ll.find(x=>x.date===d && x.status==='login')||{}).total||0);
            const logoutCounts= daysLL.map(d=> (ll.find(x=>x.date===d && x.status==='logout')||{}).total||0);
            new Chart(document.getElementById('chartLoginLogout'), { type: 'line', data: {
                    labels: daysLL,
                    datasets: [ { label: 'Login', data: loginCounts, fill: false }, { label: 'Logout', data: logoutCounts, fill: false } ]
                }});

            // 5. Попытки раньше времени
            new Chart(document.getElementById('chartWarnings'), { type: 'bar', data: {
                    labels: @json($warnings->pluck('date')),
                    datasets: [{ label: 'Попытки', data: @json($warnings->pluck('total')) }]
                }});
        });
    </script>
@endsection
