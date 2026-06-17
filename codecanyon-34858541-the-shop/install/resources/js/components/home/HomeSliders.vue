<template>
  <div class="mb-5 hp-hero">
    <v-container class="pt-md-6 pb-0 px-0 px-md-3">
      <v-row
        class="gutters-7 md-gutters-10 lh-0"
        v-if="loading"
      >
        <v-col
          cols="12"
          lg="6"
          class=""
        >
          <v-skeleton-loader
            type="image"
            height="310"
            class="loader"
          ></v-skeleton-loader>
        </v-col>
        <v-col
          cols="6"
          lg="3"
          class=""
        >
          <v-skeleton-loader
            type="image"
            height="310"
            class="loader"
          ></v-skeleton-loader>
        </v-col>
        <v-col
          cols="6"
          lg="3"
          class=""
        >
          <v-skeleton-loader
            type="image"
            height="145"
            class="right-first loader-half"
          ></v-skeleton-loader>
          <v-skeleton-loader
            type="image"
            height="145"
            class="loader-half"
          ></v-skeleton-loader>
        </v-col>
      </v-row>
      <v-row
        class="gutters-7 md-gutters-10 lh-0"
        v-else
      >
        <v-col
          cols="12"
          lg="6"
          class=""
        >
          <!-- <swiper
          :slides-per-view=carouselOption.slidesPerView
          :space-between=carouselOption.spaceBetween
          :autoplay=carouselOption.autoplay
            :options="carouselOption"
            class="mySwiper"
          >
            <swiper-slide
              v-for="(slider, i) in sliders.one"
              :key="i"
              class=""
            >
              <banner
                :loading="false"
                :banner="slider"
              />
            </swiper-slide>
          </swiper> -->


          <div class="hp-hero-main">
            <swiper
                :spaceBetween="30"
                :centeredSlides="true"
                :autoplay=carouselOption.autoplay
                :modules="modules"
                class="mySwiper"
            >
                    <swiper-slide
                      v-for="(slider, i) in sliders.one"
                      :key="i"
                      class=""
                    >
                      <banner
                        :loading="false"
                        :banner="slider"
                      />
                    </swiper-slide>
            </swiper>
            <div class="hp-hero-overlay" v-if="heroHasText">
              <h1 class="hp-hero-title">{{ hero.headline }}</h1>
              <p class="hp-hero-sub" v-if="hero.subtext">{{ hero.subtext }}</p>
              <a class="hp-hero-cta" v-if="heroHasCta" :href="hero.cta_link">{{ hero.cta_label }}</a>
            </div>
          </div>

        </v-col>
        <v-col
          cols="6"
          lg="3"
          class=""
        >
          <swiper
          :spaceBetween="30"
              :centeredSlides="true"
              :autoplay=carouselOption.autoplay
              :modules="modules"
            class="mySwiper"
          >
            <swiper-slide
              v-for="(slider, i) in sliders.two"
              :key="i"
              class=""
            >
              <banner
                :loading="false"
                :banner="slider"
              />
            </swiper-slide>
          </swiper>
        </v-col>
        <v-col
          cols="6"
          lg="3"
          class="d-flex justify-space-between flex-column"
        >
          <swiper
          :spaceBetween="30"
              :centeredSlides="true"
              :autoplay=carouselOption.autoplay
              :modules="modules"
            class="right-first w-100 mySwiper"
          >
            <swiper-slide
              v-for="(slider, i) in sliders.three"
              :key="i"
              class=""
            >
              <banner
                :loading="false"
                :banner="slider"
              />
            </swiper-slide>
          </swiper>
          <swiper
          :spaceBetween="30"
              :centeredSlides="true"
              :autoplay=carouselOption.autoplay
              :modules="modules"
            class="w-100"
          >
            <swiper-slide
              v-for="(slider, i) in sliders.four"
              :key="i"
              class=""
            >
              <banner
                :loading="false"
                :banner="slider"
              />
            </swiper-slide>
          </swiper>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
// Import Swiper Vue.js components
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide, } from "swiper/vue";

export default {
  components: {
    Swiper,
    SwiperSlide,
  },
  setup() {
      return {
        modules: [Autoplay, Pagination, Navigation],
      };
    },

  data: () => ({
    loading: true,
    sliders: null,
    hero: null,
    carouselOption: {
      slidesPerView: 1,
      spaceBetween: 0,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
    },
  }),
  computed: {
    heroHasText() {
      return !!(this.hero && this.hero.headline && this.hero.headline.trim());
    },
    heroHasCta() {
      return !!(this.hero && this.hero.cta_label && this.hero.cta_label.trim() && this.hero.cta_link && this.hero.cta_link.trim());
    },
  },
  async created() {
    const res = await this.call_api("get", "setting/home/sliders");
    if (res.data.success) {
      this.sliders = res.data.data;
      this.hero = res.data.data.hero || null;
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.loader {
  height: 200px !important;
}
.loader-half {
  height: 92px !important;
}
.row.gutters-7 > [class*="col-"] {
  padding-top: 7px;
  padding-bottom: 7px;
}
.col-lg-6 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}
.col-lg-3:nth-of-type(2) {
  padding-left: 0px;
}
.col-lg-3:nth-of-type(3) {
  padding-right: 0px;
}
.right-first {
  margin-bottom: 14px;
}
.row {
  margin-left: 0;
  margin-right: 0;
}
.v-application-is-rtl .col-lg-3:nth-of-type(2) {
  padding-left: 7px;
  padding-right: 0;
}
.v-application-is-rtl .col-lg-3:nth-of-type(3) {
  padding-right: 7px;
  padding-left: 0;
}
@media (min-width: 600px) {
}
@media (min-width: 960px) {
  .loader {
    height: 310px !important;
  }
  .loader-half {
    height: 145px !important;
  }
  .right-first {
    margin-bottom: 20px;
  }
  .row {
    margin-left: -10px;
    margin-right: -10px;
  }
  .row.md-gutters-10 > [class*="col-"] {
    padding-top: 10px;
    padding-bottom: 10px;
  }
  .col-lg-6 {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
  .col-lg-3:nth-of-type(2) {
    padding-left: 10px;
  }
  .col-lg-3:nth-of-type(3) {
    padding-right: 10px;
  }
  .v-application-is-rtl .col-lg-3,
  .v-application-is-rtl .col-lg-3 {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
}
@media (min-width: 1264px) {
}
.hp-hero :deep(img) {
  border-radius: var(--hp-radius-card);
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .35s ease-out;
}
.hp-hero :deep(.lh-0) { overflow: hidden; border-radius: var(--hp-radius-card); }
.hp-hero :deep(a:hover img) { transform: scale(1.03); }
.hp-hero :deep(.swiper-pagination-bullet-active) { background: var(--primary); }
.hp-hero :deep(.swiper-button-next), .hp-hero :deep(.swiper-button-prev) { color: var(--primary); }
.hp-hero-main { position: relative; }
.hp-hero-overlay {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; justify-content: center;
  padding: 0 8%;
  border-radius: var(--hp-radius-card);
  background: linear-gradient(90deg, rgba(8,11,15,.82) 0%, rgba(8,11,15,.45) 45%, rgba(8,11,15,0) 78%);
  pointer-events: none;
}
.hp-hero-overlay > * { pointer-events: auto; }
.hp-hero-title {
  color: #fff; font-weight: 800; letter-spacing: -.02em;
  font-size: clamp(20px, 2.6vw, 34px); line-height: 1.1; margin: 0 0 8px; max-width: 70%;
}
.hp-hero-sub { color: #E6E8EC; font-size: clamp(13px, 1.2vw, 16px); line-height: 1.4; margin: 0 0 16px; max-width: 60%; }
.hp-hero-cta {
  display: inline-flex; align-items: center; width: max-content;
  background: var(--primary); color: #fff; font-weight: 700; font-size: 15px;
  padding: 12px 26px; border-radius: 999px; text-decoration: none;
  box-shadow: 0 8px 20px color-mix(in srgb, var(--primary) 40%, transparent);
  transition: transform .15s ease-out, background .15s ease-out;
}
.hp-hero-cta:hover { background: var(--hov-primary); transform: translateY(-2px); }
.hp-hero-cta:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.v-locale--is-rtl .hp-hero-overlay {
  background: linear-gradient(270deg, rgba(8,11,15,.82) 0%, rgba(8,11,15,.45) 45%, rgba(8,11,15,0) 78%);
  text-align: right;
}
@media (max-width: 600px) {
  .hp-hero-title, .hp-hero-sub { max-width: 80%; }
  .hp-hero-overlay { padding: 0 6%; }
}
@media (prefers-reduced-motion: reduce) {
  .hp-hero :deep(img) { transition: none; } .hp-hero :deep(a:hover img) { transform: none; }
  .hp-hero-cta { transition: none; } .hp-hero-cta:hover { transform: none; }
}
</style>