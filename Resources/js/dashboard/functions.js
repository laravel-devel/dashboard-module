Vue.prototype.$modal = function (id = null, options = {}) {
    Events.$emit('modal', { id: id, options: options });
}