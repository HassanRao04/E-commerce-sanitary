import './bootstrap';

import Alpine from 'alpinejs';
import adminShell from './admin/admin-shell';
import './admin/product-form';
import homepageProductPicker from './admin/homepage-product-picker';
import headerNavEditor from './admin/header-nav-editor';

window.Alpine = Alpine;
Alpine.data('adminShell', adminShell);
Alpine.data('homepageProductPicker', homepageProductPicker);
Alpine.data('headerNavEditor', headerNavEditor);

Alpine.start();
