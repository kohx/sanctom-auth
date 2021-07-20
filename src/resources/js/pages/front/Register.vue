<template>
  <div class="container">
    <h1>Register</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

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
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Register",
  components: {
    Nav,
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
};
</script>
