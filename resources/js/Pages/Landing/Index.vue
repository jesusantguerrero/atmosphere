<template>
  <div>
    <HeroSection />
    <!-- <learn-more /> -->
    <div class="flex flex-col items-center py-24 bg-white">
      <h2 class="text-4xl font-bold text-pink-400">Try Loger for free.</h2>

      <div class="mt-12">
        <Link
            role="button"
            href="/register"
            class="py-3 px-5 mb-2 font-bold border-2 text-white border-pink-400 transition rounded-md bg-gradient-to-br from-purple-400 to-pink-500 hover:bg-pink-500"
        >
          Organize your daily routine
        </Link>
      </div>
    </div>
    <FooterSection />
    <SiteFooter />
  </div>
</template>

<script setup>
import HeroSection from "./Hero.vue";
import FooterSection from "./Footer.vue";
import SiteFooter from "./SiteFooter.vue";
import { Link } from "@inertiajs/inertia-vue3"
</script>

<style lang="scss">
.bg-secondary {
  background: linear-gradient(to left bottom, #3a4a73 0, #1b243f);
  color: white;

  .prose h3 {
    color: white !important;
    margin-bottom: 10px;
  }
}

.bg-primary {
  background: #3a4a73;
}

.btn-action {
  @apply bg-green-400 flex items-center justify-center;
  color: white;
  border: none;
  margin: 10px 0;
  transition: all ease 0.3s;
  height: 40px;

  &:hover {
    @apply bg-green-500;
  }
}

html {
  scroll-behavior: smooth;
}
</style>
