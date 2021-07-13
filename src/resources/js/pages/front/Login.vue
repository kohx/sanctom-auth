<template>
  <div class="container">
    <h1>Login</h1>

    <form @submit.prevent="login">
      <input type="email" name="email" v-model="loginForm.email" />
      <input type="password" name="password" v-model="loginForm.password" />
      <button type="submit">login</button>
    </form>
    <div>{{ user.id }}</div>
    <div>{{ user.name }}</div>
    <div>{{ user.email }}</div>
    <button @click="logout">logout</button>
  </div>
</template>

<script>
export default {
  name: "Home",
  data() {
    return {
      user: {
        id: null,
        name: null,
        email: null,
      },
      loginForm: {
        email: "user1@example.com",
        password: "password",
        remember: true,
      },
    };
  },
  methods: {
    async login() {
      // get token
      await axios.get("csrf-cookie");

      // login
      const { data, status } = await axios.post("login", this.loginForm);
      if (status === 200) {
        this.user.id = data.id;
        this.user.name = data.name;
        this.user.email = data.email;
      }
    },
    async logout() {
      // logout
      const { data, status } = await axios.post("/logout");
      if (status === 200) {
        this.user.id = null;
        this.user.name = null;
        this.user.email = null;
      }
    },
  },
  async created() {
    // get user
    const { data, status } = await axios.post("/user");
    if (status === 200) {
      this.user.id = data.id;
      this.user.name = data.name;
      this.user.email = data.email;
    } else {
      this.user.id = null;
      this.user.name = null;
      this.user.email = null;
    }
  },
};
</script>
