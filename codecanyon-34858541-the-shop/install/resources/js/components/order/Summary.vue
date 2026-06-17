<template>
  <div v-if="!is_empty_obj(orderDetails) && orderDetails.orders.length > 0">

    <div
      class="bg-grey-lighten-4 border border-gray-200 pa-4 rounded d-flex justify-space-between align-center"
    >

      <p class="text-reset fs-16 fw-700 lh-1">{{ $t('order_summary') }}</p>

      <span class="text-end">
        <div
        @click="reOrder(orderDetails.orders)"
          class="fs-12 c-pointer text-primary"
        > {{$t('reorder')  }}</div>
  
      </span>
    </div>


    <v-row class="mb-3">
      <v-col
        md="6"
        cols="12"
        class="pb-0 pb-md-3"
      >
        <v-list dense>
          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('order_code') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title >{{ orderDetails.code }}</v-list-item-title>
              </v-col>
            </v-row>
          </v-list-item>
          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('name') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title >{{ orderDetails.user.name }}</v-list-item-title>
              </v-col>
            </v-row>            
          </v-list-item>

          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('email') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title >{{ orderDetails.user.email }}</v-list-item-title>
              </v-col>
            </v-row> 
          </v-list-item>
          <v-list-item>
            <v-row v-if="orderDetails.orders[0].type_of_delivery != 'pickup'">
              <v-col cols="6">
                <v-list-item-title class="fw-700 align-self-baseline">{{ $t('shipping_address') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title >
              <span class="d-block lh-1-6">{{ orderDetails.shipping_address.address }}, {{ orderDetails.shipping_address.postal_code }}</span>
              <span class="d-block lh-1-6">{{ orderDetails.shipping_address.city }}, {{ orderDetails.shipping_address.state }}, {{ orderDetails.shipping_address.country }}</span>
              <span class="lh-1-6">{{ orderDetails.shipping_address.phone }}</span>
            </v-list-item-title>
              </v-col>
            </v-row> 
          </v-list-item>
        </v-list>
      </v-col>
      <v-col
        md="6"
        cols="12"
        class="pt-0 pt-md-3"
      >
        <v-list dense>
          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('total_order_amount') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end">{{ format_price(orderDetails.grand_total) }}</v-list-item-title>
              </v-col>
            </v-row>            
          </v-list-item>

          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('payment_method') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end text-capitalize">{{ $t(orderDetails.orders[0].payment_type) }}</v-list-item-title>
              </v-col>
            </v-row> 
          </v-list-item>

          <!-- show offline payment data -->
          <v-list-item v-if="orderDetails.orders[0].payment_type === 'offline_payment'">
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('payment_details') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end text-capitalize">
              <span>{{ $t('transaction_id') }}: {{ $t(orderDetails.orders[0].manual_payment_data.transactionId) }}</span>
              <span>
                {{ $t('paid_via') }}: {{ $t(orderDetails.orders[0].manual_payment_data.payment_method) }}
                <a
                  :href="appUrl+'/public/'+orderDetails.orders[0].manual_payment_data.reciept"
                  v-if="orderDetails.orders[0].manual_payment_data.reciept"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  ({{ $t('receipt') }})
                </a>
              </span>
            </v-list-item-title>
              </v-col>
            </v-row>            
          </v-list-item>
          <!-- show offline payment data -->

          <v-list-item>
            <v-row>
              <v-col cols="6">
                <v-list-item-title class="fw-700">{{ $t('delivery_type') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end text-capitalize">{{orderDetails.orders[0].type_of_delivery == 'pickup' ? 'pickup' : orderDetails.orders[0].delivery_type.replaceAll('_',' ') }}</v-list-item-title>
              </v-col>
            </v-row> 
          </v-list-item>

          <v-list-item>
            <v-row v-if="orderDetails.orders[0].type_of_delivery != 'pickup'">
              <v-col cols="6">
                <v-list-item-title class="fw-700 align-self-baseline">{{ $t('billing_address') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end">
              <span class="d-block lh-1-6">{{ orderDetails.billing_address.address }}, {{ orderDetails.billing_address.postal_code }}</span>
              <span class="d-block lh-1-6">{{ orderDetails.billing_address.city }}, {{ orderDetails.billing_address.state }}, {{ orderDetails.billing_address.country }}</span>
              <span class="lh-1-6">{{ orderDetails.billing_address.phone }}</span>
            </v-list-item-title>
              </v-col>
            </v-row> 
            <v-row v-else>
              <v-col cols="6">
                <v-list-item-title class="fw-700 align-self-baseline">{{ $t('pickup_point') }} :</v-list-item-title>
              </v-col>
              <v-col cols="6">
                <v-list-item-title class="align-end">
              <span class="d-block lh-1-6">{{ orderDetails.orders[0].pickup_point?.name }}, {{ orderDetails.orders[0].pickup_point?.manager }}</span>
              <span class="d-block lh-1-6">{{ orderDetails.orders[0].pickup_point?.location }}</span>
              <span class="lh-1-6">{{ orderDetails.orders[0].pickup_point?.phone }}</span>
            </v-list-item-title>
              </v-col>
            </v-row> 
          </v-list-item>
          
        </v-list>
      </v-col>
    </v-row>
    <v-sheet
      class=""
      color="white"
      elevation="0"
      v-for="(order, i) in orderDetails.orders"
      :key="i"
    >
      <order-package :order-details="order" />
    </v-sheet>
  </div>
</template>

<script>
import { mapActions, mapGetters, mapMutations } from "vuex";

import OrderPackage from "./OrderPackage.vue";
export default {
  components: { OrderPackage },
  computed: {
    ...mapGetters("app", ["appUrl"]),
  },
  props: {
    orderDetails: { type: Object, default: () => {} },
  },
  methods:{
    ...mapActions("cart", ["addToCart"]),
    ...mapMutations("auth", ["updateCartDrawer"]),
    // async reOrder(orders) {
    //   let orederedProducts = orders[0].products.data;
    //   orederedProducts.forEach((product) => {
    //     this.addToCart({
    //     variation_id: product.product_variation_id,
    //     qty: product.quantity,
    //   });
    //   });
    //   this.checkout();
    // },

    async reOrder(orders) {
            orders.forEach(order=>{
                this.multipleShop(order)
            })
           
        },

        multipleShop(order){
            order.products.data.forEach((product) => {
                this.addToCart({
                    variation_id: product.product_variation_id,
                    qty: product.quantity,
                });
            });
            this.checkout();
        },


    checkout() {    
        this.$router.push({ name: "Checkout" }).catch((e) => {
          if (this.$route.name == "Checkout") {
            this.updateCartDrawer(false);
          }
        });
    },
  }
};
</script>