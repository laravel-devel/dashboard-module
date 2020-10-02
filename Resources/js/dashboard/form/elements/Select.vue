<template>
    <div>
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <div class="select">
            <div class="select-input">
                <input v-if="attrs.search || multipleChoice"
                    type="text"
                    class="form-element"
                    :placeholder="placeholder"
                    autocomplete="off"
                    v-model="search"
                    ref="input"
                    :disabled="attrs.disabled"
                    @focus="openOnFocus">

                <div v-else class="form-element" ref="input" @click="toggleOpen">
                    <span v-if="selectedOptions.length > 0" class="value">
                        {{ selectedOptions[0][textField] }}
                    </span>

                    <span v-else class="placeholder">
                        {{ placeholder }}
                    </span>
                </div>

                <div class="select-arrow"
                    :class="{ 'open': open, 'disabled': attrs.disabled }"
                    @click="toggleOpen"
                    ref="arrow"
                >
                    <i v-if="open" class="las la-angle-up" ref="arrow-up"></i>
                    <i v-else class="las la-angle-down" ref="arrow-down"></i>
                </div>
            </div>

            <div v-show="open" class="select-dropdown">
                <ul>
                    <li v-if="!multipleChoice && !attrs.required"
                        class="placeholder"
                        @click="unselectOption"
                    >
                        {{ placeholder }}
                    </li>

                    <li v-for="(option, index) in filteredOptions"
                        :key="index"
                        @click="selectOption(option)"
                        :class="{
                            'group-name': option.groupName,
                            'subitem': option.isSubitem,
                        }"
                    >{{ option[textField] }}</li>
                </ul>
            </div>

            <div v-if="multipleChoice" class="select-selected-items">
                <div v-for="(option, index) in selectedOptions"
                    :key="index"
                    class="select-seleced-item"
                >
                    <div class="text">{{ option[textField] }}</div>

                    <div v-if="!attrs.disabled"
                        class="remove"
                        @click="unselectOption(option)"
                    >
                        <i class="las la-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="multipleChoice && selections.length > 0">
            <input v-for="(selection, index) in selections"
                :key="index"
                type="hidden"
                :name="`${attrs.name}[]`"
                autocomplete="off"
                :value="selection">
        </template>

        <input v-else-if="multipleChoice && selections.length == 0"
            type="hidden"
            :name="`${attrs.name}[]`"
            autocomplete="off"
            value=""
            v-model="selections">

        <input v-else
            type="hidden"
            :name="`${attrs.name}`"
            autocomplete="off"
            :value="selections[0]">
    </div>
</template>

<script>
export default {
    props: ['attrs', 'value', 'collections'],

    data() {
        const collectionName = this.attrs.collection
            ? this.attrs.collection
            : this.attrs.name;

        return {
            collectionName: collectionName,
            options: (this.collections && this.collections[collectionName])
                ? this.collections[collectionName]
                : [],
            selectedOptions: [],
            availableOptions: [],
            filteredOptions: [],
            idField: '',
            textField: '',
            multipleChoice: false,
            limit: this.attrs.limit ? this.attrs.limit : 0,
            open: false,
            search: '',
            selections: [],
        };
    },

    computed: {
        placeholder() {
            let defaultPlaceholder = ' - ';

            if (this.multipleChoice) {
                defaultPlaceholder = (this.limitLeft() > 0)
                    ? `Start typing... (you can select ${this.limitLeft()} more option(s))`
                    : "You can't select more options"
            }

            return this.attrs.placeholder
                ? this.attrs.placeholder
                : defaultPlaceholder;
        }
    },

    created() {
        if (!this.attrs || !this.attrs.idField || !this.attrs.textField) {
            throw 'Missing attributes for the select field.';
        }

        this.idField = this.attrs.idField;
        this.textField = this.attrs.textField;
        this.multipleChoice = (this.attrs.multipleChoice == true);

        // Extract/process any groupped options
        const ungrouppedOptions = [];

        for (let option of this.options) {
            // Add the option
            ungrouppedOptions.push(option);

            // If the option has suboptions - add them
            if (option.options) {
                for (let suboption of option.options) {
                    // console.log(suboption);
                    ungrouppedOptions.push(Object.assign(suboption, {
                        isSubitem: true,
                    }));
                }
            }
        }

        this.options = ungrouppedOptions;

        // Select selected options
        if (this.value) {
            // console.log(this.value);
            const value = Array.isArray(this.value) ? this.value : [this.value];

            for (let item of value) {
                if (typeof item !== 'object') {
                    item = this.options.find(i => i[this.idField] == item);
                    
                    if (!item) {
                        continue;
                    }
                }

                if (item) {
                    this.selectOption(item);
                }
            }
        } else if (this.attrs.required) {
            if (this.options.length) {
                this.selectOption(this.options[0]);
            }
        }

        this.calculateAvailableOptions();
        this.filterOptions();
    },

    mounted() {
        document.addEventListener('click', (e) => {
            const condition = e.target === this.$refs['input']
                || e.target.parentNode === this.$refs['input']
                || e.target === this.$refs['arrow']
                || e.target === this.$refs['arrow-up']
                || e.target === this.$refs['arrow-down'];

            if (!condition) {
                this.open = false;
            }
        });
    },

    watch: {
        search(newValue) {
            this.filterOptions();
        },

        value(newValue) {
            this.onValueUpdated(newValue);
        },

        collections(newVal, oldVal) {
            newVal = newVal[this.collectionName] 
                ? newVal[this.collectionName]
                : [];

            oldVal = oldVal[this.collectionName]
                ? oldVal[this.collectionName]
                : [];

            if (newVal === oldVal) {
                return;
            }

            this.options = newVal ? newVal : [];

            this.calculateAvailableOptions();
            this.filterOptions();
        },
    },

    methods: {
        formatOption(option) {
            return {
                [this.idField]: option[this.idField],
                [this.textField]: option[this.textField],
                groupName: option.hasOwnProperty('options') && option.options !== undefined,
                isSubitem: option.isSubitem ? true : false,
            };
        },

        calculateAvailableOptions() {
            this.availableOptions.splice(0);
            
            for (let option of this.options) {
                const selected = this.selectedOptions.find(item => {
                    return item[this.idField] === option[this.idField];
                });

                if (selected === undefined || !this.multipleChoice) {
                    this.availableOptions.push(this.formatOption(option));
                }
            }
        },

        filterOptions() {
            this.filteredOptions.splice(0);

            const search = this.search.trim().toLowerCase();

            this.filteredOptions = this.availableOptions.filter(item => {
                return item[this.textField].toLowerCase().indexOf(search) >= 0;
            });
        },

        toggleOpen() {
            if (!this.attrs.disabled && this.limitLeft() > 0) {
                this.open = ! this.open;
            }
        },

        openOnFocus() {
            if (!this.attrs.disabled && this.limitLeft() > 0) {
                this.open = true;
            }
        },

        selectOption(option) {
            if (!option) {
                return;
            }

            // Group names cannot be selected
            if (option.groupName) {
                return;
            }

            // If multi-choice and the limit has been reached - don't select new
            // options
            if (this.limitReached()) {
                return;
            }

            // If not multi-choice - unselect the currently selected option
            if (!this.multipleChoice) {
                this.selectedOptions.splice(0);
            }

            // Push a clone of the object, not the original object!
            this.selectedOptions.push(
                Object.assign({}, this.formatOption(option))
            );

            // For multi-choice - clear the search text after selecting an
            // option
            if (this.multipleChoice) {
                this.search = '';
            } else if (this.attrs.search) {
                // For single-choice with search - set the search text to the
                // selected option
                this.search = option[this.textField];
            }

            this.open = false;

            this.onSelectionsUpdated();
        },

        unselectOption(option = null) {
            // When in single-choice mode
            if (!this.multipleChoice) {
                this.selectedOptions = [];

                this.onSelectionsUpdated();

                // When the search is on - clear the search text
                if (this.attrs.search) {
                    this.search = '';
                }

                return;
            }

            if (!option) {
                return;
            }

            const index = this.selectedOptions.indexOf(option);

            if (index >= 0) {
                this.selectedOptions.splice(index, 1);
            }

            this.onSelectionsUpdated();
        },

        onSelectionsUpdated() {
            this.calculateAvailableOptions();
            this.filterOptions();

            this.selections.splice(0);
            this.selections = this.selectedOptions.map(item => item[this.idField]);

            this.$emit('input', this.selections);
        },

        onValueUpdated(newValue) {
            if (!Array.isArray(newValue)) {
                newValue = [newValue];
            }

            this.selections = newValue;

            this.selectedOptions.splice(0);

            for (let option of this.selections) {
                const item = this.options.find(item => item[this.idField] === option);

                if (item) {
                    // Push a clone of the object, not the original object!
                    this.selectedOptions.push(Object.assign({}, item));
                }
            }
        },

        /**
         * Whether the selected options limit has been reached
         * (for multi-select mode only)
         * 
         * @return boolean
         */
        limitReached() {
            return this.multipleChoice
                && this.limit > 0
                && this.selectedOptions.length == this.limit;
        },

        /**
         * How many more options can be selected until the limit will be reached
         * (for multi-select mode only)
         * 
         * @return integer
         */
        limitLeft() {
            if (!this.multipleChoice) {
                return 1;
            }

            return this.limit - this.selectedOptions.length;
        },
    }
}
</script>
