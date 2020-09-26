<template>
    <div>
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <textarea class="form-element textarea"
            :type="attrs.type"
            :name="attrs.name"
            autocomplete="off"
            :placeholder="attrs.placeholder ? attrs.placeholder : ''"
            :value="value"
            :disabled="attrs.disabled"
            @input="$emit('input', $event.target.value)"></textarea>
    </div>
</template>

<script>
export default {
    props: ['attrs', 'value'],

    created() {
        if (this.value !== undefined) {
            this.$emit('input', this.value);
        }
    },
}
</script>
