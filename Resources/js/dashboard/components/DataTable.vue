<template>
    <div class="datatable">
        <slot name="before" :tableData="tableData"></slot>

        <div v-if="this.filterFields.length > 0">
            <p class="mb-1">
                <strong>Filters</strong>

                <a href="#" class="btn ml-1" @click.prevent="resetFilters">
                    Reset
                </a>
            </p>

            <div class="flex flex-wrap space-between"
                :class="{ 'mb-1': !hasHiddenFilters }"
            >
                <div v-for="(filter, index) in this.filterFields"
                    :key="index"
                    class="filter mr-05 ml-05 mb-1"
                    :class="{ 'filter-hidden': filter.hidden && !showHiddenFilters }"
                >
                    <div class="mb-05 text-semibold">{{ filter.label }}:</div>

                    <v-form-el :inline="true"
                        :field="{
                            type: filter.type,
                            attrs: filter.attrs,
                        }"
                        :collections="{ [filter.name]: filter.options }"
                        v-model="filter.value"
                        class="filter-field"></v-form-el>
                </div>
            </div>

            <p v-if="hasHiddenFilters" class="mb-1">
                <a href="#" class="btn" @click.prevent="toggleHiddenFilters">
                    {{ showHiddenFilters ? 'Less Filters' : 'More Filters' }}
                </a>
            </p>
        </div>

        <div class="flex pb-1">
            <div class="flex flex-align-end flex-1">
                <div v-if="bulkActionsOn && hasBulkActions" class="mr-1">
                    <v-form-el :inline="true"
                        :field="{
                            type: 'select',
                            attrs: bulkActionsSelect.attrs,
                        }"
                        :collections="bulkActionsSelect.collection"
                        class="bulk-action-select"
                        v-model="selectedBulkAction"
                        @change="onBulkActionSelected"></v-form-el>
                </div>

                <a v-if="this.exportableFields && Object.keys(this.exportableFields).length"
                    href="#"
                    class="btn"
                    @click.prevent="showExportModal">Export</a>

                <div v-if="searchOn">
                    <span class="mr-1">Search:</span>

                    <v-form-el :inline="true"
                        :field="{
                            type: 'text'
                        }"
                        v-model="searchQuery"
                        class="search-field"></v-form-el>
                </div>
            </div>

            <div class="flex flex-align-end top-panel-extra">
                <slot name="top-panel" :tableData="tableData"></slot>

                <a v-if="createAction && allowedTo('create')"
                    :href="createAction.url"
                    class="btn">Add</a>
            </div>
        </div>

        <div class="table-wrapper" ref="table-wrapper">
            <table class="table card">
                <thead>
                    <th v-if="bulkActionsOn && hasBulkActions" class="bulk-actions">
                        <v-form-el :inline="true"
                            :field="{
                                type: 'checkbox'
                            }"
                            :value="allPageItemsSelected"
                            @change="onSelectAllToggle"></v-form-el>
                    </th>

                    <th v-for="key in Object.keys(visibleFields)"
                        :key="key"
                        :class="{ 'sortable': fields[key].sortable }"
                        @click="toggleSort(key)"
                    >
                        {{ fields[key].name }}

                        <span v-if="fields[key].sortable">
                            <i v-if="sort === key && sortAsc" class="las la-sort-up"></i>

                            <i v-else-if="sort === key" class="las la-sort-down"></i>
                            
                            <i v-else class="las la-sort"></i>
                        </span>
                    </th>

                    <th v-if="hasActions">Actions</th>
                </thead>
                
                <tbody>
                    <tr v-if="allPageItemsSelected && !this.selectedItems._all">
                        <td :colspan="columnsCount" class="text-center">
                            All items on this page are selected.
                            <a href="#" @click.prevent="selectAllItems">
                                Select all {{ tableData.total }} items
                            </a>
                        </td>
                    </tr>
                    <tr v-for="(item, index) in filteredItems"
                        :key="index"
                        :class="{
                            'text-bold': item._style_text_bold,
                            'text-semibold': item._style_text_semibold,
                            'text-primary': item._style_text_primary,
                            'text-danger': item._style_text_danger,
                            'text-success': item._style_text_success,
                        }"
                    >
                        <td v-if="bulkActionsOn && hasBulkActions && item._selectable !== false" class="bulk-actions">
                            <v-form-el :inline="true"
                                :field="{
                                    type: 'checkbox'
                                }"
                                v-model="selectedItems[primaryKey(item)]"
                                @change="onItemSelectionToggled(item)"></v-form-el>
                        </td>

                        <td v-for="key in Object.keys(visibleFields)"
                            :key="key"
                        >
                            <slot name="cell"
                                :field="key"
                                :item="item"
                                :original-value="item[key]"
                                :formatted-value="formatted(fields[key], item, key)"
                            >
                                <div v-html="formatted(fields[key], item, key)"></div>
                            </slot>
                        </td>

                        <td v-if="hasActions" class="actions">
                            <template v-for="(action, index) in allActions.single">
                                <el-dropdown v-if="isActionsList(action) && showActionForItem(action, item)"
                                    :key="index"
                                    @command="onDropdownActionClick"
                                >
                                    <a href="#"
                                        class="el-dropdown-link"
                                        :class="`action-btn ${action.class ? action.class : 'primary'}`"
                                        :title="action.title ? action.title : ''"
                                        @click.prevent
                                    >
                                        <span v-html="actionIcon(action, item)"></span><i class="el-icon-arrow-down el-icon--right"></i>
                                    </a>

                                    <el-dropdown-menu slot="dropdown" >
                                        <template v-for="(subAction, index) in action.list">
                                            <el-dropdown-item :key="index"
                                                v-if="allowedTo(subAction.name) && showActionForItem(subAction, item)"
                                                :title="subAction.title ? subAction.title : ''"
                                                :command="{ subAction, item }"
                                                @click="confirmAction(subAction, item)"
                                                v-html="actionIcon(subAction, item)"
                                            ></el-dropdown-item>
                                        </template>
                                    </el-dropdown-menu>
                                </el-dropdown>

                                <a v-else-if="!isActionsList(action) && !action.grouped && allowedTo(action.name) && showActionForItem(action, item)"
                                    :key="index"
                                    href="#"
                                    :class="`action-btn ${action.class ? action.class : 'primary'}`"
                                    :title="action.title ? action.title : ''"
                                    @click.prevent="confirmAction(action, item)"
                                    v-html="actionIcon(action, item)"
                                ></a>
                            </template>
                        </td>
                    </tr>

                    <tr v-if="!filteredItems.length" class="m-1 text-center">
                        <td :colspan="columnsCount">
                            NO DATA
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-show="processing" class="processing-overlay">
                <i class="las la-sync spin-ease"></i>
            </div>
        </div>

        <v-paginator :page="page"
            :info="tableData"
            @pageChanged="onPageChanged"></v-paginator>

        <v-modal v-if="exportOn" m-id="export-modal">
            <p class="text-bold mb-1">
                Export {{ selectedItemsCount }} selected items
            </p>

            <v-form :action="exportUrl"
                method="POST"
                :button="{
                    text: 'Export Data'
                }"
                @submitsuccess="onExportDataReady"
            >
                <template v-slot:default="slotProps">
                    <v-form-tab name="Main"
                        :errors="slotProps.errors"
                        :values="slotProps.values"
                    >
                        <div class="flex">
                            <v-form-el v-for="(key, index) in Object.keys(exportableFields)"
                                :key="index"
                                :inline="true"
                                :field="{
                                    name: 'export_fields[]',
                                    type: 'checkbox',
                                    label: exportableFields[key].name,
                                    value: exportableFields[key].name
                                        + '|' + key
                                        + '|' + (exportFormat(key)),
                                    checked: exportableFields[key].selected,
                                }"
                                ></v-form-el>
                        </div>

                        <v-form-el :inline="true"
                            :field="{
                                name: 'export_format',
                                type: 'select',
                                label: 'Format',
                                attrs: {
                                    idField: 'value',
                                    textField: 'text',
                                    required: true,
                                },
                                options: [
                                    { value: 'csv', text: 'CSV', },
                                    { value: 'txt', text: 'Text', },
                                ],
                            }"
                            ></v-form-el>

                        <template v-if="selectedItems._all">
                            <v-form-el :field="{
                                name: 'items[]',
                                type: 'hidden',
                                value: '_all',
                            }"></v-form-el>
                        </template>

                        <template v-else>
                            <template v-for="(index, loopIndex) in Object.keys
                            (selectedItems)">
                                <input v-if="index !== '_all'"
                                    :key="loopIndex"
                                    type="hidden"
                                    name="items[]"
                                    :value="index">
                            </template>
                        </template>
                    </v-form-tab>
                </template>
            </v-form>
        </v-modal>

        <slot name="after" :tableData="tableData"></slot>
    </div>
</template>

<script>
import Paginator from './Paginator';

export default {
    components: {
        'v-paginator': Paginator,
    },

    props: {
        baseUrl: String,

        fields: {
            type: Object,
            default: () => {
                return {};
            },
            required: true,
        },

        // Row actions
        actions: {
            default: () => {
                return {};
            },
        },

        filters: {
            default: () => {
                return {};
            },
        },

        permissions: {
            default: () => {
                return {};
            },
        },

        // Default sorting
        sortBy: {
            type: String,
        },

        // Whether the search input should be visible
        searchOn: {
            type: Boolean,
            default: true,
        },

        // Whether the bulk actions should be enabled
        bulkActionsOn: {
            type: Boolean,
            default: true,
        },

        // Data export options. Format:
        // {
        //        url: String 'backend endpoint',
        //        fields: Array [list of available fields] | String 'all',
        //        selected: Array [list of fields selected by default],
        //        filenamePrefix: String
        // }
        export: {
            type: Object,
            default: function () {
                return {};
            },
        },

        clientSideSorting: {
            type: Boolean,
            default: false,
        },

        clientSidePagination: {
            type: Boolean,
            default: false,
        },

        itemsPerPage: {
            type: Number,
            default: 20,
        },

        useLocalData: {
            type: Boolean,
            default: false,
        },

        localData: {
            type: Object,
            default: () => {
                return {};
            },
        },

        urlMode: {
            type: Boolean,
            default: true,
        },
    },

    computed: {
        endpoint() {
            // Filters
            const filters = this.makeFiltersQuery();

            const connector = (this.baseUrl.indexOf('?') > -1) ? '&' : '?';

            return `${this.baseUrl}${connector}page=${this.page}&sort=${this.sort}|${this.sortAsc ? 'asc' : 'desc'}&search=${this.searchQuery}${filters}`;
        },

        hasActions() {
            return this.allActions.single && this.allActions.single.length > 0;
        },

        hasBulkActions() {
            return this.allActions.bulk && this.allActions.bulk.length > 0;
        },

        columnsCount() {
            return Object.keys(this.visibleFields).length
                + (this.bulkActionsOn && this.hasBulkActions ? 1 : 0)
                + (this.hasActions ? 1 : 0)
        },

        selectedItemsCount() {
            return this.selectedItems._all
                ? this.tableData.total
                : (Object.keys(this.selectedItems).length - 1);;
        },

        hasHiddenFilters() {
            return this.filterFields.filter(item => item.hidden).length > 0;
        },

        exportOn() {
            return this.export && this.export.url && this.export.fields;
        },

        exportUrl() {
            const url = this.export.url;
            const filters = this.makeFiltersQuery();

            return url.indexOf('?') === -1
                ? `${url}?search=${this.searchQuery}&sort=${this.sort}${filters}`
                : `${url}&search=${this.searchQuery}&sort=${this.sort}${filters}`;
        },

        allPageItemsSelected() {
            let selectedOnPage = 0;

            for (let item of this.items) {
                const key = this.primaryKey(item);

                if (this.selectedItems[key] || item._selectable === false) {
                    selectedOnPage++;
                }
            }

            return (
                this.items.length > 0 && this.items.length === selectedOnPage 
            );
        }
    },

    data() {
        return {
            initialized: false,
            processing: false,
            tableData: [],
            items: [],
            filteredItems: [],
            selectedItems: {},
            page: 1,
            sort: Object.keys(this.fields)[0],
            sortAsc: true,
            searchQuery: '',
            searchTimeout: null,
            visibleFields: {},
            exportableFields: {},

            filterFields: [],
            showHiddenFilters: false,

            allActions: {
                single: [],
                bulk: [],
            },
            createAction: null,
            primaryKeyFormat: 'id',

            bulkActionsSelect: {
                attrs: {},
                collection: {},
            },
            selectedBulkAction: [],

            filterDebounceTimeout: null,
        };
    },
 
    created() {
        this.parseFields();
        this.parsePageNumber();
        this.parseSortRule();
        this.parseFilters();
        this.parseActions();
        this.parseBulkActions();
        this.parseExportOptions();
        this.registerEvents();

        this.$nextTick(() => {
            this.fetchData();
            this.initialized = true;
        });
    },

    watch: {
        searchQuery() {
            this.onSearchQueryChanged();
        },

        filterFields: {
            deep: true,

            handler: function (newVal, oldVal) {
                this.onFiltersChanged(newVal, oldVal);
            }
        },

        selectedItems: {
            deep: true,

            handler: function (newVal, oldVal) {
                this.onSelectedItemsChanged(newVal, oldVal);
            }
        },

        tableData: {
            deep: true,

            handler: function () {
                this.onTableDataChanged();
            }
        }
     },

    methods: {
        /**
         * Get primary key value for an item. Composite keys are supported.
         */
        primaryKey(item) {
            const value = [];

            this.primaryKeyFormat.split('|').forEach((field) => {
                value.push(item[field])
            });

            return value.join('|');
        },

        registerEvents() {
            if (this.urlMode) {
                window.addEventListener('popstate', (event) => {
                    // Block watchers that re-fetch data
                    this.initialized = false;

                    this.parseSortRule();
                    this.parsePageNumber();
                    this.parseFilters();
                    
                    this.$nextTick(() => {
                        this.fetchData();
                        this.initialized = true;
                    });
                });
            }
        },

        parseFields() {
            for (let key of Object.keys(this.fields)) {
                if (key === '_primary_key') {
                    this.primaryKeyFormat = this.fields[key];

                    continue;
                }

                if (this.fields[key].exportOnly === true) {
                    continue;
                }

                this.visibleFields[key] = this.fields[key];
            }
        },

        parsePageNumber() {
            const queryParams = new URL(window.location).searchParams;

            let page = queryParams.get('page')
                ? parseInt(queryParams.get('page'))
                : 1;
            
            this.page = (!isNaN(page) && page > 0) ? page : 1;
        },

        parseSortRule() {
            let sortString;
            const queryParams = new URL(window.location).searchParams;

            if (this.urlMode && queryParams.get('sort')) {
                sortString = queryParams.get('sort');
            } else {
                sortString = this.sortBy;
            }

            if (!sortString) {
                return;
            }

            let parts = sortString.split('|');

            if (parts.length < 2) {
                return;
            }

            this.sort = parts[0];
            this.sortAsc = parts.length === 1
                || parts[1].toLowerCase() === 'asc';
        },

        parseFilters() {
            if (typeof this.filters !== 'object') {
                return;
            }

            this.filterFields = [];

            const queryParams = new URL(window.location).searchParams;

            for (let name of Object.keys(this.filters)) {
                const field = this.filters[name];
                let value;

                let filterName = 'f_' + name.replace(/\./, '__');

                if (this.urlMode && queryParams.has(filterName)) {
                    value = this.parseFilterValue(queryParams.get(filterName));

                    if (field.attrs && field.attrs.hasOwnProperty('value')) {
                        field.attrs.value = value;
                    }
                }

                if (field.options) {
                    let options = [];

                    if (field.options.length && typeof field.options[0] === 'string') {
                        for (let value of field.options) {
                            options.push({
                                [name]: value,
                            });
                        }
                    } else {
                        options = field.options;
                    }

                    const idField = field.attrs ? field.attrs.idField : name;

                    if (value === undefined) {
                        value = (field.attrs && field.attrs.required)
                            ? [options[0][idField]]
                            : null;
                    }

                    this.filterFields.push({
                        name: name,
                        label: field.name,
                        type: 'select',
                        options: options,
                        attrs: field.attrs
                            ? Object.assign(field.attrs, { collection: name })
                            : {
                                idField: idField,
                                textField: name,
                                collection: name,
                            },
                        value: value,
                        hidden: field.hidden || false,
                    });
                } else {
                    this.filterFields.push({
                        name: name,
                        label: field.name,
                        type: field.type ? field.type : 'text',
                        attrs: field.attrs ? field.attrs : {},
                        value: value,
                        hidden: field.hidden || false,
                    });
                }
            }
        },

        parseFilterValue(value) {
            if (value.toLowerCase() === 'true') {
                return true;
            } else if (value.toLowerCase() === 'false') {
                return false;
            }

            return value;
        },

        parseActions() {
            for (let name of Object.keys(this.actions)) {
                this.parseAction(name, this.actions[name]);
            }
        },

        parseAction(name, action) {
            const isBulkAction = !! (action.bulkUrl || action.bulk);

            // If the action is a list of actions - add all its children too
            if (this.isActionsList(action)) {
                for (let subName of Object.keys(action.list)) {
                    const subAction = action.list[subName];
                    subAction.grouped = true;

                    this.parseAction(subName, action.list[subName]);
                }
            }

            // Calculate action properties
            action.name = name;
            action.confirm = action.confirm ? action.confirm : false;
            action.icon = action.icon ? action.icon : null;
            action.class = action.class ? action.class : 'primary';
            action.title = action.title ? action.title : '';
            action.success = action.success ? action.success : null;
            action.ajax = action.ajax === false ? false : true;
            action.method = action.method ? action.method : 'post';
            action.customHandler = action.customHandler === true ? true : false;

            // Some default action have default settings
            if (name === 'create' || name === 'edit') {
                action.ajax = false;
                // NOTE: Doesn't seem like 'noBulk' is used anywhere
                // action.noBulk = true;
            }

            if (name === 'create') {
                // The create action is saved in its own object
                this.createAction = action;

                return;
            }

            if (name === 'edit') {
                action.icon = action.icon ? action.icon : 'la-edit';
                action.title = action.title ? action.title : 'Edit';
            }

            if (name === 'delete') {
                action.confirm = 'Are you sure you want to delete this item?';
                action.confirmBulk = 'Are you sure you want to delete the selected items?';
                action.icon = action.icon ? action.icon : 'la-trash';
                action.class = 'danger';
                action.title = action.title ? action.title : 'Delete';
                action.method = 'delete';
            }

            // Add action to the bulk actions collection
            if (isBulkAction) {
                const b = Object.assign({}, action, { bulk: true });
                
                // Bulk deletes are done via POST
                if (name === 'delete') {
                    b.method = 'post';
                }

                this.allActions.bulk.push(b);
            } else {
                // Add action to the single actions collection
                const a = Object.assign({}, action, { bulk: false });

                this.allActions.single.push(a);
            }
        },

        parseBulkActions() {
            this.bulkActionsSelect.attrs = {
                idField: 'name',
                textField: 'title',
                collection: 'bulkActions',
                placeholder: 'Bulk actions',
            };

            this.bulkActionsSelect.collection.bulkActions = [];

            for (let action of this.allActions.bulk) {
                // Skip subactions at this point
                if (action.grouped) {
                    continue;
                }

                if (this.isActionsList(action)) {
                    const options = [];

                    for (let name of Object.keys(action.list)) {
                        options.push({
                            name: name,
                            title: action.list[name].title,
                        });
                    }
                    
                    this.bulkActionsSelect.collection.bulkActions.push({
                        name: action.name,
                        title: action.title,
                        options: options,
                    });
                } else {
                    this.bulkActionsSelect.collection.bulkActions.push({
                        name: action.name,
                        title: action.title,
                        options: action.options,
                    });
                }
            }
        },

        parseExportOptions() {
            if (!this.exportOn) {
                return;
            }

            const fields = this.export.fields === 'all'
                ? Object.keys(this.fields)
                : this.export.fields;

            const selected = this.export.selected ? this.export.selected : [];

            this.exportableFields = Object.assign({});

            for (let field of fields) {
                const tableField = this.fields[field];

                let name = field;
                let format = null;

                if (tableField) {
                    name = tableField.name;

                    format = tableField.exportFormat
                        ? tableField.exportFormat
                        : tableField.format;
                }

                this.exportableFields[field] = {
                    name: name,
                    format: format,
                    selected: selected.indexOf(field) !== -1,
                };
            }
        },

        onSearchQueryChanged() {
            clearTimeout(this.searchTimeout);

            this.searchTimeout = setTimeout(() => {
                this.$set(this.selectedItems, '_all', false);

                this.fetchData();
            }, 250);
        },

        onFiltersChanged(newVal, oldVal) {
            if (!this.initialized) {
                return;
            }

            this.unselectAllItems();

            this.updateUrl(this.makeFiltersQuery());

            clearTimeout(this.filterDebounceTimeout);
            
            this.filterDebounceTimeout = setTimeout(() => {
                this.fetchData();
            }, 500);
        },

        onSelectedItemsChanged(newVal, oldVal) {
            //
        },

        onTableDataChanged() {
            this.$emit('data-updated', this.tableData);
        },

        makeFiltersQuery() {
            let filters = '';
            
            for (let filter of this.filterFields) {
                if (filter.value === undefined || filter.value === null) {
                    continue;
                }

                let value = filter.value;
                // Dots get converted to underscores when sending form data, so:
                let name = filter.name.replace(/\./g, '__');

                if (Array.isArray(value)) {
                    value = value.join('|');
                }

                filters += `&f_${name}=${value}`;
            }

            return filters;
        },

        fetchData() {
            this.processing = true;

            // When local data was provided
            if (this.useLocalData) {
                this.tableData = this.localData;
                this.items = this.localData.data;
                
                // Filter/sort/paginate items client-side as/if required
                this.filterItemsClientSide();  

                this.processing = false;

                return;
            }

            axios.get(this.endpoint)
                .then(({ data }) => {
                    this.tableData = data;
                    this.items = data.data;

                    if (this.page > data.last_page && this.page > 1) {
                        this.page = data.last_page;

                        this.fetchData();

                        return;
                    }

                    // Filter/sort/paginate items client-side as/if required
                    this.filterItemsClientSide();

                    this.processing = false;
                });
        },

        onPageChanged(page) {
            if (page == this.page) {
                return;
            }

            this.page = page;

            this.updateUrl({ page: page });

            this.fetchData();

            this.scrollToTop();
        },

        toggleSort(key) {
            if (!this.fields[key].sortable) {
                return;
            }

            this.sortAsc = (this.sort !== key) ? true : ! this.sortAsc;
            this.sort = key;

            this.updateUrl({ sort: `${key}|${this.sortAsc ? 'asc' : 'desc'}` });

            if (this.clientSideSorting) {
                this.filterItemsClientSide();
            } else {
                this.fetchData();
            }
        },
        
        sortItemsClientSide() {
            this.items.sort(this.itemSortingFunction);
        },

        itemSortingFunction(a, b) {
            if (!a[this.sort]) {
                return this.sortAsc ? -1 : 1;
            }

            if (!b[this.sort]) {
                return this.sortAsc ? 1 : -1;
            }

            if (a[this.sort] < b[this.sort]) {
                return this.sortAsc ? -1 : 1;
            }

            if (a[this.sort] > b[this.sort]) {
                return this.sortAsc ? 1 : -1;
            }

            return 0;
        },

        filterItemsClientSide() {
            // Client-side sorting
            if (this.clientSideSorting) {
                this.sortItemsClientSide();
            }

            // Client-side pagination
            if (this.clientSidePagination) {
                this.tableData = Object.assign(
                    this.tableData,
                    this.makePaginationData(this.items, this.page, this.itemsPerPage)
                );

                this.filteredItems = this.paginate(this.items, this.tableData, this.page);
            } else {
                // Server-side pagination
                this.filteredItems = this.items.map(item => item);
            }
        },

        formatted(field, item, key) {
            let value;

            if (key.indexOf('.') > -1) {
                // A relationship field
                const keys = key.split('.');
                value = item[keys[0]];

                for (let i = 1; i < keys.length; i++) {
                    if (typeof value !== 'object' || !value) {
                        break;
                    }

                    value = value[keys[i]];
                 }
            } else {
                // A regular field
                value = item[key];
            }

            if (field.format) {
                // Sometimes a relationship that is referenced in the format
                // string is missing
                try {
                    return eval(field.format);
                } catch (error) {
                    return '-';
                }
            } else {
                value = (value !== null && value !== undefined) ? value : '-';
            }

            return value;
        },

        actionEndpoint(action, item) {
            let url = action.bulk ? action.bulkUrl : action.url;

            // If the action has no URLs
            if (!url) {
                return null;
            }

            if (action.bulk) {
                // Apply filters to the URL for bulk actions
                const filters = this.makeFiltersQuery();

                return url.indexOf('?') === -1
                    ? `${url}?search=${this.searchQuery}${filters}`
                    : `${url}&search=${this.searchQuery}${filters}`;
            }

            const params = url.match(new RegExp(':([a-zA-Z].*?)[^\/|$|\.]*', 'g'));

            if (params) {
                for (let param of params) {
                    param = param.replace('/', '');
                    const attr = param.replace(':', '');

                    if (item[attr]) {
                        url = url.replace(param, item[attr]);
                    }
                }
            }

            return url;
        },

        allowedTo(action) {
            // If a permissions for an action weren't specified - it is allowed
            if (!this.permissions[action]) {
                return true;
            }

            return $auth.hasPermissions(this.permissions[action]);
        },

        confirmAction(action, item) {
            const confirmation = action.bulk
                ? (action.confirmBulk ? action.confirmBulk : action.confirm)
                : action.confirm;

            if (confirmation) {
                this.$confirm(confirmation, 'Confirmation', {})
                    .then(() => {
                        this.doAction(action, item);
                    })
                    .catch(() => {
                        // Ignore 'cancel' button press
                    });
            } else {
                this.doAction(action, item);
            }
        },

        doAction(action, item = null) {
            const url = this.actionEndpoint(action, item);

            // A non-AJAX action - just go to the URL
            if (!action.ajax) {
                if (url) {
                    if (action.openInTab) {
                        window.open(url);
                    } else {
                        window.location = url;
                    }
                }

                return;
            }

            let data = null;

            if (action.bulk) {
                let selected = [];

                if (this.selectedItems._all) {
                    selected = ['all'];
                } else {
                    selected = Object.keys(this.selectedItems).filter(key => {
                        return this.selectedItems[key];
                    });
                }

                if (!selected.length) {
                    return;
                }
                
                data = { items: selected };

                this.unselectAllItems();
            }

            // A custom action - just fire an event
            if (action.customHandler) {
                return this.doCustomAction(
                    action, url, action.bulk ? data.items : item
                );
            }

            // An AJAX action
            axios[action.method](url, data)
                .then((response) => {
                    this.fetchData();

                    const msg = action.success
                        ? action.success
                        : 'Action has been performed successfully!';

                    this.$notify({
                        title: 'Action complete',
                        message: msg,
                        type: 'success' 
                    });
                })
                .catch(({ response }) => {
                    let error = response.data.message
                        ? response.data.message
                        : 'Something went wrong!';

                    this.$notify({
                        title: 'Action error',
                        message: error,
                        type: 'error' 
                    });

                    this.processing = false;
                });
        },

        doCustomAction(action, url, items = null) {
            this.$emit('action', {
                action: action,
                url: url,
                items: items,
            });
        },

        onItemSelectionToggled(item) {
            this.$set(this.selectedItems, '_all', false);
        },

        onSelectAllToggle() {
            this.$set(this.selectedItems, '_all', false);

            const selected = !this.allPageItemsSelected;

            for (let item of this.items) {
                if (item._selectable === false) {
                    continue;
                }

                this.$set(
                    this.selectedItems,
                    this.primaryKey(item),
                    selected
                );
            }
        },

        selectAllItems() {
            this.$set(this.selectedItems, '_all', true);
        },

        onBulkActionSelected() {
            if (!this.selectedBulkAction.length) {
                return;
            }

            const key = this.selectedBulkAction[0];
            const action = this.allActions.bulk.find(item => item.name === key);

            if (!action) {
                console.error(`Bulk action '${key}' doesn't exist.`);

                return;
            }

            this.confirmAction(action);

            this.selectedBulkAction = [];
        },

        scrollToTop() {
            this.$refs['table-wrapper'].scrollIntoView();
        },

        actionIcon(action, item) { 
            if (action.customIcon) {
                return eval(action.customIcon);
            } else if (action.icon) {
                return `<i class="las ${action.icon}"></i>`;
            }
            
            return `<span>${action.title}</span>`;
        },

        showActionForItem(action, item) {
            if (!action.hasOwnProperty('show')) {
                return true
            };

            return eval(action.show);
        },

        isActionsList(action) {
            return action.hasOwnProperty('list')
                && typeof action.list === 'object';
        },

        onDropdownActionClick(data) {
            this.confirmAction(data.subAction, data.item);
        },

        makePaginationData(items, page = 1, itemsPerPage = 20) {
            const lastPage = Math.ceil(items.length / itemsPerPage);
            const total = items.length;
            const from = itemsPerPage * page - (itemsPerPage - 1);
            const to = from + (itemsPerPage - 1);

            return {
                last_page: lastPage,
                total: total,
                from: from,
                to: (to <= total) ? to : total,
            };
        },

        paginate(items, tableData, page) {
            return items.slice(tableData.from - 1, tableData.to);
        },

        resetFilters() {
            for (let filter of this.filterFields) {
                const defaultValues = this.filters[filter.name];
                
                if (!defaultValues) {
                    continue;
                }

                const defaultValue = defaultValues.value
                    || (defaultValues.attrs ? defaultValues.attrs.value : null)
                    || null;

                this.$set(filter, 'value', defaultValue);
            }

            this.resetFiltersInUrl();
        },

        toggleHiddenFilters() {
            this.showHiddenFilters = !this.showHiddenFilters;
        },

        updateUrl(updates) {
            if (!this.urlMode) {
                return;
            }

            // If updates were sent in the form of a query string
            if (typeof updates === 'string') {
                const pairs = updates.split('&');
                updates = {};

                for (let pair of pairs) {
                    if (!pair) {
                        continue;
                    }
                    
                    pair = pair.split('=');
                    updates[pair[0]] = pair[1];
                }
            }

            // Get current URL
            const url = new URL(window.location);

            // Set/update query params
            for (let key in updates) {
                if (url.searchParams.get(key) !== updates[key]) {
                    url.searchParams.set(key, updates[key]);
                }
            }

            window.history.pushState({}, '', url);
        },

        resetFiltersInUrl() {
            if (!this.urlMode) {
                return;
            }

            // Get current URL
            const url = new URL(window.location);

            // Remove query params (filters, sort, page)
            const toDelete = [];

            for (let param of url.searchParams) {
                const key = param[0];

                if (key.startsWith('f_')) {
                    toDelete.push(key);
                }
            }

            for (let key of toDelete) {
                url.searchParams.delete(key);
            }

            window.history.pushState({}, '', url);
        },

        showExportModal() {
            if (!Object.keys(this.selectedItems).length) {
                this.$notify({
                    title: 'Action error',
                    message: 'Select items to export',
                    type: 'error' 
                });

                return;
            }

            this.$showModal('export-modal');
        },

        exportFormat(key) {
            return this.exportableFields[key].format
                ? this.exportableFields[key].format
                : key;
        },

        onExportDataReady(data) {
            const contents = data.data;
            const format = data.format;

            const prefix = this.export.filenamePrefix
                ? this.export.filenamePrefix
                : 'export-';

            const filename = prefix
                + moment().format('Y-MM-DD-HH-mm-ss')
                + `.${format}`;

            this.promptFileDownload(contents, format, filename);
        },

        promptFileDownload(contents, format, filename) {
            const element = document.createElement('a');
            const mime = {
                csv: 'text/csv',
                txt: 'text/plain',
            }[format];

            element.style.display = 'none';
            element.setAttribute('download', filename);
            element.setAttribute(
                'href', 
                `data:${mime};charset=utf-8,${encodeURIComponent(contents)}`
            );

            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        },

        unselectAllItems() {
            this.selectedItems = Object.assign({}, {
                _all: false,
            });
        }
    }
}
</script>
