<template>
  <div>
    <div class="flex mt-6 mb-10 ">
      <div class="w-full md:max-w-6xl m-auto">
        <div class="w-full md:w-1/2 m-auto md:p-6">
          <div class="border rounded-md p-6 shadow-md sticky top-4">
            <h2 class="font-semibold text-2xl">
              Login to Medkart
            </h2>
            <small>Welcome back! Please enter your details.</small>
            <login-form />
          </div>
        </div>
      </div>
    </div>
    <open-form-footer />
  </div>
</template>

<script setup>
import LoginForm from "~/components/pages/auth/components/LoginForm.vue"

definePageMeta({
  middleware: "guest"
})
useOpnSeoMeta({
  title: 'Login'
})
</script>
