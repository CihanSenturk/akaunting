<x-index.container>
    <x-tabs active="{{ $tab_active }}">
        <x-slot name="navs">
            @foreach($tabs as $tab)
                @php
                    $route_params = ['search' => 'type:' . $tab['key']];
                @endphp

                <x-tabs.nav-link
                    id="category-{{ $tab['key'] }}"
                    name="{{ $tab['name'] }}"
                    href="{{ route('categories.index', $route_params) }}"
                    active="{{ $tab_active == 'category-' . $tab['key'] }}"
                />
            @endforeach
        </x-slot>

        <x-slot name="content">
            <x-index.search
                search-string="App\Models\Setting\Category"
                bulk-action="App\BulkActions\Settings\Categories"
            />

            <x-tabs.tab id="{{ $tab_active }}">
                <x-table>
                    <x-table.thead>
                        <x-table.tr>
                            <x-table.th kind="bulkaction">
                                <x-index.bulkaction.all />
                            </x-table.th>

                            <x-table.th class="w-6/12">
                                <x-sortablelink column="name" title="{{ trans('general.name') }}" />
                            </x-table.th>

                            <x-table.th class="w-6/12">
                                <x-sortablelink column="type" title="{{ trans_choice('general.types', 1) }}" />
                            </x-table.th>
                        </x-table.tr>
                    </x-table.thead>

                    <x-table.tbody>
                        @foreach($categories as $item)
                            <x-table.tr href="{{ route('categories.edit', $item->id) }}">
                                <x-table.td kind="bulkaction">
                                    <x-index.bulkaction.single
                                        id="{{ $item->id }}"
                                        name="{{ $item->name }}"
                                        :disabled="($item->isTransferCategory()) ? true : false"
                                    />
                                </x-table.td>

                                <x-table.td class="w-6/12">
                                    <div class="flex items-center">
                                        @if ($item->sub_categories->count())
                                            <x-tooltip id="tooltip-category-{{ $item->id }}" placement="bottom" message="{{ trans('categories.collapse') }}">
                                                <button
                                                    type="button"
                                                    class="w-4 h-4 flex items-center justify-center mx-2 leading-none align-text-top rounded-lg"
                                                    node="child-{{ $item->id }}"
                                                    onClick="toggleSub('child-{{ $item->id }}', event)"
                                                >
                                                    <span class="material-icons transform rotate-90 -ml-2 transition-all text-xl leading-none align-middle rounded-full bg-{{ $item->color }} text-white" style="background-color:{{ $item->color }};">chevron_right</span>
                                                </button>
                                            </x-tooltip>

                                            <div class="flex items-center font-bold">
                                                {{ $item->name }}
                                            </div>
                                    </div>
                                    @else
                                        <div class="flex items-center">
                                            <span class="material-icons text-{{ $item->color }}" class="text-3xl" style="color:{{ $item->color }};">circle</span>

                                            <span class="font-bold ltr:ml-2 rtl:mr-2">
                                                {{ $item->name }}
                                            </span>
                                        </div>
                                    @endif

                                    @if (! $item->enabled)
                                        <x-index.disable text="{{ trans_choice('general.categories', 1) }}" />
                                    @endif
                                </x-table.td>

                                <x-table.td class="w-6/12">
                                    @if (! empty($types[$item->type]))
                                        {{ $types[$item->type] }}
                                    @else
                                        <x-empty-data />
                                    @endif
                                </x-table.td>

                                <x-table.td kind="action">
                                    <x-table.actions :model="$item" />
                                </x-table.td>
                            </x-table.tr>

                            @foreach($item->sub_categories as $sub_category)
                                @include('settings.categories.sub_category', ['parent_category' => $item, 'sub_category' => $sub_category, 'tree_level' => 1])
                            @endforeach
                        @endforeach
                    </x-table.tbody>
                </x-table>

                <x-pagination :items="$categories" />
            </x-tabs.tab>
        </x-slot>
    </x-tabs>
</x-index.container>
