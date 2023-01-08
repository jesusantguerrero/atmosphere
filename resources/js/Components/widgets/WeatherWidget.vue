<template>
  <WidgetCard
    :title="state.name"
    :subtitle="today"
    :value-description="description"
    >
        <template #title>
            <div class="group cursor-pointer transition" @click="fetchWeatherInfo()">
                {{ state.name }} <small class="group-hover:inline-block hidden">Update</small> 
            </div>
        </template>
        <template #leftTitle>
            <img :src="image" alt="weather-image" />
        </template>
        <h4 class="text-secondary font-bold text-4xl relative">
            {{ state.main.temp }} <span class="text-sm absolute">Cº</span>
        </h4>
  </WidgetCard>
</template>

<script setup>
import { useLocalStorage } from "@vueuse/core";
import { addMinutes, format } from "date-fns";
import { onMounted, reactive, computed } from "vue";
import WidgetCard from "../molecules/WidgetCard.vue";
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

const fetchWeatherInfo = () => {
    navigator.geolocation.getCurrentPosition(async (position) => {
        try {
          const response = await getWeather(position);
          setWeatherData(response);
        } catch (err) {
          console.error(err);
        }
    });
}

onMounted(() => {
  if (state.value.expireTime < new Date()) {
    fetchWeatherInfo()
  }
});
</script>
