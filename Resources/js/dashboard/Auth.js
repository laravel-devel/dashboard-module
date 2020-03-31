export default class {
    constructor(object = {}) {
        this.user = object;
        this.authenticated = (Object.keys(object).length > 0);
    }

    hasPermissions(permissions = []) {
        if (typeof permissions !== 'string' && !Array.isArray(permissions)) {
            return false;
        }

        if (typeof permissions === 'string') {
            permissions = [permissions];
        }

        for (let permission of permissions) {
            if (permission.indexOf('||') > -1 || permission.indexOf('&&') > -1) {
                // A conditional/comples permission
                if (!this.hasConditionalPermissions(permission)) {
                    return false;
                }
            } else {
                // Simple permission
                if (!this.hasPermission(permission)) {
                    return false;
                }
            }
        }

        return true;
    }

    hasPermission(permission = '') {
        if (!permission) {
            return false;
        }

        return this.hasPersonalPermission(permission) || this.hasPermissionViaRole(permission);
    }

    hasConditionalPermissions(condition = '') {
        if (!condition) {
            return false;
        }

        const matches = condition.match(/[a-z\._]*/g);

        const permissions = matches.filter(item => item.length);

        for (let key of permissions) {
            const permitted = this.hasPermission(key) ? 'true' : 'false';

            condition = condition.replace(key, permitted);
        }

        return eval(condition);
    }

    hasPersonalPermission(permission = '') {
        return !!this.user.permissions.find(item => item.key === permission);
    }

    hasPermissionViaRole(permission = '') {
        for (let role of this.user.roles) {
            if (role.permissions.find(item => item.key === permission)) {
                return true;
            }
        }

        return false;
    }
}