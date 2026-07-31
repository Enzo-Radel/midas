# Midas - Claude Development Guide

## Frontend Component Rules

Regras específicas para trabalhar com componentes do frontend.

### 1. Nunca Use Emojis

Emojis são inconsistentes entre sistemas, unprofissionais e prejudicam acessibilidade. Use símbolos ou indicadores semânticos.

**❌ Errado:**
```vue
<span class="icon">💰</span>
```

**✅ Correto:**
```vue
<span class="icon">◆</span>
<!-- ou use ícones SVG/font -->
```

### 2. Prefira Bordas Arredondadas

Bordas arredondadas criam uma aparência moderna e friendly. Nunca use sharp corners (0px).

**Padrão de border-radius:**
- Botões/Inputs: `border-radius: 8px`
- Cards: `border-radius: 12-14px`
- Containers grandes: `border-radius: 16px`
- Ícones: `border-radius: 8-10px`

**❌ Errado:**
```css
.button {
    border-radius: 0;
}
```

**✅ Correto:**
```css
.button {
    border-radius: 8px;
}
```

### 3. Use Cores Semânticas

Defina um sistema de cores semânticas consistente. Cada cor deve comunicar um significado.

**Paleta semântica:**
- **Primary** (#6366f1, #8b5cf6): ações principais, marca
- **Success** (#10b981, #059669): confirmações, positivo
- **Danger** (#ef4444, #dc2626): erros, ações destrutivas
- **Warning** (#f59e0b, #d97706): alertas, cautela
- **Info** (#3b82f6, #2563eb): informações
- **Neutral** (#6b7280, #9ca3af, #e5e7eb): texto, bordas, backgrounds

**❌ Errado:**
```css
.success { color: #00ff00; }
.error { color: #ff0000; }
```

**✅ Correto:**
```css
.success { color: #10b981; }
.danger { color: #ef4444; }
```

### 4. Feedback Visual para Elementos Interativos

Todo elemento que o usuário pode interagir **deve ter feedback visual além do cursor**.

#### Feedback Mínimo Obrigatório:

**Botões:**
- Hover: mudança de cor/sombra/escala
- Active: efeito de press/feedback tátil
- Disabled: aparência distinta (opacity reduzida)

```css
.button {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.button:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.button:active {
    transform: scale(0.98);
}

.button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

**Links:**
- Hover: mudança de cor ou underline
- Active: cor distinta ou background

```css
.link {
    color: #3b82f6;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}

.link:hover {
    border-bottom-color: #3b82f6;
}

.link:active {
    color: #1e40af;
}
```

**Cards/Containers clicáveis:**
- Hover: elevação (shadow), escala leve, mudança de borda
- Active: sombra reduzida

```css
.card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
    border-color: #d1d5db;
}

.card:active {
    transform: translateY(-2px);
}
```

**Inputs/Formulários:**
- Focus: border-color, focus-ring, background
- Hover: border-color leve

```css
.input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.input:hover {
    border-color: #9ca3af;
}

.input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
```

#### Transições

Sempre use transições suaves em feedback visual:

```css
transition: all 0.2s ease;  /* padrão para interações rápidas */
transition: all 0.3s ease;  /* para animações um pouco mais lentas */
```

## Checklist de Implementação

Antes de submeter um componente, verifique:

- [ ] Nenhum emoji no código
- [ ] Todos os elementos têm `border-radius` (mínimo 8px)
- [ ] Cores usam nomes semânticos (primary, success, danger, etc)
- [ ] Todos os `<button>` têm estados hover/active/disabled
- [ ] Todos os `<a>` links têm estado hover
- [ ] Cards/containers clicáveis têm elevação no hover
- [ ] Inputs têm focus ring visível
- [ ] Todas as transições são suaves (0.2-0.3s)
- [ ] Elementos disabled têm aparência visual distinta
- [ ] Testado em navegador para validar feedback visual

## Exemplo Completo

```vue
<template>
  <div class="card">
    <h3 class="card-title">{{ title }}</h3>
    <p class="card-text">{{ description }}</p>
    <button class="button button-primary">Action</button>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  description: String,
})
</script>

<style scoped>
.card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-4px);
  border-color: #d1d5db;
}

.card-title {
  margin: 0 0 0.5rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.card-text {
  margin: 0 0 1rem;
  color: #6b7280;
  line-height: 1.6;
}

.button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.button-primary {
  background-color: #3b82f6;
  color: white;
}

.button-primary:hover {
  background-color: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transform: translateY(-2px);
}

.button-primary:active {
  transform: scale(0.98);
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
```
