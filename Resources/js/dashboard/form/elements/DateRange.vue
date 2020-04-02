<template>
    <div>
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <el-date-picker
            v-model="model"
            type="daterange"
            align="right"
            unlink-panels
            range-separator="-"
            start-placeholder="Start date"
            end-placeholder="End date"
            :picker-options="pickerOptions"
            :disabled="attrs.disabled">
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
                shortcuts: [{
                    text: 'Last week',
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: 'Last month',
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: 'Last 3 months',
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        picker.$emit('pick', [start, end]);
                    }
                }]
            },
        };
    },

    mounted() {
        // Set the default value
        this.model = [new Date(this.value), new Date()];
    },

    watch: {
        model(newVal) {
            this.$emit('input', this.formattedValue);
        }
    },

    computed: {
        formattedValue() {
            return this.formatDate(this.model[0]) + '|' + this.formatDate(this.model[1]);
        }
    },

    methods: {
        formatDate(value) {
            return moment(value).format('Y-MM-DD');
        }
    }
}
</script>
