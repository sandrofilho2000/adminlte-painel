<?php

namespace Classes;

/**
 * Classe auxiliar para padronizar componentes do AdminLTE 3.
 */
class UIHelper
{
    /**
     * Renderiza um Card do AdminLTE.
     * 
     * @param string $title Título do card.
     * @param string $content Conteúdo HTML.
     * @param string $variant Variante de cor (primary, success, info, warning, danger).
     * @param array $options Opções adicionais (collapsed, outline, maximize).
     * @return string
     */
    public static function card(string $title, string $content, string $variant = 'primary', array $options = []): string
    {
        $outline = ($options['outline'] ?? true) ? 'card-outline' : '';
        $collapsed = ($options['collapsed'] ?? false) ? 'collapsed-card' : '';
        $icon = $options['icon'] ?? '';

        $html = "<div class=\"card card-{$variant} {$outline} {$collapsed}\">";
        $html .= "  <div class=\"card-header\">";
        $html .= "    <h3 class=\"card-title\">";
        if ($icon) {
            $html .= "<i class=\"{$icon} mr-1\"></i> ";
        }
        $html .= htmlspecialchars($title) . "</h3>";
        $html .= "    <div class=\"card-tools\">";
        $html .= "      <button type=\"button\" class=\"btn btn-tool\" data-card-widget=\"collapse\"><i class=\"fas fa-minus\"></i></button>";
        if ($options['maximize'] ?? false) {
            $html .= "      <button type=\"button\" class=\"btn btn-tool\" data-card-widget=\"maximize\"><i class=\"fas fa-expand\"></i></button>";
        }
        $html .= "    </div>";
        $html .= "  </div>";
        $html .= "  <div class=\"card-body\">{$content}</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Renderiza um Info-Box.
     */
    public static function infoBox(string $title, $value, string $icon, string $color = 'info'): string
    {
        return "
        <div class=\"info-box\">
          <span class=\"info-box-icon bg-{$color}\"><i class=\"{$icon}\"></i></span>
          <div class=\"info-box-content\">
            <span class=\"info-box-text\">" . htmlspecialchars($title) . "</span>
            <span class=\"info-box-number\">{$value}</span>
          </div>
        </div>";
    }

    /**
     * Renderiza um Small-Box (Widget de estatísticas).
     * 
     * @param string $title Título/Descrição.
     * @param mixed $value Valor numérico ou texto principal.
     * @param string $icon Classe do ícone FontAwesome (ex: 'fas fa-shopping-cart').
     * @param string $color Variante de cor (primary, success, warning, danger).
     * @param string $link URL para o rodapé.
     * @param string $linkText Texto do link de rodapé.
     * @return string
     */
    public static function smallBox(string $title, $value, string $icon, string $color = 'primary', string $link = '#', string $linkText = 'Mais informações'): string
    {
        return "
        <div class=\"small-box bg-{$color}\">
          <div class=\"inner\">
            <h3>{$value}</h3>
            <p>" . htmlspecialchars($title) . "</p>
          </div>
          <div class=\"icon\">
            <i class=\"{$icon}\"></i>
          </div>
          <a href=\"{$link}\" class=\"small-box-footer\">
            " . htmlspecialchars($linkText) . " <i class=\"fas fa-arrow-circle-right\"></i>
          </a>
        </div>";
    }

    /**
     * Renderiza o botão de toggle do menu lateral.
     */
    public static function menuToggleButton(): string
    {
        return '
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>';
    }
}