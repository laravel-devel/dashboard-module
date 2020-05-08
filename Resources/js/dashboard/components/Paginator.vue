<template>
    <div v-show="lastPage > 1" class="paginator">
        <div class="summary">
            Showing rows {{ info.from }}-{{ info.to }} from {{ info.total }}
        </div>

        <div class="nav">
            <a href="#"
                class="btn"
                :disabled="page == 1"
                @click.prevent="prevPage">Prev</a>

            <template v-if="showFirstPage">
                <a href="#"
                    class="btn number"
                    :class="{ active: page == 1 }"
                    @click.prevent="changePage(1)">1</a>

                <a href="#"
                    class="btn dots"
                    @click.prevent="leapBackwrad">...</a>
            </template>

            <a v-for="(p, index) in pages"
                :key="index"
                href="#"
                class="btn page"
                :class="{ active: p == page }"
                @click.prevent="changePage(p)"
                v-text="p"></a>

            <template v-if="showLastPage">
                <a href="#"
                    class="btn dots"
                    @click.prevent="leapForward">...</a>

                <a href="#"
                    class="btn number"
                    :class="{ active: page == lastPage }"
                    @click.prevent="changePage(lastPage)"
                    v-text="lastPage"></a>
            </template>
                
            <a href="#"
                class="btn"
                :disabled="page == lastPage"
                @click.prevent="nextPage">Next</a>

            <v-form-el :inline="true"
                :field="{
                    type: 'text'
                }"
                v-model="pageInput"
                class="page-input"></v-form-el>

            <button class="btn" @click="goToPage">Go</button>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        page: Number,

        info: {},

        maxNumbers: {
            type: Number,
            default: 5,
        },
    },

    data() {
        return {
            pages: [],
            pageInput: this.page,
        };
    },

    computed: {
        lastPage() {
            return this.info ? this.info.last_page : 1;
        },

        showFirstPage() {
            if (this.lastPage <= this.maxNumbers) {
                return;
            }
            
            const threshold = Math.ceil(this.maxNumbers / 2);

            return (this.page > threshold);
        },

        showLastPage() {
            if (this.lastPage <= this.maxNumbers) {
                return;
            }

            const threshold = Math.floor(this.maxNumbers / 2);

            return (this.page < this.lastPage - threshold);
        },
    },

    watch: {
        info() {
            this.generatePages();
        },

        page(value) {
            this.pageInput = value;
        }
    },

    methods: {
        generatePages() {
            // Determine the first and the last page number in the sequence
            let first = this.page - 2;
            let last = first + (this.maxNumbers - 1);

            if (first < 1) {
                const diff = 1 - first;

                first += diff;
                last += diff;
            }

            if (last > this.lastPage) {
                const diff = last - this.lastPage;

                last = this.lastPage;
                first = (first - diff >= 1) ? (first - diff) : 1;
            }

            // Populate the pages array
            this.pages = [];

            for (let i = first; i <= last; i++) {
                this.pages.push(i);
            }
        },

        changePage(page) {
            this.$emit('pageChanged', page);
        },

        prevPage() {
            this.changePage(this.page > 1 ? this.page - 1 : 1);
        },

        nextPage() {
            this.changePage(this.page < this.lastPage ? this.page + 1 : this.lastPage);
        },

        goToPage() {
            const page = parseInt(this.pageInput);

            if (isNaN(page)) {
                return;
            }

            if (page < 1 || page > this.lastPage) {
                return;
            }

            this.changePage(page);
        },

        leapBackwrad() {
            let page = this.page - this.maxNumbers;

            this.changePage(page < 1 ? 1 : page);
        },

        leapForward() {
            let page = this.page + this.maxNumbers;

            this.changePage(page > this.lastPage ? this.lastPage : page);
        },
    }
}
</script>

<style scoped>
.page-input {
    width: 5rem;
}
</style>