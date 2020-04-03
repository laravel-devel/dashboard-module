<template>
    <div class="file-input">
        <label v-if="attrs.label" v-text="attrs.label"></label>

        <input type="file"
            ref="file"
            :name="attrs.name"
            autocomplete="off"
            :placeholder="attrs.placeholder ? attrs.placeholder : ''"
            :value="value"
            :accept="attrs.accept ? attrs.accept : '*'"
            :disabled="attrs.disabled"
            @change="onFileChanged">

        <a href="#" class="btn">
            {{ attrs.text ? attrs.text : 'Choose a file' }}
        </a>
    </div>
</template>

<script>
export default {
    props: ['attrs', 'value'],

    methods: {
        onFileChanged() {
            this.$emit('input', this.$refs.file.files[0])
        }
    }
}
</script>
