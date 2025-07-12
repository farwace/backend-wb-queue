@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      'Статистика' => false,
    ];
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">Статистика по направлениям</span>
        </h2>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Общая статистика по направлениям</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Направление</th>
                                <th>Код</th>
                                <th>Столов в очереди</th>
                                <th>Обработано сегодня</th>
                                <th>Обработано за неделю</th>
                                <th>Обработано за месяц</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentStats as $stat)
                            <tr>
                                <td>{{ $stat['name'] }}</td>
                                <td>{{ $stat['code'] }}</td>
                                <td><span class="badge badge-warning">{{ $stat['tables_in_queue'] }}</span></td>
                                <td><span class="badge badge-success">{{ $stat['processed_pallets_today'] }}</span></td>
                                <td><span class="badge badge-info">{{ $stat['processed_pallets_week'] }}</span></td>
                                <td><span class="badge badge-primary">{{ $stat['processed_pallets_month'] }}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showDepartmentChart({{ $stat['id'] }}, '{{ $stat['name'] }}')">
                                        <i class="la la-chart-line"></i> График
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Поиск по сотруднику</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="worker_code">Номер бейджа сотрудника:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="worker_code" placeholder="Введите номер бейджа">
                        <div class="input-group-append">
                            <button class="btn btn-primary" onclick="searchWorker()">
                                <i class="la la-search"></i> Поиск
                            </button>
                        </div>
                    </div>
                </div>
                <div id="worker-info" style="display: none;">
                    <div class="alert alert-info">
                        <strong>Сотрудник:</strong> <span id="worker-name"></span><br>
                        <strong>Бейдж:</strong> <span id="worker-badge"></span><br>
                        <strong>Направление:</strong> <span id="worker-department"></span>
                    </div>
                </div>
                <div id="worker-error" style="display: none;">
                    <div class="alert alert-danger">
                        <span id="worker-error-message"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">График сотрудника (последние 30 дней)</h3>
            </div>
            <div class="card-body">
                <canvas id="workerChart" style="display: none;"></canvas>
                <div id="no-worker-data" class="text-center text-muted">
                    <i class="la la-search" style="font-size: 48px;"></i>
                    <p>Введите номер бейджа для отображения статистики</p>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentDepartmentId = null;
let departmentChart = null;
let workerChart = null;

function showDepartmentChart(departmentId, departmentName) {
    currentDepartmentId = departmentId;
    document.getElementById('modal-department-name').textContent = departmentName;
    $('#departmentChartModal').modal('show');

    // Reset period buttons
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector('[data-period="days"]').classList.add('active');

    loadDepartmentChart('days');
}

function loadDepartmentChart(period) {
    if (!currentDepartmentId) return;

    fetch(`{{ url(config('backpack.base.route_prefix')) }}/statistics/department/${currentDepartmentId}/chart?period=${period}`)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('departmentChart').getContext('2d');

            if (departmentChart) {
                departmentChart.destroy();
            }

            departmentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.data.map(item => item.label),
                    datasets: [{
                        label: 'Обработано палетов',
                        data: data.data.map(item => item.value),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
        });
}

function searchWorker() {
    const workerCode = document.getElementById('worker_code').value.trim();

    if (!workerCode) {
        showWorkerError('Введите номер бейджа');
        return;
    }

    fetch(`{{ url(config('backpack.base.route_prefix')) }}/statistics/worker?worker_code=${workerCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showWorkerError(data.error);
                return;
            }

            showWorkerInfo(data.worker);
            showWorkerChart(data.daily_stats);
        })
        .catch(error => {
            console.error('Error searching worker:', error);
            showWorkerError('Ошибка при поиске сотрудника');
        });
}

function showWorkerInfo(worker) {
    document.getElementById('worker-name').textContent = worker.name;
    document.getElementById('worker-badge').textContent = worker.code;
    document.getElementById('worker-department').textContent = worker.department;

    document.getElementById('worker-info').style.display = 'block';
    document.getElementById('worker-error').style.display = 'none';
}

function showWorkerError(message) {
    document.getElementById('worker-error-message').textContent = message;
    document.getElementById('worker-error').style.display = 'block';
    document.getElementById('worker-info').style.display = 'none';
    document.getElementById('workerChart').style.display = 'none';
    document.getElementById('no-worker-data').style.display = 'block';
}

function showWorkerChart(dailyStats) {
    const ctx = document.getElementById('workerChart').getContext('2d');

    if (workerChart) {
        workerChart.destroy();
    }

    workerChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dailyStats.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
            }),
            datasets: [{
                label: 'Обработано палетов',
                data: dailyStats.map(item => item.pallets_count),
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    document.getElementById('workerChart').style.display = 'block';
    document.getElementById('no-worker-data').style.display = 'none';
}

// Period button handlers
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');

            // Update active button
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');

            loadDepartmentChart(period);
        });
    });

    // Enter key handler for worker search
    document.getElementById('worker_code').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchWorker();
        }
    });

    // Modal close button handler
    document.getElementById('modal-close-btn').addEventListener('click', function() {
        $('#departmentChartModal').modal('hide');
    });

    // Handle modal backdrop clicks to close modal
    document.getElementById('departmentChartModal').addEventListener('click', function(e) {
        if (e.target === this) {
            $('#departmentChartModal').modal('hide');
        }
    });
});
</script>
<style>
    .modal-backdrop.fade.show{

    }
</style>
@endsection

<!-- Department Chart Modal -->
<div class="modal fade" id="departmentChartModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">График направления: <span id="modal-department-name"></span></h4>
                <button type="button" class="close" id="modal-close-btn">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Период:</label>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-period="days">По дням</button>
                        <button type="button" class="btn btn-outline-primary" data-period="hours">По часам</button>
                        <button type="button" class="btn btn-outline-primary" data-period="months">По месяцам</button>
                    </div>
                </div>
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
    </div>
</div>
