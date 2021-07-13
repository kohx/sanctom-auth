<template>
  <div class="container">
    <h1>Test</h1>
    <div>{{message}}</div>
    <router-link :to="{ name: 'home'}">Home</router-link>
    <router-link :to="{ name: 'login'}">Login</router-link>
  </div>
</template>

<script>
export default {
  name: "Home",
  data() {
    return {
      message: 'Unauthorized'
    };
  },
  async created() {
    // get user
    const { data, status } = await axios.get("/test");
    if (status === 200) {
      this.message = data.message;
    }
  },
};
</script>
