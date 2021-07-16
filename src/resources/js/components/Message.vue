<template>
  <div class="message" v-if="title">
    <h3 class="message__title">{{ title }}</h3>
    <div class="message__content" v-for="(content, key) in contents" :key="key">
      <h4 class="message__content__title">{{ key }}</h4>
      <ul class="message__content__list">
        <li
          class="message__content__items"
          v-for="(value, key) in content"
          :key="key"
        >
          {{ value }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "Message",
  props: {
    title: {
      type: String,
      default: null,
    },
    contents: {
      type: Object,
      default: null,
    },
    timeout: {
      type: Number,
      default: 5000,
    },
  },
  data() {
      return {
          id: null
      }
  },
  watch: {
    title: function (after, before) {
      clearTimeout(this.id);
      this.id = setTimeout(() => this.$emit("close"), this.timeout);
    },
  },
};
</script>

<style scoped>
.message {
  border: 1px solid cadetblue;
  padding: 1rem;
}
</style>
