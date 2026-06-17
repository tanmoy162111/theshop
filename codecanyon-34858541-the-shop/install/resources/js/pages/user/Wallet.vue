<template>
  <div class="ps-lg-7 pt-4">
    <h1 class="fs-24 fw-700 opacity-80 mb-5 mt-3">{{ $t("my_wallet") }}</h1>
    <v-row class="mb-4">
      <v-col
        cols="12"
        xl="9"
        sm="6"
      >
        <v-sheet
          color="grey-darken-3"
          rounded="rounded"
          elevation="1"
          height="150"
          class="d-flex justify-center align-center white--text flex-column"
        >
          <div class="fs-16 mb-3">{{ $t("wallet_balance") }}</div>
          <div class="fw-300 text-h3">{{ format_price(currentUser.balance) }}</div>
        </v-sheet>
      </v-col>
      <v-col
        cols="12"
        sm="6"
        xl="3"
      >
        <recharge-dialog
          :show="rechargeDialogShow"
          from="/user/wallet"
          @close="rechargeDialogClosed"
        />
        <v-sheet
          color="grey-darken-1"
          rounded="rounded"
          elevation="1"
          height="150"
          class="d-flex justify-center align-center white--text flex-column c-pointer"
          @click.stop="rechargeDialogShow = true"
        >
          <div class="fs-16 mb-3">{{ $t("recharge_wallet") }}</div>
          <div class="fw-300 display-2">+</div>
        </v-sheet>
      </v-col>
    </v-row>
    <h2 class="fs-20 fw-700 opacity-80 mb-4">{{ $t("wallet_history") }}</h2>
    <div>
      <v-data-table
        :headers="headers"
        :items="rechargeHistories"
        class="border px-4 pt-3"
        :loading-text="$t('loading_please_wait')"
        hide-default-footer
        :loading="loading"
      >

        <template v-slot:[`item.date`]="{ item }">
          <span class="fw-600">{{ item.date }}</span>
        </template>

        <template v-slot:[`item.amount`]="{ item }">
          <span class="fw-600">{{ format_price(item.amount) }}</span>
        </template>

        <template v-slot:[`item.details`]="{ item }">
          <span
            class="fw-600"
            v-if="item.details == 'Recharge'"
          >{{ item.details }} <span v-if="item.payment_method"> ({{ item.payment_method }})</span></span>
          <span
            class="fw-600"
            v-else
          >{{ item.details }} <a
              :href="item.receipt"
              target="_blank"
              v-if="item.receipt"
            >({{ $t('receipt') }})</a></span>
        </template>
        <template v-slot:[`item.type`]="{ item }">
          <v-btn
            v-if="item.type == 'Added'"
            size="x-small"
            color="success"
            elevation="0"
          >{{ $t('Added') }}</v-btn>
          <v-btn
            v-else-if="item.type == 'Deducted' || item.type == 'Cancelled'"
            size="x-small"
            color="error"
            elevation="0"
          >{{ item.type }}</v-btn>
          <v-btn
            v-else
            size="x-small"
            color="warning"
            elevation="0"
          >{{ item.type }}</v-btn>
        </template>

      </v-data-table>

      <div
        class="text-center"
        v-if="totalPages > 1"
      >
        <v-pagination
          v-model="currentPage"
          @update:modelValue="getList"
          :length="totalPages"
          prev-icon="las la-angle-left"
          next-icon="las la-angle-right"
          :total-visible="7"
          elevation="0"
          class="my-4"
        ></v-pagination>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import RechargeDialog from "../../components/wallet/RechargeDialog.vue";
export default {
  data: () => ({
    rechargeDialogShow: false,
    loading: true,
    currentPage: 1,
    totalPages: 1,
    rechargeHistories: [],
  }),
  components: {
    RechargeDialog,
  },
  computed: {
    ...mapGetters("auth", ["currentUser"]),
    headers() {
      return [
        {
          title: this.$i18n.t("date"),
          align: "start",
          sortable: false,
          value: "date",
        },
        {
          title: this.$i18n.t("amount"),
          sortable: false,
          value: "amount",
        },
        {
          title: this.$i18n.t("details"),
          sortable: false,
          value: "details",
        },
        {
          title: this.$i18n.t("type"),
          sortable: false,
          align: "center",
          value: "type",
        },
      ];
    },
  },
  watch: {
    currentPage() {
      this.$router
        .push({
          query: {
            ...this.$route.query,
            page: this.currentPage,
          },
        })
        .catch(() => {});
    },
  },
  methods: {
    ...mapActions("auth", ["rechargeWallet"]),
    async getList(number) {
      this.loading = true;
      const res = await this.call_api(
        "get",
        `user/wallet/history?page=${number}`
      );
      if (res.data.success) {
        this.rechargeHistories = res.data.data;
        this.totalPages = res.data.meta.last_page;
        this.currentPage = res.data.meta.current_page;
      } else {
        this.snack({
          message: this.$i18n.t("something_went_wrong"),
          color: "red",
        });
      }
      this.loading = false;
    },
    rechargeDialogClosed() {
      this.rechargeDialogShow = false;
    },
  },
  created() {
    let page = this.$route.query.page || this.currentPage;
    this.getList(page);
  },
  mounted() {
    this.rechargeWallet(this.$route.query.wallet_payment);
  },
};
</script>