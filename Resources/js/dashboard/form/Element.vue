<template>
    <div class="form-group" :class="{ 'inline': inline }">
        <slot :attrs="attrs"
            :val="val"
            :collections="collections"
            :on-input="onInput"
        >
            <v-fel-input v-if="inputTypes.indexOf(field.type) >= 0"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-input>

            <v-fel-textarea v-else-if="field.type === 'textarea'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-textarea>

            <v-fel-checkbox v-else-if="field.type === 'checkbox'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-checkbox>

            <v-fel-switch v-else-if="field.type === 'switch'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-switch>

            <v-fel-radiogroup v-else-if="field.type === 'radiogroup'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-radiogroup>

            <v-fel-link v-else-if="field.type === 'link'"
                :attrs="attrs"></v-fel-link>

            <v-fel-select v-else-if="field.type === 'select'"
                :attrs="attrs"
                :value="val"
                :collections="collections"
                @input="onInput"></v-fel-select>

            <v-fel-datetime v-else-if="field.type === 'datetime'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-datetime>

            <v-fel-daterange v-else-if="field.type === 'daterange'"
                :attrs="attrs"
                :value="val"
                :with-time="false"
                @input="onInput"></v-fel-daterange>

            <v-fel-daterange v-else-if="field.type === 'datetimerange'"
                :attrs="attrs"
                :value="val"
                :with-time="true"
                @input="onInput"></v-fel-daterange>

            <v-fel-file v-else-if="field.type === 'file'"
                :attrs="attrs"
                :value="val"
                @input="onInput"></v-fel-file>
        </slot>

        <div v-if="errors" class="hint danger">
            {{ errors[0] }}
        </div>
    </div>
</template>

<script>
import Input from './elements/Input';
import Textarea from './elements/Textarea';
import Checkbox from './elements/Checkbox';
import Switch from './elements/Switch';
import Link from './elements/Link';
import Select from './elements/Select';
import DateTime from './elements/DateTime';
import DateRange from './elements/DateRange';
import File from './elements/File';
import RadioGroup from './elements/RadioGroup';

export default {
    components: {
        'v-fel-input': Input,
        'v-fel-textarea': Textarea,
        'v-fel-checkbox': Checkbox,
        'v-fel-switch': Switch,
        'v-fel-link': Link,
        'v-fel-select': Select,
        'v-fel-datetime': DateTime,
        'v-fel-daterange': DateRange,
        'v-fel-file': File,
        'v-fel-radiogroup': RadioGroup,
    },

    props: {
        field: {},

        collections: {
            type: Object,
            default: () => {
                return {};
            },
        },

        inline: {
            type: Boolean,
            default: false,
        },

        showLabel: {
            type: Boolean,
            default: true,
        },

        disabled: {
            type: Boolean,
            default: false,
        },

        value: {},
    },

    data() {
        return {
            inputTypes: [
                'text',
                'hidden',
                'email',
                'password',
                'number',
                'color',
                'range',
            ],
            attrs: {},
            readOnly: this.$parent.tabReadOly,
        };
    },

    computed: {
        errors() {
            if (!this.$parent.errors) {
                return undefined;
            }

            return this.attrs.name
                ? this.$parent.errors[this.attrs.name]
                : undefined;
        },

        val() {
            if (this.attrs.value !== undefined) {
                return this.attrs.value;
            }

            return this.attrs.name
                ? (
                    this.$parent.values && this.$parent.values.hasOwnProperty(this.attrs.name)
                        ? this.$parent.values[this.attrs.name]
                        : this.value
                )
                : this.value !== undefined ? this.value : undefined;
        },
    },

    created() {
        this.attrs = Object.assign({}, this.field, this.field.attrs);

        this.$set(this.attrs, 'label', this.showLabel ? this.field.label : undefined);

        this.$set(
            this.attrs,
            'disabled',
            this.readOnly || this.disabled || ((this.attrs.disabled == true) ? true : false)
        );

        this.setChecked();
    },

    watch: {
        value(newVal) {
            this.$set(this.attrs, 'value', newVal);

            this.updateChecked();
        },

        disabled(newVal) {
            this.$set(
                this.attrs,
                'disabled',
                newVal
            );
        }
    },

    methods: {
        onInput(input) {
            this.$emit('input', input);
            this.$emit('change', input);
        },

        // Set the "check" attr of the checkbox-like fields
        setChecked() {
            if (this.field.type !== 'checkbox' && this.field.type !== 'switch') {
                return;
            }

            this.$set(this.attrs, 'checked', this.attrs.checked === undefined
                ? (!! this.val)
                : this.attrs.checked);
        },

        // Update the "check" attr of the checkbox-like fields
        updateChecked() {
            if (this.field.type !== 'checkbox' && this.field.type !== 'switch') {
                return;
            }

            this.$set(this.attrs, 'checked', this.value);
        },
    }
}
</script>
