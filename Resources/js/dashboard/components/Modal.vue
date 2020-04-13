<template>
    <div v-if="showing" class="full-overlay" @click="hide">
        <div class="card modal" @click.stop>
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    props: ['mId'],

    data() {
        return {
            showing: false,
        };
    },
 
    created() {
        this.registerEventListeners();
    },

    methods: {
        registerEventListeners() {
            Events.$on('show-modal', (options) => {
                if (options.id === this.mId) {
                    this.show();
                } else if (!this.mId && !options.id) {
                    this.show();
                }
            });

            Events.$on('hide-modal', (options) => {
                if (this.mId && options.id === this.mId) {
                    this.hide();
                }
            });
        },

        show() {
            this.showing = true;
        },

        hide() {
            this.showing = false;
        },
    }
}
</script>
