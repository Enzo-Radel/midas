import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Sidebar from './Sidebar.vue';

describe('Sidebar', () => {
    it('tem o item Preços apontando para /precos', () => {
        const link = mount(Sidebar).findAll('a.nav-item').find((a) => a.text() === 'Preços');

        expect(link.attributes('href')).toBe('/precos');
    });
});
