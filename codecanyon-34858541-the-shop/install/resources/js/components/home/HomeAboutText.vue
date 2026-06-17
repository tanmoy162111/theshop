<template>
    <div v-if="!loading" class="hp-about">
        <div :class="['hp-about__body', { 'hp-about__body--collapsed': !expanded }]" v-html="data"></div>
        <button class="hp-about__toggle" type="button" @click="expanded = !expanded">
            {{ expanded ? showLessLabel : readMoreLabel }}
        </button>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: true,
        data: null,
        expanded: false,
    }),
    computed: {
        readMoreLabel() {
            const t = this.$t('read_more');
            return (t && t !== 'read_more') ? t : 'Read more';
        },
        showLessLabel() {
            const t = this.$t('show_less');
            return (t && t !== 'show_less') ? t : 'Show less';
        },
    },
    async created(){
        const res = await this.call_api("get", "setting/home/home_about_text");
        if (res.data.success) {
            this.data = res.data.data
            this.loading = false
        }
    }
}
</script>

<style scoped>
.hp-about { max-width: 72ch; margin: 0 auto; background: var(--hp-surface);
  border-radius: var(--hp-radius-card); box-shadow: var(--hp-shadow-sm); padding: 24px; }
.hp-about__body :deep(*) { line-height: 1.7; }
.hp-about__body :deep(h1), .hp-about__body :deep(h2), .hp-about__body :deep(h3) {
  margin: 1.2em 0 .5em; line-height: 1.3; }
.hp-about__body :deep(p) { margin: 0 0 1em; }
.hp-about__body--collapsed { max-height: 160px; overflow: hidden;
  -webkit-mask-image: linear-gradient(180deg, #000 60%, transparent); mask-image: linear-gradient(180deg, #000 60%, transparent); }
.hp-about__toggle { margin-top: 12px; color: var(--primary); font-weight: 600; cursor: pointer; }
.hp-about__toggle:hover { color: var(--hov-primary); }
</style>