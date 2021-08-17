<template>
  <div class="container">
    <h1>Home</h1>
    <Nav />
    <div>
      <span @click="setLang('ja')">ja</span>|<span @click="setLang('en')"
        >en</span
      >
    </div>
  </div>
</template>

<script>
import Nav from "@/components/Nav.vue";
export default {
  name: "Home",
  components: {
    Nav,
  },
  methods: {
    async setLang(lang) {
      alert(lang);
      const { data, status } = await axios.get(`/set-lang/${lang}`);

      if (status === 200) {
        console.log(`language change to ${data}`);
      } else {
        console.log(data.message);
        console.log(data.errors);
      }
    },
  },
};
</script>
