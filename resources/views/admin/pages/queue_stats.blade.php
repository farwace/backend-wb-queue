@extends(backpack_view('blank'))

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <form method="GET" action="{{ route('queue-stats.index') }}" class="form-inline">
                <label for="worker_badge" class="mr-2">Сотрудник:</label>
                <select name="worker_badge" id="worker_badge" class="form-control mr-2">
                    <option value="">Все</option>
                    @foreach($workers as $w)
                        <option value="{{ $w->worker_badge }}" {{ (string)$selectedBadge === (string)$w->worker_badge ? 'selected' : '' }}>
                            {{ $w->worker_name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary">Показать</button>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach(['All','Worker','Dept','LoginLogout','Warnings'] as $chart)
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5>
                            @switch($chart)
                                @case('All') Обработано товаров всеми @break
                                @case('Worker') Обработано товаров сотрудником @break
                                @case('Dept') По направлениям @break
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Все товары по дням
            new Chart(document.getElementById('chartAll'), {
                type: 'line',
                data: { labels: @json($dates), datasets: [{ label: 'Товары', data: @json($allTotals), fill: false }] }
            });

            // 2. По выбранному сотруднику
            new Chart(document.getElementById('chartWorker'), {
                type: 'line',
                data: { labels: @json($workerDates), datasets: [{ label: 'Товары', data: @json($workerTotals), fill: false }] }
            });

            // 3. По направлениям (департаментам)
            new Chart(document.getElementById('chartDept'), {
                type: 'bar',
                data: { labels: @json($deptLabels), datasets: [{ label: 'Товары', data: @json($deptTotals) }] }
            });

            // 4. Входы/выходы по дням
            const ll = @json($loginLogout);
            const days = [...new Set(ll.map(i=>i.date))];
            const logins = days.map(d=> (ll.find(x=>x.date===d&&x.status==='login')||{}).total||0);
            const logouts = days.map(d=> (ll.find(x=>x.date===d&&x.status==='logout')||{}).total||0);
            new Chart(document.getElementById('chartLoginLogout'), {
                type: 'line',
                data: { labels: days, datasets: [ { label: 'Login', data: logins, fill: false }, { label: 'Logout', data: logouts, fill: false } ] }
            });

            // 5. Попытки раньше времени
            new Chart(document.getElementById('chartWarnings'), {
                type: 'bar',
                data: { labels: @json($warningDates), datasets: [{ label: 'Попытки', data: @json($warningTotals) }] }
            });
        });
    </script>
