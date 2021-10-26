require('./bootstrap');

window.Vue = require('vue').default;

Vue.component('file-uploader', require('./components/FileUploader.vue').default);
Vue.component('results-list', require('./components/ResultsList.vue').default);

export const EventBus = new Vue();

const app = new Vue({
    el: '#app'
});
