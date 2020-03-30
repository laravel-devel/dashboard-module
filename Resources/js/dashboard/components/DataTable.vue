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
            <div v-if="searchOn" class="flex flex-align-center flex-1">
                <span class="mr-1">Search:</span>

                <v-form-el :inline="true"
                    :field="{
                        type: 'text'
                    }"
                    v-model="searchQuery"
                    class="search-field"></v-form-el>
            </div>

            <div v-else class="flex flex-align-center flex-1"></div>

            <div v-if="hasActions && actions.create && allowedTo('create')">
                <a :href="actions.create" class="btn">Add</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table card">
                <thead>
                    <th v-for="key in Object.keys(fields)"
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
                        <td v-for="key in Object.keys(fields)"
                            :key="key"
                            v-html="formatted(fields[key], item, key)"></td>

                        <td v-if="hasActions" class="actions">
                            <a v-if="actions.delete && allowedTo('delete')"
                                href="#"
                                class="action-btn danger"
                                title="Delete"
                                @click.prevent="deleteItemConfirm(item, actions.delete)"
                            >
                                <i class="las la-trash"></i>
                            </a>

                            <a v-if="actions.edit && (allowedTo('edit') || allowedTo('view'))"
                                :href="actionEndpoint(actions.edit, item)"
                                class="action-btn primary"
                                title="Edit"
                            >
                                <i class="las la-edit"></i>
                            </a>
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
            default: {},
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

        deleteConfirmation: {
            type: String,
            default: 'Are you sure you want to delete this item?',
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
            return this.actions && Object.keys(this.actions).length > 0;
        }
    },

    data() {
        return {
            initialized: false,
            processing: false,
            tableData: [],
            items: [],
            page: 1,
            sort: Object.keys(this.fields)[0],
            sortAsc: true,
            searchQuery: '',
            searchTimeout: null,
            filterFields: [],
        };
    },
 
    created() {
        this.parseDefaultSorting();
        this.parseFilters();
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
        }
    },

    methods: {
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
                return eval(field.format);
            }
            
            return value;
        },

        actionEndpoint(url, item) {
            const params = url.match(new RegExp(':([a-zA-Z].*?)(/|$)', 'g'));

            for (let param of params) {
                param = param.replace('/', '');
                const attr = param.replace(':', '');

                if (item[attr]) {
                    url = url.replace(param, item[attr]);
                }
            }

            return url;
        },

        deleteItemConfirm(item, url) {
            this.$confirm(this.deleteConfirmation, {
                onOk: () => {
                    this.deleteItem(item, url);
                }
            });
        },

        deleteItem(item, url) {
            this.processing = true;

            url = this.actionEndpoint(url, item);

            axios.delete(url)
                .then((response) => {
                    this.fetchData();

                    this.$notify('Item has been deleted!', 'success');
                })
                .catch(({ response }) => {
                    let error = response.data.message
                        ? response.data.message
                        : 'Something went wrong!';

                    this.$notify(error, 'error');

                    this.processing = false;
                });
        },

        allowedTo(action) {
            // If a permissions for an action weren't specified - it is allowed
            if (!this.permissions[action]) {
                return true;
            }

            return $auth.hasPermissions(this.permissions[action]);
        },
    }
}
</script>
