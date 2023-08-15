/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue').default;


// import VueSkeletonLoader from 'skeleton-loader-vue';

// Register the component globally

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */
// Vue.component('vue-skeleton-loader', VueSkeletonLoader);


const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

// Vue.component('example-component', require('./components/ExampleComponent.vue').default);
// Vue.component('artical', require('./components/Artical.vue').default);

// // Home Components
// Vue.component('widget-view-profile', require('./components/Home/WidgetViewProfile.vue').default);
// Vue.component('widget-like-photo', require('./components/Home/WidgetLikePhoto.vue').default);
// Vue.component('widget-follow-suggestion', require('./components/Home/WidgetFollowSuggestion.vue').default);


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
