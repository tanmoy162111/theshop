<template>
  <v-container class="pt-7">
    <v-row align="start">
      <v-col
        lg="10"
        cols="12"
        class="main-bar"
      >

        <add-to-cart
          class="mb-10"
          :is-loading="detailsLoading"
          :product-details="productDetails"
        />

        <div
          cols="12"
          class="mb-6"
        >
          <v-expansion-panels
            v-model="panel"
            class="product-details-expansion"
            flat
            multiple
          >
            <v-expansion-panel class="mb-3">
              <v-expansion-panel-title collapse-icon="las la-arrow-circle-right" expand-icon="las la-arrow-circle-right">
                <div class="d-flex align-center">
                  <svg
                    id="_004-product-description"
                    data-name="004-product-description"
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                  >
                    <g
                      id="Group_1264"
                      data-name="Group 1264"
                    >
                      <path
                        id="Path_3166"
                        data-name="Path 3166"
                        d="M18.75,5H1.25A1.252,1.252,0,0,1,0,3.75V1.25A1.252,1.252,0,0,1,1.25,0h17.5A1.252,1.252,0,0,1,20,1.25v2.5A1.252,1.252,0,0,1,18.75,5ZM1.25.833a.417.417,0,0,0-.417.417v2.5a.417.417,0,0,0,.417.417h17.5a.417.417,0,0,0,.417-.417V1.25A.417.417,0,0,0,18.75.833Z"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1265"
                      data-name="Group 1265"
                      transform="translate(15 7.5)"
                    >
                      <path
                        id="Path_3167"
                        data-name="Path 3167"
                        d="M20.5,14A2.5,2.5,0,1,1,23,11.5,2.5,2.5,0,0,1,20.5,14Zm0-4.167A1.667,1.667,0,1,0,22.167,11.5,1.668,1.668,0,0,0,20.5,9.833Z"
                        transform="translate(-18 -9)"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1266"
                      data-name="Group 1266"
                      transform="translate(15 15)"
                    >
                      <path
                        id="Path_3168"
                        data-name="Path 3168"
                        d="M20.5,23A2.5,2.5,0,1,1,23,20.5,2.5,2.5,0,0,1,20.5,23Zm0-4.167A1.667,1.667,0,1,0,22.167,20.5,1.668,1.668,0,0,0,20.5,18.833Z"
                        transform="translate(-18 -18)"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1267"
                      data-name="Group 1267"
                      transform="translate(0 8.333)"
                    >
                      <path
                        id="Path_3169"
                        data-name="Path 3169"
                        d="M12.917,10.833H.417a.417.417,0,1,1,0-.833h12.5a.417.417,0,1,1,0,.833Z"
                        transform="translate(0 -10)"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1268"
                      data-name="Group 1268"
                      transform="translate(0 10.833)"
                    >
                      <path
                        id="Path_3170"
                        data-name="Path 3170"
                        d="M12.917,13.833H.417a.417.417,0,1,1,0-.833h12.5a.417.417,0,1,1,0,.833Z"
                        transform="translate(0 -13)"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1269"
                      data-name="Group 1269"
                      transform="translate(0 15.833)"
                    >
                      <path
                        id="Path_3171"
                        data-name="Path 3171"
                        d="M12.917,19.833H.417a.417.417,0,1,1,0-.833h12.5a.417.417,0,1,1,0,.833Z"
                        transform="translate(0 -19)"
                        fill="#f5a100"
                      />
                    </g>
                    <g
                      id="Group_1270"
                      data-name="Group 1270"
                      transform="translate(0 18.333)"
                    >
                      <path
                        id="Path_3172"
                        data-name="Path 3172"
                        d="M12.917,22.833H.417a.417.417,0,0,1,0-.833h12.5a.417.417,0,1,1,0,.833Z"
                        transform="translate(0 -22)"
                        fill="#f5a100"
                      />
                    </g>
                  </svg>
                  <span class="fs-16 ms-5">{{ $t("description") }}</span>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <div v-html="productDetails.description"></div>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel class="mb-3">
              <v-expansion-panel-title collapse-icon="las la-arrow-circle-right" expand-icon="las la-arrow-circle-right">
                <div class="d-flex align-center">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                  >
                    <g
                      id="Group_1286"
                      data-name="Group 1286"
                      transform="translate(-567 -1260.5)"
                    >
                      <g
                        id="Group_1272"
                        data-name="Group 1272"
                        transform="translate(567 1260.5)"
                      >
                        <g
                          id="Group_1271"
                          data-name="Group 1271"
                          transform="translate(0 0)"
                        >
                          <path
                            id="Path_3173"
                            data-name="Path 3173"
                            d="M6.65,2.545a.417.417,0,0,0-.287-.273L4.655,1.805,3.684.325a.433.433,0,0,0-.7,0l-.971,1.48L.307,2.271a.417.417,0,0,0-.216.663L1.2,4.316,1.115,6.085a.417.417,0,0,0,.564.41l1.656-.627,1.656.627a.417.417,0,0,0,.564-.41L5.471,4.316,6.579,2.935A.417.417,0,0,0,6.65,2.545ZM4.722,3.917a.418.418,0,0,0-.091.281L4.692,5.49,3.483,5.032a.416.416,0,0,0-.3,0L1.978,5.49,2.04,4.2a.417.417,0,0,0-.091-.281L1.14,2.909l1.248-.341a.417.417,0,0,0,.239-.174l.709-1.081.709,1.081a.417.417,0,0,0,.239.174l1.247.341Z"
                            transform="translate(0 -0.148)"
                            fill="#fff"
                          />
                        </g>
                      </g>
                      <g
                        id="Group_1285"
                        data-name="Group 1285"
                        transform="translate(567 1274.127)"
                      >
                        <g
                          id="Group_1271-2"
                          data-name="Group 1271"
                          transform="translate(0 0)"
                        >
                          <path
                            id="Path_3173-2"
                            data-name="Path 3173"
                            d="M6.65,2.545a.417.417,0,0,0-.287-.273L4.655,1.805,3.684.325a.433.433,0,0,0-.7,0l-.971,1.48L.307,2.271a.417.417,0,0,0-.216.663L1.2,4.316,1.115,6.085a.417.417,0,0,0,.564.41l1.656-.627,1.656.627a.417.417,0,0,0,.564-.41L5.471,4.316,6.579,2.935A.417.417,0,0,0,6.65,2.545ZM4.722,3.917a.418.418,0,0,0-.091.281L4.692,5.49,3.483,5.032a.416.416,0,0,0-.3,0L1.978,5.49,2.04,4.2a.417.417,0,0,0-.091-.281L1.14,2.909l1.248-.341a.417.417,0,0,0,.239-.174l.709-1.081.709,1.081a.417.417,0,0,0,.239.174l1.247.341Z"
                            transform="translate(0 -0.148)"
                            fill="#fff"
                          />
                        </g>
                      </g>
                      <g
                        id="Group_1278"
                        data-name="Group 1278"
                        transform="translate(567 1267.314)"
                      >
                        <g
                          id="Group_1277"
                          data-name="Group 1277"
                        >
                          <path
                            id="Path_3174"
                            data-name="Path 3174"
                            d="M6.65,169.895a.418.418,0,0,0-.287-.273l-1.708-.466-.971-1.48a.433.433,0,0,0-.7,0l-.971,1.48-1.708.466a.417.417,0,0,0-.216.663L1.2,171.666l-.084,1.769a.417.417,0,0,0,.564.41l1.656-.627,1.656.627a.417.417,0,0,0,.564-.41l-.084-1.769,1.108-1.381A.417.417,0,0,0,6.65,169.895Zm-1.928,1.372a.418.418,0,0,0-.091.281l.062,1.292-1.209-.458a.416.416,0,0,0-.3,0l-1.209.458.062-1.291a.417.417,0,0,0-.091-.281l-.809-1.009,1.248-.34a.417.417,0,0,0,.239-.174l.709-1.081.709,1.081a.417.417,0,0,0,.239.174l1.247.34Z"
                            transform="translate(0 -167.498)"
                            fill="#fff"
                          />
                        </g>
                      </g>
                      <rect
                        id="Rectangle_1289"
                        data-name="Rectangle 1289"
                        width="6.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1262.583)"
                        fill="#fff"
                      />
                      <rect
                        id="Rectangle_1293"
                        data-name="Rectangle 1293"
                        width="6.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1276.21)"
                        fill="#fff"
                      />
                      <rect
                        id="Rectangle_1291"
                        data-name="Rectangle 1291"
                        width="6.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1269.25)"
                        fill="#fff"
                      />
                      <rect
                        id="Rectangle_1290"
                        data-name="Rectangle 1290"
                        width="11.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1265.083)"
                        fill="#fff"
                      />
                      <rect
                        id="Rectangle_1294"
                        data-name="Rectangle 1294"
                        width="11.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1278.71)"
                        fill="#fff"
                      />
                      <rect
                        id="Rectangle_1292"
                        data-name="Rectangle 1292"
                        width="11.667"
                        height="0.833"
                        rx="0.417"
                        transform="translate(575.333 1271.75)"
                        fill="#fff"
                      />
                    </g>
                  </svg>
                  <span class="fs-16 ms-5">{{ $t("rating__reviews") }}</span>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text class="">
                <ProductReviews
                  :id="productDetails.id"
                  :is-loading="detailsLoading"
                  :review-summary="reviewSummary"
                />
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </div>
        <div
          v-if="boughtTogetherProducts.length > 0"
          class="mb-5"
        >
          <h2 class="mb-3 fs-21 opacity-80">
            {{ $t("frequently_bought_together") }}
          </h2>
          <swiper
                :slides-per-view=carouselOption.slidesPerView
                :space-between=carouselOption.spaceBetween
                :breakpoints= carouselOption.breakpoints
          >
            <swiper-slide
              v-for="(product, i) in boughtTogetherProducts"
              :key="i"
              class=""
            >
              <product-box
                :product-details="product"
                :is-loading="togetherLoading"
              />
            </swiper-slide>
          </swiper>
        </div>
        <div class="mb-5">
          <h2 class="mb-3 fs-21 opacity-80">
            {{ $t("more_items_to_explore") }}
          </h2>
          <swiper
                :slides-per-view=carouselOption.slidesPerView
                :space-between=carouselOption.spaceBetween
                :breakpoints= carouselOption.breakpoints
          >
            <swiper-slide
              v-for="(product, i) in moreProducts"
              :key="i"
              class=""
            >
              <product-box
                :product-details="product"
                :is-loading="moreLoading"
              />
            </swiper-slide>
          </swiper>
        </div>
      </v-col>
      <v-col
        lg="2"
        cols="12"
        class="sticky-top right-bar"
      >
        <v-row>
          <v-col
            lg="12"
            md="3"
            sm="4"
            cols="12"
          >
            <template v-if="is_addon_activated('multi_vendor')">
              <template v-if="detailsLoading">
                <v-skeleton-loader
                  type="image"
                  height="158"
                  class="mb-3"
                />
              </template>
              <div
                v-else
                class="border rounded px-3 py-2 mb-3 border-gray-300 bg-grey-lighten-5"
              >
                <div class="fw-700 fs-12 mb-2">{{ $t('shop') }}</div>
                <div class="d-flex mb-2">
                  <img
                    :src="productDetails.shop.logo"
                    :alt="productDetails.shop.name"
                    class="flex-shrink-0 border rounded-circle shadow-xl size-50px mb-2"
                  >
                  <div class="fs-13 fw-500 ms-3 mt-2 text-truncate-2 minw-0">{{ productDetails.shop.name }}
                    <span
                      class="ml-2"
                      v-if="productDetails.shop.isVarified"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="17.5"
                        height="17.5"
                        viewBox="0 0 17.5 17.5"
                      >
                        <g
                          id="Group_25616"
                          data-name="Group 25616"
                          transform="translate(-537.249 -1042.75)"
                        >
                          <path
                            id="Union_5"
                            data-name="Union 5"
                            d="M0,8.75A8.75,8.75,0,1,1,8.75,17.5,8.75,8.75,0,0,1,0,8.75Zm.876,0A7.875,7.875,0,1,0,8.75.875,7.883,7.883,0,0,0,.876,8.75Zm.875,0a7,7,0,1,1,7,7A7.008,7.008,0,0,1,1.751,8.751Zm3.73-.907a.789.789,0,0,0,0,1.115l2.23,2.23a.788.788,0,0,0,1.115,0l3.717-3.717a.789.789,0,0,0,0-1.115.788.788,0,0,0-1.115,0l-3.16,3.16L6.6,7.844a.788.788,0,0,0-1.115,0Z"
                            transform="translate(537.249 1042.75)"
                            fill="#3490f3"
                          />
                        </g>
                      </svg>
                    </span>

                  </div>
                </div>
                <div class="d-flex align-center fs-12 mb-2">
                  <span class="">{{ productDetails.shop.rating.toFixed(2) }}</span>
                  <v-rating
                    class="lh-1-5 mx-1"
                    background-color=""
                    empty-icon="las la-star"
                    full-icon="las la-star active"
                    half-icon="las la-star half"
                    hover
                    half-increments
                    readonly
                    size="x-small"
                    length="5"
                    :model-value="productDetails.shop.rating"
                  >
                  </v-rating>
                  <span class="opacity-50"> ({{ productDetails.shop.review_count }} {{ $t("ratings") }}) </span>
                </div>
                <router-link
                  :to="{ name: 'ShopDetails', params: {slug: productDetails.shop.slug}}"
                  class="text-primary fw-500 fs-11"
                >
                  {{ $t('visit_store') }}
                </router-link>
              </div>
            </template>
            <template v-if="detailsLoading">
              <v-skeleton-loader
                type="image"
                height="200"
                class=""
              />
            </template>
            <template v-else>
              <banner
                :loading="false"
                :banner="$store.getters['app/banners'].product_page"
                class=""
              />
            </template>

            <div
              v-if="productDetails.has_warranty == 1"
              class="bg-soft-primary border border-primary d-flex rounded px-4 py-3 mt-3"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16.667"
                height="20"
                viewBox="0 0 16.667 20"
              >
                <g
                  id="Policy"
                  transform="translate(-8.752 -4.644)"
                >
                  <path
                    id="Path_3102"
                    data-name="Path 3102"
                    d="M17.085,24.644a.7.7,0,0,1-.211-.033,17.254,17.254,0,0,1-6.395-4.9C8.608,17.09,8.647,11.575,8.841,7.654a.368.368,0,0,1,.133-.267.353.353,0,0,1,.28-.077,9.543,9.543,0,0,0,7.578-2.558.354.354,0,0,1,.509,0,9.507,9.507,0,0,0,7.578,2.558.353.353,0,0,1,.28.077.368.368,0,0,1,.133.267c.194,3.921.233,9.436-1.638,12.06a17.431,17.431,0,0,1-6.395,4.9A.7.7,0,0,1,17.085,24.644ZM9.558,8.063c-.172,3.779-.161,8.855,1.516,11.2A16.654,16.654,0,0,0,17.1,23.9a16.743,16.743,0,0,0,6.029-4.634c1.66-2.339,1.67-7.415,1.5-11.2A9.979,9.979,0,0,1,17.1,5.505,10,10,0,0,1,9.558,8.063Z"
                    fill="#b8b8b8"
                  />
                  <path
                    id="Path_3103"
                    data-name="Path 3103"
                    d="M25.523,30.137a.358.358,0,0,1-.258-.111L23.387,28.1a.359.359,0,1,1,.513-.5l1.613,1.652,3.405-3.724a.359.359,0,0,1,.531.484l-3.66,4a.376.376,0,0,1-.258.118Z"
                    transform="translate(-9.323 -13.133)"
                    fill="#b8b8b8"
                  />
                </g>
              </svg>
              <span class="ms-2">
                <div class="fw-700">
                  {{ $t("warranty_available") }}
                </div>
                <router-link
                  :to="{ name: 'CustomPage', params: { pageSlug: 'warranty-policy' }, }"
                  class="text-decoration-underline text-grey fs-12"
                >
                  * {{ $t("view_warranty_policy") }}
                </router-link>
              </span>
            </div>
            <div
              v-if="productDetails.for_pickup == 1"
              class="bg-grey-lighten-4 border border-gray-300  d-flex rounded px-4 py-3 mt-3"
            >
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
              <defs>
                <clipPath id="clip-Pick_up_Available">
                  <rect width="20" height="20"/>
                </clipPath>
              </defs>
              <g id="Pick_up_Available" data-name="Pick up Available" clip-path="url(#clip-Pick_up_Available)">
                <rect width="20" height="20" fill="#fff"/>
                <g id="Group_30211" data-name="Group 30211" transform="translate(0.951 0.979)">
                  <path id="Path_29462" data-name="Path 29462" d="M2.945,16.9c0,.3,0,.542,0,.787,0,.217-.1.326-.287.329s-.3-.114-.3-.338c.006-.474.019-.948.028-1.422,0-.067,0-.134,0-.2-.195-.027-.378-.051-.56-.079a.418.418,0,0,1-.337-.251c-.467-.949-.953-1.889-1.406-2.845a.8.8,0,0,1,.007-.548,4.009,4.009,0,0,1,.44-.927,10.112,10.112,0,0,1,.9-1.118c.356-.391.744-.752,1.115-1.123-.195-.363-.189-.4.1-.7l.915-.913c-.178-.353-.341-.681-.51-1.007a.183.183,0,0,0-.09-.076A1.247,1.247,0,0,1,2.333,4.5s0-.012.013-.039a1.1,1.1,0,0,1-.257-.875c.012-.48-.023-.964.018-1.441A2.034,2.034,0,0,1,3.54.349,5.116,5.116,0,0,1,6.428.106a2.245,2.245,0,0,1,1.8,1.757c.017.066.03.133.05.218.359,0,.721,0,1.082,0a1.523,1.523,0,0,1,1.419.975.988.988,0,0,1-.353,1.227,2.45,2.45,0,0,1-1.2.421c-.2.024-.4.033-.573.047-.081.551-.135,1.1-.246,1.629A2.774,2.774,0,0,1,7.65,7.82a6.375,6.375,0,0,0,.783.758,2.524,2.524,0,0,0,.753.224V7.108c0-.579.012-.591.585-.591h7.85c.36,0,.4.042.4.4v7.744c0,.361-.069.43-.431.43H15.678a1.258,1.258,0,0,1-1.4,1.178c-.764.014-1.527-.01-2.291.007-.446.01-.891.069-1.354.106V17.3c0,.633-.045.753-.29.755s-.3-.122-.3-.766v-.709c-.106.033-.2.06-.287.089-1.2.394-2.4.8-3.607,1.18A3.206,3.206,0,0,1,4.7,18a2.78,2.78,0,0,1-1.613-.936ZM9.791,7.118v6.787c.079-.022.144-.042.21-.056a6.559,6.559,0,0,1,1.062-.221c.725-.035,1.452-.018,2.179-.009a.848.848,0,0,1,.827.71c.011.054.023.107.036.164h3.348V7.122H15.1v.159c0,.623,0,1.245,0,1.867,0,.282-.2.394-.444.267-.3-.152-.591-.3-.893-.442a.315.315,0,0,0-.233.006c-.3.14-.6.294-.894.441-.282.141-.486.015-.491-.3,0-.271,0-.542,0-.813V7.118ZM3,9.561c-.27.273-.532.523-.78.79-.375.405-.767.8-1.1,1.241a5.034,5.034,0,0,0-.483.945.279.279,0,0,0,.012.208q.63,1.305,1.273,2.6a.241.241,0,0,0,.144.115,1.7,1.7,0,0,0,1.962-1.013.322.322,0,0,0-.023-.34c-.247-.389-.486-.784-.721-1.181a.3.3,0,0,1,.2-.48c.168-.026.258.078.334.208.367.634.733,1.268,1.106,1.9a1.118,1.118,0,0,0,1.351.512c.905-.3,1.808-.6,2.713-.9a.17.17,0,0,0,.135-.2c-.007-.172,0-.345,0-.517,0-1.282,0-2.562,0-3.844,0-.154-.044-.22-.194-.242a2.851,2.851,0,0,1-.412-.1c-.1-.032-.153,0-.2.1-.116.258-.243.511-.368.764-.146.293-.394.317-.587.058-.14-.188-.276-.378-.415-.567-.03-.04-.065-.076-.11-.128-.134.337-.245.65-.384.951a.323.323,0,0,1-.228.169.475.475,0,0,1-.274-.133.513.513,0,0,1-.095-.2c-.112-.27-.223-.54-.336-.815a.315.315,0,0,0-.05.036l-.755.958c-.2.25-.326.264-.566.063Zm.02,6.452c.153.2.277.384.419.552a2.692,2.692,0,0,0,1,.771,2.093,2.093,0,0,0,1.483-.014c1.215-.374,2.423-.77,3.627-1.178a7.52,7.52,0,0,1,2.673-.463c.652.021,1.305.016,1.957,0a2.225,2.225,0,0,0,.656-.149.411.411,0,0,0,.249-.405h-.195l-2.123-.031c-.213,0-.328-.107-.33-.291s.113-.3.319-.3c.242,0,.485,0,.733,0-.061-.236-.136-.3-.357-.3-.585,0-1.17-.006-1.755,0a2.8,2.8,0,0,0-.62.065c-.47.119-.938.25-1.4.4-.952.31-1.9.644-2.849.953a1.723,1.723,0,0,1-1.893-.569c-.033-.041-.069-.079-.113-.129A2.244,2.244,0,0,1,3.024,16.013ZM7.872,3.5,7.826,3.5c-.051.068-.1.137-.153.2a7.128,7.128,0,0,1-.459.552,1.592,1.592,0,0,1-1.208.495c-.972-.017-1.944-.008-2.916,0a.385.385,0,0,0-.275.1.768.768,0,0,0-.086.738.507.507,0,0,0,.489.334.423.423,0,0,1,.392.326,3.737,3.737,0,0,0,.381.793A2.047,2.047,0,0,0,5.9,7.984a1.981,1.981,0,0,0,1.81-1.326,5.072,5.072,0,0,0,.276-2.342C7.96,4.044,7.91,3.774,7.872,3.5ZM4.5.7a2.979,2.979,0,0,0-1.171.431,1.269,1.269,0,0,0-.621.978c-.035.6-.024,1.206-.024,1.809a.229.229,0,0,0,.224.23A1.367,1.367,0,0,0,4.3,3.663c.091-.124.184-.246.275-.37a2.547,2.547,0,0,1,2.3-1.214c.26.018.522,0,.845,0a4.877,4.877,0,0,0-.358-.708A1.623,1.623,0,0,0,5.814.662,1.433,1.433,0,0,0,4.568,1.748a3.594,3.594,0,0,0-.111.615c-.025.195-.144.319-.32.308s-.272-.127-.272-.329A2.365,2.365,0,0,1,3.882,2.1,2.518,2.518,0,0,1,4.5.7ZM7.786,2.676c-.442,0-.89,0-1.338,0a.874.874,0,0,0-.3.074,3.371,3.371,0,0,0-1.452,1.4c.466,0,.909-.009,1.351,0a.888.888,0,0,0,.612-.21A2.79,2.79,0,0,0,7,3.612C7.264,3.307,7.521,2.994,7.786,2.676ZM12.741,8.7c.248-.122.477-.227.7-.347a.344.344,0,0,1,.363,0c.225.122.457.229.692.346V7.118H12.741ZM8.587,4.165a5.4,5.4,0,0,0,.727-.077,3.6,3.6,0,0,0,.75-.278.336.336,0,0,0,.181-.437,1,1,0,0,0-.682-.668,1.461,1.461,0,0,0-1.143.289.143.143,0,0,0-.038.123C8.444,3.455,8.513,3.791,8.587,4.165ZM4.4,9.947l.8-1L3.936,8.113l-.8.8ZM7.328,8.313c-.056.158-.1.277-.139.4-.014.048-.03.117-.006.152.144.211.3.415.466.641.093-.178.177-.327.248-.482a.154.154,0,0,0-.018-.139C7.708,8.7,7.531,8.521,7.328,8.313ZM6.241,9.542l.4-1.079L5.8,8.6Z" transform="translate(0 0)" fill="#b2b2b2"/>
                  <path id="Path_29463" data-name="Path 29463" d="M12.693,14.366c-.19,0-.381,0-.571,0a.281.281,0,0,1-.309-.291.277.277,0,0,1,.3-.3q.581-.007,1.161,0a.279.279,0,0,1,.31.29c0,.183-.114.3-.319.3C13.074,14.37,12.883,14.366,12.693,14.366Z" transform="translate(-1.148 -1.342)" fill="#b2b2b2"/>
                  <path id="Path_29464" data-name="Path 29464" d="M12.416,12.794c.1,0,.2-.005.294,0a.277.277,0,0,1,.282.3.272.272,0,0,1-.273.288c-.214.009-.43.01-.644,0a.271.271,0,0,1-.264-.3.275.275,0,0,1,.272-.289C12.195,12.787,12.306,12.794,12.416,12.794Z" transform="translate(-1.148 -1.247)" fill="#b2b2b2"/>
                </g>
              </g>
            </svg>

              <span class="ms-2">
                <div class="fw-700">
                  {{ $t("pickup_available") }}
                </div>
                <router-link
                  :to="{ name: 'CustomPage', params: { pageSlug: 'pickup-policy' }, }"
                  class="text-decoration-underline text-grey fs-12 text-primary"
                >
                  * {{ $t("view_pickup_policy") }}
                </router-link>
              </span>
            </div>
          </v-col>
          <v-col
            lg="12"
            md="9"
            sm="8"
            cols="12"
          >
            <div class="mb-4">
              <div class="mb-3">{{ $t("related_products") }}</div>
              <v-row class="row-cols-2 row-cols-md-3 row-cols-lg-1 gutters-10">
                <v-col
                  v-for="(product, i) in relatedProducts"
                  :key="i"
                  class="py-2"
                >
                  <product-box
                    :product-details="product"
                    :is-loading="relatedLoading"
                    box-style="two"
                  />
                </v-col>
              </v-row>
            </div>
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { mapActions } from "vuex";
import AddToCart from "../components/product/AddToCart.vue";
import ProductReviews from "../components/product/ProductReviews.vue";
// Import Swiper Vue.js components
import { Swiper, SwiperSlide } from "swiper/vue";
import { useHead } from "@unhead/vue";
export default {

  data: () => ({
    metaTitle: "",
    metaDescription: "",
    detailsLoading: true,
    productDetails: {},
    reviewSummary: { average: 0 },
    relatedLoading: true,
    relatedProducts: [{}, {}, {}, {}, {}],
    togetherLoading: true,
    boughtTogetherProducts: [{}, {}, {}, {}, {}],
    moreLoading: true,
    moreProducts: [{}, {}, {}, {}, {}],
    panel: [0, 1],
    carouselOption: {
      slidesPerView: 5,
      spaceBetween: 20,
      breakpoints: {
        0: {
          slidesPerView: 2,
          spaceBetween: 12,
        },
        // when window width is >= 320px
        599: {
          slidesPerView: 3,
          spaceBetween: 16,
        },
        // when window width is >= 480px
        960: {
          slidesPerView: 4,
          spaceBetween: 20,
        },
        // when window width is >= 640px
        1264: {
          slidesPerView: 4,
          spaceBetween: 20,
        },
        1904: {
          slidesPerView: 5,
          spaceBetween: 20,
        },
      },
    },
  }),
  components: {
    ProductReviews,
    AddToCart,
    Swiper,
    SwiperSlide,
  },
  computed: {},
  watch:{
    metaTitle(newTitle){
      this.updateHead(newTitle, this.metaDescription);
    },
    metaDescription(newDescription){
      this.updateHead(this.metaTitle, newDescription);
    }
  },
  methods: {
    ...mapActions("recentlyViewed", ["addNewRecentlyViewedProduct"]),
    async getDetails() {
      const res = await this.call_api(
        "get",
        `product/details/${this.$route.params.slug}`
      );
      if (res.data.success) {
        this.metaTitle = res.data.data.metaTitle;
        this.metaDescription = res.data.data.description.replace(/<[^>]*>?/gm, '');
        this.productDetails = res.data.data;
        this.reviewSummary = this.productDetails.review_summary;

        this.getRelatedProducts(this.productDetails.id);
        this.getBoughtTogetherProducts(this.productDetails.id);
        this.getMoreProducts(this.productDetails.id);
        this.addNewRecentlyViewedProduct(this.productDetails.id);
      } else {
        this.snack({
          message: res.data.message,
          color: "red",
        });
        this.$router.push({ name: "404" });
      }
      this.detailsLoading = false;
    },
    async getRelatedProducts(id) {
      const res = await this.call_api("get", `product/related/${id}`);
      if (res.data.success) {
        this.relatedProducts = res.data.data;
        this.relatedLoading = false;
      }
    },
    async getBoughtTogetherProducts(id) {
      const res = await this.call_api("get", `product/bought-together/${id}`);
      if (res.data.success) {
        this.boughtTogetherProducts = res.data.data;
        this.togetherLoading = false;
      }
    },
    async getMoreProducts(id) {
      const res = await this.call_api("get", `product/random/10/${id}`);
      if (res.data.success) {
        this.moreProducts = res.data.data;
        this.moreLoading = false;
      }
    },
    async productReferralCode(product_referral_code) {
      const res = await this.call_api("post", "product-refferal-code", {
        product_referral_code: product_referral_code,
        slug: this.$route.params.slug,
      });
    },
    updateHead(title,description) {
      useHead({
        title: title,
        meta: [
          { name: 'description', content: description }
        ]
      });
    }
  },
  async created() {
    this.getDetails();
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const product_referral_code = urlParams.get("product_referral_code");
    if (product_referral_code != null) {
      this.productReferralCode(product_referral_code);
    }
  },
};
</script>
<style scoped>
@media (max-width: 1263px) {
}
@media (min-width: 1264px) {
  .main-bar {
    max-width: calc(100% - 224px);
  }
  .right-bar {
    width: 224px;
    max-width: 224px;
  }
}
</style>