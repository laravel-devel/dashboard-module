<template>
    <div v-if="this.attrs.options && this.attrs.options.length">
        <p v-if="attrs.label" class="label" v-text="attrs.label"></p>

        <label v-for="(option, index) in this.attrs.options"
            :key="index"
            class="radio"
            :class="{ 'disabled': attrs.disabled }"
        >
            <span v-if="option.text">{{ option.text }}</span>

            <input class="form-element" 
                type="radio"
                :name="attrs.name"
                autocomplete="off"
                :checked="value == option.value"
                :value="option.value"
                :disabled="attrs.disabled"
                @change="$emit('input', option.value)">

            <span class="checkmark"></span>
        </label>
    </div>
</template>

<script>
export default {
    props: ['attrs', 'value'],
}
</script>
