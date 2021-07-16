<template>
  <div class="container">
    <h1>Verify</h1>
    <Message :title="message" :contents="errors" @close="close" />
  </div>
</template>

<script>
import Message from "@/components/Message.vue";
export default {
  name: "Verify",
  components: {
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
    } else {
      this.message = data.message;
      this.errors = data.errors || null;
    }
  },
};
</script>













