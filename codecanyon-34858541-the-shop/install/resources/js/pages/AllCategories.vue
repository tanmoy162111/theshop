<template>
    <div class="pb-6">
        <v-container>
            <h1 class="mb-7 mt-4">{{ $t('all_categories') }}</h1>
            <v-row v-if="categories.length">
                <v-col cols="12" md="6" v-for="(category, i) in categories" :key="i">
                    <div v-if="loading">
                        <v-skeleton-loader
                            type="image"
                            class=""
                            height="235"
                        ></v-skeleton-loader>
                    </div>
                    <v-card outlined class="pa-6" v-else variant="outlined">
                        <v-row align="center">
                            <v-col cols="4">
                                <router-link :to="{ name: 'Category', params: {categorySlug: category.slug}}" class="text-reset">
                                    <img
                                        class="img-fluid"
                                        :src="category.banner"
                                        :alt="category.name"
                                        @error="imageFallback($event)"
                                    />
                                </router-link>
                            </v-col>
                            <v-col cols="8">
                                <h3 class="mb-3">
                                    <router-link :to="{ name: 'Category', params: {categorySlug: category.slug}}" class="text-reset">{{ category.name }}</router-link>
                                </h3>
                                <div v-if="category.children.data.length">
                                    <v-hover v-slot="{ hover }" v-for="(children, j) in category.children.data" :key="j">
                                        <v-hover v-slot="{ hover }">
                                        <router-link
                                            :class="['text-reset me-1 opacity-80 category-item-text', {'primary--text text-decoration-underline':hover}]"
                                            :to="{ name: 'Category', params: {categorySlug: children.slug}}"
                                        >{{ children.name }}<span v-if="j+1 !== category.children.data.length" class="">,</span></router-link>
                                    </v-hover>
                                </v-hover>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>

<script>
export default {

    head: {
        title: 'All Categories',
    },
    data: () => ({
        loading: true,
        categories: [{},{},{},{}]
    }),
    mounted: () => {},
    methods: {},
    async created() {
        const res = await this.call_api("get", "all-categories");
        if (res.data.success) {
            this.categories = res.data.data
            this.loading = false
        }
    }
};
</script>

<!-- <style scoped>
.category-item-text:hover {
  color: var(--primary) !important; /* Change the color to your desired color */
  text-decoration: underline;
}
</style> -->