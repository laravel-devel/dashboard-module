<template>
    <div class="datatable">
        <div v-if="this.filterFields.length > 0">
            <p class="mb-1">
                <strong>Filter</strong>
            </p>

            <div class="flex flex-wrap space-between mb-1">
                <div v-for="(filter, index) in this.filterFields" :key="index" class="mr-05 ml-05 mb-1">
                    <div class="mb-05 text-semibold">{{ filter.label }}:</div>

                    <v-form-el v-if="filter.type === 'select'"
                        :inline="true"
                        :field="{
                            type: 'select',
                            attrs: filter.attrs,
                        }"
                        :collections="{ [filter.name]: filter.options }"
                        v-model="filter.value"
                        class="search-field"></v-form-el>

                    <v-form-el v-else
                        :inline="true"
                        :field="{
                            type: 'text'
                        }"
                        v-model="filter.value"
                        class="search-field"></v-form-el>
                </div>
            </div>
        </div>

        <div class="flex pb-1">
            <div class="flex flex-align-center flex-1">
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

            <div v-if="hasActions && createAction && allowedTo('create')">
                <a :href="createAction.url" class="btn">Add</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table card">
                <thead>
                    <th v-if="bulkActionsOn && hasBulkActions" class="bulk-actions">
                        <v-form-el :inline="true"
                            :field="{
                                type: 'checkbox'
                            }"
                            v-model="selectedItemsAll"
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
                    <tr v-for="(item, index) in items" :key="index">
                        <td v-if="bulkActionsOn && hasBulkActions" class="bulk-actions">
                            <v-form-el :inline="true"
                                :field="{
                                    type: 'checkbox'
                                }"
                                v-model="selectedItems[index]"></v-form-el>
                        </td>

                        <td v-for="key in Object.keys(visibleFields)"
                            :key="key"
                            v-html="formatted(fields[key], item, key)"></td>

                        <td v-if="hasActions" class="actions">
                            <template v-for="(action, index) in allActions.single">
                                <a v-if="allowedTo(action.name)"
                                    :key="index"
                                    href="#"
                                    :class="`action-btn ${action.class ? action.class : 'primary'}`"
                                    :title="action.title ? action.title : ''"
                                    @click.prevent="confirmAction(action, item)"
                                >
                                    <i v-if="action.icon"
                                        :class="`las ${action.icon}`"></i>

                                    <span v-else v-text="action.title"></span>
                                </a>
                            </template>
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
            default: {},
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
    },

    computed: {
        endpoint() {
            // Filters
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

            return `${this.baseUrl}?page=${this.page}&sort=${this.sort}|${this.sortAsc ? 'asc' : 'desc'}&search=${this.searchQuery}${filters}`;
        },

        hasActions() {
            return this.allActions.single && this.allActions.single.length > 0;
        },

        hasBulkActions() {
            return this.allActions.bulk && this.allActions.bulk.length > 0;
        },
    },

    data() {
        return {
            initialized: false,
            processing: false,
            tableData: [],
            items: [],
            selectedItems: {},
            selectedItemsAll: false,
            page: 1,
            sort: Object.keys(this.fields)[0],
            sortAsc: true,
            searchQuery: '',
            searchTimeout: null,
            visibleFields: {},
            filterFields: [],

            allActions: {
                single: [],
                bulk: [],
            },
            createAction: null,
            primaryKey: '',

            bulkActionsSelect: {
                attrs: {},
                collection: {},
            },
            selectedBulkAction: [],
        };
    },
 
    created() {
        this.parseFields();
        this.parseDefaultSorting();
        this.parseFilters();
        this.parseActions();
        this.parseBulkActions();
        this.fetchData();

        this.$nextTick(() => {
            this.initialized = true;
        });
    },

    watch: {
        searchQuery() {
            clearTimeout(this.searchTimeout);

            this.searchTimeout = setTimeout(() => {
                this.fetchData();
            }, 250);
        },

        filterFields: {
            deep: true,

            handler: function (newVal, oldVal) {
                if (!this.initialized) {
                    return;
                }
                
                this.fetchData();
            }
        },

        selectedItems: {
            deep: true,

            handler: function () {
                let selected = Object.keys(this.selectedItems).filter(key => {
                    return this.selectedItems[key];
                });

                this.selectedItemsAll = (selected.length === this.items.length);
            }
        },
     },

    methods: {
        parseFields() {
            for (let key of Object.keys(this.fields)) {
                if (key === '_primary_key') {
                    this.primaryKey = this.fields[key];

                    continue;
                }

                this.visibleFields[key] = this.fields[key];
            }
        },

        parseDefaultSorting() {
            if (!this.sortBy) {
                return;
            }

            let parts = this.sortBy.split('|');

            if (parts.length < 2) {
                return;
            }

            this.sort = parts[0];
            this.sortAsc = parts[1].toLowerCase() === 'asc';
        },

        parseFilters() {
            if (typeof this.filters !== 'object') {
                return;
            }

            for (let name of Object.keys(this.filters)) {
                const field = this.filters[name];

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
                        value: (field.attrs && field.attrs.required)
                            ? [options[0][idField]]
                            : null,
                    });
                } else {
                    this.filterFields.push({
                        name: name,
                        label: field.name,
                        type: 'text',
                        value: null,
                    });
                }
            }
        },

        parseActions() {
            for (let name of Object.keys(this.actions)) {
                this.parseAction(name, this.actions[name]);
            }
        },

        parseAction(name, action) {
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
                action.noBulk = true;
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

            // Add action to the single actions collection
            if (action.url) {
                const a = Object.assign({}, action, { bulk: false });

                this.allActions.single.push(a);
            }

            // Add action to the bulk actions collection
            if (action.bulkUrl) {
                const b = Object.assign({}, action, { bulk: true });
                
                // Bulk deletes are done via POST
                if (name === 'delete') {
                    b.method = 'post';
                }

                this.allActions.bulk.push(b);
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
                this.bulkActionsSelect.collection.bulkActions.push({
                    name: action.name,
                    title: action.title,
                });
            }
        },

        fetchData() {
            this.processing = true;

            axios.get(this.endpoint)
                .then(({ data }) => {
                    this.tableData = data;
                    this.items = data.data;

                    this.processing = false;
                });
        },

        onPageChanged(page) {
            if (page == this.page) {
                return;
            }

            this.page = page;

            this.fetchData();
        },

        toggleSort(key) {
            if (!this.fields[key].sortable) {
                return;
            }

            this.sortAsc = (this.sort !== key) ? true : ! this.sortAsc;
            this.sort = key;

            this.fetchData();
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

            value = value ? value : '-';

            if (field.format) {
                // Sometimes a relationship that is referenced in the format
                // string is missing
                try {
                    return eval(field.format);
                } catch (error) {
                    return '-';
                }
            }
            
            return value;
        },

        actionEndpoint(action, item) {
            let url = action.bulk ? action.bulkUrl : action.url;

            if (action.bulk) {
                return url;
            }

            const params = url.match(new RegExp(':([a-zA-Z].*?)(/|$)', 'g'));

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
                this.$confirm(confirmation, {
                    onOk: () => {
                        this.doAction(action, item);
                    }
                });
            } else {
                this.doAction(action, item);
            }
        },

        doAction(action, item = null) {
            const url = this.actionEndpoint(action, item);

            // A non-AJAX action - just go to the URL
            if (!action.ajax) {
                window.location = url;

                return;
            }

            let data = null;

            if (action.bulk) {
                const indexes = Object.keys(this.selectedItems).filter(index => {
                    return this.selectedItems[index];
                }).map(key => parseInt(key));

                if (!indexes.length) {
                    return;
                }

                const selected = [];

                // Populate data
                for (let i of indexes) {
                    const item = this.items[i];

                    selected.push(item[this.primaryKey]);
                }
                
                data = { items: selected };

                // Unselect all the items
                this.selectedItems = Object.assign({});
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

                    this.$notify(msg, 'success');
                })
                .catch(({ response }) => {
                    let error = response.data.message
                        ? response.data.message
                        : 'Something went wrong!';

                    this.$notify(error, 'error');

                    this.processing = false;
                });
        },

        doCustomAction(action, url, items = null) {
            // A URL with inserted parameters
            action.url = url;

            this.$emit('action', {
                action: action,
                url: url,
                items: items,
            });
        },

        onSelectAllToggle() {
            for (let i = 0; i < this.items.length; i++) {
                this.$set(this.selectedItems, i, this.selectedItemsAll);
            }
        },

        onBulkActionSelected() {
            if (!this.selectedBulkAction.length) {
                return;
            }

            const key = this.selectedBulkAction[0];
            const action = this.allActions.bulk.find(item => item.name === key);

            if (!action) {
                console.error(`Bulk action '${key}' doesn't exist`);

                return;
            }

            this.confirmAction(action);

            this.selectedBulkAction = [];
        },
    }
}
</script>
