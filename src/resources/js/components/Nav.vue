<template>
  <nav class="nav">
    <h2 class="nav__title">menu</h2>
    <ul class="nav__list">
      <li class="nav__item">
        <router-link :to="{ name: 'login' }">Login</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'register' }">Register</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'test' }">Test</router-link>
      </li>
      <li class="nav__item">
        <router-link :to="{ name: 'forgot' }">Forgot</router-link>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
  name: "Nav",
};
</script>

<style scoped>
.nav {
}
.nav__title {
}
.nav__list {
    display: flex;
    list-style: none;
}
.nav__item {
    border: 1px solid gray;
}
.nav__item a {
    color: inherit;
    text-decoration: none;
    padding: 1rem;
}
</style>
