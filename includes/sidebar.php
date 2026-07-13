<?php

use Classes\Rotinas;

$itensMenu = (new Rotinas())->getItensMenu();
$caminhoAtual = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$caminhoAtual = '/' . trim(is_string($caminhoAtual) ? $caminhoAtual : '', '/');

if (!function_exists('normalizarUrlItemMenu')) {
    function normalizarUrlItemMenu(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '#';
        }

        if (
            preg_match('#^https?://#i', $url)
            || str_starts_with($url, '#')
            || str_starts_with($url, '/adminlte-painel/')
        ) {
            return $url;
        }

        if (str_starts_with($url, '/admin/')) {
            return '/adminlte-painel' . $url;
        }

        return '/adminlte-painel/admin/' . ltrim($url, '/');
    }
}

if (!function_exists('itemMenuEstaAtivo')) {
    function itemMenuEstaAtivo(array $item, string $caminhoAtual): bool
    {
        $url = normalizarUrlItemMenu($item['url'] ?? null);
        $caminhoItem = parse_url($url, PHP_URL_PATH);
        $caminhoItem = '/' . trim(is_string($caminhoItem) ? $caminhoItem : '', '/');

        if (
            $url !== '#'
            && ($caminhoAtual === $caminhoItem || str_starts_with($caminhoAtual, $caminhoItem . '/'))
        ) {
            return true;
        }

        foreach ($item['filhas'] ?? [] as $filha) {
            if (itemMenuEstaAtivo($filha, $caminhoAtual)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('classesIconeItemMenu')) {
    function classesIconeItemMenu(?string $classes): string
    {
        $classesValidas = array_filter(
            preg_split('/\s+/', trim((string) $classes)) ?: [],
            static fn(string $classe): bool => preg_match('/^[a-zA-Z0-9_-]+$/', $classe) === 1
        );

        return $classesValidas ? implode(' ', $classesValidas) : 'far fa-circle';
    }
}

if (!function_exists('renderizarItensMenu')) {
    function renderizarItensMenu(array $itens, string $caminhoAtual, int $nivel = 0): void
    {
        foreach ($itens as $item) {
            $filhas = is_array($item['filhas'] ?? null) ? $item['filhas'] : [];
            $possuiFilhas = count($filhas) > 0;
            $ativo = itemMenuEstaAtivo($item, $caminhoAtual);
            $url = $possuiFilhas ? '#' : normalizarUrlItemMenu($item['url'] ?? null);
            $descricao = trim((string) ($item['descricao'] ?? $item['rotina'] ?? 'Item'));
            $classesIcone = classesIconeItemMenu($item['icon'] ?? null);
            $classeItem = 'nav-item' . ($possuiFilhas ? ' has-treeview' : '') . ($possuiFilhas && $ativo ? ' menu-open' : '');
            $classeLink = 'nav-link' . ($nivel > 0 ? ' nav-link-submenu' : '') . ($ativo ? ' active' : '');
            $estiloNivel = $nivel > 0 ? '--nivel-menu:' . $nivel : '';
            ?>
            <li class="<?= $classeItem ?>">
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $classeLink ?>"
                   <?= $estiloNivel !== '' ? 'style="' . $estiloNivel . '"' : '' ?>>
                    <i class="nav-icon <?= htmlspecialchars($classesIcone, ENT_QUOTES, 'UTF-8') ?>"></i>
                    <p>
                        <?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($possuiFilhas): ?>
                            <i class="right fas fa-angle-left"></i>
                        <?php endif; ?>
                    </p>
                </a>

                <?php if ($possuiFilhas): ?>
                    <ul class="nav nav-treeview">
                        <?php renderizarItensMenu($filhas, $caminhoAtual, $nivel + 1); ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php
        }
    }
}
?>

<style>

</style>

<aside class="main-sidebar sidebar-light-primary elevation-4">
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" data-accordion="false">
                <li class="nav-item">
                    <a href="/adminlte-painel/admin/" class="nav-link <?= $caminhoAtual === '/adminlte-painel/admin' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Painel</p>
                    </a>
                </li>

                <?php renderizarItensMenu($itensMenu, $caminhoAtual); ?>
            </ul>
        </nav>
    </div>
</aside>
