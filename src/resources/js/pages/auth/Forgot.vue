<template>
  <div class="container">
    <h1>Forgot</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

    <form @submit.prevent="forgot">
      <input type="email" name="email" v-model="forgotForm.email" />
      <button type="submit">forgot</button>
    </form>
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Forgot",
  components: {
    Nav,
    Message,
  },
  data() {
    return {
      forgotForm: {
        email: "user1@example.com",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async forgot() {
      const { data, status } = await axios.post("forgot", this.forgotForm);
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
