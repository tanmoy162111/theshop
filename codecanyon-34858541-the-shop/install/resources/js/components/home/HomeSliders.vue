<template>
  <div class="mb-5 hp-hero">
    <v-container class="pt-md-6 pb-0 px-3 px-md-3">
      <!-- loading skeleton -->
      <div class="hp-hero-grid" v-if="loading">
        <v-skeleton-loader type="image" class="hp-skel-main"></v-skeleton-loader>
        <div class="hp-hero-side">
          <v-skeleton-loader type="image" class="hp-skel-side"></v-skeleton-loader>
          <v-skeleton-loader type="image" class="hp-skel-side"></v-skeleton-loader>
        </div>
      </div>

      <!-- bold split hero -->
      <div class="hp-hero-grid" v-else>
        <!-- left: cinematic banner + overlay copy -->
        <div class="hp-hero-main">
          <swiper
            v-if="heroSlides.length"
            :spaceBetween="0"
            :centeredSlides="true"
            :autoplay="carouselOption.autoplay"
            :modules="modules"
            class="mySwiper hp-hero-swiper"
          >
            <swiper-slide v-for="(slider, i) in heroSlides" :key="i">
              <img :src="slider.img" :alt="hero && hero.headline" @error="imageFallback($event)" />
            </swiper-slide>
          </swiper>
          <div v-else class="hp-hero-fallback"></div>

          <div class="hp-hero-overlay">
            <span class="hp-eyebrow">{{ eyebrow }}</span>
            <h1 class="hp-hero-title">{{ headline }}</h1>
            <p class="hp-hero-sub" v-if="subtext">{{ subtext }}</p>
            <div class="hp-hero-ctas">
              <component
                :is="isExternal(cta1Link) ? 'a' : 'router-link'"
                :to="isExternal(cta1Link) ? undefined : cta1Link"
                :href="isExternal(cta1Link) ? cta1Link : undefined"
                class="hp-hero-cta"
              >{{ cta1Label }} <span class="hp-arrow">&rarr;</span></component>
              <component
                :is="isExternal(cta2Link) ? 'a' : 'router-link'"
                :to="isExternal(cta2Link) ? undefined : cta2Link"
                :href="isExternal(cta2Link) ? cta2Link : undefined"
                class="hp-hero-cta hp-hero-cta--ghost"
              >{{ cta2Label }}</component>
            </div>
          </div>
        </div>

        <!-- right: two stacked promo cards -->
        <div class="hp-hero-side">
          <component
            v-for="(promo, i) in promos"
            :key="i"
            :is="isExternal(promo.link) ? 'a' : 'router-link'"
            :to="isExternal(promo.link) ? undefined : promo.link"
            :href="isExternal(promo.link) ? promo.link : undefined"
            :class="['hp-promo', 'hp-promo--' + (i + 1)]"
          >
            <span class="hp-promo-bg" :style="promo.img ? { backgroundImage: 'url(' + promo.img + ')' } : {}"></span>
            <span class="hp-promo-shade"></span>
            <span class="hp-promo-copy">
              <span class="hp-promo-title">{{ promo.title }}</span>
              <span class="hp-promo-cta">{{ promo.cta }} <span class="hp-arrow">&rarr;</span></span>
            </span>
          </component>
        </div>
      </div>
    </v-container>
  </div>
</template>

<script>
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
  components: { Swiper, SwiperSlide },
  setup() {
    return { modules: [Autoplay, Pagination, Navigation] };
  },
  data: () => ({
    loading: true,
    sliders: null,
    hero: null,
    carouselOption: {
      slidesPerView: 1,
      spaceBetween: 0,
      autoplay: { delay: 4000, disableOnInteraction: false },
    },
  }),
  computed: {
    heroSlides() {
      return (this.sliders && this.sliders.one) ? this.sliders.one : [];
    },
    h() {
      return this.hero || {};
    },
    eyebrow() {
      return this.clean(this.h.eyebrow) || "Mid-Season Sale";
    },
    headline() {
      return this.clean(this.h.headline) || "Everyday essentials, delivered to your door.";
    },
    subtext() {
      const s = this.clean(this.h.subtext);
      return s !== null ? s : "Up to 40% off across electronics, home & lifestyle. Fresh deals dropping every day.";
    },
    cta1Label() {
      return this.clean(this.h.cta_label) || "Shop the sale";
    },
    cta1Link() {
      return this.clean(this.h.cta_link) || "/all-offers";
    },
    cta2Label() {
      return this.clean(this.h.cta2_label) || "Browse all";
    },
    cta2Link() {
      return this.clean(this.h.cta2_link) || "/all-categories";
    },
    promos() {
      const two = (this.sliders && this.sliders.two) || [];
      const three = (this.sliders && this.sliders.three) || [];
      const four = (this.sliders && this.sliders.four) || [];
      const sideImg = (...groups) => {
        for (const g of groups) { if (g && g[0] && g[0].img) return g[0].img; }
        return null;
      };
      return [
        {
          title: this.clean(this.h.promo1_title) || "New Arrivals",
          cta: "Discover",
          link: this.clean(this.h.promo1_link) || "/search",
          img: sideImg(two, three),
        },
        {
          title: this.clean(this.h.promo2_title) || "Top Brands · 25% off",
          cta: "Shop now",
          link: this.clean(this.h.promo2_link) || "/all-brands",
          img: sideImg(three, four, two),
        },
      ];
    },
  },
  methods: {
    clean(v) {
      if (v === undefined || v === null) return null;
      const s = String(v).trim();
      return s.length ? s : null;
    },
    isExternal(to) {
      return typeof to === "string" && to.slice(0, 4) === "http";
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
.hp-hero-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
  height: 440px;
}
.hp-skel-main, .hp-skel-side { height: 100% !important; border-radius: var(--hp-radius-card); }
.hp-hero-side {
  display: grid;
  grid-template-rows: 1fr 1fr;
  gap: 16px;
  min-height: 0;
}

/* ---- left cinematic banner ---- */
.hp-hero-main {
  position: relative;
  border-radius: var(--hp-radius-card);
  overflow: hidden;
  box-shadow: var(--hp-shadow-md);
  min-height: 0;
}
.hp-hero-main :deep(.swiper),
.hp-hero-main :deep(.swiper-wrapper),
.hp-hero-main :deep(.swiper-slide),
.hp-hero-swiper { height: 100%; }
.hp-hero-main img { width: 100%; height: 100%; object-fit: cover; display: block; }
.hp-hero-fallback {
  width: 100%; height: 100%;
  background: linear-gradient(120deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 55%, #1b1f24) 100%);
}
.hp-hero-overlay {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; justify-content: center;
  padding: 0 56px; max-width: 640px;
  background: linear-gradient(90deg, rgba(10,12,16,.84) 0%, rgba(10,12,16,.5) 45%, rgba(10,12,16,0) 78%);
  pointer-events: none;
}
.hp-hero-overlay > * { pointer-events: auto; }
.hp-eyebrow {
  display: inline-block; width: max-content;
  font-size: 12px; letter-spacing: .12em; text-transform: uppercase;
  font-weight: 700; color: #FFD68A;
}
.hp-hero-title {
  color: #fff; font-weight: 800; letter-spacing: -.02em;
  font-size: clamp(26px, 3.4vw, 44px); line-height: 1.08; margin: 12px 0 14px;
}
.hp-hero-sub {
  color: #E6E8EC; font-size: clamp(14px, 1.3vw, 17px); line-height: 1.5;
  margin: 0 0 26px; max-width: 420px;
}
.hp-hero-ctas { display: flex; gap: 12px; flex-wrap: wrap; }
.hp-hero-cta {
  display: inline-flex; align-items: center; gap: 8px; width: max-content;
  background: var(--primary); color: #fff; font-weight: 700; font-size: 15px;
  padding: 13px 26px; border-radius: 999px; text-decoration: none;
  box-shadow: 0 8px 20px color-mix(in srgb, var(--primary) 40%, transparent);
  transition: transform .15s ease-out, background .15s ease-out;
}
.hp-hero-cta:hover { background: var(--hov-primary); transform: translateY(-2px); }
.hp-hero-cta:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.hp-hero-cta--ghost {
  background: rgba(255,255,255,.14); color: #fff; box-shadow: none;
  backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,.4);
}
.hp-hero-cta--ghost:hover { background: rgba(255,255,255,.26); }
.hp-arrow { font-weight: 700; }

/* ---- right promo cards ---- */
.hp-promo {
  position: relative; display: block; height: 100%;
  border-radius: var(--hp-radius-card); overflow: hidden;
  box-shadow: var(--hp-shadow-sm); text-decoration: none;
  transition: transform .18s ease-out, box-shadow .18s ease-out;
}
.hp-promo:hover { transform: translateY(-3px); box-shadow: var(--hp-shadow-md); }
.hp-promo:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.hp-promo-bg {
  position: absolute; inset: 0; background-size: cover; background-position: center;
  transition: transform .35s ease-out;
}
.hp-promo--1 .hp-promo-bg { background-image: linear-gradient(135deg, #243b55, #141e30); }
.hp-promo--2 .hp-promo-bg { background-image: linear-gradient(135deg, color-mix(in srgb, var(--primary) 70%, #000), #2b2118); }
.hp-promo:hover .hp-promo-bg { transform: scale(1.06); }
.hp-promo-shade {
  position: absolute; inset: 0;
  background: linear-gradient(0deg, rgba(10,12,16,.66) 0%, rgba(10,12,16,0) 62%);
}
.hp-promo-copy {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; justify-content: flex-end;
  padding: 20px;
}
.hp-promo-title { color: #fff; font-size: 18px; font-weight: 700; line-height: 1.2; }
.hp-promo-cta { color: #FFD68A; font-size: 13px; font-weight: 700; margin-top: 4px; }

/* ---- responsive ---- */
@media (max-width: 959px) {
  .hp-hero-grid { grid-template-columns: 1fr; height: auto; }
  .hp-hero-main { height: 300px; }
  .hp-hero-side { grid-template-columns: 1fr 1fr; grid-template-rows: none; height: 150px; }
  .hp-hero-overlay { padding: 0 28px; }
}
@media (max-width: 600px) {
  .hp-hero-main { height: 240px; }
  .hp-hero-overlay { padding: 0 22px; background: linear-gradient(90deg, rgba(10,12,16,.86) 0%, rgba(10,12,16,.55) 60%, rgba(10,12,16,.2) 100%); }
  .hp-hero-side { height: 120px; }
  .hp-promo-title { font-size: 15px; }
}
.v-locale--is-rtl .hp-hero-overlay {
  background: linear-gradient(270deg, rgba(10,12,16,.84) 0%, rgba(10,12,16,.5) 45%, rgba(10,12,16,0) 78%);
  text-align: right;
}
@media (prefers-reduced-motion: reduce) {
  .hp-hero-cta, .hp-promo, .hp-promo-bg { transition: none; }
  .hp-hero-cta:hover, .hp-promo:hover { transform: none; }
  .hp-promo:hover .hp-promo-bg { transform: none; }
}
</style>
