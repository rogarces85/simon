<?php
/**
 * Flash messages consistentes con iconos Lucide (nunca emojis).
 *
 * Uso:
 *   flash_set('success', 'Perfil actualizado correctamente.');
 *   flash_set('error', 'Error al guardar.');
 *   flash_set('warning', 'Este campo es requerido.');
 *   flash_set('info', 'Tienes 3 notificaciones nuevas.');
 *
 *   echo flash_render();
 */

function flash_set($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $alerts = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $icons = [
        'success' => 'check-circle',
        'error'   => 'alert-circle',
        'warning' => 'alert-triangle',
        'info'    => 'info',
    ];

    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-700',
        'error'   => 'bg-red-50 border-red-200 text-red-700',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-700',
        'info'    => 'bg-blue-50 border-blue-200 text-blue-700',
    ];

    $html = '';
    foreach ($alerts as $alert) {
        $type = $alert['type'];
        $icon = $icons[$type] ?? 'info';
        $class = $colors[$type] ?? $colors['info'];
        $msg = htmlspecialchars($alert['message']);
        $html .= '<div class="' . $class . ' p-4 rounded-xl mb-6 flex items-center gap-3 border" role="alert">';
        $html .= '<i data-lucide="' . $icon . '" class="w-5 h-5 shrink-0"></i>';
        $html .= '<span>' . $msg . '</span>';
        $html .= '</div>';
    }

    return $html;
}
