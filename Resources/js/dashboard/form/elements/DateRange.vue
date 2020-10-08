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

            formattedValue: '',
            emitValue: true,
        };
    },

    mounted() {
        // Set the default value
        this.setFromValue(this.value);
    },

    watch: {
        model(newVal) {
            this.formattedValue = this.formatValue(newVal);

            if (this.emitValue) {
                this.$emit('input', this.formattedValue);
            } else {
                this.emitValue = true;
            }
        },

        value(newVal) {
            this.emitValue = false;

            this.setFromValue(newVal);
        }
    },

    methods: {
        formatDate(value) {
            return moment(value).format('Y-MM-DD');
        },

        setFromValue(value) {
            const values = value.split('|');

            this.model = [
                new Date(values[0]),
                values[1] ? new Date(values[1]) : new Date(),
            ];
        },

        formatValue(value) {
            if (value[1]) {
                return this.formatDate(value[0])
                    + '|'
                    + this.formatDate(value[1]);
            } else {
                return this.formatDate(value[0]);
            }
        }
    }
}
</script>
