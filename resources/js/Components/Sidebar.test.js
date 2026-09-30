import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Sidebar from './Sidebar.vue';

describe('Sidebar', () => {
    it('tem o item Preços apontando para /precos', () => {
        const link = mount(Sidebar).findAll('a.nav-item').find((a) => a.text() === 'Preços');

        expect(link.attributes('href')).toBe('/precos');
    });

    it('tem um botão de menu que abre e fecha o menu no celular, com o item Preços presente', async () => {
        const wrapper = mount(Sidebar);
        const botao = wrapper.find('button.menu-btn');

        expect(wrapper.find('aside').classes()).not.toContain('active');

        await botao.trigger('click');
        expect(wrapper.find('aside').classes()).toContain('active');
        expect(botao.attributes('aria-expanded')).toBe('true');
        expect(wrapper.findAll('a.nav-item').some((a) => a.text() === 'Preços')).toBe(true);

        await botao.trigger('click');
        expect(wrapper.find('aside').classes()).not.toContain('active');
    });
});
