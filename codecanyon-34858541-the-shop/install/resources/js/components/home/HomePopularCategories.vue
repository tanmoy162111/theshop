<template>
  <div class="mb-5">
    <v-container class="py-0 pe-0 pe-md-3 ps-3">
      <div class="d-flex justify-space-between align-center mb-4 pe-3 pe-md-0">
        <h2 class="">{{ $t('popular_categories') }}</h2>
        <router-link
          :to="{ name: 'AllCategories' }"
          class="py-2 primary-text lh-1 d-inline-block"
        >
          {{ $t('view_all') }}
          <i class="las la-angle-right"></i>
        </router-link>
      </div>
      <div v-if="loading">
        <swiper
          class=""
          :options="carouselOption"
        >
          <swiper-slide
            v-for="(i) in 8"
            :key="i"
            class=""
          >
            <v-skeleton-loader
              type="image"
              height="186"
            ></v-skeleton-loader>
          </swiper-slide>
        </swiper>
      </div>
      <div v-else>
        <swiper
          :options="carouselOption"
          :slides-per-view=carouselOption.slidesPerView
          :space-between=carouselOption.spaceBetween
          :breakpoints= carouselOption.breakpoints
        >
          <swiper-slide
            v-for="(category, i) in categories"
            :key="i"
            class=""
          >
            <router-link
              class="hp-cat-tile text-reset d-block text-center"
              :to="{ name: 'Category', params: {categorySlug: category.slug}}"
            >
              <div class="hp-cat-img">
                <img
                  :src="category.banner"
                  :alt="category.name"
                  @error="imageFallback($event)"
                >
              </div>
              <div class="hp-cat-name d-none d-md-block">{{ category.name }}</div>
            </router-link>
          </swiper-slide>
        </swiper>
      </div>
    </v-container>
  </div>
</template>

<script>
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
  components: {
    Swiper,
    SwiperSlide,
  },

  data: () => ({
    loading: true,
    categories: [],
    carouselOption: {
      slidesPerView: 8,
      spaceBetween: 20,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
      breakpoints: {
        0: {
          slidesPerView: 4.5,
          spaceBetween: 12,
        },
        // when window width is >= 320px
        599: {
          slidesPerView: 5,
          spaceBetween: 16,
        },
        // when window width is >= 480px
        960: {
          slidesPerView: 6,
          spaceBetween: 20,
        },
        // when window width is >= 640px
        1264: {
          slidesPerView: 7,
          spaceBetween: 20,
        },
        1904: {
          slidesPerView: 8,
          spaceBetween: 20,
        },
      },
    },
  }),
  async created() {
    const res = await this.call_api("get", "setting/home/popular_categories");
    if (res.data.success) {
      this.categories = res.data.data.data;
      this.loading = false;
    }
  },
};
</script>
<style scoped>
h2 {
  font-size: 16px;
}
@media (min-width: 960px) {
  h2 {
    font-size: 24px;
  }
}
.hp-cat-tile {
  border-radius: var(--hp-radius-card); background: var(--hp-surface-muted);
  padding: 12px; transition: transform .2s ease-out, box-shadow .2s ease-out;
}
.hp-cat-tile:hover { transform: translateY(-3px); box-shadow: var(--hp-shadow-md); }
.hp-cat-img { aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: #fff; }
.hp-cat-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease-out; }
.hp-cat-tile:hover .hp-cat-img img { transform: scale(1.06); }
.hp-cat-name { margin-top: 10px; font-size: 13px; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (prefers-reduced-motion: reduce) {
  .hp-cat-tile, .hp-cat-img img { transition: none; }
  .hp-cat-tile:hover { transform: none; } .hp-cat-tile:hover .hp-cat-img img { transform: none; }
}
</style>