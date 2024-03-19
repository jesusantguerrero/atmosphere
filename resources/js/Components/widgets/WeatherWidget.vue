<script setup lang="ts">
import { useLocalStorage } from "@vueuse/core";
import { addMinutes, format } from "date-fns";
import { onMounted, computed } from "vue";
import WidgetCard from "../molecules/WidgetCard.vue";
import { ref } from "vue";
import { config } from "@/config";
const endpoint = config.WEATHER_ENDPOINT

const state = useLocalStorage('loger::climate', {
  name: "",
  main: {
    temp: 0
  },
  visibility: 0,
  wind: {},
  weather: {
    icon: "",
    description: ""
  },
  data: {},
  expireTime: new Date(),
  lastFetch: new Date()
});

const image = computed(() => {
  return state.value.weather?.icon;
});

const description = computed(() => {
  return  state.value.weather?.description;
});

const today = format(new Date(), "iiii");

const setWeatherData = (data: any) => {
  Object.keys(state.value).forEach((key) => {
    state.value[key] = data[key] || state.value[key];
  });
  state.value.expireTime = addMinutes(new Date(), 15);
  state.value.lastFetch = new Date();
};

interface LocationData {
    coords: { latitude: number, longitude: number}
}
const getWeather = ({ coords: { latitude, longitude } } : LocationData ) => {
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

const showImage = ref(true)
const hideImage = () => {
    showImage.value = false;
}
</script>

<template>
  <WidgetCard
    :title="state.name"
    :subtitle="today"
    :value-description="description"
    >
        <template #title>
            <div class="transition cursor-pointer group" @click="fetchWeatherInfo()">
                {{ state.name }} <small class="hidden group-hover:inline-block">Update</small>
            </div>
        </template>
        <template #leftTitle>
            <img :src="image" alt="weather-image" @error="hideImage"/>
        </template>
        <h4 class="relative text-4xl font-bold text-secondary">
            {{ state.main.temp }} <span class="absolute text-sm">Cº</span>
        </h4>
  </WidgetCard>
</template>


