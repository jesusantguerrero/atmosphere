<template>
  <main
    class="min-h-screen bg-base-lvl-3 home-container"
    v-auto-animate
    :class="{ expanded: isExpanded }"
  >
    <nav class="app-header md:pl-{var(--app)} bg-base-lvl-3 border-b" :class="navClass">
      <slot name="navigation" />
    </nav>

    <article class="app-content">
      <aside class="app-side-container">
        <slot name="aside" />
      </aside>

      <section class="app-content__inner ic-scroller bg-base border">
        <slot name="main-section" />
      </section>
    </article>
  </main>
</template>

<script setup>
defineProps({
  navClass: {
    type: [String, Object],
  },
  isExpanded: {
    type: Boolean,
    default: true,
  },
});
</script>

<style lang="scss">
body,
html {
  background-color: #1e293b;
}
.home-container {
  position: relative;
  height: 100vh;

  --app-side-width: 74px;

  &.expanded {
    --app-side-width: 230px;
  }
}

.app-header {
  width: 100%;
  top: 0;
  position: fixed;
  z-index: 1000;
}

@screen lg {
    .app-header {
      padding-left: var(--app-side-width);
    }

}

.app-side-container {
  padding-right: 0 !important;
  position: fixed;
  display: grid;
  width: var(--app-side-width);
  height: 100%;
  z-index: 1001;
}

.app-content {
  display: grid;
  grid-template-columns: var(--app-side-width) minmax(0, 1fr);
  position: relative;
  height: 100vh;
  transition: all ease 0.3s;

  &__inner {
    width: 100%;
    grid-column-start: 2;
    padding: 64px 0;
    padding-top: 63px;
    padding-bottom: 0;
    position: relative;
    max-height: 100%;
    transition: all ease 0.3s;

    &.header-replacer-mode {
      padding-top: 0;

      .header-replacer {
        height: 73px;
        margin: 0;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        width: 100%;
        z-index: 1000;
        background: white;
        align-items: center;
        padding: 0 10px;
      }

      .section-body {
        padding-top: 140px;
      }
    }
  }
}

.splash-screen {
  background: dodgerblue;
  width: 100%;
  height: 100vh;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media screen and (max-width: 992px) {
  .app-side-container {
    z-index: 999;
    width: 256px;
    left: -260px;
    transition: all ease 0.3s;
  }

  .app-content {
    height: auto;
    &__inner {
      grid-column-start: 1;
      grid-column-end: 3;
      padding-bottom: 40px;
    }
  }

  .home-container.menu-expanded {
    .app-side-container {
      left: 0;
    }
  }
}

@media print {
  .app-side-container,
  .no-print,
  button {
    display: none;
  }

  table {
    width: 100% !important;
    overflow: hidden;
  }

  th td {
    overflow: hidden;
  }

  .app-content {
    grid-column-start: 1;
    grid-column-end: 3;
  }
}

.ic-scroller {
  &::-webkit-scrollbar-thumb {
    background-color: transparentize($color: #000000, $amount: 0.7);
    border-radius: 4px;

    &:hover {
      background-color: transparentize($color: #000000, $amount: 0.7);
    }
  }

  &::-webkit-scrollbar {
    background-color: transparent;
    width: 8px;
    height: 10px;
  }

  &-slim {
    transition: all ease 0.3s;
    &::-webkit-scrollbar {
      height: 0;
    }

    &:hover {
      &::-webkit-scrollbar {
        height: 3px;
      }
    }
  }
}
</style>
