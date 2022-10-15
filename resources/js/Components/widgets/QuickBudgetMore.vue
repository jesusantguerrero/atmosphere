<template>
  <section class="space-y-2 border border-transparent rounded bg-base-lvl-3 p-4" :class="[cardShadow]">
    <header class="flex justify-between items-center">
      <SectionTitle>{{ state.name }}</SectionTitle>
      <span class="text-secondary font-bold text-sm">{{ today }}</span>
    </header>
    <article class="flex">
      <img :src="image" alt="weather-image" />
      <section>
        <h4 class="text-secondary font-bold text-4xl relative">28<span class="text-sm absolute">Cº</span></h4>
        <p class="text-body-1/80">{{ description }}</p>
      </section>
    </article>
  </section>
</template>

<script setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import { useLocalStorage } from "@vueuse/core";
import { addMinutes, format } from "date-fns";
import { onMounted, reactive, computed } from "vue";
const endpoint = import.meta.env.VITE_WEATHER_ENDPOINT;

const state = useLocalStorage('loger::climate', {
  name: "",
  main: {},
  visibility: 0,
  wind: {},
  weather: [],
  data: {},
  expireTime: 0,
  lastFetch: 0
});

const image = computed(() => {
  return state.value.weather.length && state.value.weather[0].icon;
});

const description = computed(() => {
  return state.value.weather.length && state.value.weather[0].description;
});

const today = format(new Date(), "iiii");

const setWeatherData = (data) => {
  Object.keys(state.value).forEach((key) => {
    state.value[key] = data[key] || state.value[key];
  });
  state.value.expireTime = addMinutes(new Date(), 15);
  state.value.lastFetch = new Date();
};

const getWeather = ({ coords: { latitude, longitude } }) => {
  const params = `lat=${latitude}&lon=${longitude}`;
  return fetch(endpoint + params).then((res) => res.json());
};

onMounted(() => {
  if (state.value.expireTime < new Date()) {
      navigator.geolocation.getCurrentPosition(async (position) => {
        try {
          const response = await getWeather(position);
          setWeatherData(response);
        } catch (err) {
          console.log(err);
        }
      });
  }
});
</script>
