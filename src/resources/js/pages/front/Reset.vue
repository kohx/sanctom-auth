<template>
  <div class="container">
    <h1>Reset</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />

    <form @submit.prevent="reset">
      <input type="password" name="password" v-model="resetForm.password" />
      <input
        type="password"
        name="password_confirmation"
        v-model="resetForm.password_confirmation"
      />
      <button type="submit">reset</button>
    </form>
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Reset",
  components: {
    Nav,
    Message,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      resetForm: {
        password: "$Pw111111",
        password_confirmation: "$Pw111111",
      },
      message: null,
      errors: null,
    };
  },
  methods: {
    async reset() {
      this.resetForm.token = this.token;

      const { data, status } = await axios.post("change", this.resetForm);
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
  async created() {
    this.resetForm.token = this.token;
    const { data, status } = await axios.post("reset", {
      token: this.token,
    });
    if (status === 200) {
      this.message = data.message;
      this.errors = null;
    } else {
      this.message = data.message;
      this.errors = data.errors;
    }
  },
};
</script>













