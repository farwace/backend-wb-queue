@extends(backpack_view('blank'))


@section('content')
    <div id="appRoot">
        <h2>Рабочее пространство</h2>
        <div class="queue">
            <template v-for="item in orderItems" :key="item.table.id + '-' + item.worker.id">
                <div class="item" :class="{fire:item.isRed}">
                    <div class="small"> @{{ item.table.name }}  @{{ item.worker.name }} </div>
                    <div> @{{ item.worker.code }} </div>
                    <small> @{{ item.timer }} </small>
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

                    const loadItems = () => {
                        $.ajax({
                            url: '/api/worker/v1.0/unavailable-tables',
                            type: 'GET',
                            success: function (res){
                                if(res.length && Array.isArray(res)){
                                    items.value = res;
                                }
                            }
                        })
                    }

                    setInterval(() => {
                        loadItems();
                    }, 15000)

                    loadItems();

                    const orderItems = computed(() => {
                        return [...items.value].sort((a, b) => new Date(a.updated_at) - new Date(b.updated_at)).map((item) => {
                            const now = new Date();
                            const updatedAt = new Date(item.updated_at);
                            const diffMs = now - updatedAt;

                            const totalSeconds = Math.floor(diffMs / 1000);
                            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
                            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
                            //const seconds = String(totalSeconds % 60).padStart(2, '0');

                            // item.timer = `${hours}:${minutes}:${seconds}`;
                            // item.isRed = diffMs > 9000000;
                            return {
                                ...item,
                                timer: `${hours}:${minutes}`,
                                isRed: diffMs > 9000000,
                            }
                            return item;
                        });
                    })

                    return {
                        items,
                        orderItems,
                    }
                }
            });

            app.mount('#appRoot');

        });
    </script>
    <style>
        .queue{
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem;
        }

        .queue .item{
            border-radius: 1rem;
            border: 1px solid #0056b3;
            padding: 2rem;
            text-align: center;
            font-size: 3rem;
        }
        .queue .item.fire{
            background-color: rgba(255, 0, 0, 0.2);
        }
    </style>
@endsection
