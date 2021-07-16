<template>
  <div class="container">
    <h1>Test</h1>
    <div>{{ message }}</div>
    <router-link :to="{ name: 'home' }">Home</router-link>
    <router-link :to="{ name: 'login' }">Login</router-link>
  </div>
</template>

<script>
export default {
  name: "Test",
  data() {
    return {
      message: "Unauthorized",
    };
  },
  async created() {
    // get user
    const { data, status } = await axios.get("/test");
      console.log(status);
      console.log(data.message);
    if (status === 200) {
      this.message = data.message;
    } else {
      console.log(data.message);
    }
  },
};
</script>
