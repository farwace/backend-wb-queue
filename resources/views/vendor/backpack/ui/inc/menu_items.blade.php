{{-- This file is used for menu items by any Backpack v6 theme --}}

<x-backpack::menu-item title="{{ trans('backpack::base.dashboard') }}" icon="la la-home" :link="backpack_url('dashboard')" />
@if(backpack_user()->is_root)
<x-backpack::menu-item title="Администраторы" icon="la la-user-tie" :link="backpack_url('admins')" />
@endif
<x-backpack::menu-item title="Направления" icon="la la-directions" :link="backpack_url('departments')" />

<x-backpack::menu-item title="Столы" icon="la la-table" :link="backpack_url('tables')" />
<x-backpack::menu-item title="Сотрудники" icon="la la-user" :link="backpack_url('workers')" />
<x-backpack::menu-item title="Очередь" icon="la la-hourglass-start" :link="backpack_url('queue')" />
<x-backpack::menu-item title="Грузчики" icon="la la-hard-hat" :link="backpack_url('loaders-settings')" />
<x-backpack::menu-item title="Отписки сотрудников" icon="la la-sticky-note" :link="backpack_url('reports')" />
<x-backpack::menu-item title="Отписки грузчиков" icon="la la-ticket" :link="backpack_url('replies')" />
<x-backpack::menu-item title="Инциденты" icon="la la-comment" :link="backpack_url('incidents')" />
@if(backpack_user()->is_root)
<x-backpack::menu-item title="Activity Logs" icon="la la-stream" :link="backpack_url('activity-log')" />
@endif
