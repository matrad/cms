import Vue from 'vue';
import Toast from './mixins/Toast.js';
import Statamic from './components/Statamic.js';

Vue.config.silent = false;
Vue.config.devtools = true;
Vue.config.productionTip = false

window.Vue = Vue;
window.Statamic = Statamic;
window._ = require('underscore');
window.$ = window.jQuery = require('jquery');
window.rangy = require('rangy');
window.EQCSS = require('eqcss');

require('./bootstrap/globals');
require('./bootstrap/polyfills');
require('./bootstrap/underscore-mixins');
require('./bootstrap/jquery-plugins');
require('./bootstrap/plugins');
require('./bootstrap/filters');
require('./bootstrap/mixins');
require('./bootstrap/components');
require('./bootstrap/fieldtypes');
require('./bootstrap/directives');

// import Wizard from './mixins/Wizard.js';
import axios from 'axios';
import PortalVue from "portal-vue";
import VModal from "vue-js-modal";
import Vuex from 'vuex';
import StatamicStore from './store';
import Popover  from 'vue-js-popover'
import VTooltip from 'v-tooltip'
import ReactiveProvide from 'vue-reactive-provide';
import vSelect from 'vue-select'
import VCalendar from 'v-calendar';

// Customize vSelect UI components
vSelect.props.components.default = () => ({
    Deselect: {
        render: createElement => createElement('span', __('×')),
    },
    OpenIndicator: {
        render: createElement => createElement('span', {
            class: { 'toggle': true },
            domProps: {
                innerHTML: '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 20 20"><path fill="currentColor" d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>'
            }
        })
    }
});

Statamic.booting(Statamic => {
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['X-CSRF-TOKEN'] = Statamic.$config.get('csrfToken');
});

Vue.prototype.$axios = axios;
Vue.prototype.$events = new Vue();
Vue.prototype.$echo = Statamic.$echo;
Vue.prototype.$bard = Statamic.$bard;
Vue.prototype.$keys = Statamic.$keys;

window.moment = Vue.moment = Vue.prototype.$moment = require('moment');

Vue.use(Popover, { tooltip: true })
Vue.use(PortalVue)
Vue.use(VModal, { componentName: 'vue-modal' })
Vue.use(VTooltip)
Vue.use(Vuex);
Vue.use(ReactiveProvide);
Vue.use(VCalendar);

Vue.component(vSelect)

Statamic.$store = new Vuex.Store({
    modules: {
        statamic: StatamicStore,
        publish: {
            namespaced: true
        }
    }
});

require('./components/ToastBus');
require('./components/ModalBus');
require('./components/stacks/Stacks');
require('./components/panes/Panes');
require('./components/ProgressBar');
require('./components/DirtyState');
require('./components/Config');
require('./components/Preference');
require('./components/Permission');

Statamic.app({
    el: '#statamic',

    mixins: [Toast],

    store: Statamic.$store,

    components: {
        GlobalSearch: require('./components/GlobalSearch.vue').default,
        SiteSelector: require('./components/SiteSelector.vue').default,
        PageTree: require('./components/structures/PageTree.vue').default,
        Login: require('./components/login/login'),
        LoginModal: require('./components/login/LoginModal.vue').default,
        BaseEntryCreateForm: require('./components/entries/BaseCreateForm.vue').default,
        BaseTermCreateForm: require('./components/terms/BaseCreateForm.vue').default,
        CreateEntryButton: require('./components/entries/CreateEntryButton.vue').default,
        CreateTermButton: require('./components/terms/CreateTermButton.vue').default,
        Importer: require('./components/importer/importer'),
        FieldsetListing: require('./components/fieldsets/Listing.vue').default,
        FieldsetEditForm: require('./components/fieldsets/EditForm.vue').default,
        BlueprintListing: require('./components/blueprints/Listing.vue').default,
        BlueprintBuilder: require('./components/blueprints/Builder.vue').default,
        FormListing: require('./components/forms/Listing.vue').default,
        FormSubmissionListing: require('./components/forms/SubmissionListing.vue').default,
        GlobalListing: require('./components/globals/Listing.vue').default,
        GlobalPublishForm: require('./components/globals/PublishForm.vue').default,
        GlobalCreateForm: require('./components/globals/Create.vue').default,
        UserListing: require('./components/users/Listing.vue').default,
        UserWizard: require('./components/users/Wizard.vue').default,
        RoleListing: require('./components/roles/Listing.vue').default,
        RolePublishForm: require('./components/roles/PublishForm.vue').default,
        UserGroupListing: require('./components/user-groups/Listing.vue').default,
        UserGroupPublishForm: require('./components/user-groups/PublishForm.vue').default,
        CollectionWizard: require('./components/collections/Wizard.vue').default,
        CollectionEditForm: require('./components/collections/EditForm.vue').default,
        SessionExpiry: require('./components/SessionExpiry.vue').default,
        StructureWizard: require('./components/structures/Wizard.vue').default,
        StructureListing: require('./components/structures/Listing.vue').default,
        StructureEditForm: require('./components/structures/EditForm.vue').default,
        Stacks: require('./components/stacks/Stacks.vue').default,
        TaxonomyWizard: require('./components/taxonomies/Wizard.vue').default,
        TaxonomyEditForm: require('./components/taxonomies/EditForm.vue').default,
        AssetContainerCreateForm: require('./components/asset-containers/CreateForm.vue').default,
        AssetContainerEditForm: require('./components/asset-containers/EditForm.vue').default,
        FormWizard: require('./components/forms/Wizard.vue').default,
    },

    data: {
        showLoginModal: false,
        navOpen: true,
        modals: [],
        stacks: [],
        panes: [],
        appendedComponents: []
    },

    computed: {

        version() {
            return Statamic.$config.get('version');
        },

        computedNavOpen() {
            // if (this.stackCount > 0) return false;

            return this.navOpen;
        },

        stackCount() {
            return this.$stacks.count();
        }

    },

    mounted() {
        this.bindWindowResizeListener();

        this.$keys.bind(['command+\\'], e => {
            e.preventDefault();
            this.toggleNav();
        });

        if (this.$config.get('broadcasting.enabled')) {
            this.$echo.start();
        }

        // Set moment locale
        window.moment.locale(Statamic.$config.get('locale'))
        Vue.moment.locale(Statamic.$config.get('locale'))
        Vue.prototype.$moment.locale(Statamic.$config.get('locale'))
    },

    created() {
        const state = localStorage.getItem('statamic.nav') || 'open';
        this.navOpen = state === 'open';
    },

    methods: {

        bindWindowResizeListener() {
            window.addEventListener('resize', () => {
                this.$store.commit('statamic/windowWidth', document.documentElement.clientWidth);
            });
            window.dispatchEvent(new Event('resize'));
        },

        toggleNav() {
            this.navOpen = ! this.navOpen;
            localStorage.setItem('statamic.nav', this.navOpen ? 'open' : 'closed');
        }
    }

});

// TODO: Drag events
// TODO: Live Preview
