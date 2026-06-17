<template>
  <v-container class="px-3 px-md-3">
    <div class="hp-trust">
      <div class="hp-trust__item" v-for="(item, i) in items" :key="i">
        <div class="hp-trust__ico"><i :class="item.icon"></i></div>
        <div>
          <b class="hp-trust__title">{{ item.title }}</b>
          <span class="hp-trust__sub">{{ item.sub }}</span>
        </div>
      </div>
    </div>
  </v-container>
</template>

<script>
export default {
  computed: {
    items() {
      return [
        { icon: "las la-shipping-fast", title: this.t("trust_free_delivery", "Free Delivery"), sub: this.t("trust_free_delivery_sub", "On orders over $50") },
        { icon: "las la-undo-alt", title: this.t("trust_easy_returns", "Easy Returns"), sub: this.t("trust_easy_returns_sub", "7-day money back") },
        { icon: "las la-lock", title: this.t("trust_secure_payment", "Secure Payments"), sub: this.t("trust_secure_payment_sub", "100% protected") },
        { icon: "las la-star", title: this.t("trust_trusted_store", "Trusted Store"), sub: this.t("trust_trusted_store_sub", "4.8 / 5 rating") },
      ];
    },
  },
  methods: {
    t(key, fallback) {
      const v = this.$t(key);
      return v && v !== key ? v : fallback;
    },
  },
};
</script>

<style scoped>
.hp-trust {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
  margin: 18px 0 6px;
}
.hp-trust__item {
  display: flex; align-items: center; gap: 12px;
  background: var(--hp-surface); border-radius: 12px; padding: 14px 16px;
  box-shadow: var(--hp-shadow-sm);
}
.hp-trust__ico {
  width: 42px; height: 42px; flex: 0 0 auto; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: var(--soft-primary); color: var(--primary); font-size: 20px;
}
.hp-trust__title { display: block; font-size: 14px; line-height: 1.2; }
.hp-trust__sub { font-size: 12px; color: #6B7280; }
@media (max-width: 600px) {
  .hp-trust { grid-template-columns: repeat(2, 1fr); }
}
</style>
