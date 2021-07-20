<template>
  <div class="container">
    <h1>Verify</h1>
    <Nav />
    <Message :title="message" :contents="errors" @close="close" />
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Verify",
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
      message: null,
      errors: null,
    };
  },
  methods: {
    close() {
      this.message = null;
      this.errors = null;
    },
  },
  async created() {
    const { data, status } = await axios.post("verify", {
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













