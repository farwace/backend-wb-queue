{{-- This file is used for menu items by any Backpack v6 theme --}}

<x-backpack::menu-item title="{{ trans('backpack::base.dashboard') }}" icon="la la-home" :link="backpack_url('dashboard')" />
<x-backpack::menu-item title="Направления" icon="la la-directions" :link="backpack_url('departments')" />
<x-backpack::menu-item title="Столики" icon="la la-table" :link="backpack_url('tables')" />
<x-backpack::menu-item title="Сотрудники" icon="la la-user" :link="backpack_url('workers')" />
<x-backpack::menu-item title="Очередь" icon="la la-hourglass-start" :link="backpack_url('queue')" />
<x-backpack::menu-item title="Грузчики" icon="la la-hard-hat" :link="backpack_url('loaders-settings')" />
