<template>
  <div class="container">
    <h1>Login</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

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
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Login",
  components: {
    Nav,
    Message,
  },
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
      message: null,
      errors: null,
    };
  },
  methods: {
    async login() {
      // get token
      await axios.get("csrf-cookie");

      // login
      const { data, status } = await axios.post("login", this.loginForm);
      if (status === 200) {
        this.user.id = data.user.id;
        this.user.name = data.user.name;
        this.user.email = data.user.email;
        this.message = data.message;
        this.errors = null;
      } else {
        this.message = data.message;
        this.errors = data.errors;
      }
    },
    async logout() {
      // logout
      const { data, status } = await axios.post("logout");
      if (status === 200) {
        this.user.id = null;
        this.user.name = null;
        this.user.email = null;
        this.message = data.message;
        this.errors = null;
      } else {
        this.message = data.message;
        this.errors = data.errors;
      }
    },
    close() {
      this.message = null;
      this.errors = null;
    },
  },
  async created() {
    // get user
    const { data, status } = await axios.post("/user");
    if (status === 200) {
      this.user.id = data.user.id;
      this.user.name = data.user.name;
      this.user.email = data.user.email;
    } else {
      this.user.id = null;
      this.user.name = null;
      this.user.email = null;
    }
  },
};
</script>
