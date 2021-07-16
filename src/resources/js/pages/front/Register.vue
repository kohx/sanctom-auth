<template>
  <div class="container">
    <h1>Register</h1>
    <Message :title="message" :contents="errors" @close="close" />
    <router-link :to="{ name: 'home' }">Home</router-link>
    <router-link :to="{ name: 'login' }">Login</router-link>
    <form @submit.prevent="register">
      <input
        type="name"
        name="name"
        v-model="registerForm.name"
        placeholder="name"
      />
      <input
        type="email"
        name="email"
        v-model="registerForm.email"
        placeholder="email"
      />
      <input
        type="password"
        name="password"
        v-model="registerForm.password"
        placeholder="password"
      />
      <input
        type="password"
        name="password_confirmation"
        v-model="registerForm.password_confirmation"
        placeholder="password confirmation"
      />
      <button type="submit">register</button>
    </form>
  </div>
</template>

<script>
import Message from "@/components/Message.vue";
export default {
  name: "Register",
  components: {
    Message,
  },
  data() {
    return {
      registerForm: {
        name: "user0",
        email: "user0@example.com",
        password: "11111111",
        password_confirmation: "11111111",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async register() {
      const { data, status } = await axios.post("register", this.registerForm);
      if (status === 200) {
        this.message = data.message;
      } else {
        this.message = data.message;
        this.errors = data.errors || null
      }
    },
    close() {
      this.message = null;
      this.errors = null;
    },
  },
};
</script>
