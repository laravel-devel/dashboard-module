Vue.prototype.$showModal = function (id = null, options = {}) {
    Events.$emit('show-modal', { id: id, options: options });
}

Vue.prototype.$hideModal = function (id = null) {
    Events.$emit('hide-modal', { id: id });
}