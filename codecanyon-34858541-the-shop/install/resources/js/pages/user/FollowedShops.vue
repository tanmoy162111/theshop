<template>
  <div class="ps-lg-7 pt-4">
    <v-row
      v-if="shops.length > 0"
      class="row-cols-1 row-cols-sm-2 row-cols-md-3"
    >
      <v-col
        v-for="(shop, i) in shops"
        :key="i"
      >
        <shop-box
          :is-loading="loading"
          :shop-details="shop"
        />
      </v-col>
    </v-row>
    <div
      class="text-center"
      v-else
    >
      <div>{{ $t("no_followed_shop_found") }}</div>
    </div>
  </div>
</template>

<script>
import ShopBox from "../../components/shop/ShopBox.vue";
export default {
  data: () => ({
    loading: true,
    shops: [{}, {}, {}, {}, {}, {}],
  }),
  components: { ShopBox },
  async created() {
    const res = await this.call_api("get", "user/follow");
    if (res.data.success) {
      this.shops = res.data.data;
      this.loading = false;
    }
  },
};
</script>
