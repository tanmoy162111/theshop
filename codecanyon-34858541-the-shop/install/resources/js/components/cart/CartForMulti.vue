<template>
  <div>
    <v-list-item class="d-block pa-4 border-bottom side-cart-top">
      <div class="w-100 d-flex">
          <div class="d-flex" style="align-items: center;">
            <i class="la la-shopping-cart la-3x me-2 text-primary" />
            <div class="lh-1-4">
              <div class="fs-16 fw-500">
                  {{ getCartCount }} {{ $t("items") }}
              </div>
            </div>        
          </div>
          <button class="ms-auto" type="button" @click.stop="updateCartDrawer(false)">
              <i class="la la-close fs-20" />
          </button>
      </div>
    </v-list-item>

    <div
      class="side-cart-content c-scrollbar px-2"
      v-if="getCartShops.length > 0"
    >
      <v-expansion-panels
        class=""
        v-model="panel"
        accordion
        flat
      >
        <v-expansion-panel
          v-for="(shop,i) in getCartShops"
          :key="i"
        >
          <div :class="['d-flex align-center pa-2',{'border-top': i != 0}]">


            <v-checkbox
              true-icon="las la-check"
              hide-details
              class="mt-0 pt-0"
              :model-value="shop.selected"
              @update:modelValue="toggleCartShop({ shop_id: shop.id, status: $event })"
            />
            <v-expansion-panel-title class="v-expansion-panel-title-cart ms-auto px-2 py-2">
              <div class="d-flex align-center flex-grow-1">
                <img
                  :src="shop.logo"
                  :alt="shop.name"
                  class="size-40px flex-shrink-0 me-2"
                >
                <div>
                  <span class="fw-500">{{ shop.name }}</span>
                  <div class="fs-12 opacity-70 mt-1">{{ format_price(getShopCartPrice(shop.id)) }}</div>
                </div>
              </div>
              <template v-slot:actions>
                <i class="las la-angle-down"></i>
              </template>
            </v-expansion-panel-title>
          </div>

          <v-expansion-panel-text class="border-top">

            <min-order-progress
              class="mt-3"
              :shop-id="shop.id"
              :cart-price="getShopCartPrice(shop.id)"
              :min-order="getShopMinOrder(shop.id)"
              v-if="getShopMinOrder(shop.id) > 0"
            />

            <v-list
              dense
              class=""
            >
              <cart-items :cart-items="getShopProductsById(shop.id)" />
            </v-list>

            <coupon-form
              class="mb-3"
              :shop-id="shop.id"
            />

          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>

    <div
      v-else
      class="px-5 py-2 side-cart-content"
    >
      <div class="d-flex flex-column justify-center h-100 text-center pa-5">
        <img
          class="img-fluid"
          :src="static_asset(`/assets/img/no-cart-item.jpg`)"
          alt="$t('your_shopping_bag_is_empty_start_shopping')"
        />
        <div class="fs-20">
          {{ $t("your_shopping_bag_is_empty_start_shopping") }}
        </div>
      </div>
    </div>

    <v-list-item class="pa-4 border-top side-cart-bottom d-block">
      <v-btn
        style="color: #fff !important;"
        elevation="0"
        color="primary"
        class=""
        large
        block
        @click="checkout"
      >
        {{ $t("checkout") }}
        {{ format_price(getCartPrice - getTotalCouponDiscount) }}
      </v-btn>
    </v-list-item>
  </div>
</template>

<script>
import { mapActions, mapGetters, mapMutations } from "vuex";
import CartItems from "./CartItems.vue";
import CouponForm from "./CouponForm.vue";
import MinOrderProgress from "./MinOrderProgress.vue";
export default {
  components: { CartItems, CouponForm, MinOrderProgress },
  data: () => ({
    panel: 0,
    couponCode: null,
    couponLoading: false,
  }),
  computed: {
    ...mapGetters("cart", [
      "getCartCount",
      "getCartPrice",
      "getCartShops",
      "getShopMinOrder",
      "getShopCartPrice",
      "getShopProductsById",
      "getTotalCouponDiscount",
    ]),
  },
  methods: {
    ...mapActions("cart", [
      "updateQuantity",
      "toggleCartShop",
      "toggleCartItem",
      "removeFromCart",
    ]),
    ...mapMutations("auth", ["updateCartDrawer"]),
    checkout() {
      if (this.getCartPrice > 0) {
        this.$router.push({ name: "Checkout" }).catch((e) => {
          if (this.$route.name == "Checkout") {
            this.updateCartDrawer(false);
          }
        });
      } else {
        this.snack({
          message: this.$i18n.t("please_select_a_cart_product"),
          color: "red",
        });
        return;
      }
    },
  },
};
</script>
<style scoped>
.side-cart-content {
  height: calc(100vh - 152px);
  max-height: calc(100vh - 152px);
  overflow-y: auto;
}
</style>