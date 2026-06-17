<template>
  <v-container class="pt-7">
    <v-row>
      <v-col
        xl="8"
        lg="10"
        cols="12"
        class="mx-auto"
        v-if="!is_empty_obj(order)"
      >
        <div class="text-center py-5">
          <h1>{{ $t('thank_you_for_your_order') }}</h1>
          <div>Order Code : <span class="secondary--text">{{ order.code }}</span></div>
          <div
            class="font-italic"
            v-if="order.user.email"
          >{{ $t('a_copy_of_your_order_summary_has_been_sent_to') }} {{ order.user.email }}</div>
        </div>
        <Summary :order-details="order" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import Summary from "../components/order/Summary.vue";
export default {
  data: () => {
    return {
      order: {},
    };
  },
  components: {
    Summary,
  },
  methods: {
    async getDetails() {
      const res = await this.call_api(
        "get",
        `user/order/${this.$route.query.orderCode}`
      );
      if (res.data.success) {
        this.order = res.data.data;
      } else {
        this.snack({
          message: res.data.message,
          color: "red",
        });
        return;
      }
    },
  },
  created() {
    this.getDetails();
  },
};
</script>