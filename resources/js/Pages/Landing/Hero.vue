<template>
    <div class="flex justify-start px-2 py-3 text-xs font-bold bg-white border-b md:px-10 md:justify-between md:flex">
        <div class="px-2 text-xs text-gray-500 transition cursor-pointer md:text-sm md:px-14 max-w-7xl md:text-left hover:text-pink-500">
            <span class="px-1 py-1 mr-3 text-white bg-pink-400 rounded-md"> New </span>
            Introducing Loger - The Digital Home Management System
            <i class="fa fa-chevron-right"></i>
        </div>
        <div class="mt-4 space-x-4 md:mt-0">

        </div>
    </div>
    <header class="relative w-full px-10 pb-16 antialiased bg-gray-900 select-none md:pb-0 md:px-0">
        <AtSiteHeader
            title="Loger"
            brand-class="font-brand"
            :links="state.links"
            link-class="text-white hover:text-pink-500"
            dot-class="text-pink-400"
            :actions="state.actions"
            @action="$inertia.visit($event)"
            :routerComponent="Link"
        />

        <div class="relative z-50 py-10 mx-auto max-w-7xl md:items-center md:py-32 sm:px-4 md:flex md:justify-between">
            <div class="w-full text-left">
                <h1 class="text-2xl font-extrabold tracking-tight text-white md:text-4xl sm:text-5xl leading-none xl:text-3xl">The Home Management System</h1>
                <div class="max-w-lg mt-1 text-sm text-gray-200 md:mt-4 sm:text-base -md:max-w-xl md:text-lg xl:text-xl">Manage finance, grocery list, menu and more all in one place </div>
                <div class="max-w-md mt-12 text-left md:space-x-5 md:items-center md:flex">
                    <AtButton
                        @click="$inertia.visit('/register')"
                        class="mb-2 font-bold border-2 text-white border-pink-400 rounded-md bg-gradient-to-br from-purple-400 to-pink-500 hover:bg-pink-500">
                        Organize your home stuff
                    </AtButton>
                </div>
            </div>
            <div class="w-full">
                <div class="mt-10 md:mt-0">
                    <AtVideo
                        class="overflow-hidden rounded-md shadow-lg shadow-slate-200/20 border-pink-400/75"
                        imageUrl="/images/finance-dashboard.png"
                        src="https://www.framer.com/images/developers/hero.mp4"
                        :style="{with: '400px'}"
                    />
                </div>
            </div>
        </div>

        <down-wave class="z-50" />
    </header>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { AtVideo,  AtSiteHeader, AtButton } from "atmosphere-ui";
import DownWave from "./waves/down-wave.vue";

const state = {
  title: "Loger",
  links: [
    {
      name: "Home",
      url: "#home",
    },
    {
      name: "About",
      url: "#about",
    },
    {
      name: "Features",
      url: "#features",
    },
    {
      name: "Contact",
      url: "#contact",
    },
  ],
  sections: {
    general: {
      label: "General Resources",
      links: {
        Docs: "",
        Blog: "",
        "Use Cases": "",
      },
    },
    more: {
      label: "More",
      links: {
        Zenboard: "",
        Atmosphere: "",
        Lumiere: "",
        "Insane Code": "",
      },
    },
    about: {
      label: "About Neatlancer",
      links: {
        "Open Source Software": "",
        Github: "",
        Twitter: "",
      },
    },
    legal: {
      label: "Legal",
      links: {
        "Privacy Policy": "/privacy-policy",
        "Terms of service": "/terms",
      },
    },
  },
  actions: [
    {
      name: "Login",
      url: "/login",
      class: "bg-white text-pink-500 hover:bg-gray-100 transition",
    },
    {
      name: "Start for free",
      url: "/register",
      class: "mb-2 font-bold border-2 text-white border-pink-400 transition rounded-md bg-gradient-to-br from-purple-400 to-pink-500 hover:bg-pink-500",
    },
  ],
  footerLinks: [
    {
      name: "facebook",
    },
    {
      name: "twitter",
    },
    {
      name: "github",
    },
    {
      name: "github",
    },
  ],
  features: {
    row1: [
      {
        emoji: "📦",
        name: "Firebase",
      },
      {
        emoji: "📦",
        name: "TailwindCSS",
      },
      {
        emoji: "📦",
        name: "Storybook",
      },
    ],
    row2: [
      {
        emoji: "📦",
        name: "Atmosphere UI",
      },
      {
        emoji: "📦",
        name: "Vue 3",
      },
      {
        emoji: "📦",
        name: "Vue Testing Library",
      },
    ],
    row3: [
      {
        emoji: "📦",
        name: "Full auth flow",
        description:
          "Authentication, registration, password update, password confirmation, Social logins",
      },
      {
        emoji: "📦",
        name: "API, Teams, Payment",
      },
      {
        emoji: "📦",
        name: "Settings",
      },
    ],
  },
};
</script>

<style lang="scss" scoped>
header {
    background: url('../../assets/zen-photo.jpg');
    background-size: cover;
    position: relative;
    width: 100%;
    height: 100%;

    &::after {
        content: "";
        background: linear-gradient(to left bottom,#3a4a73 0,#1b243f);
        display: block;
        position: absolute;
        width: 100%;
        top: 0;
        opacity: .95;
        left: 0;
        height: 100%;
        z-index: 40;
    }
}

.btn-action {
    @apply bg-pink-400 flex items-center justify-center;
    color: white;
    border: none;
    margin: 10px 0;
    transition: all ease 0.3s;
    height: 40px;

    &:hover {
        @apply bg-pink-500;
    }
}
</style>
