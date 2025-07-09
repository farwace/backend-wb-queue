@extends(backpack_view('blank'))


@section('content')
    <div id="appRoot">
        <template v-if="currentDepartment?.name">
            <h2>
                @{{ currentDepartment?.name }}
                <button class="btn btn-secondary" type="button" @click="setDepartment(0)">Назад</button>
                &nbsp;<a class="btn btn-warnings" :href="'/admin/export-logs/' + currentDepartment.id" target="_blank">Логи 3 дня</a>
                @if(backpack_user()->is_root == '1')
                    &nbsp;<a class="btn btn-warnings" :href="'/admin/export-logs-month/' + currentDepartment.id + '?duration=month'" target="_blank">Логи за месяц</a>
                @endif
            </h2>
        </template>
        <template v-if="cCheckTables && cCheckTables.length > 0">
            <h2>Проверить стол (сотрудник нажал выход)</h2>
            <div class="queue small mb-6">
                <template v-for="item in cCheckTables" :key="item.id + '-' + item.code">
                    <div class="item blue">
                        <small class="small"> @{{ item.name }}  @{{ item.workerName.slice(0,15) }} </small>
                        <div><small> @{{ item.workerCode }} </small></div>
                        <div><small v-if="item.time"> @{{ item.time }} </small></div>
                        <button class="btn btn-secondary" type="button" @click="sendChecked(item.id)">Проверено!</button>
                    </div>
                </template>
            </div>
        </template>
        <h2>
            Рабочее пространство
        </h2>
        <template v-if="departmentId < 1">
            <h2>Выберите направление:</h2>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-secondary" type="button" :key="'dep-' + dep.id" v-for="dep in departments" @click="setDepartment(dep.id)">
                    @{{ dep.name }}
                </button>
            </div>
        </template>
        <div class="queue">
            <template v-for="item in orderItems" :key="item.table.id + '-' + item.worker.id">
                <div class="item" :class="{fire:item.isRed}">
                    <small class="small"> @{{ item.table.name }}  @{{ item.worker.name.slice(0,15) }} </small>
                    <div><small> @{{ item.worker.code }} </small></div>
                    <div class="microtext">Время с момента получения:</div>
                    <div><small>@{{ item.timer }} </small></div>
                    <button class="btn btn-danger" type="button" @click="tryClearTable(item.id, item.table.name, item.worker.name, item.worker.code)">Освободить стол</button>
                </div>
            </template>
        </div>
    </div>
    <script src="/assets/vue/vue.global.js"></script>
    <script>
        addEventListener('DOMContentLoaded', function (){
            const { createApp, ref, onMounted, onUnmounted, watch, computed, TransitionGroup, nextTick } = Vue;

            const app = createApp({
                setup: () => {
                    const items = ref([]);
                    const checkTables = ref([]);
                    const departmentId = ref(localStorage.getItem('department') || 0);
                    const departments = ref([]);

                    const now = ref(new Date());

                    const loadItems = () => {
                        if(departments.value.length < 1){
                            $.ajax({
                                url: '/admin/departments-list',
                                type: "GET",
                                success: function (res){
                                    departments.value = res;
                                }
                            })
                        }

                        if(departmentId.value < 1) {
                            return;
                        }

                        $.ajax({
                            url: `/api/worker/v1.0/unavailable-tables/${departmentId.value}`,
                            type: 'GET',
                            success: function (res){
                                if('in_progress' in res){
                                    items.value = res['in_progress'];
                                }
                                if('closed' in res){
                                    checkTables.value = res['closed'];
                                }
                            }
                        })
                    }

                    setInterval(() => {
                        now.value = new Date();
                    }, 1000)

                    setInterval(() => {
                        loadItems();
                    }, 15000)

                    loadItems();

                    const orderItems = computed(() => {
                        return [...items.value].filter((i) => {
                            return i?.table?.department?.code == currentDepartment.value?.code;
                        }).sort((a, b) => new Date(a.updated_at) - new Date(b.updated_at)).map((item) => {

                            const updatedAt = new Date(item.updated_at);
                            const diffMs = now.value - updatedAt;

                            const totalSeconds = Math.floor(diffMs / 1000);
                            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
                            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                            const seconds = String(totalSeconds % 60).padStart(2, '0');

                            // item.timer = `${hours}:${minutes}:${seconds}`;
                            // item.isRed = diffMs > 9000000;
                            return {
                                ...item,
                                timer: `${hours}:${minutes}:${seconds}`,
                                isRed: diffMs > 9000000,
                            }
                        });
                    })

                    const sendChecked = (tableId) => {
                        $.ajax({
                            url: '/api/worker/v1.0/check-table',
                            type: 'POST',
                            data: {
                                table_id: tableId
                            },
                            success: function (res){
                                checkTables.value = checkTables.value.filter((i) => {
                                    return i.id != tableId;
                                })
                            }
                        })
                    }

                    const setDepartment = (depId) => {
                        console.log(depId)
                        localStorage.setItem('department', depId);
                        departmentId.value = depId;
                        loadItems();
                    }

                    const currentDepartment = computed(() => {
                        return departments.value.filter((d) => {
                            return d.id == departmentId.value
                        })?.[0] || undefined
                    })

                    const cCheckTables = computed(() => {
                        return checkTables.value.map((i) => {
                            i.time = '';
                            if(i.timestamp){
                                const date = new Date(i.timestamp)
                                i.time = date.toLocaleTimeString('ru-RU', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                });
                            }
                            return i;
                        }).filter((i) => {
                            return !i['departmentId'] || i['departmentId'] == departmentId.value
                        })
                    })

                    const tryClearTable = (id, tableName, workerName, workerCode) => {
                        swal({
                            title: `Подтвердите освобождение ${tableName}`,
                            text: `[${workerCode}] ${workerName} - разлогинить сотрудника?`,
                            icon: 'warning',
                            buttons: {
                                cancel: {
                                    text: 'Отменить',
                                    value: null,
                                    className: 'bg-secondary',
                                    closeModal: true,
                                    visible: true,
                                },
                                delete: {
                                    text: 'Освободить',
                                    className: 'bg-danger',
                                    value: true,
                                    visible: true,
                                },
                            },
                            dangerMode: true,
                        }).then(function (e){
                            if(e){
                                $.ajax({
                                    url: '/api/worker/v1.0/leave-table',
                                    type: 'POST',
                                    beforeSend: function(request) {
                                        request.setRequestHeader("badge-code", workerCode);
                                    },
                                    data: {
                                        'is_admin': true,
                                    },
                                    success: function (){
                                        items.value = [...items.value].filter((i) => {
                                            return i.id != id
                                        })
                                    }
                                })
                            }
                        })
                    }

                    return {
                        items,
                        orderItems,
                        cCheckTables,
                        sendChecked,
                        departmentId,
                        setDepartment,
                        departments,
                        currentDepartment,
                        tryClearTable
                    }
                }
            });

            app.mount('#appRoot');

        });
    </script>
    <style>
        .queue{
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 2rem;
        }
        .queue.small{
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
        }
        .queue.small .item{
            font-size: 2rem;
            padding: 1rem;
        }
        .queue .item{
            border-radius: 1rem;
            border: 1px solid #0056b3;
            text-align: center;
            font-size: 2rem;
            padding: 1rem;
        }
        .queue .item.fire{
            background-color: rgba(255, 0, 0, 0.2);
        }
        .queue .item.blue{
            background-color: rgba(17, 0, 255, 0.2);
        }
        .microtext{
            font-size: 14px;
            margin-bottom: -14px;
        }
    </style>
@endsection
