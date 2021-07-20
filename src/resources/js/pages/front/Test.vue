<template>
  <div class="container">
    <h1>Test</h1>
    <Nav />
    <Message :title="message" @close="close" />
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
import Message from "@/components/Message.vue";
export default {
  name: "Test",
  components: {
    Nav,
    Message,
  },
  data() {
    return {
      message: "Unauthorized",
    };
  },
  methods: {
    close() {
      this.message = null;
    },
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
