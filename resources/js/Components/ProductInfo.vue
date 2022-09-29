<template>
    <article class="about-screen space-y-4">
        <section class="rounded-md bg-white p-4 w-full">
            <header class="items-center mx-auto flex flex-col">
                <h4 class="flex font-brand ml-2 items-center cursor-pointer"> 
                    <AppIcon />
                    <span class="logo text-body-1 transition text-4xl ml-2">
                        Loger.
                    </span>
                </h4>
                <div class="block-container mt-2">
                    <p class="app-version"><span>{{appVersion}}</span></p>
                </div>
                <p class="about-nav">
                    <button
                        v-for="(section, sectionName) in sections"
                        :key="sectionName"
                        :class="{selected: sectionName == selectedSection}"
                        @click="selectedSection=sectionName"
                        class="m-0 mt-8 px-4 py-2 bg-base-lvl-2/80 border-base border-2 border-solid"
                    >
                        {{ section }}
                    </button>
                </p>
            </header>

            <section class="information-container">
                <slot>
                    <div class="prose lg:prose-md w-full max-w-full" v-html="descriptionHtml" />
                </slot>
            </section>
        </section>

        <footer class="rounded-md bg-white p-4">
            <div class="logos"></div>
            <div class="block-container">
                <p class="app-version">
                    Developed by
                    <span class="author">{{ developer }}</span>
                </p>
            </div>
            <div class=""></div>
        </footer>
    </article>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    sections: {
        type: Array,
        default: () => ([])
    },
    appVersion: {
        type: String
    },
    developer: {
        type: String
    },
    descriptionHtml: {
        type: String,
    }
})
</script>

<style lang="scss">
    h1 {
        @apply text-xl font-bold;
    }
</style>
    
<style lang="scss" scoped>
    h1 {
        @apply text-lg;
        color: red;
    }
    
    .about-screen {
      width: 100%;
      text-align: center;
      overflow-y: auto;
    
        .block-container {
            display: flex;
            justify-content: center;
            width: 100%;
        }

    
       .logo {
            &:hover {
                color: dodgerblue;
                .movable {
                    -webkit-transform: rotate(35deg);
                    transform: rotate(35deg); }
            }
        }
    
    }
    
      .about-screen .app-version {
        font-size: 24px;
        font-weight: bolder;
        color: #ccc; }
        .about-screen .app-version:hover {
          color: #999; }
    
    .about-nav {
        button:first-child {
            border-radius: 4px 0 0 4px;
        }
        button:last-child {
            border-radius: 0 4px 4px 0;
        }
        button:hover {
            border: 2px solid #999;
        }
        button.selected {
            @apply bg-gray-500 border-gray-500 text-gray-100;
        }
    }
    
    .about-screen .information-container {
        text-align: left;
        width: 100%;
        padding: 20px 100px;
    }
    
    .about-screen .logos {
        margin-top: 70px;
    }
    
    .about-screen .logos img {
        width: 120px;
    }
    </style>
    
