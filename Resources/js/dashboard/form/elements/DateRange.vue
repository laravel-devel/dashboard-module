<template>
    <div>
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <el-date-picker
            v-model="model"
            :type="withTime ? 'datetimerange' : 'daterange'"
            align="right"
            unlink-panels
            range-separator="-"
            start-placeholder="Start date"
            end-placeholder="End date"
            :picker-options="pickerOptions"
            :default-time="['00:00:00', '23:59:59']"
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
    props: ['attrs', 'value', 'withTime'],

    data() {
        return {
            model: '',

            pickerOptions: {
                shortcuts: [{
                    text: 'Last week',
                    onClick(picker) {
                        const end = new Date().setHours(23, 59, 59, 999);
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        start.setHours(0, 0, 0, 0);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: 'Last month',
                    onClick(picker) {
                        const end = new Date().setHours(23, 59, 59, 999);
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        start.setHours(0, 0, 0, 0);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: 'Last 3 months',
                    onClick(picker) {
                        const end = new Date().setHours(23, 59, 59, 999);
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        start.setHours(0, 0, 0, 0);
                        picker.$emit('pick', [start, end]);
                    }
                }],
                firstDayOfWeek: 1,
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
            return this.withTime
                ? moment(value).format('Y-MM-DD HH:mm:ss')
                : moment(value).format('Y-MM-DD');
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
