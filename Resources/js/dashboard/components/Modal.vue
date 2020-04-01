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
        Events.$on('modal', (options) => {
            if (options.id === this.mId) {
                this.show();
            } else if (!this.mId && !options.id) {
                this.show();
            }
        });
    },

    methods: {
        show() {
            this.showing = true;
        },

        hide() {
            this.showing = false;
        },
    }
}
</script>
