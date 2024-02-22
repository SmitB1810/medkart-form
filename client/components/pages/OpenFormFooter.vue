<template>
  <div class="w-full">
    <div class="grid md:grid-cols-2 my-8">
      <div class="flex mt-2 items-center">
        <p class="text-sm text-black-600 dark:text-black-400 text-center w-full">
          © Copyright {{ currYear }}. All Rights Reserved
        </p>
      </div>
      <div class="flex justify-center">
        <router-link :to="{ name: user ? 'home' : 'index' }" class="flex-shrink-0 font-semibold flex items-center">
          <img src="/img/logo.png" alt="notion tools logo" class="w-10 h-10">
          <span class="ml-2 text-xl text-black dark:text-white">
            Medkart
          </span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import opnformConfig from "~/opnform.config.js";

export default {
  setup () {
    const authStore = useAuthStore()
    return {
      user : computed(() => authStore.user),
      opnformConfig
    }
  },

  data: () => ({
    currYear: new Date().getFullYear(),
  }),

}
</script>
