<template>
    <div>
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <el-date-picker
            v-model="model"
            type="datetime"
            placeholder="Select date and time"
            :picker-options="pickerOptions"
            :disabled="attrs.disabled"
            class="fullwidth-force">
        </el-date-picker>

        <input
            type="hidden"
            :name="attrs.name"
            autocomplete="off"
            v-model="formattedValue">
    </div>
</template>

<script>
export default {
    props: ['attrs', 'value'],

    data() {
        return {
            model: '',

            pickerOptions: {
                firstDayOfWeek: 1,
            },
        };
    },

    mounted() {
        // Set the default value
        this.model = this.value ? new Date(this.value) : new Date();
    },

    watch: {
        model(newVal) {
            this.$emit('input', this.formattedValue);
        },

        value(newVal) {
            this.model = (typeof newVal === 'string') 
                ? new Date(newVal)
                : newVal;
        }
    },

    computed: {
        formattedValue() {
            return this.formatDate(this.model);
        }
    },

    methods: {
        formatDate(value) {
            return value ? moment(value).format('Y-MM-DD H:m:s') : null;
        }
    }
}
</script>
