<template>
  <v-row class="">
    <v-col
      cols="12"
      md="6"
      lg="5"
    >
      <ProductGallery
        :is-loading="isLoading"
        :gallery-imgaes="productDetails.photos"
        :selectedVariation ="selectedVariation"
      />
    </v-col>
    <v-col
      md="6"
      lg="7"
    >
      <!-- <template v-if="isLoading">
        <v-skeleton-loader
          type="table-cell,sentences,table-cell,divider,table-cell,table-row"
          class="mb-3"
        />
      </template> -->
      <template v-if="!isLoading">
        <h1 class="fs-21 opacity-80 mb-3 lh-1-3"> {{ productDetails.name }} </h1>

        <div class="d-flex fs-12 ">
          <span class="me-3 opacity-50">{{ productDetails.review_summary.average.toFixed(2) }}</span>
          <v-rating
            class="lh-1-5 "
            background-color=""
            empty-icon="las la-star"
            full-icon="las la-star active"
            half-icon="las la-star half"
            hover
            half-increments
            readonly
            length="5"
            :model-value="productDetails.review_summary.average"
            size="x-small"
          >
          </v-rating>
           
          <span class="ms-3 opacity-50"> ({{ productDetails.review_summary.total_count }} {{ $t("ratings") }}) </span>

          <!-- todo:: message seller -->
          <div
            v-if="generalSettings.conversation_system == 1 && is_addon_activated('multi_vendor') && currentUser.user_type == 'customer'"
            class="ms-auto"
          >
            <button
              class="primary-text pa-1 lh-1 d-flex align-center"
              @click="showConversationDialog({status:true})"
            >
              <i class="las la-comment ts-02 fs-14 me-1"></i>
              <span class="fw-700">{{ $t("contact_to_seller") }}</span>
            </button>
          </div>

          <conversation-dialog :product="productDetails" />
          <!-- message seller -->

          <div
            :class="['ms-2', { 'ms-auto': generalSettings.conversation_system != 1 }, { 'ms-auto': !is_addon_activated('multi_vendor') }]"
            v-if="currentUser.user_type == 'customer'"
          >
            <template v-if="isThisWishlisted(productDetails.id)">
              <button
                class="primary-text pa-1 lh-1 d-flex align-center"
                @click="removeFromWishlist(productDetails.id)"
              >
                <i class="las la-heart ts-02 fs-14 me-1"></i>
                <span class="fw-700">{{ $t("remove_from_wishlist") }}</span>
              </button>
            </template>
            <template v-else>
              <button
                class="primary-text pa-1 lh-1 d-flex align-center"
                @click="addNewWishlist(productDetails.id)"
              >
                <i class="las la-heart-o ts-02 fs-14 me-1"></i>
                <span class="fw-700">{{ $t("add_to_wishlist") }}</span>
              </button>
            </template>
          </div>
        </div>

        <v-divider class="mb-4 mt-2"></v-divider>

        <v-row
          v-if="productDetails.brand.name"
          align="center"
          class="mb-4"
        >
          <v-col
            cols="3"
            lg="2"
          >
            <span class="border pa-2 lh-0 rounded d-inline-block">
              <router-link :to="{ name: 'Brand', params: { brandId: productDetails.brand.id, } }">
                <img
                  class="img-fluid h-40px"
                  :src="productDetails.brand.logo"
                  :alt="productDetails.brand.name"
                  @error="imageFallback($event)"
                />
              </router-link>
            </span>
          </v-col>
          <v-col
            cols="9"
            lg="10"
          >
            <span class="d-flex flex-column align-baseline lh-1-3">
              <span class="fs-12 opacity-60">{{ $t("brand") }}</span>
              <span class="fw-700">{{ productDetails.brand.name }}</span>
              <router-link
                :to="{ name: 'Brand', params: { brandId: productDetails.brand.id, }}"
                class="text-reset border-bottom fs-10 opacity-60"
              >{{ $t("view_other_products")}}</router-link>
            </span>
          </v-col>
        </v-row>

        <div
          v-if="discount > 0"
          class="lh-1"
        >
          <v-row>
            <v-col
              cols="3"
              lg="2"
              class="py-2"
            >
              <span class="opacity-60 fs-12">{{ $t("price") }}</span>
            </v-col>
            <v-col
              cols="9"
              lg="10"
              class="py-2 d-flex align-baseline"
            >
              <del>
                <template v-if=" productDetails.base_price !== productDetails.highest_price ">
                  <span class="d-inline-flex align-center fs-20 fw-500 opacity-50">
                    <span class="">{{ format_price( productDetails.base_price ) }}</span>
                    <span class="mx-2">-</span>
                    <span class="">{{ format_price( productDetails.highest_price ) }}</span>
                  </span>
                </template>
                <template v-else>
                  <span class="fs-20 fw-500 opacity-60">{{ format_price( productDetails.base_price ) }}</span>
                </template>
              </del>
              <span
                v-if="productDetails.unit"
                class="fs-12 opacity-50 ms-1"
              >/{{ productDetails.unit }}</span>

              <span
                v-if="discount > 0"
                class="discount-badge details-page ms-2"
              >
                {{ $t("off") }} {{ discount }}%
              </span>
              <div
                v-if="generalSettings.club_point == 1"
                class="d-flex flex-row align-center max-w-80px club-badge rounded-sm ms-2"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="8"
                  viewBox="0 0 18 12.001"
                >
                  <g
                    id="Group_23890"
                    data-name="Group 23890"
                    transform="translate(-631 -822)"
                  >
                    <path
                      id="Subtraction_84"
                      data-name="Subtraction 84"
                      d="M7583,12a5.989,5.989,0,0,1-1.8-.274,7.1,7.1,0,0,0,0-11.45A5.927,5.927,0,0,1,7583,0a6,6,0,1,1,0,12Zm-1.885-.3A3.016,3.016,0,1,0,7580,11.2a6.1,6.1,0,0,0,1.113.5Z"
                      transform="translate(-6940 822)"
                      fill="#fff"
                    />
                    <path
                      id="Subtraction_85"
                      data-name="Subtraction 85"
                      d="M7581.91,10.636a4.954,4.954,0,0,1-.807-.066,7.09,7.09,0,0,0,0-9.689,4.914,4.914,0,0,1,5.717,4.844A4.916,4.916,0,0,1,7581.91,10.636Zm-2.011-.43h0a4.912,4.912,0,0,1,0-8.961,6,6,0,0,1,0,8.961Z"
                      transform="translate(-6938.91 822.274)"
                      fill="#f5a100"
                      opacity="0.7"
                    />
                    <path
                      id="Subtraction_86"
                      data-name="Subtraction 86"
                      d="M7580.277,9.049v0a7.089,7.089,0,0,0,.35-.765,1.19,1.19,0,0,0,.392-.3,1.245,1.245,0,0,0,.3-.852,1.073,1.073,0,0,0-.213-.695c-.012-.014-.023-.028-.037-.042.021-.224.031-.449.031-.67,0-.083,0-.173-.005-.275a1.875,1.875,0,0,1,.555.375,1.556,1.556,0,0,1,.362.556,1.912,1.912,0,0,1,.125.7,1.906,1.906,0,0,1-1.861,1.968Zm-1.191-.144h0a2.076,2.076,0,0,1-.269-.113,1.7,1.7,0,0,1-.643-.62,2.2,2.2,0,0,1-.316-1.047l.8-.15a1.986,1.986,0,0,0,.327.94,1.293,1.293,0,0,0,.441.371,5.917,5.917,0,0,1-.339.619Zm.7-1.6h0V5.857a3.326,3.326,0,0,1-1-.381,1.487,1.487,0,0,1-.586-.588,1.764,1.764,0,0,1-.2-.851,1.747,1.747,0,0,1,.6-1.374,1.485,1.485,0,0,1,.407-.249,5.99,5.99,0,0,1,.357.613,1.06,1.06,0,0,0-.571.947,1.022,1.022,0,0,0,.229.679,1.567,1.567,0,0,0,.771.438V4.143a6.026,6.026,0,0,1,0,3.157Zm1.37-3.352h0a1.352,1.352,0,0,0-.293-.716.988.988,0,0,0-.3-.227,7.086,7.086,0,0,0-.609-1.152h.3V2.23a2,2,0,0,1,1.1.407,1.817,1.817,0,0,1,.626,1.187l-.821.124Z"
                      transform="translate(-6936.998 822.274)"
                      fill="#fff"
                    />
                    <circle
                      id="Ellipse_666"
                      data-name="Ellipse 666"
                      cx="6"
                      cy="6"
                      r="6"
                      transform="translate(631 822)"
                      fill="#fff"
                    />
                    <circle
                      id="Ellipse_667"
                      data-name="Ellipse 667"
                      cx="4.91"
                      cy="4.91"
                      r="4.91"
                      transform="translate(632.09 823.09)"
                      fill="#f5a100"
                      opacity="0.7"
                    />
                    <path
                      id="Path_25349"
                      data-name="Path 25349"
                      d="M43.364,56.224v-.542a2.936,2.936,0,0,1-.969-.267,1.689,1.689,0,0,1-.645-.62,2.22,2.22,0,0,1-.316-1.047l.8-.15a2.016,2.016,0,0,0,.327.941,1.159,1.159,0,0,0,.8.473V52.48a3.319,3.319,0,0,1-1-.38,1.468,1.468,0,0,1-.585-.587,1.871,1.871,0,0,1,.4-2.226,2.089,2.089,0,0,1,1.192-.433v-.38h.468v.38a1.963,1.963,0,0,1,1.1.406,1.806,1.806,0,0,1,.627,1.188l-.821.124a1.36,1.36,0,0,0-.294-.718,1.083,1.083,0,0,0-.612-.329v2.292a6.312,6.312,0,0,1,.795.234,1.964,1.964,0,0,1,.605.4,1.6,1.6,0,0,1,.36.556,2.046,2.046,0,0,1-.4,2.076,1.928,1.928,0,0,1-1.36.592v.551h-.468Zm0-6.707a1.178,1.178,0,0,0-.731.371,1.1,1.1,0,0,0-.04,1.391,1.566,1.566,0,0,0,.771.437Zm.468,5.494a1.178,1.178,0,0,0,.766-.4,1.245,1.245,0,0,0,.3-.852,1.067,1.067,0,0,0-.214-.7,1.849,1.849,0,0,0-.855-.47Z"
                      transform="translate(593.424 775.651)"
                      fill="#fff"
                    />
                  </g>
                </svg>
                <div class="fs-13 ms-1">{{ productDetails.earn_point }}</div>
              </div>
            </v-col>
          </v-row>
          <v-row>
            <v-col
              cols="3"
              lg="2"
              class="py-2"
            >
              <span class="opacity-60 fs-12">{{$t("discounted_price")}}</span>
            </v-col>
            <v-col
              cols="9"
              lg="10"
              class="py-2"
            >
              <template v-if="productDetails.base_price !== productDetails.highest_price">
                <span class="d-inline-flex align-center primary-text fs-24 fw-500">
                  <span class="">{{ format_price(productDetails.base_discounted_price) }}</span>
                  <span class="mx-2">-</span>
                  <span class="">{{ format_price(productDetails.highest_discounted_price) }}</span>
                </span>
              </template>
              <template v-else>
                <span class="primary-text fs-24 fw-500">{{ format_price(productDetails.base_discounted_price) }}</span>
              </template>
              <span
                v-if="productDetails.unit"
                class="fs-12 opacity-50 ms-1"
              >/{{ productDetails.unit }}</span>
            </v-col>
          </v-row>
        </div>

        <div
          v-else
          class="lh-1"
        >
          <v-row>
            <v-col
              cols="3"
              lg="2"
              class="py-2"
            >
              <span class="opacity-60 fs-12">{{ $t("price") }}</span>
            </v-col>
            <v-col
              cols="9"
              lg="10"
              class="py-2"
            >
              <template v-if="productDetails.base_price !== productDetails.highest_price">
                <span class="d-inline-flex align-center primary-text fs-24 fw-500">
                  <span class="">{{ format_price(productDetails.base_price) }}</span>
                  <span class="mx-2">-</span>
                  <span class="">{{ format_price(productDetails.highest_price) }}</span>
                </span>
              </template>
              <template v-else>
                <span class="primary-text fs-24 fw-500">{{ format_price(productDetails.base_price) }}</span>
              </template>
              <span
                v-if="productDetails.unit"
                class="fs-12 opacity-50 ms-1"
              >/{{ productDetails.unit }}</span>
              <span class="bg-primary rounded-sm text-white px-2 h-15px ms-1">
                <span v-if="generalSettings.club_point == 1">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="8"
                    viewBox="0 0 18 12.001"
                  >
                    <g
                      id="Group_23890"
                      data-name="Group 23890"
                      transform="translate(-631 -822)"
                    >
                      <path
                        id="Subtraction_84"
                        data-name="Subtraction 84"
                        d="M7583,12a5.989,5.989,0,0,1-1.8-.274,7.1,7.1,0,0,0,0-11.45A5.927,5.927,0,0,1,7583,0a6,6,0,1,1,0,12Zm-1.885-.3A3.016,3.016,0,1,0,7580,11.2a6.1,6.1,0,0,0,1.113.5Z"
                        transform="translate(-6940 822)"
                        fill="#fff"
                      />
                      <path
                        id="Subtraction_85"
                        data-name="Subtraction 85"
                        d="M7581.91,10.636a4.954,4.954,0,0,1-.807-.066,7.09,7.09,0,0,0,0-9.689,4.914,4.914,0,0,1,5.717,4.844A4.916,4.916,0,0,1,7581.91,10.636Zm-2.011-.43h0a4.912,4.912,0,0,1,0-8.961,6,6,0,0,1,0,8.961Z"
                        transform="translate(-6938.91 822.274)"
                        fill="#f5a100"
                        opacity="0.7"
                      />
                      <path
                        id="Subtraction_86"
                        data-name="Subtraction 86"
                        d="M7580.277,9.049v0a7.089,7.089,0,0,0,.35-.765,1.19,1.19,0,0,0,.392-.3,1.245,1.245,0,0,0,.3-.852,1.073,1.073,0,0,0-.213-.695c-.012-.014-.023-.028-.037-.042.021-.224.031-.449.031-.67,0-.083,0-.173-.005-.275a1.875,1.875,0,0,1,.555.375,1.556,1.556,0,0,1,.362.556,1.912,1.912,0,0,1,.125.7,1.906,1.906,0,0,1-1.861,1.968Zm-1.191-.144h0a2.076,2.076,0,0,1-.269-.113,1.7,1.7,0,0,1-.643-.62,2.2,2.2,0,0,1-.316-1.047l.8-.15a1.986,1.986,0,0,0,.327.94,1.293,1.293,0,0,0,.441.371,5.917,5.917,0,0,1-.339.619Zm.7-1.6h0V5.857a3.326,3.326,0,0,1-1-.381,1.487,1.487,0,0,1-.586-.588,1.764,1.764,0,0,1-.2-.851,1.747,1.747,0,0,1,.6-1.374,1.485,1.485,0,0,1,.407-.249,5.99,5.99,0,0,1,.357.613,1.06,1.06,0,0,0-.571.947,1.022,1.022,0,0,0,.229.679,1.567,1.567,0,0,0,.771.438V4.143a6.026,6.026,0,0,1,0,3.157Zm1.37-3.352h0a1.352,1.352,0,0,0-.293-.716.988.988,0,0,0-.3-.227,7.086,7.086,0,0,0-.609-1.152h.3V2.23a2,2,0,0,1,1.1.407,1.817,1.817,0,0,1,.626,1.187l-.821.124Z"
                        transform="translate(-6936.998 822.274)"
                        fill="#fff"
                      />
                      <circle
                        id="Ellipse_666"
                        data-name="Ellipse 666"
                        cx="6"
                        cy="6"
                        r="6"
                        transform="translate(631 822)"
                        fill="#fff"
                      />
                      <circle
                        id="Ellipse_667"
                        data-name="Ellipse 667"
                        cx="4.91"
                        cy="4.91"
                        r="4.91"
                        transform="translate(632.09 823.09)"
                        fill="#f5a100"
                        opacity="0.7"
                      />
                      <path
                        id="Path_25349"
                        data-name="Path 25349"
                        d="M43.364,56.224v-.542a2.936,2.936,0,0,1-.969-.267,1.689,1.689,0,0,1-.645-.62,2.22,2.22,0,0,1-.316-1.047l.8-.15a2.016,2.016,0,0,0,.327.941,1.159,1.159,0,0,0,.8.473V52.48a3.319,3.319,0,0,1-1-.38,1.468,1.468,0,0,1-.585-.587,1.871,1.871,0,0,1,.4-2.226,2.089,2.089,0,0,1,1.192-.433v-.38h.468v.38a1.963,1.963,0,0,1,1.1.406,1.806,1.806,0,0,1,.627,1.188l-.821.124a1.36,1.36,0,0,0-.294-.718,1.083,1.083,0,0,0-.612-.329v2.292a6.312,6.312,0,0,1,.795.234,1.964,1.964,0,0,1,.605.4,1.6,1.6,0,0,1,.36.556,2.046,2.046,0,0,1-.4,2.076,1.928,1.928,0,0,1-1.36.592v.551h-.468Zm0-6.707a1.178,1.178,0,0,0-.731.371,1.1,1.1,0,0,0-.04,1.391,1.566,1.566,0,0,0,.771.437Zm.468,5.494a1.178,1.178,0,0,0,.766-.4,1.245,1.245,0,0,0,.3-.852,1.067,1.067,0,0,0-.214-.7,1.849,1.849,0,0,0-.855-.47Z"
                        transform="translate(593.424 775.651)"
                        fill="#fff"
                      />
                    </g>
                  </svg>
                  <span class="fs-13 ms-1">{{ productDetails.earn_point }}</span>
                </span>
              </span>
            </v-col>
          </v-row>
        </div>

        <div
          v-if="productDetails.is_variant == 1"
          class="border-top mt-4"
        >
          <v-row
            v-for="( variation_option, i ) in productDetails.variation_options"
            :key="i"
            class="mt-4"
          >
            <v-col
              cols="3"
              lg="2"
              class="py-2"
            >
              <span class="opacity-60 fs-12">{{ variation_option.name }}</span>
            </v-col>
            <v-col
              cols="9"
              lg="10"
              class="py-2"
            >
              <label
                v-for="(value, j) in variation_option.values"
                :key="j"
                class="aiz-megabox ps-0 me-2 d-inline-flex"
              >
                <input
                  v-model="chooseOptions[i]"
                  type="radio"
                  :name="`option_${variation_option.id}`"
                  :value="variation_option.id + ':' + value.id "
                  @change="optionChosen"
                />
                <span class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center py-1 px-3 mb-2">
                  <span class="fw-700">{{ value.name }}</span>
                </span>
              </label>
            </v-col>
          </v-row>
        </div>

        <v-row
          class="mt-4 gutters-5"
          v-if="!productDetails.is_digital"
        >
          <v-col
            cols="12"
            sm="6"
          >
            <div class="bg-soft-primary border border-primary rounded px-4 py-3 lh-1 stock_admin_contact_btn">
              <div class="mb-3 d-flex align-center">
                <template v-if="stock">
                  <i class="las la-check bg-green rounded-circle size-15px fs-8 white-text d-inline-flex align-center justify-center me-3"></i>
                  <span class="fs-12">{{ $t("in_stock") }}</span>
                </template>
                <template v-else>
                  <i class="las la-check grey rounded-circle size-15px fs-8 white-text d-inline-flex align-center justify-center me-3"></i>
                  <span class="fs-12">{{ $t("out_of_stock") }}</span>
                </template>
              </div>
              <button 
                v-if="generalSettings.support_chat"
                class="primary-text d-flex align-center"
                @click.stop="updateChatWindow(true)"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="14.994"
                  viewBox="0 0 16 14.994"
                >
                  <g
                    id="Group_22650"
                    data-name="Group 22650"
                    transform="translate(11363.863 11582.499)"
                  >
                    <path
                      id="Path_26196"
                      data-name="Path 26196"
                      d="M74.137,7.123v.813a1.064,1.064,0,0,0-.028.134,6.337,6.337,0,0,1-1.656,3.743,8.251,8.251,0,0,1-5.526,2.708,8.546,8.546,0,0,1-3.661-.426.245.245,0,0,0-.262.056,10.229,10.229,0,0,1-1,.711,4.905,4.905,0,0,1-2.434.633.464.464,0,0,1-.445-.658,1.021,1.021,0,0,1,.167-.224,2.793,2.793,0,0,0,.695-2.472.533.533,0,0,0-.119-.243,6.746,6.746,0,0,1-1.4-2.394,6.1,6.1,0,0,1-.152-3.43,6.866,6.866,0,0,1,3.1-4.218A8.509,8.509,0,0,1,67.1.559a8.286,8.286,0,0,1,4.89,2.2A6.588,6.588,0,0,1,74,6.249c.062.288.093.583.139.874m-11.695-1a1.4,1.4,0,1,0,1.382,1.424,1.414,1.414,0,0,0-1.382-1.424m3.749,0a1.4,1.4,0,1,0,1.383,1.423,1.414,1.414,0,0,0-1.383-1.423m5.132,1.428A1.4,1.4,0,1,0,69.9,8.934a1.414,1.414,0,0,0,1.424-1.381"
                      transform="translate(-11422 -11583)"
                      fill="#f5a100"
                    />
                  </g>
                </svg>
                <span class="fw-500 fs-12 ms-2"  >{{ $t("contact_admin") }}</span>
              </button>
            </div>
          </v-col>
          <v-col
            cols="12"
            sm="6"
          >
            <div class="bg-grey-lighten-3 border-gray-300 rounded px-4 py-3 d-flex lh-1-3">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="19.847"
                viewBox="0 0 20 19.847"
              >
                <g
                  id="Group_1226"
                  data-name="Group 1226"
                  transform="translate(-7 -11)"
                >
                  <path
                    id="Path_3097"
                    data-name="Path 3097"
                    d="M16.6,44.374a4.283,4.283,0,0,1-2-.723,17.011,17.011,0,0,1-1.733-1.1l-2.451.735a.445.445,0,0,1-.395-.071,9.261,9.261,0,0,1-2.986-3.853.444.444,0,0,1,.381-.614,10.4,10.4,0,0,0,3.718-.941,10.209,10.209,0,0,0,1.178-.647,11.321,11.321,0,0,1,1.654-.962,10.309,10.309,0,0,1,1.092-.4c.033-.009.089-.024.162-.045l.074-.023c.072-.022.138-.043.3-.084s.351-.088.6-.139a5.637,5.637,0,0,1,1.723-.14,3.7,3.7,0,0,1,.763.137c.33.1.625.219.911.335a8.959,8.959,0,0,0,.941.342,4.288,4.288,0,0,0,.845.153c.283.027.636.061.788.435a.886.886,0,0,1-.083.772,1.892,1.892,0,0,1-2.139.971c-.123-.019-.234-.04-.337-.064a4.139,4.139,0,0,0,.479.3,4.183,4.183,0,0,0,2.044.481h1.342a.444.444,0,0,1,.442.4,1.841,1.841,0,0,1-.18,1.047,2.5,2.5,0,0,1-2.672,1.066c-.933.726-1.875,1.408-2.8,2.029a2.986,2.986,0,0,1-1.555.607ZM12.95,41.62a.444.444,0,0,1,.265.088,16.105,16.105,0,0,0,1.813,1.166c1.155.636,1.473.619,1.626.611a2.129,2.129,0,0,0,1.107-.458c.959-.643,1.935-1.353,2.9-2.111a.442.442,0,0,1,.359-.086,1.779,1.779,0,0,0,1.916-.566.784.784,0,0,0,.059-.15h-.862a5.045,5.045,0,0,1-4.245-2.151,4.185,4.185,0,0,0-.571,0c-.08.006-.147.013-.191.018a.444.444,0,1,1-.1-.883c.053-.006.133-.015.232-.021a5.166,5.166,0,0,1,.931.021H18.2a3.4,3.4,0,0,1,.95.265,3.725,3.725,0,0,0,.927.269,1.336,1.336,0,0,0,.487.032,1.232,1.232,0,0,0,.678-.452,5.07,5.07,0,0,1-.955-.178,9.83,9.83,0,0,1-1.031-.373c-.278-.113-.541-.22-.831-.307a2.794,2.794,0,0,0-.581-.1,4.834,4.834,0,0,0-1.472.126c-.23.047-.416.092-.554.128s-.192.053-.253.072l-.085.026c-.085.025-.15.043-.176.05a9.7,9.7,0,0,0-.967.359,10.647,10.647,0,0,0-1.522.889,11.069,11.069,0,0,1-4.7,1.679,8.364,8.364,0,0,0,2.269,2.793l2.444-.733a.443.443,0,0,1,.128-.019Z"
                    transform="translate(0 -13.528)"
                    fill="#b8b8b8"
                  />
                  <path
                    id="Path_3098"
                    data-name="Path 3098"
                    d="M31,26.555H25.929a.444.444,0,0,1,0-.889h4.627V11.889H15.889V24.831a.444.444,0,1,1-.889,0V11.444A.444.444,0,0,1,15.444,11H31a.444.444,0,0,1,.444.444V26.111A.444.444,0,0,1,31,26.555Z"
                    transform="translate(-4.444)"
                    fill="#b8b8b8"
                  />
                  <path
                    id="Path_3099"
                    data-name="Path 3099"
                    d="M32.5,15.444H28.944A.444.444,0,0,1,28.5,15V11.444A.444.444,0,0,1,28.944,11H32.5a.444.444,0,0,1,.444.444V15A.444.444,0,0,1,32.5,15.444Zm-3.111-.889h2.667V11.889H29.389Z"
                    transform="translate(-11.944)"
                    fill="#b8b8b8"
                  />
                </g>
              </svg>
              <div class="ms-3">
                <div class="fw-700 mb-3 text-truncate">{{ $t("estimated_delivery_time") }}</div>
                <div
                  v-if=" Math.ceil( productDetails.express_delivery_time / 24 ) == Math.ceil( productDetails.standard_delivery_time / 24 )"
                  class="fs-12 opacity-50"
                >
                  {{ Math.ceil( productDetails.express_delivery_time / 24 )}}
                  {{ $t("days") }}
                </div>
                <div v-else>
                  {{ Math.ceil( productDetails.express_delivery_time / 24 ) }}
                  -
                  {{ Math.ceil( productDetails.standard_delivery_time / 24 ) }}
                  {{ $t("days") }}
                </div>
              </div>
            </div>
          </v-col>
        </v-row>

        <v-row v-if="!productDetails.is_digital">
            <v-col cols="3" lg="2">
                <span class="opacity-60 fs-12">{{ $t("quantity") }}</span>
            </v-col>
            <v-col cols="9" lg="10">
              <vue-number-input
                  v-model="cartQuantity"
                  :min="1"
                  :max="maxCartLimit"
                  :step="1"
                  inline controls
                  align="center"
                  size="small"
              ></vue-number-input>
            </v-col>
        </v-row>

        <v-row v-if=" !is_empty_obj(selectedVariation) && Number.isInteger(cartQuantity) && !productDetails.is_digital">
          <v-col
            cols="3"
            lg="2"
          >
            <span class="opacity-60 fs-12">{{ $t("total_price") }}</span>
          </v-col>
          <v-col
            cols="9"
            lg="10"
          >
            <span class="primary-text fs-24 fw-600">{{ format_price( selectedVariation.price * cartQuantity ) }}</span>
          </v-col>
        </v-row>
        <div class="my-5">
          <v-btn
            v-if="Number.isInteger(cartQuantity)"
            color="grey-darken-4 white-text"
            elevation="0"
            block
            @click="addCart"
          >{{ $t("add_to_cart") }}</v-btn>
        </div>
        <div
          class="my-5"
          v-if="isAffiliatedUser && getUserReferralCode && getAffiliateOption"
        >
          <v-btn
            color="grey-white-text"
            elevation="0"
            block
            @click="copyLink(getUserReferralCode)"
          >{{ $t("copy_the_promote_link") }}</v-btn>
        </div>
        <v-row>
          <v-col
            cols="3"
            lg="2"
          >
            <span class="opacity-60 fs-12">{{ $t("share") }}</span>
          </v-col>
          <v-col
            cols="9"
            lg="10"
          >
            <SocialShare :title="productDetails.name" />
          </v-col>
        </v-row>
      </template>
    </v-col>
  </v-row>
</template>


<script>
import { mapActions, mapGetters, mapMutations } from "vuex";
import ConversationDialog from "../product/ConversationDialog.vue";
import SocialShare from "../product/SocialShare.vue";
import ProductGallery from "../product/ProductGallery.vue";
export default {
  props: {
    isLoading: { type: Boolean, required: true, default: true },
    productDetails: { type: Object, required: true, default: {} },
  },
  data: () => ({
    cartQuantity: 1,
    chooseOptions: [],
    stock: 1,
    selectedVariation: {},
    minCartLimit: 1,
    maxCartLimit: Infinity,
    selectedVariantImage:null,
  }),
  watch: {
    productDetails: {
      immediate: true,
      handler(newVal, oldVal) {
        if (!this.is_empty_obj(newVal)) {
          this.cartQuantity = 1;
          this.stock = newVal.stock;
          this.maxCartLimit = newVal.max_qty > 0 ? newVal.max_qty : Infinity;
          this.minCartLimit = newVal.min_qty;
          this.selectedVariation =
            newVal.is_variant == 1 ? {} : newVal.variations[0];
          this.chooseOptions = [];
        }
      },
    },
  },
  components: {
    ProductGallery,
    SocialShare,
    ConversationDialog,
  },
  computed: {
    ...mapGetters("app", ["generalSettings"]),
    ...mapGetters("auth", ["isAuthenticated", "currentUser"]),
    ...mapGetters("wishlist", ["isThisWishlisted"]),
    ...mapGetters("cart", ["isThisInCart", "findCartItemByVariationId"]),
    ...mapGetters("affiliate", [
      "getUserReferralCode",
      "isAffiliatedUser",
      "getAffiliateOption",
    ]),
    discount() {
      return this.discount_percent(
        this.productDetails.base_price,
        this.productDetails.base_discounted_price
      );
    },
  },
  methods: {
    ...mapActions("affiliate", ["fetchAffiliatedUser"]),
    ...mapActions("wishlist", ["addNewWishlist", "removeFromWishlist"]),
    ...mapActions("cart", ["addToCart", "updateQuantity"]),
    ...mapActions("auth", ["showConversationDialog"]),
    ...mapMutations("auth", ["updateChatWindow","showAddToCartDialog"]),

    addCart() {
      if (this.isAuthenticated && this.currentUser.user_type != "customer") {
        this.snack({
          message: this.$i18n.t(
            "please_login_as_a_customer_first_to_add_product_to_the_cart"
          ),
          color: "red",
        });
        return;
      }
      if (this.productDetails.is_variant == 1) {
        // for variant product

        let chooseOptions = this.chooseOptions.filter((el) => el != "");
        if (
          this.productDetails.variation_options.length > chooseOptions.length
        ) {
          // if all options is not selected

          this.snack({
            message: this.$i18n.t("please_select_all_options"),
            color: "red",
          });
          return;
        }
      }

      if (!this.stock) {
        // selected variation stock check

        this.snack({
          message: this.$i18n.t("this_product_is_out_of_stock"),
          color: "red",
        });
        return;
      }

      let minMaxCheck = this.checkMinMaxLimit(this.selectedVariation.id);
      if (!minMaxCheck.success) {
        // selected variation min max limit check

        let message =
          minMaxCheck.type == "min_limit"
            ? `${this.$i18n.t("you_need_to_purchase_minimum_quantity")} ${
                this.minCartLimit
              }.`
            : `${this.$i18n.t("you_can_purchase_maximum_quantity")} ${
                this.maxCartLimit
              }.`;

        this.snack({
          message: message,
          color: "red",
        });
        return;
      }

      this.addToCart({
        variation_id: this.selectedVariation.id,
        qty: this.cartQuantity,
      });
      this.snack({
        message: this.$i18n.t("product_added_to_cart"),
        color: "green",
      });
      this.showAddToCartDialog({status:false,slug:null})
    },
    optionChosen() {
     
      let chooseOptions = this.chooseOptions.filter((el) => el != "");
      if (
        this.productDetails.variation_options.length === chooseOptions.length
      ) {
        let filteredVariations = this.productDetails.variations;

        chooseOptions.forEach((chosenOption) => {
          filteredVariations = filteredVariations.filter((variation) => {
            return variation.code.includes(chosenOption);
          });
        });

        if (filteredVariations.length == 1) {
          this.stock = filteredVariations[0].stock;
          this.selectedVariation = filteredVariations[0];

        } else {
          this.selectedVariation = {};
        }
      }
    },
    checkMinMaxLimit(variation_id) {
      if (this.isThisInCart(variation_id)) {
        // if this is already in cart check

        if (
          this.findCartItemByVariationId(variation_id).qty + this.cartQuantity <
          this.minCartLimit
        ) {
          // minimum purchase quantity check

          return { success: false, type: "min_limit" };
        } else if (
          this.findCartItemByVariationId(variation_id).qty + this.cartQuantity >
          this.maxCartLimit
        ) {
          // maximum purchase quantity check

          return { success: false, type: "max_limit" };
        }

        return { success: true, type: "" };
      } else {
        if (this.cartQuantity < this.minCartLimit) {
          // minimum purchase quantity check

          return { success: false, type: "min_limit" };
        } else if (this.cartQuantity > this.maxCartLimit) {
          // maximum purchase quantity check

          return { success: false, type: "max_limit" };
        }

        return { success: true, type: "" };
      }
    },
    copyLink(referrelCode) {
      const textArea = document.createElement("textarea");
      textArea.value =
        window.location.href + "?product_referral_code=" + referrelCode;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand("copy");
      document.body.removeChild(textArea);
      this.snack({
        message: this.$i18n.t("link_copied_successfully!"),
        color: "green",
      });
    },
  },
  created() {
    if (this.isAuthenticated) {
      this.fetchAffiliatedUser();
    }
  },
};
</script>